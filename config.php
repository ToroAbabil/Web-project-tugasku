<?php
// ============================================================
//  TUGASKU - Konfigurasi
//  Isi sesuai instruksi setup di README.md
// ============================================================

define('GOOGLE_CLIENT_ID',     'GANTI_DENGAN_CLIENT_ID_KAMU');
define('GOOGLE_CLIENT_SECRET', 'GANTI_DENGAN_CLIENT_SECRET_KAMU');
define('GOOGLE_REDIRECT_URI',  'http://localhost'); // sesuaikan dengan URL kamu

// Folder Google Drive tempat tugas disimpan (nama bebas)
define('GDRIVE_FOLDER_NAME', 'TugasKu - Pengumpulan Tugas');

// Timezone
date_default_timezone_set('Asia/Makassar'); // WIB=Asia/Jakarta | WITA=Asia/Makassar | WIT=Asia/Jayapura

// Path penyimpanan data tugas (JSON lokal)
define('DATA_FILE', __DIR__ . '/data/tasks.json');
define('SESSION_NAME', 'tugasku_session');

// Pastikan folder data ada
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

session_name(SESSION_NAME);
session_start();
