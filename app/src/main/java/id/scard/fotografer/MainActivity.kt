package id.scard.fotografer

import android.app.Activity
import android.app.AlertDialog
import android.os.Bundle
import android.content.Intent
import android.net.Uri
import android.hardware.usb.UsbManager
import android.app.PendingIntent
import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.view.Gravity
import android.view.View
import android.widget.*
import java.io.File
import java.net.URL
import org.json.JSONObject
import org.json.JSONArray
import java.util.concurrent.Executors
import javax.net.ssl.HttpsURLConnection

class MainActivity : Activity() {
    private val background = Color.rgb(11, 18, 32)
    private val panel = Color.rgb(24, 35, 55)
    private val muted = Color.rgb(165, 181, 207)
    private val blue = Color.rgb(64, 147, 255)
    private lateinit var cameraStatus: TextView
    private lateinit var serverStatus: TextView
    private lateinit var summary: TextView
    private lateinit var photoPreview: ImageView
    private lateinit var emptyPreview: TextView
    private lateinit var uploadButton: Button
    private lateinit var selectButton: Button
    private var usbPermissionReceiver: android.content.BroadcastReceiver? = null
    private val selected = mutableListOf<Uri>()
    private val cameraExecutor = Executors.newSingleThreadExecutor()
    @Volatile private var cameraRunning = false
    private var cameraButton: Button? = null

    override fun onCreate(state: Bundle?) {
        super.onCreate(state)
        window.statusBarColor = background
        window.navigationBarColor = background
        render()
        registerUsbPermissionReceiver()
        restoreQueue()
    }

    private fun dp(value: Int) = (value * resources.displayMetrics.density).toInt()
    private fun rounded(color: Int, radius: Int = 18) = GradientDrawable().apply {
        setColor(color)
        cornerRadius = dp(radius).toFloat()
    }
    private fun text(value: String, size: Float, bold: Boolean = false, color: Int = Color.WHITE) =
        TextView(this).apply {
            text = value
            textSize = size
            setTextColor(color)
            if (bold) setTypeface(typeface, Typeface.BOLD)
        }
    private fun button(label: String, color: Int) = Button(this).apply {
        text = label
        isAllCaps = false
        setTextColor(Color.WHITE)
        textSize = 13f
        background = rounded(color, 13)
        minHeight = dp(48)
    }
    private fun vertical() = LinearLayout(this).apply { orientation = LinearLayout.VERTICAL }
    private fun gap(parent: LinearLayout, height: Int) {
        parent.addView(View(this), LinearLayout.LayoutParams(1, dp(height)))
    }
    private fun render() {
        val scroll = ScrollView(this).apply {
            fillViewport = true
            setBackgroundColor(background)
        }
        val root = vertical().apply { setPadding(dp(18), dp(22), dp(18), dp(30)) }
        scroll.addView(root)
        val header = LinearLayout(this).apply { gravity = Gravity.CENTER_VERTICAL }
        val brand = vertical()
        brand.addView(text("FOTOGRAFER", 19f, true))
        brand.addView(text("PHOTO DELIVERY STUDIO", 10f, false, muted))
        header.addView(brand, LinearLayout.LayoutParams(0, -2, 1f))
        val settings = button("Pengaturan", panel).apply { setOnClickListener { showSettings() } }
        header.addView(settings)
        root.addView(header)
        gap(root, 30)
        root.addView(text("Foto siap dikirim.", 31f, true))
        gap(root, 6)
        root.addView(text("Pilih foto original, lalu kirim ke galeri pelanggan.", 13f, false, muted))
        gap(root, 22)

        val statusCard = vertical().apply {
            background = rounded(panel)
            setPadding(dp(18), dp(17), dp(18), dp(17))
        }
        cameraStatus = text("KAMERA  •  Memeriksa USB", 12f, true, muted)
        serverStatus = text("SERVER  •  Belum dikonfigurasi", 12f, true, muted)
        statusCard.addView(cameraStatus)
        gap(statusCard, 9)
        statusCard.addView(serverStatus)
        root.addView(statusCard)
        gap(root, 10)
        cameraButton = button("Hubungkan Canon (USB)", panel).apply { setOnClickListener { toggleCanon() } }
        root.addView(cameraButton, LinearLayout.LayoutParams(-1, dp(48)))
        gap(root, 15)

        val previewCard = vertical().apply {
            background = rounded(panel)
            setPadding(dp(12), dp(12), dp(12), dp(12))
        }
        photoPreview = ImageView(this).apply {
            scaleType = ImageView.ScaleType.FIT_CENTER
            visibility = View.GONE
            adjustViewBounds = true
        }
        emptyPreview = text("Belum ada foto dipilih\n\nGunakan tombol Pilih Foto di bawah", 15f, false, muted).apply {
            gravity = Gravity.CENTER
            setPadding(dp(10), dp(75), dp(10), dp(75))
        }
        previewCard.addView(photoPreview, LinearLayout.LayoutParams(-1, dp(250)))
        previewCard.addView(emptyPreview)
        root.addView(previewCard)
        gap(root, 15)

        summary = text("0 foto dipilih · 0 terkirim · 0 gagal", 12f, false, muted)
        root.addView(summary)
        gap(root, 12)
        selectButton = button("＋  Pilih Foto", panel).apply { setOnClickListener { selectPhotos() } }
        root.addView(selectButton, LinearLayout.LayoutParams(-1, dp(50)))
        gap(root, 9)
        uploadButton = button("Upload ke Server", blue).apply {
            isEnabled = false
            setOnClickListener { uploadPhotos() }
        }
        root.addView(uploadButton, LinearLayout.LayoutParams(-1, dp(50)))
        gap(root, 18)
        root.addView(text("Foto dikirim tanpa resize atau kompresi. Untuk Canon EOS 600D, transfer otomatis PTP masih dalam pengembangan.", 11f, false, muted))
        setContentView(scroll)
        refreshStatus()
    }

