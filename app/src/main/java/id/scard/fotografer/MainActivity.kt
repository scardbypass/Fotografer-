package id.scard.fotografer

import android.app.Activity
import android.os.Bundle
import android.hardware.usb.UsbManager
import android.graphics.Color
import android.graphics.BitmapFactory
import android.graphics.Typeface
import android.view.Gravity
import android.view.View
import android.widget.*
import java.io.File

class MainActivity : Activity() {
    private lateinit var camera: TextView
    private lateinit var server: TextView
    private lateinit var image: ImageView
    private lateinit var empty: LinearLayout
    private lateinit var filename: TextView
    private lateinit var transfer: TextView
    private lateinit var stats: TextView

    override fun onCreate(state: Bundle?) {
        super.onCreate(state)
        window.statusBarColor = Color.BLACK
        window.navigationBarColor = Color.BLACK
  val root=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setBackgroundColor(Color.BLACK)}
  val bar=LinearLayout(this).apply{gravity=Gravity.CENTER_VERTICAL;setPadding(22,10,22,10);setBackgroundColor(Color.rgb(17,17,17))}
  val brand=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL}
  brand.addView(txt("FOTOGRAFER",17f,true));brand.addView(txt("INSTANT PHOTO DELIVERY",9f,false,Color.rgb(145,145,145)))
  bar.addView(brand,LinearLayout.LayoutParams(0,-2,1f))
  camera=chip("CAMERA");server=chip("SERVER");bar.addView(camera);bar.addView(server);root.addView(bar)

  val stage=FrameLayout(this).apply{setBackgroundColor(Color.BLACK)}
  image=ImageView(this).apply{scaleType=ImageView.ScaleType.FIT_CENTER;visibility=View.GONE}
  stage.addView(image,FrameLayout.LayoutParams(-1,-1))
  empty=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;gravity=Gravity.CENTER}
  empty.addView(txt("MENUNGGU FOTO",16f,true).apply{gravity=Gravity.CENTER})
  empty.addView(txt("Hubungkan Canon melalui USB lalu mulai memotret.",12f,false,Color.rgb(130,130,130)).apply{gravity=Gravity.CENTER})
  stage.addView(empty,FrameLayout.LayoutParams(-1,-1));root.addView(stage,LinearLayout.LayoutParams(-1,0,1f))

  val bottom=LinearLayout(this).apply{gravity=Gravity.CENTER_VERTICAL;setPadding(22,10,22,10);setBackgroundColor(Color.rgb(17,17,17))}
  val info=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL}
  filename=txt("Belum ada foto",14f,true);transfer=txt("Siap menerima JPEG",11f,false,Color.rgb(150,150,150))
  info.addView(filename);info.addView(transfer);bottom.addView(info,LinearLayout.LayoutParams(0,-2,1f))
  stats=txt("0 UP   0 QUEUE   0 FAIL",11f,true,Color.rgb(190,190,190));bottom.addView(stats);root.addView(bottom)
  setContentView(root);refreshUsb()
 }

 private fun txt(v:String,s:Float,b:Boolean=false,c:Int=Color.WHITE)=TextView(this).apply{text=v;textSize=s;setTextColor(c);setPadding(5,3,5,3);if(b)setTypeface(typeface,Typeface.BOLD)}
 private fun chip(v:String)=txt("  $v  ",10f,true,Color.rgb(150,150,150)).apply{setPadding(10,8,10,8)}

 fun showCapturedPhoto(file:File){runOnUiThread{
  val opts=android.graphics.BitmapFactory.Options().apply{inSampleSize=2};val bmp=BitmapFactory.decodeFile(file.absolutePath,opts)
  if(bmp!=null){image.setImageBitmap(bmp);image.visibility=View.VISIBLE;empty.visibility=View.GONE}
  filename.text=file.name;transfer.text="Foto diterima · menyiapkan upload"
 }}

 fun updateUploadState(uploaded:Int,pending:Int,failed:Int,message:String){runOnUiThread{
  stats.text="$uploaded UP   $pending QUEUE   $failed FAIL";transfer.text=message
  server.text=if(failed>0)"  SERVER !  " else "  SERVER ●  ";server.setTextColor(if(failed>0)Color.rgb(232,173,84) else Color.rgb(112,203,146))
 }}

 private fun refreshUsb(){val usb=getSystemService(USB_SERVICE) as UsbManager;val d=usb.deviceList.values.firstOrNull()
  camera.text=if(d==null)"  CAMERA ○  " else "  CAMERA ●  ";camera.setTextColor(if(d==null)Color.rgb(210,160,82) else Color.rgb(112,203,146))
  server.text="  SERVER ○  "
 }
}
