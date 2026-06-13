<?php
require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

flash('error', 'Reklama tizimi o\'chirilgan.');
redirect('admin/index.php');
