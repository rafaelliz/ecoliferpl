<?php
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('index.php'); }
if (csrf_check()) {
    destroy_session();
    start_session();
    flash('info', 'Kamu sudah keluar. Sampai jumpa!');
    redirect('login.php');
}
redirect('index.php');
