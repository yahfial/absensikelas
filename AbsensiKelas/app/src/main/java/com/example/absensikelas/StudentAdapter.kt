package com.example.absensikelas

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView

class StudentAdapter(
    private val onClick: (StudentDto) -> Unit
) : RecyclerView.Adapter<StudentAdapter.VH>() {

    private val items = mutableListOf<StudentDto>()

    fun submit(list: List<StudentDto>) {
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
        val s = items[position]
        holder.tvTitle.text = "${s.nim} — ${s.nama}"
        holder.tvSub.text = "${s.namaKelas} | kelasId=${s.kelasId} | Angkatan ${s.angkatan}"
        holder.tvSub.text = "${s.namaKelas ?: "-"} | Angkatan ${s.angkatan}"
        holder.itemView.setOnClickListener { onClick(s) }
    }
}