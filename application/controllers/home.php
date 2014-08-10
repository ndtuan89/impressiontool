<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Home extends CI_Controller {

	public function index() {
		$resultWhois = array();
		if (isset($_POST['ipAddress'])) {
			$ipAddress = $_POST['ipAddress'];
			$ipAddressArray = explode(PHP_EOL, trim($ipAddress));
			if ($ipAddressArray[0] == "") {
				$ipAddressArray[] = $ipAddress;
			}
			$this->load->library('phpwhois');
			$phpwhois = new Phpwhois();
			$tempResultWhois = array();
			$this->load->model('Lookup_model', '', TRUE);
			$this->load->model('Blockname_model', '', TRUE);
			$this->load->model('Blockrange_model', '', TRUE);
			$this->load->model('Blockowner_model', '', TRUE);
			$cacheLookups = $this->Lookup_model->getLookupList($ipAddressArray);
			foreach ($ipAddressArray as $ip) {
				$ip = trim($ip);
				if ($phpwhois->whois->checkValidateIp($ip)) {
					if (!isset($cacheLookups[$ip])) {
						$rawWhois = $phpwhois->whois->Getipowner($ip);
						foreach ($rawWhois as $whois) {
							$tempResultWhois['ip'] = $ip;
							$tempResultWhois['ip_block_name'] = $whois['regrinfo']['network']['name'];
							$tempResultWhois['ip_block_range'] = $whois['regrinfo']['network']['inetnum'];
							$tempResultWhois['ip_block_owner'] = $whois['regrinfo']['owner']['organization'];
							//check black list or white list from owner, name, range
							$tempResultWhois['whois_status'] = $this->Blockowner_model->getStatus($tempResultWhois['ip_block_owner']);
							if ($tempResultWhois['whois_status'] == 0) { //owner is white
								$tempResultWhois['whois_status'] = $this->Blockrange_model->getStatus($tempResultWhois['ip_block_range']);
								if ($tempResultWhois['whois_status'] == 0) { //owner is white
									$tempResultWhois['whois_status'] = $this->Blockname_model->getStatus($tempResultWhois['ip_block_name']);
								}
							}
							//insert lookup result
							$this->Lookup_model->insert_lookup($ip, $tempResultWhois['ip_block_name'], $tempResultWhois['ip_block_range'], $tempResultWhois['ip_block_owner']);
							$resultWhois[] = $tempResultWhois;
						}
					} else {
						foreach ($cacheLookups[$ip] as $lookup) {
							$tempResultWhois['ip'] = $lookup->ip;
							$tempResultWhois['ip_block_name'] = $lookup->ip_block_name;
							$tempResultWhois['ip_block_range'] = $lookup->ip_block_range;
							$tempResultWhois['ip_block_owner'] = $lookup->ip_block_owner;
							//check black list or white list from owner, name, range
							$tempResultWhois['whois_status'] = $this->Blockowner_model->getStatus($tempResultWhois['ip_block_owner']);
							if ($tempResultWhois['whois_status'] == 0) { //owner is white
								$tempResultWhois['whois_status'] = $this->Blockrange_model->getStatus($tempResultWhois['ip_block_range']);
								if ($tempResultWhois['whois_status'] == 0) { //owner is white
									$tempResultWhois['whois_status'] = $this->Blockname_model->getStatus($tempResultWhois['ip_block_name']);
								}
							}
							$resultWhois[] = $tempResultWhois;
						}
					}
				}
			}
		}
		$viewData = array('resultWhois' => $resultWhois);
		$this->load->view('home', $viewData);
	}

	public function ajax() {
		$action = $_REQUEST['action'];
		switch ($action) {
			case 'editBlockName':
				$this->load->model('Blockname_model', '', TRUE);
				$ip_block_name = $_POST['ip_block_name'];
				$status = $_POST['status'];
				$this->Blockname_model->insert_blockname($ip_block_name, $status);
				echo 1;
				break;
			case 'editBlockRange':
				$this->load->model('Blockrange_model', '', TRUE);
				$ip_block_range = $_POST['ip_block_range'];
				$status = $_POST['status'];
				$this->Blockrange_model->insert_blockrange($ip_block_range, $status);
				echo 1;
				break;
			case 'editBlockOwner':
				$this->load->model('Blockowner_model', '', TRUE);
				$ip_block_owner = $_POST['ip_block_owner'];
				$status = $_POST['status'];
				$this->Blockowner_model->insert_blockowner($ip_block_owner, $status);
				echo 1;
				break;
			default:
				echo 0;
				break;
		}
	}

}