    private fun toggleCanon() {
        if (cameraRunning) {
            cameraRunning = false
            cameraButton?.text = "Hubungkan Canon (USB)"
            return
        }
        val manager = getSystemService(USB_SERVICE) as UsbManager
        val device = CanonPtp.find(manager)
        if (device == null) {
            Toast.makeText(this, "Kamera PTP belum terdeteksi", Toast.LENGTH_LONG).show()
            return
        }
        if (!manager.hasPermission(device)) {
            val intent = Intent(USB_PERMISSION_ACTION).setPackage(packageName)
            val pending = PendingIntent.getBroadcast(
                this, 0, intent, PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_MUTABLE
            )
            manager.requestPermission(device, pending)
            Toast.makeText(this, "Menunggu izin akses USB...", Toast.LENGTH_LONG).show()
            return
        }
        cameraRunning = true
        cameraButton?.text = "Putuskan Canon"
        cameraStatus.text = "KAMERA  •  Membuka sesi PTP..."
        cameraExecutor.execute {
            try {
                CanonPtp(manager, device).use { ptp ->
                    runOnUiThread { cameraStatus.text = "KAMERA  •  PTP tersambung, menunggu JPEG baru" }
                    ptp.watch(File(filesDir, "canon"), { cameraRunning }) { file ->
                        runOnUiThread {
                            val uri = Uri.fromFile(file)
                            selected.add(uri)
                            saveQueue()
                            showCapturedPhoto(file)
                            uploadButton.isEnabled = true
                            summary.text = "${selected.size} foto menunggu upload"
                            uploadPhotos()
                        }
                    }
                }
            } catch (e: Exception) {
                runOnUiThread {
                    cameraStatus.text = "KAMERA  •  PTP gagal: ${e.message}"
                }
            } finally {
                cameraRunning = false
                runOnUiThread { cameraButton?.text = "Hubungkan Canon (USB)" }
            }
        }
    }

