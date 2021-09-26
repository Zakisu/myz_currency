<?php
require_once 'db.php';
$db = DB::getInstance();
$base = isset($_GET['base']) ? $_GET['base'] : 'EUR';
$currencies = $db->table("base")->where('base', $base)->get()->first()->data;
header("Content-Type: application/json");
echo $currencies;