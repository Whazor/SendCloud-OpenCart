<?php
// require_once(dirname(__FILE__).'/../sendcloud/sendcloud_api.php');
include_once(DIR_SYSTEM . 'library/SendCloud-API-PHP-Wrapper/src/SendCloudApi.php');

class ControllerModuleSendcloud extends Controller {

	private $error = array();

	public function index() {

		$settings = $this->load->model('setting/setting');
		$language = $this->load->language('module/sendcloud');

		// Set language data
		$data['heading_title'] = $this->language->get('heading_title');
		$data['settings_title'] = $this->language->get('settings_title');
		$data['settings_h3'] = $this->language->get('settings_h3');
		$data['default_status_label'] = $this->language->get('default_status_label');
		$data['default_status'] = $this->language->get('default_status');
		$data['msg_settings_saved'] = $this->language->get('msg_settings_saved');
		$data['sendcloud_address2_as_housenumber_label'] = $this->language->get('sendcloud_address2_as_housenumber_label');
		$data['text_disabled']= $this->language->get('text_disabled');
		$data['text_enabled']= $this->language->get('text_enabled');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('sendcloud', $this->request->post);
			$this->session->data['success'] = $data['msg_settings_saved'];
			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$sendcloud_settings = $this->model_setting_setting->getSetting('sendcloud');

		$this->document->setTitle($this->language->get('heading_title'));

		$statuses = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'order_status');
		$data['statuses'] = $statuses->rows;

 		if (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		// Go over every value
		$vals = ['sendcloud_automate', 'sendcloud_api_key', 'sendcloud_api_secret',
			'sendcloud_address2_as_housenumber'];
		foreach ($vals as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} else {
				$data[$key] = $this->config->get($key);
			}

			$data[$key] = isset($sendcloud_settings[$key]) ? $sendcloud_settings[$key] : "";
		}

		$data['breadcrumbs'] = array();
 		$data['breadcrumbs'][] = array(
     		'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
    		'separator' => false
 		);
 		$data['breadcrumbs'][] = array(
     		'text'      => $this->language->get('settings_title'),
				'href'      => $this->url->link('module/sendcloud', 'token=' . $this->session->data['token'], 'SSL'),
    		'separator' => ' :: '
 		);

		$data['form_action'] = $this->url->link('module/sendcloud', 'token=' . $this->session->data['token'], 'SSL');
		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->session->data['success'] = $data['msg_settings_saved'];
		}

		$this->response->setOutput($this->load->view('module/sendcloud.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/information')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function bulk() {
		$settings = $this->load->model('setting/setting');
		$sendcloud_settings = $this->model_setting_setting->getSetting('sendcloud');
		$language = $this->load->language('module/sendcloud');

		if (!empty($sendcloud_settings['sendcloud_api_key']) && !empty($sendcloud_settings['sendcloud_api_secret'])) {
			$api = new SendCloudApi($sendcloud_settings['sendcloud_api_key'], $sendcloud_settings['sendcloud_api_secret']);
		} else {
			$this->showErrorMessage($this->language->get('msg_no_api_settings'));
		}

		if(!isset($_POST['selected'])){
			$message = $this->language->get('error_no_orders');
			$this->showErrorMessage($message);
		}
		$selected = $_POST['selected'];

		$this->load->model('sale/order');
		$order_model = $this->model_sale_order;

		$orders = Array();

		foreach ($selected as $key => $s) {
			$orders[] = $order_model->getOrder($s);
		}

		foreach ($orders as $order) {
			try{
				$send = array(
					'name'=> $order['shipping_firstname'] . ' ' . $order['shipping_lastname'],
					'address' => $order['shipping_address_1'],
					'company_name' => $order['shipping_company'],
					'city' => $order['shipping_city'],
					'postal_code' => $order['shipping_postcode'],
					'requestShipment' => false,
					'email' => $order['email'],
					'country' => $order['shipping_iso_code_2'],
					'order_number' => $order['order_id']
				);
				if($sendcloud_settings['sendcloud_address2_as_housenumber']) {
					$send['house_number'] = $order['shipping_address_2'];
				} else {
					$send['address_2'] = $order['shipping_address_2'];
				}
				$result = $api->parcels->create($send);

				// we do assume that webshop owners sometimes forget installing the SQL file
				$this->load->model('module/sendcloud');
				if (!$this->model_module_sendcloud->saveShipment($order['order_id'], $result['id'])) {
					//TODO: show error
				}
			} catch (SendCloudApiException $exception) {
				$message = $this->language->get('msg_process_orders') .  $order['order_id'] .", ". $this->language->get('msg_api_error_reason'). $exception->message.'.';
				$this->showErrorMessage($message);
			}

			if(isset($sendcloud_settings['sendcloud_automate']) && !empty($sendcloud_settings['sendcloud_automate'])){
				$this->updateOrderStatus($order);
			}
		}

		$this->session->data['success'] = $this->language->get('msg_success');
		$this->response->redirect($this->url->link('sale/order', 'token=' . $this->session->data['token'], 'SSL'));
	}

	private function updateOrderStatus($order){
		$order_id = $order['order_id'];
		$sendcloud_settings = $this->model_setting_setting->getSetting('sendcloud');
		$order_status_id = $sendcloud_settings['sendcloud_automate'];
		$notify = false;
		$comment = nl2br($this->language->get('log_message'));
		$date_added = date($this->language->get('date_format_short'));

		// Queries Borrowed from /catalog/model/checkout/order.php
		$this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
	}

	private function showErrorMessage($message) {
		// FIXME: Hack to show error message.
		$this->session->data['success'] = "<span class='alert alert-danger' style='width:100%; width: calc(100% + 22px); float:left; position:relative; top:-29px; left:-11px;'>
		<i class='fa fa-exclamation-circle'></i> ". $message ."</span>";
		$this->response->redirect($this->url->link('sale/order', 'token=' . $this->session->data['token'], 'SSL'));
	}
}
?>
