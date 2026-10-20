package id.scard.fotografer

import android.content.Context
import android.net.Uri
import androidx.work.*
import org.json.JSONArray
import org.json.JSONObject
import java.io.File
import java.net.URL
import java.util.concurrent.TimeUnit
import javax.net.ssl.HttpsURLConnection

/**
 * Persistent upload queue. Uses unique WorkManager work and persists each successful removal.
 * Camera JPEGs remain on internal storage until the server acknowledges them.
 */
class UploadWorker(context: Context, params: WorkerParameters) : Worker(context, params) {
    override fun doWork(): Result {
        val prefs = applicationContext.getSharedPreferences("fotografer_settings", Context.MODE_PRIVATE)
        val base = prefs.getString("server", "")?.trimEnd('/') ?: ""
        val token = prefs.getString("token", "") ?: ""
        if (!base.startsWith("https://") || token.isBlank()) return Result.failure()
        val list = synchronized(lock) {
            val arr = JSONArray(prefs.getString("pending_uris", "[]") ?: "[]")
            (0 until arr.length()).map { arr.getString(it) }
        }
        var retry = false
        for (item in list) {
            if (isStopped) return Result.retry()
            val uri = Uri.parse(item)
            try {
                send(base, token, uri)
                synchronized(lock) {
                    val current = JSONArray(prefs.getString("pending_uris", "[]") ?: "[]")
                    val updated = JSONArray()
                    for (i in 0 until current.length()) {
                        if (current.getString(i) != item) updated.put(current.getString(i))
                    }
                    prefs.edit().putString("pending_uris", updated.toString()).commit()
                }
                if (uri.scheme == "file") {
                    val file = uri.path?.let(::File)
                    if (file != null && file.parentFile?.canonicalPath == File(applicationContext.filesDir, "canon").canonicalPath) {
                        file.delete()
                    }
                }
            } catch (e: Exception) {
                if (e is IllegalArgumentException || e.message?.contains("HTTP 401") == true ||
                    e.message?.contains("HTTP 413") == true || e.message?.contains("HTTP 415") == true) {
                    return Result.failure()
                }
                retry = true
            }
        }
        return if (retry) Result.retry() else Result.success()
    }

    private fun send(base: String, token: String, uri: Uri) {
        val mime = if (uri.scheme == "file") "image/jpeg"
            else applicationContext.contentResolver.getType(uri) ?: "image/jpeg"
        require(mime == "image/jpeg" || mime == "image/png") { "Only JPEG/PNG supported" }
        val boundary = "Fotografer" + System.nanoTime()
        val connection = URL("$base/api/upload.php").openConnection() as HttpsURLConnection
        connection.connectTimeout = 20000
        connection.readTimeout = 120000
        connection.requestMethod = "POST"
        connection.doOutput = true
        connection.setRequestProperty("Authorization", "Bearer $token")
        connection.setRequestProperty("Content-Type", "multipart/form-data; boundary=$boundary")
        connection.setChunkedStreamingMode(65536)
        try {
            val filename = (uri.lastPathSegment ?: "photo.jpg").substringAfterLast('/')
                .replace("\r", "_").replace("\n", "_").replace("\"", "_")
            connection.outputStream.use { out ->
                out.write("--$boundary\r\nContent-Disposition: form-data; name=\"photo\"; filename=\"$filename\"\r\nContent-Type: $mime\r\n\r\n".toByteArray())
                val input = if (uri.scheme == "file") File(requireNotNull(uri.path)).inputStream()
                    else applicationContext.contentResolver.openInputStream(uri)
                requireNotNull(input) { "Source file missing" }.use { it.copyTo(out) }
                out.write("\r\n--$boundary--\r\n".toByteArray())
            }
            val status = connection.responseCode
            val response = (if (status in 200..299) connection.inputStream else connection.errorStream)
                ?.bufferedReader()?.use { it.readText() }.orEmpty()
            if (status !in 200..299 || !JSONObject(response).optBoolean("ok")) {
                throw IllegalStateException("HTTP $status: upload rejected")
            }
        } finally { connection.disconnect() }
    }

    companion object {
        val lock = Any()
        fun schedule(context: Context) {
            val constraints = Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build()
            val request = OneTimeWorkRequestBuilder<UploadWorker>()
                .setConstraints(constraints)
                .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 30, TimeUnit.SECONDS)
                .build()
            WorkManager.getInstance(context).enqueueUniqueWork(
                "fotografer_upload", ExistingWorkPolicy.KEEP, request
            )
        }
    }
}
