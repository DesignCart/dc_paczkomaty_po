<?php
/**
 * Controller shipping DC PaczkomatyPO
 *
 * @version 1.1
 * @author Design Cart
 */

namespace Opencart\Admin\Controller\Extension\DcPaczkomatyPO\Shipping;

class DcPaczkomatyPO extends \Opencart\System\Engine\Controller {
	private array $error = [];

	public function index(): void {
		$this->load->language('extension/dc_paczkomaty_po/shipping/dc_paczkomaty_po');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('localisation/geo_zone');
		$this->load->model('localisation/tax_class');

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_dc_paczkomaty_po', $this->request->post);
			$this->session->data['success'] = $this->language->get('tx_success');
			$this->response->redirect(
				$this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping')
			);
		}

		$data['heading_title']        = $this->language->get('heading_title');
		$data['text_edit']            = $this->language->get('text_edit');
		$data['button_save']          = $this->language->get('button_save');
		$data['button_cancel']        = $this->language->get('button_cancel');

		$data['action'] = $this->url->link('extension/dc_paczkomaty_po/shipping/dc_paczkomaty_po', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		// Config values
		$data['shipping_dc_paczkomaty_po_cost']        = $this->config->get('shipping_dc_paczkomaty_po_cost') ?? '12.99';
		$data['shipping_dc_paczkomaty_po_tax_class_id'] = $this->config->get('shipping_dc_paczkomaty_po_tax_class_id') ?? 0;
		$data['shipping_dc_paczkomaty_po_geo_zone_id'] = $this->config->get('shipping_dc_paczkomaty_po_geo_zone_id') ?? 0;
		$data['shipping_dc_paczkomaty_po_status']      = $this->config->get('shipping_dc_paczkomaty_po_status') ?? 0;
		$data['shipping_dc_paczkomaty_po_sort_order']  = $this->config->get('shipping_dc_paczkomaty_po_sort_order') ?? 0;

		// Lists
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
		$data['geo_zones']   = $this->model_localisation_geo_zone->getGeoZones();

		// Common parts
		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/dc_paczkomaty_po/shipping/dc_paczkomaty_po', $data));
	}

	private function validate(): bool {
		if (!$this->user->hasPermission('modify', 'extension/dc_paczkomaty_po/shipping/dc_paczkomaty_po')) {
			$this->error['warning'] = $this->language->get('tx_error_permission');
		}
		return !$this->error;
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'dc_paczkomaty_po_checkout_js',
			'description' => 'Dodaje JS paczkomatów tylko na stronie checkout',
			'trigger'     => 'catalog/controller/checkout/checkout/before',
			'action'      => 'extension/dc_paczkomaty_po/event/script.addCheckoutScript',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		// Clean up config if needed
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('dc_paczkomaty_po');

		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('dc_paczkomaty_po_js');
	}
}
