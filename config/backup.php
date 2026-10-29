<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lokasi Penyimpanan Backup
    |--------------------------------------------------------------------------
    | Folder utama di mana seluruh arsip backup akan disimpan.
    | Pada lingkungan Windows lokal, disarankan folder terpisah dari project,
    | misalnya 'D:\Backup_Simpera'. Tidak boleh di dalam web root (public).
    |
    */
    'path' => env('SIMPERA_BACKUP_PATH', 'D:\Backup_Simpera'),

    /*
    |--------------------------------------------------------------------------
    | Lokasi Direktori Staging Pemulihan (Restore)
    |--------------------------------------------------------------------------
    | Direktori aman untuk menguji pemulihan (restore staging) tanpa menimpa
    | data produksi aktif. Jika null, akan menggunakan subfolder 'staging'
    | di dalam lokasi backup utama.
    |
    */
    'staging_path' => env('SIMPERA_RESTORE_STAGING_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Retensi Backup (Berapa Banyak Backup yang Disimpan)
    |--------------------------------------------------------------------------
    | Jumlah backup terverifikasi yang dipertahankan.
    | Nilai 0 berarti menyimpan semua backup (tidak menghapus otomatis).
    | Penghapusan backup lama hanya dilakukan setelah backup baru terverifikasi 100%.
    |
    */
    'keep_backups' => (int) env('SIMPERA_BACKUP_KEEP', 0),

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Filesystem Dokumen Privat
    |--------------------------------------------------------------------------
    | Disk dan subdirektori tempat berkas dokumen Document Vault disimpan.
    | Sesuai audit, Simpera menggunakan disk 'local' pada storage/app/private.
    |
    */
    'documents_disk' => env('SIMPERA_BACKUP_DOCUMENTS_DISK', 'local'),
    'documents_directory' => 'documents',

    /*
    |--------------------------------------------------------------------------
    | Koneksi Database
    |--------------------------------------------------------------------------
    | Nama koneksi database yang akan dicadangkan.
    |
    */
    'database_connection' => env('DB_CONNECTION', 'sqlite'),

];

