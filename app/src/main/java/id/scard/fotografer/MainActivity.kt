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
 private lateinit var cameraStatus:TextView
 private lateinit var serverStatus:TextView
 private lateinit var preview:ImageView
 private lateinit var emptyPreview:TextView
 private lateinit var fileName:TextView
 private lateinit var uploadStatus:TextView
 private lateinit var counter:TextView

 override fun onCreate(savedInstanceState:Bundle?){
  super.onCreate(savedInstanceState)
  window.statusBarColor=Color.rgb(7,9,12);window.navigationBarColor=Color.rgb(7,9,12)

  val root=LinearLayout(this).apply{
   orientation=LinearLayout.VERTICAL;setPadding(22,16,22,16);setBackgroundColor(Color.rgb(7,9,12))
  }

  val top=LinearLayout(this).apply{gravity=Gravity.CENTER_VERTICAL}
  top.addView(label("FOTOGRAFER",22f,true),LinearLayout.LayoutParams(0,-2,1f))
  cameraStatus=label("● CAMERA",12f,true);top.addView(cameraStatus)
  serverStatus=label("  ● SERVER",12f,true);top.addView(serverStatus)
  root.addView(top)
  root.addView(label("Canon USB • Instant Preview & Delivery",12f),LinearLayout.LayoutParams(-1,-2))

  val frame=FrameLayout(this).apply{setBackgroundColor(Color.rgb(15,18,23))}
  preview=ImageView(this).apply{scaleType=ImageView.ScaleType.FIT_CENTER;visibility=View.GONE}
  frame.addView(preview,FrameLayout.LayoutParams(-1,-1))
  emptyPreview=label("Hasil jepretan akan tampil di sini",18f,true).apply{gravity=Gravity.CENTER}
  frame.addView(emptyPreview,FrameLayout.LayoutParams(-1,-1))
  root.addView(frame,LinearLayout.LayoutParams(-1,0,1f).apply{setMargins(0,14,0,14)})

  val info=LinearLayout(this).apply{gravity=Gravity.CENTER_VERTICAL;setPadding(16,10,16,10);setBackgroundColor(Color.rgb(19,23,29))}
  val left=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL}
  fileName=label("Belum ada foto",16f,true);uploadStatus=label("Menunggu jepretan Canon EOS",12f)
  left.addView(fileName);left.addView(uploadStatus);info.addView(left,LinearLayout.LayoutParams(0,-2,1f))
  counter=label("UP 0   •   QUEUE 0   •   FAIL 0",12f,true);info.addView(counter)
  root.addView(info)

  setContentView(root);refreshUsb()
 }

 private fun label(v:String,s:Float,b:Boolean=false)=TextView(this).apply{
  text=v;textSize=s;setTextColor(Color.rgb(235,239,245));setPadding(6,5,6,5)
  if(b)setTypeface(typeface,Typeface.BOLD)
 }

 /** Call this when the Canon/PTP layer finishes downloading a new JPEG. */
 fun showCapturedPhoto(file:File){
  runOnUiThread{
   val bmp=BitmapFactory.decodeFile(file.absolutePath)
   if(bmp!=null){preview.setImageBitmap(bmp);preview.visibility=View.VISIBLE;emptyPreview.visibility=View.GONE}
   fileName.text=file.name;uploadStatus.text="Foto diterima • menunggu/upload ke server"
  }
 }

 /** Call this from the uploader/queue whenever counters change. */
 fun updateUploadState(uploaded:Int,pending:Int,failed:Int,message:String){
  runOnUiThread{counter.text="UP $uploaded   •   QUEUE $pending   •   FAIL $failed";uploadStatus.text=message}
 }

 private fun refreshUsb(){
  val usb=getSystemService(USB_SERVICE) as UsbManager;val d=usb.deviceList.values.firstOrNull()
  cameraStatus.text=if(d==null)"○ CAMERA" else "● CAMERA"
  cameraStatus.setTextColor(if(d==null)Color.rgb(255,183,77) else Color.rgb(91,214,145))
  serverStatus.text="  ○ SERVER"
  serverStatus.setTextColor(Color.rgb(150,158,170))
 }
}
