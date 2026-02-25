(function () {
  // =========================
  // UTIL LOCAL STORAGE
  // =========================
  function loadJSON(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      if (!raw) return fallback;
      return JSON.parse(raw);
    } catch {
      return fallback;
    }
  }

  function saveJSON(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  }

  const Store = {
    getStudents() { return loadJSON("students", []); },
    setStudents(list) { saveJSON("students", list); },

    getAttendance() { return loadJSON("attendance", []); },
    setAttendance(list) { saveJSON("attendance", list); },

    getUser() { return loadJSON("user", null); },
    setUser(user) { saveJSON("user", user); },
    clearUser() { localStorage.removeItem("user"); }
  };

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function todayISO() {
    return new Date().toISOString().slice(0, 10);
  }

  // =========================
  // LOADER
  // =========================
  const loader = document.getElementById("appLoader");
  const MIN_LOADING_MS = 450;
  const start = performance.now();

  function hideLoader() {
    if (!loader) return;
    loader.classList.add("hide");
    setTimeout(() => loader.remove(), 350);
  }

  window.addEventListener("load", () => {
    const elapsed = performance.now() - start;
    const remaining = Math.max(0, MIN_LOADING_MS - elapsed);
    setTimeout(hideLoader, remaining);
  });

  // =========================
  // SIDEBAR DRAWER
  // =========================
  const btnOpen = document.getElementById("btnOpenSidebar");
  const btnClose = document.getElementById("btnCloseSidebar");
  const backdrop = document.getElementById("backdrop");

  function openSidebar() {
    document.body.classList.add("sidebar-open");
    backdrop?.setAttribute("aria-hidden", "false");
  }

  function closeSidebar() {
    document.body.classList.remove("sidebar-open");
    backdrop?.setAttribute("aria-hidden", "true");
  }

  btnOpen?.addEventListener("click", openSidebar);
  btnClose?.addEventListener("click", closeSidebar);
  backdrop?.addEventListener("click", closeSidebar);

  window.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeSidebar();
  });

  // =========================
  // ACTIVE MENU (auto highlight)
  // =========================
  const currentFile = (location.pathname.split("/").pop() || "index.html").toLowerCase();
  document.querySelectorAll(".menu-item").forEach((a) => {
    const href = (a.getAttribute("href") || "").toLowerCase();
    if (!href || href === "#") return;
    a.classList.toggle("active", href === currentFile);
  });

  // =========================
  // LOGOUT
  // =========================
  const btnLogout = document.getElementById("btnLogout");
  btnLogout?.addEventListener("click", () => {
    Store.clearUser();
    location.href = "login.html";
  });

  // =========================
  // DASHBOARD (index.html)
  // =========================
  function initDashboard() {
    const elTotal = document.getElementById("dashTotalMahasiswa");
    const elToday = document.getElementById("dashAbsensiHariIni");
    const elBar = document.getElementById("dashHadirBar");
    const elText = document.getElementById("dashHadirText");
    const elUser = document.getElementById("dashUser");

    if (!elTotal && !elToday && !elBar && !elText && !elUser) return;

    const students = Store.getStudents();
    const attendance = Store.getAttendance();
    const today = todayISO();
    const todayRecords = attendance.filter((r) => r.tanggal === today);

    const hadirCount = todayRecords.filter((r) => r.status === "HADIR").length;
    const percent = todayRecords.length ? Math.round((hadirCount / todayRecords.length) * 100) : 0;

    if (elTotal) elTotal.textContent = String(students.length);
    if (elToday) elToday.textContent = String(todayRecords.length);
    if (elBar) elBar.style.width = `${percent}%`;
    if (elText) elText.textContent = `${percent}% hadir hari ini`;

    const user = Store.getUser();
    if (elUser) elUser.textContent = user?.username ? `Login sebagai: ${user.username}` : "Belum login (demo statis)";
  }

  // =========================
  // LOGIN (login.html)
  // =========================
  function initLogin() {
    const form = document.getElementById("loginForm");
    const msg = document.getElementById("loginMessage");
    if (!form || !msg) return;

    function show(type, text) {
      msg.className = "message " + type;
      msg.textContent = text;
      msg.style.display = "block";
    }

    function clear() {
      msg.className = "message";
      msg.textContent = "";
      msg.style.display = "none";
    }

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      clear();

      const username = document.getElementById("username").value.trim();
      const password = document.getElementById("password").value;

      if (!username || !password) {
        show("error", "Username dan password wajib diisi.");
        return;
      }
      if (password.length < 6) {
        show("error", "Password minimal 6 karakter (untuk demo).");
        return;
      }

      Store.setUser({ username, loginAt: new Date().toISOString() });
      show("success", "Login berhasil. Mengarahkan ke Dashboard...");
      setTimeout(() => (location.href = "index.html"), 500);
    });
  }

  // =========================
  // MAHASISWA (mahasiswa.html)
  // =========================
  function initMahasiswa() {
    const form = document.getElementById("mhsForm");
    const body = document.getElementById("mhsBody");
    const msg = document.getElementById("mhsMessage");
    const total = document.getElementById("mhsTotal");
    if (!form || !body || !msg) return;

    function show(type, text) {
      msg.className = "message " + type;
      msg.textContent = text;
      msg.style.display = "block";
    }

    function clear() {
      msg.className = "message";
      msg.textContent = "";
      msg.style.display = "none";
    }

    function render() {
      const students = Store.getStudents();
      if (total) total.textContent = String(students.length);

      body.innerHTML = "";
      if (!students.length) {
        body.innerHTML = `<tr><td colspan="6" class="muted center">Belum ada data mahasiswa.</td></tr>`;
        return;
      }

      students.forEach((s, idx) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${escapeHtml(s.nim)}</td>
          <td>${escapeHtml(s.nama)}</td>
          <td>${escapeHtml(s.kelas)}</td>
          <td>${escapeHtml(s.angkatan)}</td>
          <td class="actions-cell">
            <button class="btn small danger" data-action="delete" data-nim="${escapeHtml(s.nim)}">Hapus</button>
          </td>
        `;
        body.appendChild(tr);
      });
    }

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      clear();

      const nim = document.getElementById("mhsNim").value.trim();
      const nama = document.getElementById("mhsNama").value.trim();
      const kelas = document.getElementById("mhsKelas").value.trim();
      const angkatan = document.getElementById("mhsAngkatan").value.trim();

      if (!nim || !nama || !kelas || !angkatan) {
        show("error", "Semua field wajib diisi.");
        return;
      }
      if (!/^[0-9]+$/.test(nim) || nim.length < 8 || nim.length > 12) {
        show("error", "NIM harus angka 8–12 digit.");
        return;
      }
      if (!/^[0-9]{4}$/.test(angkatan)) {
        show("error", "Angkatan harus 4 digit (contoh: 2024).");
        return;
      }

      const students = Store.getStudents();
      const exists = students.some((s) => s.nim === nim);
      if (exists) {
        show("error", "NIM sudah terdaftar. Gunakan NIM lain.");
        return;
      }

      students.push({ nim, nama, kelas, angkatan, createdAt: new Date().toISOString() });
      Store.setStudents(students);
      show("success", "Mahasiswa berhasil ditambahkan.");
      form.reset();
      render();
    });

    body.addEventListener("click", (e) => {
      const btn = e.target.closest("button");
      if (!btn) return;

      const action = btn.getAttribute("data-action");
      const nim = btn.getAttribute("data-nim");
      if (action !== "delete" || !nim) return;

      const students = Store.getStudents();
      const updated = students.filter((s) => s.nim !== nim);
      Store.setStudents(updated);
      show("success", "Data mahasiswa dihapus.");
      render();
    });

    render();
  }

  // =========================
  // ABSENSI (absensi.html)
  // =========================
  function initAbsensi() {
    const form = document.getElementById("absensiForm");
    const msg = document.getElementById("absensiMessage");
    const body = document.getElementById("absensiBody");
    const selNim = document.getElementById("absNim");
    const inpNama = document.getElementById("absNama");
    const inpKelas = document.getElementById("absKelas");
    const inpTanggal = document.getElementById("absTanggal");
    if (!form || !msg || !body || !selNim || !inpNama || !inpKelas || !inpTanggal) return;

    function show(type, text) {
      msg.className = "message " + type;
      msg.textContent = text;
      msg.style.display = "block";
    }

    function clear() {
      msg.className = "message";
      msg.textContent = "";
      msg.style.display = "none";
    }

    function populateStudents() {
      const students = Store.getStudents();
      selNim.innerHTML = `<option value="">-- pilih mahasiswa --</option>`;
      if (!students.length) {
        selNim.innerHTML += `<option value="" disabled>(Belum ada data mahasiswa)</option>`;
        return;
      }
      students.forEach((s) => {
        const opt = document.createElement("option");
        opt.value = s.nim;
        opt.textContent = `${s.nim} — ${s.nama}`;
        selNim.appendChild(opt);
      });
    }

    function fillStudent(nim) {
      const students = Store.getStudents();
      const s = students.find((x) => x.nim === nim);
      if (!s) {
        inpNama.value = "";
        inpKelas.value = "";
        return;
      }
      inpNama.value = s.nama;
      inpKelas.value = s.kelas;
    }

    function render() {
      const list = Store.getAttendance();
      body.innerHTML = "";
      if (!list.length) {
        body.innerHTML = `<tr><td colspan="7" class="muted center">Belum ada data absensi.</td></tr>`;
        return;
      }

      list.slice().reverse().forEach((r, idx) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${escapeHtml(r.tanggal)}</td>
          <td>${escapeHtml(r.nim)}</td>
          <td>${escapeHtml(r.nama)}</td>
          <td>${escapeHtml(r.kelas)}</td>
          <td>${escapeHtml(r.status)}</td>
          <td class="actions-cell">
            <button class="btn small danger" data-action="delete" data-id="${escapeHtml(r.id)}">Hapus</button>
          </td>
        `;
        body.appendChild(tr);
      });
    }

    // default tanggal hari ini
    inpTanggal.value = todayISO();

    selNim.addEventListener("change", () => fillStudent(selNim.value));

    form.addEventListener("submit", (e) => {
      e.preventDefault();
      clear();

      const tanggal = inpTanggal.value;
      const nim = selNim.value;
      const nama = inpNama.value.trim();
      const kelas = inpKelas.value.trim();
      const status = document.getElementById("absStatus").value;

      if (!tanggal || !nim || !nama || !kelas || !status) {
        show("error", "Semua field wajib diisi.");
        return;
      }

      const record = {
        id: String(Date.now()),
        tanggal,
        nim,
        nama,
        kelas,
        status
      };

      const list = Store.getAttendance();
      list.push(record);
      Store.setAttendance(list);

      show("success", "Absensi berhasil disimpan (localStorage).");
      form.reset();
      inpTanggal.value = todayISO();
      inpNama.value = "";
      inpKelas.value = "";
      populateStudents();
      render();
      initDashboard(); // update dashboard kalau user balik ke index
    });

    body.addEventListener("click", (e) => {
      const btn = e.target.closest("button");
      if (!btn) return;

      const action = btn.getAttribute("data-action");
      const id = btn.getAttribute("data-id");
      if (action !== "delete" || !id) return;

      const list = Store.getAttendance();
      Store.setAttendance(list.filter((r) => r.id !== id));
      show("success", "Data absensi dihapus.");
      render();
      initDashboard();
    });

    populateStudents();
    render();
  }

  // =========================
  // REKAP (rekap.html)
  // =========================
  function initRekap() {
    const totalMhs = document.getElementById("rekapTotalMhs");
    const totalAbs = document.getElementById("rekapTotalAbs");
    const hadir = document.getElementById("rekapHadir");
    const izin = document.getElementById("rekapIzin");
    const sakit = document.getElementById("rekapSakit");
    const alpa = document.getElementById("rekapAlpa");

    const filterKelas = document.getElementById("rekapKelas");
    const filterTanggal = document.getElementById("rekapTanggal");
    const btnFilter = document.getElementById("btnFilterRekap");
    const btnReset = document.getElementById("btnResetRekap");

    const body = document.getElementById("rekapBody");
    if (!body) return;

    function computeStats(list) {
      const students = Store.getStudents();
      if (totalMhs) totalMhs.textContent = String(students.length);
      if (totalAbs) totalAbs.textContent = String(list.length);

      const cH = list.filter((r) => r.status === "HADIR").length;
      const cI = list.filter((r) => r.status === "IZIN").length;
      const cS = list.filter((r) => r.status === "SAKIT").length;
      const cA = list.filter((r) => r.status === "ALPA").length;

      if (hadir) hadir.textContent = String(cH);
      if (izin) izin.textContent = String(cI);
      if (sakit) sakit.textContent = String(cS);
      if (alpa) alpa.textContent = String(cA);
    }

    function render(list) {
      body.innerHTML = "";
      if (!list.length) {
        body.innerHTML = `<tr><td colspan="6" class="muted center">Tidak ada data untuk ditampilkan.</td></tr>`;
        return;
      }

      list.slice().reverse().forEach((r, idx) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${escapeHtml(r.tanggal)}</td>
          <td>${escapeHtml(r.nim)}</td>
          <td>${escapeHtml(r.nama)}</td>
          <td>${escapeHtml(r.kelas)}</td>
          <td>${escapeHtml(r.status)}</td>
        `;
        body.appendChild(tr);
      });
    }

    function applyFilter() {
      const all = Store.getAttendance();
      let filtered = all;

      const kelas = filterKelas?.value || "";
      const tanggal = filterTanggal?.value || "";

      if (kelas) filtered = filtered.filter((r) => r.kelas === kelas);
      if (tanggal) filtered = filtered.filter((r) => r.tanggal === tanggal);

      computeStats(filtered);
      render(filtered);
    }

    // isi dropdown kelas dari data mahasiswa
    if (filterKelas) {
      const classes = Array.from(new Set(Store.getStudents().map((s) => s.kelas))).sort();
      filterKelas.innerHTML = `<option value="">Semua kelas</option>`;
      classes.forEach((k) => {
        const opt = document.createElement("option");
        opt.value = k;
        opt.textContent = k;
        filterKelas.appendChild(opt);
      });
    }

    btnFilter?.addEventListener("click", applyFilter);
    btnReset?.addEventListener("click", () => {
      if (filterKelas) filterKelas.value = "";
      if (filterTanggal) filterTanggal.value = "";
      applyFilter();
    });

    applyFilter();
  }

  // =========================
  // INIT ALL
  // =========================
  initDashboard();
  initLogin();
  initMahasiswa();
  initAbsensi();
  initRekap();
})();