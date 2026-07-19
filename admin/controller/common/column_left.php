<?php
class ControllerCommonColumnLeft extends Controller {
	public function index() {
		if (isset($this->request->get['user_token']) && isset($this->session->data['user_token']) && ((string)$this->request->get['user_token'] == $this->session->data['user_token'])) {
			$this->load->language('common/column_left');

			// Create a 3 level menu array
			// Level 2 can not have children

			// Menu
			$data['menus'][] = array(
				'id'       => 'menu-dashboard',
				'icon'	   => 'fa-dashboard',
				'name'	   => $this->language->get('text_dashboard'),
				'href'     => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']),
				'children' => array()
			);

				// 商品管理 — 精简至两个核心入口
		$catalog = array();

		if ($this->user->hasPermission('access', 'catalog/product')) {
			$catalog[] = array(
				'name'     => '商品管理',
				'href'     => $this->url->link('catalog/product', 'user_token=' . $this->session->data['user_token']),
				'children' => array()
			);
		}

		if ($this->user->hasPermission('access', 'catalog/custom_tag')) {
			$catalog[] = array(
				'name'     => '自定义字段',
				'href'     => $this->url->link('catalog/custom_tag', 'user_token=' . $this->session->data['user_token']),
				'children' => array()
			);
		}

		if ($catalog) {
			$data['menus'][] = array(
				'id'       => 'menu-catalog',
				'icon'     => 'fa-tags',
				'name'     => '商品目录',
				'href'     => '',
				'children' => $catalog
			);
		}

		// 联系方式管理（含邮件管理子组）
		$contact = array();

		if ($this->user->hasPermission('access', 'catalog/contact')) {
			$contact[] = array(
				'name'     => '联系方式设置',
				'href'     => $this->url->link('catalog/contact', 'user_token=' . $this->session->data['user_token']),
				'children' => array()
			);
		}

		// 邮件管理（作为联系方式管理的子组）
		$email_mgmt = array();

		$mail_blast = array();
		if ($this->user->hasPermission('access', 'marketing/mail_schedule')) {
			$mail_blast[] = array(
				'name'     => '定时群发',
				'href'     => $this->url->link('marketing/mail_schedule', 'user_token=' . $this->session->data['user_token']),
				'children' => array()
			);
		}
		if ($this->user->hasPermission('access', 'marketing/contact')) {
			$mail_blast[] = array(
				'name'     => '单次群发',
				'href'     => $this->url->link('marketing/contact', 'user_token=' . $this->session->data['user_token']),
				'children' => array()
			);
		}
		if ($mail_blast) {
			$email_mgmt[] = array(
				'name'     => '邮件群发',
				'href'     => '',
				'children' => $mail_blast
			);
		}

		if ($this->user->hasPermission('access', 'marketing/mail_trigger')) {
			$email_mgmt[] = array(
				'name'     => '触发邮件',
				'href'     => $this->url->link('marketing/mail_trigger', 'user_token=' . $this->session->data['user_token']),
				'children' => array()
			);
		}

		if ($email_mgmt) {
			$contact[] = array(
				'name'     => '邮件管理',
				'href'     => '',
				'children' => $email_mgmt
			);
		}

		if ($contact) {
			$data['menus'][] = array(
				'id'       => 'menu-contact',
				'icon'     => 'fa-envelope',
				'name'     => '联系方式管理',
				'href'     => '',
				'children' => $contact
			);
		}

// Extension
			$marketplace = array();
			if ($this->user->hasPermission('access', 'marketplace/installer')) {
				$marketplace[] = array(
					'name'	   => $this->language->get('text_installer'),
					'href'     => $this->url->link('marketplace/installer', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'marketplace/extension')) {
				$extension = [];
				$types = glob(DIR_APPLICATION . 'controller/extension/extension/*.php', GLOB_BRACE);
				foreach ($types as $type) {
					$type = basename($type, '.php');
					$extension[] = array(
						'name'     => $this->language->get("text_extension_{$type}"),
						'href'     => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=' . $type),
						'children' => array()
					);
				}

				if ($extension) {
					$marketplace[] = array(
						'name'     => $this->language->get('text_extension'),
						'href'     =>'',
						'children' => $extension
					);
				}
			}

			if ($this->user->hasPermission('access', 'marketplace/modification')) {
				$marketplace[] = array(
					'name'	   => $this->language->get('text_modification'),
					'href'     => $this->url->link('marketplace/modification', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'marketplace/event')) {
				$marketplace[] = array(
					'name'	   => $this->language->get('text_event'),
					'href'     => $this->url->link('marketplace/event', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'marketplace/cron')) {
				$marketplace[] = array(
					'name'	   => $this->language->get('text_cron'),
					'href'     => $this->url->link('marketplace/cron', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($marketplace) {
				$data['menus'][] = array(
					'id'       => 'menu-extension',
					'icon'	   => 'fa-puzzle-piece',
					'name'	   => $this->language->get('text_extension'),
					'href'     => '',
					'children' => $marketplace
				);
			}

				// 页面设计 — 内容编辑器
		$design = array();
		if ($this->user->hasPermission('access', 'catalog/information')) {
			$design[] = array(
				'name'     => '页面编辑器',
				'href'     => $this->url->link('catalog/information', 'user_token=' . $this->session->data['user_token']),
				'children' => array()
			);
		}
		if ($design) {
			$data['menus'][] = array(
				'id'       => 'menu-design',
				'icon'     => 'fa-pencil',
				'name'     => '页面设计',
				'href'     => '',
				'children' => $design
			);
		}

// Sales
			$sale = array();

			if ($this->user->hasPermission('access', 'sale/order')) {
				$sale[] = array(
					'name'	   => $this->language->get('text_order'),
					'href'     => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'sale/return')) {
				$sale[] = array(
					'name'	   => $this->language->get('text_return'),
					'href'     => $this->url->link('sale/return', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($sale) {
				$data['menus'][] = array(
					'id'       => 'menu-sale',
					'icon'	   => 'fa-shopping-cart',
					'name'	   => $this->language->get('text_sale'),
					'href'     => '',
					'children' => $sale
				);
			}

			// Customer
			$customer = array();

			if ($this->user->hasPermission('access', 'customer/customer')) {
				$customer[] = array(
					'name'	   => $this->language->get('text_customer'),
					'href'     => $this->url->link('customer/customer', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'customer/customer_group')) {
				$customer[] = array(
					'name'	   => $this->language->get('text_customer_group'),
					'href'     => $this->url->link('customer/customer_group', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'customer/customer_approval')) {
				$customer[] = array(
					'name'	   => $this->language->get('text_customer_approval'),
					'href'     => $this->url->link('customer/customer_approval', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'customer/custom_field')) {
				$customer[] = array(
					'name'	   => $this->language->get('text_custom_field'),
					'href'     => $this->url->link('customer/custom_field', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($customer) {
				$data['menus'][] = array(
					'id'       => 'menu-customer',
					'icon'	   => 'fa-user',
					'name'	   => $this->language->get('text_customer'),
					'href'     => '',
					'children' => $customer
				);
			}

			// Marketing
			$marketing = array();

			if ($this->user->hasPermission('access', 'marketing/affiliate')) {
				$marketing[] = array(
					'name'	   => $this->language->get('text_affiliate'),
					'href'     => $this->url->link('marketing/affiliate', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'marketing/marketing')) {
				$marketing[] = array(
					'name'	   => $this->language->get('text_marketing'),
					'href'     => $this->url->link('marketing/marketing', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'marketing/coupon')) {
				$marketing[] = array(
					'name'	   => $this->language->get('text_coupon'),
					'href'     => $this->url->link('marketing/coupon', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			
			if ($marketing) {
				$data['menus'][] = array(
					'id'       => 'menu-marketing',
					'icon'	   => 'fa-share-alt',
					'name'	   => $this->language->get('text_marketing'),
					'href'     => '',
					'children' => $marketing
				);
			}

			// System
			$system = array();

			// Users
			$user = array();

			if ($this->user->hasPermission('access', 'user/user')) {
				$user[] = array(
					'name'	   => $this->language->get('text_users'),
					'href'     => $this->url->link('user/user', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'user/user_permission')) {
				$user[] = array(
					'name'	   => $this->language->get('text_user_group'),
					'href'     => $this->url->link('user/user_permission', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'user/api')) {
				$user[] = array(
					'name'	   => $this->language->get('text_api'),
					'href'     => $this->url->link('user/api', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($user) {
				$system[] = array(
					'name'	   => $this->language->get('text_users'),
					'href'     => '',
					'children' => $user
				);
			}

			// Localisation
			$localisation = array();

			if ($this->user->hasPermission('access', 'localisation/location')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_location'),
					'href'     => $this->url->link('localisation/location', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/language')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_language'),
					'href'     => $this->url->link('localisation/language', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/currency')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_currency'),
					'href'     => $this->url->link('localisation/currency', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/stock_status')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_stock_status'),
					'href'     => $this->url->link('localisation/stock_status', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/order_status')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_order_status'),
					'href'     => $this->url->link('localisation/order_status', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			// Returns
			$return = array();

			if ($this->user->hasPermission('access', 'localisation/return_status')) {
				$return[] = array(
					'name'	   => $this->language->get('text_return_status'),
					'href'     => $this->url->link('localisation/return_status', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/return_action')) {
				$return[] = array(
					'name'	   => $this->language->get('text_return_action'),
					'href'     => $this->url->link('localisation/return_action', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/return_reason')) {
				$return[] = array(
					'name'	   => $this->language->get('text_return_reason'),
					'href'     => $this->url->link('localisation/return_reason', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($return) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_return'),
					'href'     => '',
					'children' => $return
				);
			}

            if ($this->user->hasPermission('access', 'localisation/calling_code') && is_ft()) {
                $localisation[] = array(
                    'name'     => $this->language->get('text_calling_code'),
                    'href'     => $this->url->link('localisation/calling_code', 'user_token=' . $this->session->data['user_token']),
                    'children' => array()
                );
            }

			if ($this->user->hasPermission('access', 'localisation/country')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_country'),
					'href'     => $this->url->link('localisation/country', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/zone')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_zone'),
					'href'     => $this->url->link('localisation/zone', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

            if ($this->user->hasPermission('access', 'localisation/city')) {
                $localisation[] = array(
                    'name'	   => $this->language->get('text_city'),
                    'href'     => $this->url->link('localisation/city', 'user_token=' . $this->session->data['user_token']),
                    'children' => array()
                );
            }

			if ($this->user->hasPermission('access', 'localisation/geo_zone')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_geo_zone'),
					'href'     => $this->url->link('localisation/geo_zone', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			// Tax
			$tax = array();

			if ($this->user->hasPermission('access', 'localisation/tax_class')) {
				$tax[] = array(
					'name'	   => $this->language->get('text_tax_class'),
					'href'     => $this->url->link('localisation/tax_class', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/tax_rate')) {
				$tax[] = array(
					'name'	   => $this->language->get('text_tax_rate'),
					'href'     => $this->url->link('localisation/tax_rate', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($tax) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_tax'),
					'href'     => '',
					'children' => $tax
				);
			}

			if ($this->user->hasPermission('access', 'localisation/length_class')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_length_class'),
					'href'     => $this->url->link('localisation/length_class', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'localisation/weight_class')) {
				$localisation[] = array(
					'name'	   => $this->language->get('text_weight_class'),
					'href'     => $this->url->link('localisation/weight_class', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($localisation) {
				$system[] = array(
					'name'	   => $this->language->get('text_localisation'),
					'href'     => '',
					'children' => $localisation
				);
			}

			// Tools
			$maintenance = array();

			if ($this->user->hasPermission('access', 'tool/upgrade')) {
				$maintenance[] = array(
					'name'	   => $this->language->get('text_upgrade'),
					'href'     => $this->url->link('tool/upgrade', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'tool/backup')) {
				$maintenance[] = array(
					'name'	   => $this->language->get('text_backup'),
					'href'     => $this->url->link('tool/backup', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'tool/upload')) {
				$maintenance[] = array(
					'name'	   => $this->language->get('text_upload'),
					'href'     => $this->url->link('tool/upload', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'tool/log')) {
				$maintenance[] = array(
					'name'	   => $this->language->get('text_log'),
					'href'     => $this->url->link('tool/log', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($maintenance) {
				$system[] = array(
					'id'       => 'menu-maintenance',
					'icon'	   => 'fa-cog',
					'name'	   => $this->language->get('text_maintenance'),
					'href'     => '',
					'children' => $maintenance
				);
			}

			if ($system) {
				$data['menus'][] = array(
					'id'       => 'menu-system',
					'icon'	   => 'fa-cog',
					'name'	   => $this->language->get('text_system'),
					'href'     => '',
					'children' => $system
				);
			}

			$report = array();

			if ($this->user->hasPermission('access', 'report/report')) {
				$report[] = array(
					'name'	   => $this->language->get('text_reports'),
					'href'     => $this->url->link('report/report', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'report/online')) {
				$report[] = array(
					'name'	   => $this->language->get('text_online'),
					'href'     => $this->url->link('report/online', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($this->user->hasPermission('access', 'report/statistics')) {
				$report[] = array(
					'name'	   => $this->language->get('text_statistics'),
					'href'     => $this->url->link('report/statistics', 'user_token=' . $this->session->data['user_token']),
					'children' => array()
				);
			}

			if ($report) {
				$data['menus'][] = array(
					'id'       => 'menu-report',
					'icon'	   => 'fa-bar-chart-o',
					'name'	   => $this->language->get('text_reports'),
					'href'     => '',
					'children' => $report
				);
			}

			// Stats
			$this->load->model('sale/order');

			$order_total = $this->model_sale_order->getTotalOrders();

			$this->load->model('report/statistics');

			$complete_total = $this->model_report_statistics->getValue('order_complete');

			if ((float)$complete_total && $order_total) {
				$data['complete_status'] = round(($complete_total / $order_total) * 100);
			} else {
				$data['complete_status'] = 0;
			}

			$processing_total = $this->model_report_statistics->getValue('order_processing');

			if ((float)$processing_total && $order_total) {
				$data['processing_status'] = round(($processing_total / $order_total) * 100);
			} else {
				$data['processing_status'] = 0;
			}

			$other_total = $order_total - $complete_total - $processing_total;

			if ((float)$other_total && $order_total) {
				$data['other_status'] = round(($other_total / $order_total) * 100);
			} else {
				$data['other_status'] = 0;
			}

			return $this->load->view('common/column_left', $data);
		}
	}
}