    private fun prefs() = getSharedPreferences("fotografer_settings", MODE_PRIVATE)
    private fun refreshStatus() {
        val usb = getSystemService(USB_SERVICE) as UsbManager
        val camera = CanonPtp.find(usb)
        if (!cameraRunning) cameraStatus.text = if (camera == null) "KAMERA  •  USB PTP belum terdeteksi"
            else "KAMERA  •  Perangkat PTP terdeteksi, belum tersambung"
        val server = prefs().getString("server", "").orEmpty()
        val token = prefs().getString("token", "").orEmpty()
        serverStatus.text = if (server.isNotBlank() && token.isNotBlank())
            "SERVER  •  Konfigurasi tersedia (belum diuji)"
        else "SERVER  •  Atur URL dan token"
    }

    private fun showSettings() {
        val box = vertical().apply { setPadding(dp(20), dp(12), dp(20), 0) }
        val url = EditText(this).apply {
            hint = "https://akuimutya.my.id"
            inputType = android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_VARIATION_URI
            setSingleLine(true)
            setText(prefs().getString("server", "https://akuimutya.my.id"))
        }
        val token = EditText(this).apply {
            hint = "Upload token ft_..."
            inputType = android.text.InputType.TYPE_CLASS_TEXT or android.text.InputType.TYPE_TEXT_VARIATION_PASSWORD
            setSingleLine(true)
            setText(prefs().getString("token", ""))
        }
        box.addView(text("Server URL", 13f, true, Color.WHITE))
        box.addView(url)
        gap(box, 12)
        box.addView(text("Upload Token", 13f, true, Color.WHITE))
        box.addView(token)
        AlertDialog.Builder(this)
            .setTitle("Pengaturan Upload")
            .setView(box)
            .setNegativeButton("Batal", null)
            .setPositiveButton("Simpan") { _, _ ->
                val address = url.text.toString().trim().trimEnd('/')
                val secret = token.text.toString().trim()
                val parsed = Uri.parse(address)
                if (parsed.scheme != "https" || parsed.host.isNullOrBlank() || secret.isBlank()) {
                    Toast.makeText(this, "URL HTTPS dan token wajib diisi", Toast.LENGTH_LONG).show()
                } else {
                    prefs().edit().putString("server", address).putString("token", secret).apply()
                    refreshStatus()
                }
            }.show()
    }

