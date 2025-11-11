<?php
    namespace Opencart\Catalog\Model\Extension\DcPaczkomatyPo\Shipping;

    class DcPaczkomatyPo extends \Opencart\System\Engine\Model {

        // Helper: pobiera config z preferencją dla kluczy z "dc_", ale działa też bez.
        private function cfg(string $suffix, $default = null) {
            $keys = [
                'shipping_dc_paczkomaty_po_' . $suffix,  // np. shipping_dc_paczkomaty_po_status
                'shipping_paczkomaty_po_' . $suffix,     // np. shipping_paczkomaty_po_status
            ];
            foreach ($keys as $key) {
                $val = $this->config->get($key);
                if ($val !== null && $val !== '') return $val;
            }
            return $default;
        }

        public function getQuote(array $address): array {
            $this->load->language('extension/dc_paczkomaty_po/shipping/dc_paczkomaty_po');

            // Włącznik
            if (!$this->cfg('status', 0)) {
                return [];
            }

            // Geo Zone
            $geo_zone_id = (int)$this->cfg('geo_zone_id', 0);
            if ($geo_zone_id) {
                $q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone`
                    WHERE `geo_zone_id` = '" . $geo_zone_id . "'
                    AND `country_id` = '" . (int)$address['country_id'] . "'
                    AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");
                if (!$q->num_rows) {
                    return [];
                }
            }

            $cost         = (float)$this->cfg('cost', 0.0);
            $tax_class_id = (int)$this->cfg('tax_class_id', 0);
            $sort_order   = (int)$this->cfg('sort_order', 0);

            $quote = [
                'code'         => 'dc_paczkomaty_po.dc_paczkomaty_po',
                'name'        => $this->language->get('tx_text_title'),
                'cost'         => $cost,
                'tax_class_id' => $tax_class_id,
                'text'         => $this->currency->format(
                    $this->tax->calculate($cost, $tax_class_id, (bool)$this->config->get('config_tax')),
                    $this->session->data['currency']
                )
            ];

            return [
                'code'       => 'dc_paczkomaty_po',
                'name'      => $this->language->get('tx_text_title'),
                'quote'      => ['dc_paczkomaty_po' => $quote],
                'sort_order' => $sort_order,
                'error'      => false
            ];
        }
    }
