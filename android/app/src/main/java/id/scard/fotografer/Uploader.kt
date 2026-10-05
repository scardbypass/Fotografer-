package id.scard.fotografer

import okhttp3.*
import java.io.File
import java.util.concurrent.TimeUnit

class Uploader(private val baseUrl: String, private val token: String) {
 private val client = OkHttpClient.Builder().connectTimeout(20, TimeUnit.SECONDS).writeTimeout(120, TimeUnit.SECONDS).build()
 fun upload(event: String, file: File, callback: (Boolean, String) -> Unit) {
  val body = MultipartBody.Builder().setType(MultipartBody.FORM).addFormDataPart("event", event).addFormDataPart("photo", file.name, file.asRequestBody("image/jpeg".toMediaType())).build()
  val req = Request.Builder().url(baseUrl.trimEnd('/') + "/api/upload.php").header("Authorization", "Bearer $token").post(body).build()
  client.newCall(req).enqueue(object: Callback {
   override fun onFailure(call: Call, e: java.io.IOException) = callback(false, e.message ?: "upload failed")
   override fun onResponse(call: Call, response: Response) = response.use { callback(it.isSuccessful, it.body?.string() ?: "") }
  })
 }
}
