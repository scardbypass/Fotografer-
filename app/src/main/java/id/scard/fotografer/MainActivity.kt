package id.scard.fotografer

import android.app.Activity
import android.os.Bundle
import android.hardware.usb.UsbManager
import android.widget.*
import android.graphics.Color

class MainActivity : Activity() {
 private lateinit var status: TextView
 override fun onCreate(savedInstanceState: Bundle?) {
  super.onCreate(savedInstanceState)
  val root = LinearLayout(this).apply { orientation=LinearLayout.VERTICAL; setPadding(40,40,40,40); setBackgroundColor(Color.rgb(12,14,18)) }
  status = TextView(this).apply { textSize=26f; setTextColor(Color.WHITE) }
  val title = TextView(this).apply { text="FOTOGRAFER • Instant Delivery"; textSize=32f; setTextColor(Color.WHITE) }
  root.addView(title); root.addView(status); setContentView(root); refreshUsb()
 }
 private fun refreshUsb() {
  val usb = getSystemService(USB_SERVICE) as UsbManager
  val devices = usb.deviceList.values
  status.text = if (devices.isEmpty()) "Kamera belum terhubung\nSambungkan Canon melalui USB OTG." else "USB terdeteksi\n" + devices.joinToString("\n") { it.productName ?: "USB Camera" } + "\n\nPTP capture listener: tahap integrasi berikutnya."
 }
}
