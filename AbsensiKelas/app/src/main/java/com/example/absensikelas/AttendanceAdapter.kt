package com.example.absensikelas

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class AttendanceAdapter : RecyclerView.Adapter<AttendanceAdapter.VH>() {

    private val items = mutableListOf<AttendanceDto>()

    fun submit(list: List<AttendanceDto>) {
        items.clear()
        items.addAll(list)
        notifyDataSetChanged()
    }

    class VH(v: View) : RecyclerView.ViewHolder(v) {
        val tvTitle: TextView = v.findViewById(android.R.id.text1)
        val tvSub: TextView = v.findViewById(android.R.id.text2)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VH {
        val v = LayoutInflater.from(parent.context)
            .inflate(android.R.layout.simple_list_item_2, parent, false)
        return VH(v)
    }

    override fun getItemCount() = items.size

    override fun onBindViewHolder(holder: VH, position: Int) {
        val a = items[position]

        // Baris 1: tanggal | nim - nama
        val nama = a.namaMahasiswa?.takeIf { it.isNotBlank() }
        val title = if (nama != null) "${a.tanggal} | ${a.nim} - $nama" else "${a.tanggal} | ${a.nim}"
        holder.tvTitle.text = title

        // Baris 2: status | jam | ket
        val parts = mutableListOf<String>()
        parts.add(a.status)
        a.jamInput?.takeIf { it.isNotBlank() }?.let { parts.add(it) }
        a.keterangan?.takeIf { it.isNotBlank() }?.let { parts.add(it) }
        holder.tvSub.text = parts.joinToString(" | ")
    }
}