package id.scard.fotografer

import android.hardware.usb.*
import java.io.Closeable
import java.io.File
import java.nio.ByteBuffer
import java.nio.ByteOrder

/** Basic PTP transport. Canon EOS remote capture compatibility needs hardware testing. */
class CanonPtp(manager: UsbManager, device: UsbDevice) : Closeable {
    private val iface = (0 until device.interfaceCount).map { device.getInterface(it) }
        .firstOrNull { it.interfaceClass == 6 } ?: error("USB PTP interface not found")
    private val input = (0 until iface.endpointCount).map { iface.getEndpoint(it) }
        .first { it.type == UsbConstants.USB_ENDPOINT_XFER_BULK && it.direction == UsbConstants.USB_DIR_IN }
    private val output = (0 until iface.endpointCount).map { iface.getEndpoint(it) }
        .first { it.type == UsbConstants.USB_ENDPOINT_XFER_BULK && it.direction == UsbConstants.USB_DIR_OUT }
    private val conn = manager.openDevice(device) ?: error("USB permission required")
    private var tx = 0
    private var opened = false

    init {
        if (!conn.claimInterface(iface, true)) { conn.close(); error("Cannot claim PTP interface") }
        try { command(0x1002, 1); opened = true } catch (e: Exception) { close(); throw e }
    }

    private var receiveBuffer = ByteArray(0)
    private var receiveOffset = 0

    private fun read(size: Int): ByteArray {
        require(size in 0..(100 * 1024 * 1024)) { "PTP payload invalid" }
        val result = ByteArray(size)
        var pos = 0
        while (pos < size) {
            if (receiveOffset >= receiveBuffer.size) {
                val chunk = ByteArray(16384)
                val n = conn.bulkTransfer(input, chunk, chunk.size, 15000)
                if (n <= 0) error("PTP read timeout")
                receiveBuffer = chunk.copyOf(n)
                receiveOffset = 0
            }
            val n = minOf(size - pos, receiveBuffer.size - receiveOffset)
            receiveBuffer.copyInto(result, pos, receiveOffset, receiveOffset + n)
            pos += n
            receiveOffset += n
        }
        return result
    }

    private fun u32(b: ByteArray, offset: Int): Int =
        ByteBuffer.wrap(b, offset, 4).order(ByteOrder.LITTLE_ENDIAN).int

    @Synchronized
    private fun command(op: Int, vararg args: Int): ByteArray? {
        tx++
        val buf = ByteBuffer.allocate(12 + 4 * args.size).order(ByteOrder.LITTLE_ENDIAN)
        buf.putInt(buf.capacity()).putShort(1.toShort()).putShort(op.toShort()).putInt(tx)
        args.forEach { buf.putInt(it) }
        val packet = buf.array()
        var pos = 0
        while (pos < packet.size) {
            val n = conn.bulkTransfer(output, packet, pos, packet.size - pos, 15000)
            if (n <= 0) error("PTP command send failed")
            pos += n
        }
        var data: ByteArray? = null
        while (true) {
            val header = read(12)
            val length = u32(header, 0)
            require(length >= 12) { "Invalid PTP header" }
            val payload = read(length - 12)
            val type = ByteBuffer.wrap(header, 4, 2).order(ByteOrder.LITTLE_ENDIAN).short.toInt() and 0xffff
            val code = ByteBuffer.wrap(header, 6, 2).order(ByteOrder.LITTLE_ENDIAN).short.toInt() and 0xffff
            require(u32(header, 8) == tx) { "PTP transaction mismatch" }
            if (type == 2) data = payload
            else if (type == 3) {
                if (code != 0x2001) error("PTP error code $code")
                return data
            } else error("Unexpected PTP container")
        }
    }

    private fun handles(): Set<Int> {
        val b = command(0x1007, -1, 0, 0) ?: return emptySet()
        if (b.size < 4) return emptySet()
        val count = u32(b, 0)
        require(count >= 0 && count <= 100000 && b.size.toLong() >= 4L + count.toLong() * 4L)
        return (0 until count).map { u32(b, 4 + it * 4) }.toSet()
    }

    fun watch(folder: File, running: () -> Boolean, onPhoto: (File) -> Unit) {
        folder.mkdirs()
        var known = handles()
        while (running()) {
            Thread.sleep(2000)
            val current = handles()
            for (handle in current - known) {
                if (!running()) break
                val info = command(0x1008, handle) ?: continue
                if (info.size < 6) continue
                val format = ByteBuffer.wrap(info, 4, 2).order(ByteOrder.LITTLE_ENDIAN).short.toInt() and 0xffff
                if (format != 0x3801) continue
                val bytes = command(0x1009, handle) ?: continue
                if (bytes.size < 2 || bytes[0] != 0xff.toByte() || bytes[1] != 0xd8.toByte()) continue
                val file = File(folder, "canon_${System.currentTimeMillis()}_$handle.jpg")
                file.writeBytes(bytes)
                onPhoto(file)
            }
            known = current
        }
    }

    override fun close() {
        if (opened) try { command(0x1003) } catch (_: Exception) { }
        try { conn.releaseInterface(iface) } catch (_: Exception) { }
        conn.close()
    }

    companion object {
        fun find(manager: UsbManager): UsbDevice? = manager.deviceList.values.firstOrNull { device ->
            (0 until device.interfaceCount).any { device.getInterface(it).interfaceClass == 6 }
        }
    }
}
