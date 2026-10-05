package id.scard.fotografer

import java.io.File
import java.io.IOException
import java.util.concurrent.TimeUnit
import okhttp3.Call
import okhttp3.Callback
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.Response

class Uploader(
    serverUrl: String,
    private val uploadToken: String,
) {
    private val uploadUrl = serverUrl.trimEnd('/') + "/api/upload.php"

    private val client = OkHttpClient.Builder()
        .connectTimeout(20, TimeUnit.SECONDS)
        .writeTimeout(120, TimeUnit.SECONDS)
        .readTimeout(120, TimeUnit.SECONDS)
        .build()

    fun upload(
        file: File,
        callback: (Boolean, String) -> Unit,
    ) {
        val requestBody = MultipartBody.Builder()
            .setType(MultipartBody.FORM)
            .addFormDataPart(
                "photo",
                file.name,
                file.asRequestBody("image/jpeg".toMediaType()),
            )
            .build()

        val request = Request.Builder()
            .url(uploadUrl)
            .header("Authorization", "Bearer $uploadToken")
            .post(requestBody)
            .build()

        client.newCall(request).enqueue(object : Callback {
            override fun onFailure(call: Call, exception: IOException) {
                callback(false, exception.message ?: "Upload gagal")
            }

            override fun onResponse(call: Call, response: Response) {
                response.use {
                    callback(it.isSuccessful, it.body?.string().orEmpty())
                }
            }
        })
    }
}