    private fun selectPhotos() {
        val intent = Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
            type = "image/*"
            putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true)
            addCategory(Intent.CATEGORY_OPENABLE)
        }
        startActivityForResult(intent, 42)
    }

    @Deprecated("Using platform document picker result")
    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode != 42 || resultCode != RESULT_OK || data == null) return
        selected.clear()
        data.clipData?.let { clips ->
            for (i in 0 until clips.itemCount) selected.add(clips.getItemAt(i).uri)
        } ?: data.data?.let { selected.add(it) }
        selected.forEach { uri ->
            try { contentResolver.takePersistableUriPermission(uri, Intent.FLAG_GRANT_READ_URI_PERMISSION) } catch (_: SecurityException) { }
        }
        saveQueue()
        summary.text = "${selected.size} foto dipilih"
        uploadButton.isEnabled = selected.isNotEmpty()
        if (selected.isNotEmpty()) {
            photoPreview.setImageURI(selected.first())
            photoPreview.visibility = View.VISIBLE
            emptyPreview.visibility = View.GONE
        }
    }

    private fun uploadPhotos() {
        val server = prefs().getString("server", "").orEmpty()
        val token = prefs().getString("token", "").orEmpty()
        if (!server.startsWith("https://") || token.isBlank()) {
            showSettings()
            return
        }
        if (selected.isEmpty()) return
        saveQueue()
        val queued = selected.size
        selected.clear()
        UploadWorker.schedule(applicationContext)
        uploadButton.isEnabled = false
        summary.text = "$queued foto masuk antrean upload background"
        Toast.makeText(this, "Antrean upload dijadwalkan", Toast.LENGTH_SHORT).show()
    }

    private fun saveQueue() {
        synchronized(UploadWorker.lock) {
            val arr = JSONArray()
            val existing = JSONArray(prefs().getString("pending_uris", "[]") ?: "[]")
            val items = linkedSetOf<String>()
            for (i in 0 until existing.length()) items.add(existing.getString(i))
            selected.forEach { items.add(it.toString()) }
            items.forEach { arr.put(it) }
            prefs().edit().putString("pending_uris", arr.toString()).commit()
        }
    }

    private fun restoreQueue() {
        val raw = synchronized(UploadWorker.lock) { prefs().getString("pending_uris", "[]").orEmpty() }
        val arr = try { JSONArray(raw) } catch (_: Exception) { JSONArray() }
        selected.clear()
        for (i in 0 until arr.length()) {
            val uri = Uri.parse(arr.optString(i))
            try {
                if (uri.scheme == "file") {
                    if (uri.path?.let { File(it).isFile } == true) selected.add(uri)
                } else {
                    contentResolver.openFileDescriptor(uri, "r")?.close()
                    selected.add(uri)
                }
            } catch (_: Exception) { }
        }
        uploadButton.isEnabled = selected.isNotEmpty()
        summary.text = "${selected.size} foto menunggu upload"
        if (selected.isNotEmpty() && prefs().getString("token", "").orEmpty().isNotBlank()) {
            UploadWorker.schedule(applicationContext)
            selected.clear()
        }
    }

    private fun queryName(uri: Uri): String {
        if (uri.scheme == "file") return uri.path?.substringAfterLast("/") ?: "photo.jpg"
        contentResolver.query(uri, arrayOf(android.provider.OpenableColumns.DISPLAY_NAME), null, null, null)?.use {
            if (it.moveToFirst()) return it.getString(0) ?: "photo.jpg"
        }
        return uri.path?.substringAfterLast("/") ?: "photo.jpg"
    }

    fun showCapturedPhoto(file: File) {
        runOnUiThread {
            val opts = android.graphics.BitmapFactory.Options().apply { inSampleSize = 4 }
            photoPreview.setImageBitmap(android.graphics.BitmapFactory.decodeFile(file.absolutePath, opts))
            photoPreview.visibility = View.VISIBLE
            emptyPreview.visibility = View.GONE
        }
    }

    fun updateUploadState(uploaded: Int, pending: Int, failed: Int, message: String) {
        runOnUiThread { summary.text = "$uploaded terkirim · $pending antrean · $failed gagal — $message" }
    }

    private fun registerUsbPermissionReceiver() {
        val receiver = object : android.content.BroadcastReceiver() {
            override fun onReceive(context: android.content.Context, intent: Intent) {
                if (intent.action != USB_PERMISSION_ACTION) return
                val granted = intent.getBooleanExtra(UsbManager.EXTRA_PERMISSION_GRANTED, false)
                if (granted && !cameraRunning) toggleCanon()
                else if (!granted) Toast.makeText(this@MainActivity, "Izin kamera USB ditolak", Toast.LENGTH_LONG).show()
            }
        }
        val filter = android.content.IntentFilter(USB_PERMISSION_ACTION)
        if (android.os.Build.VERSION.SDK_INT >= 33) {
            registerReceiver(receiver, filter, android.content.Context.RECEIVER_NOT_EXPORTED)
        } else {
            @Suppress("DEPRECATION")
            registerReceiver(receiver, filter)
        }
        usbPermissionReceiver = receiver
    }

    companion object {
        private const val USB_PERMISSION_ACTION = "id.scard.fotografer.USB_PERMISSION"
    }

    override fun onResume() {
        super.onResume()
        if (::cameraStatus.isInitialized) refreshStatus()
    }

    override fun onDestroy() {
        cameraRunning = false
        usbPermissionReceiver?.let { unregisterReceiver(it) }
        cameraExecutor.shutdown()
        super.onDestroy()
    }
}
