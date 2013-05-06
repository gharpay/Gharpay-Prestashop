<?php
include_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/gharpay.php');

$gharpay = new Gharpay();
$gharpay_order_id = $_GET['order_id']; 
$timestamp = $_GET['time'];
$gharpay->updateOrderStatus($gharpay_order_id,$timestamp);
?>