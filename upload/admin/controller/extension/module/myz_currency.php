<?php

class ControllerExtensionModuleMyzCurrency extends Controller
{
	private $error = [];

	public function __construct($params){
		parent::__construct($params);

		$this->events = [
			[
				'code' => 'myz_currency',
				'trigger' => 'admin/controller/common/footer/after',
				'action' => 'extension/module/myz_currency/init'
			]
		];
	}

	protected function addEvents()
	{
		$this->load->model('setting/event');

		foreach ($this->events as $event) {
			if (!$this->model_setting_event->getEventByCode($event['code'])) {
				$this->model_setting_event->addEvent($event['code'], $event['trigger'], $event['action']);
			}
		}
	}

	protected function deleteEvents()
	{
		$this->load->model('setting/event');

		foreach ($this->events as $event) {
			if ($this->model_setting_event->getEventByCode($event['code'])) {
				$this->model_setting_event->deleteEventByCode($event['code']);
			}
		}
	}

	private function getCurrencies($currency_code){
		$ch = curl_init();
		// WEB API
		curl_setopt($ch, CURLOPT_URL, "https://[your_domain]/path/base.php?base=" . $currency_code);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);

		return json_decode($output, TRUE);
	}

	public function init() {
		$this->load->model('setting/setting');

		$date_now = date("Y-m-d");
		$date_last = $this->config->get('myz_currency_setting_date') ? date('Y-m-d', strtotime($this->config->get('myz_currency_setting_date'))) : date('Y-m-d',strtotime('-1 day'));

		if ($date_last != $date_now) {
			$default_currency = $this->config->get('config_currency');
			$currencies = $this->getCurrencies($default_currency);

			$query_active_currencies = $this->db->query("SELECT GROUP_CONCAT(code separator ',') as active_currencies FROM " . DB_PREFIX . "currency WHERE status = 1")->row['active_currencies'];
			$active_currencies = explode(",",$query_active_currencies);

			$this->db->query("TRUNCATE " . DB_PREFIX . "currency");
			foreach ($currencies as $currency) {
				if (in_array($currency['code'], $active_currencies)) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "currency SET title='" . $this->db->escape($currency['title']) . "', code='" . $this->db->escape($currency['code']) . "', symbol_left='" . $this->db->escape($currency['symbol_left']) . "', symbol_right='" . $this->db->escape($currency['symbol_right']) . "', decimal_place=" . (int)$currency['decimal_place'] . ", value=" . (float)$currency['value'] . ", status=1, date_modified='" . $this->db->escape($currency['date_modified']) . "'");
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "currency SET title='" . $this->db->escape($currency['title']) . "', code='" . $this->db->escape($currency['code']) . "', symbol_left='" . $this->db->escape($currency['symbol_left']) . "', symbol_right='" . $this->db->escape($currency['symbol_right']) . "', decimal_place=" . (int)$currency['decimal_place'] . ", value=" . (float)$currency['value'] . ", status=0, date_modified='" . $this->db->escape($currency['date_modified']) . "'");
				}
			}
			$this->model_setting_setting->editSetting('myz_currency_setting', ['myz_currency_setting_date'=>$date_now]);
		}
	}

	public function index()
	{
		// Add all events
		$this->addEvents();

		$this->load->language('extension/module/myz_currency');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('setting/event');

		$myz_event = $this->model_setting_event->getEventByCode('myz_currency');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$prefix = 'myz_currency_';
			$setting = [];

			foreach (array_keys($this->request->post) as $post) {
				$setting[$prefix . $post] = $this->request->post[$post];
			}

			$this->model_setting_setting->editSetting('myz_currency', $setting);
			$this->model_setting_setting->editSetting('module_myz_currency', ['module_myz_currency_status'=>$this->request->post['status']]);

			if ($this->request->post['status']) {
				$this->model_setting_event->enableEvent($myz_event['event_id']);
			} else {
				$this->model_setting_event->disableEvent($myz_event['event_id']);
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/module/myz_currency', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if(isset($this->session->data['success'])) {
			$data['success'] = $this->language->get('text_success');
		} else {
			$data['success'] = '';
		}

		unset($this->session->data['success']);

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		];
		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/myz_currency', 'user_token=' . $this->session->data['user_token'], true)
			];
		} else {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/myz_currency', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			];
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} else if ($this->config->get('myz_currency_status')) {
			$data['status'] = $this->config->get('myz_currency_status');
		} else {
			$data['status'] = '0';
		}

		$data['action'] = $this->url->link('extension/module/myz_currency', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/myz_currency', $data));
	}


	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/myz_currency')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('myz_currency', ['myz_currency_status' => 1]);
		$this->model_setting_setting->editSetting('module_myz_currency', ['module_myz_currency_status'=>1]);
	}

	public function uninstall()
	{
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('myz_currency');
		$this->model_setting_setting->deleteSetting('module_myz_currency');
		$this->model_setting_setting->deleteSetting('myz_currency_setting');
	}
}
