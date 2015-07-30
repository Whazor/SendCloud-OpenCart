<?php
class ModelModuleSendcloud extends Model {
  public function saveShipment($orderId, $shipmentId)
  {
    if ($this->db->query("SHOW TABLES LIKE '".DB_PREFIX . "sendcloud_order'")->num_rows==1) {
      $this->db->query("INSERT INTO " . DB_PREFIX . "sendcloud_order (order_id, parcel_id) VALUES ('" . (int)$orderId . "', '".(int)$shipmentId."') ON DUPLICATE KEY UPDATE parcel_id = '".(int)$shipmentId."'");
      return true;
    }
    return false;
  }
  public function loadBarcode($id) {
    if (!$this->db->query("SHOW TABLES LIKE '".DB_PREFIX . "sendcloud_order'")->num_rows==1) {
      return "";
    }

    $results = $this->db->query("SELECT * FROM " . DB_PREFIX . "sendcloud_order WHERE order_id = '".(int)$id."'");

    foreach ($results->rows as $value) {
      if(isset($value['parcel_id'])) {

        return "<script>$(function(){
            var canvas = $('<canvas>');
            canvas.appendTo($('.sendcloud_barcode-".$id."'));
            canvas.JsBarcode('".$value['parcel_id']."');
          });</script><style>h1 { float: left; } canvas { margin: 10px } .container { margin-top 10px }</style>";
      }

    }

    return "";
  }
}
