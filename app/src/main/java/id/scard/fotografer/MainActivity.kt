package id.scard.fotografer

import android.app.Activity
import android.os.Bundle
import android.hardware.usb.UsbManager
import android.graphics.Color
import android.graphics.Typeface
import android.view.Gravity
import android.widget.*

class MainActivity : Activity() {
 private lateinit var cameraStatus: TextView
 private fun text(value:String,size:Float=16f,bold:Boolean=false)=TextView(this).apply{
  text=value;textSize=size;setTextColor(Color.rgb(238,241,246));setPadding(0,8,0,8)
  if(bold)setTypeface(typeface,Typeface.BOLD)
 }
 private fun card():LinearLayout=LinearLayout(this).apply{
  orientation=LinearLayout.VERTICAL;setPadding(28,24,28,24)
  setBackgroundColor(Color.rgb(24,28,36))
 }
 override fun onCreate(savedInstanceState:Bundle?){
  super.onCreate(savedInstanceState)
  val scroll=ScrollView(this)
  val root=LinearLayout(this).apply{orientation=LinearLayout.VERTICAL;setPadding(32,26,32,32);setBackgroundColor(Color.rgb(10,12,16))}
  val head=LinearLayout(this).apply{gravity=Gravity.CENTER_VERTICAL}
  head.addView(text("FOTOGRAFER",28f,true),LinearLayout.LayoutParams(0,-2,1f))
  val live=text("● READY",14f,true).apply{setTextColor(Color.rgb(98,220,153))}
  head.addView(live);root.addView(head)
  root.addView(text("Instant Photo Delivery • Tablet Console",14f))

  val statusCard=card();statusCard.addView(text("CAMERA",12f,true))
  cameraStatus=text("Memeriksa USB…",22f,true);statusCard.addView(cameraStatus)
  statusCard.addView(text("Canon EOS • USB/PTP",14f));root.addView(statusCard)

  val preview=card();preview.addView(text("FOTO TERBARU",12f,true))
  preview.addView(text("Belum ada foto",30f,true))
  preview.addView(text("Hasil jepretan terbaru akan tampil otomatis di sini.",15f));root.addView(preview)

  val stats=card();stats.addView(text("UPLOAD STATUS",12f,true))
  stats.addView(text("Uploaded   0     •     Pending   0     •     Failed   0",18f,true))
  stats.addView(text("Server belum dikonfigurasi",14f));root.addView(stats)

  val settings=Button(this).apply{text="PENGATURAN SERVER & TOKEN";textSize=15f}
  root.addView(settings)
  scroll.addView(root);setContentView(scroll);refreshUsb()
 }
 private fun refreshUsb(){
  val usb=getSystemService(USB_SERVICE) as UsbManager;val devices=usb.deviceList.values
  cameraStatus.text=if(devices.isEmpty())"○ Kamera belum terhubung" else "● "+(devices.first().productName?:"USB Camera")+" terhubung"
  cameraStatus.setTextColor(if(devices.isEmpty())Color.rgb(255,190,92) else Color.rgb(98,220,153))
 }
}
