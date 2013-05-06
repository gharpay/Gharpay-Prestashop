<?php

if (!defined('_PS_VERSION_'))
	exit;

class Gharpay extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'gharpay';
		$this->tab = 'payments_gateways';
		$this->version = '0.1';
		$this->author = 'Dot Angle';
		$this->module_key = "0e512850a79a414bc73bb74f49feaa6b";
		
		parent::__construct();

		$this->displayName = $this->l('Gharpay');
		$this->description = $this->l('Payments collected by Gharpay.');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
	}

	public function install()
	{
		if (
			!$this->createTable() ||
			!parent::install() || 
			!$this->registerHook('payment') || 
			!$this->registerHook('paymentReturn') ||
			!$this->registerHook('adminOrder') ||
			!$this->createOrderStates()
		)
		{
			return false;
		}

		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall())
			return false;
			
		$this->dropTable();
		return true;
	}
	
	private function createTable()
	{
		Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'gharpay_orders (
		   id INT UNSIGNED AUTO_INCREMENT NOT NULL,
		   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		   id_cart INT NOT NULL,
		   id_order INT NOT NULL,
		   gharpay_order_id VARCHAR(60) NOT NULL,
		   order_total DOUBLE NOT NULL,
		   surcharge DOUBLE NOT NULL,
		  PRIMARY KEY (id));');
		  
		Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'gharpay_order_states (
		   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		   id_order INT NOT NULL,
		   state varchar(255));');
		  
		return true;
	}
	
	private function dropTable()
	{
		Db::getInstance()->Execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'gharpay_orders');
		Db::getInstance()->Execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'gharpay_order_states');
		return true;
	}
	
	private function createOrderStates()
	{
		$orderState = new OrderState();
		$orderState->name = array(1=>'Awaiting Gharpay Payment');
		$orderState->send_email = 0;
		$orderState->module_name = $this->name;
		$orderState->invoice = 0;
		$orderState->color = 'Blue';
		$orderState->logable = 1;
		$orderState->unremovable = 1;
		$orderState->delivery = 0;
		$orderState->hidden = 0;
		$orderState->deleted = 0;
		if(!$orderState->add())
		{
			return false;
		}
		Configuration::updateValue(strtoupper('gharpay_payment_status'), $orderState->id);
		$orderState = NULL;
		
		return true;
	}
	
	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue(strtoupper('gharpay_uri'), Tools::getValue('gharpay_uri'));
			Configuration::updateValue(strtoupper('gharpay_username'), Tools::getValue('gharpay_username'));
			Configuration::updateValue(strtoupper('gharpay_password'), Tools::getValue('gharpay_password'));
			Configuration::updateValue(strtoupper('gharpay_desc'), Tools::getValue('gharpay_desc'));
			Configuration::updateValue(strtoupper('gharpay_surcharge'), Tools::getValue('gharpay_surcharge'));
		}
		$this->_html .= '<div class="conf confirm"> '.$this->l('Settings updated').'</div>';
	}

	private function _displayBankWire()
	{
		$this->_html .= '<img src="../modules/gharpay/logo_org.png" style="float:left; margin-right:15px;"><b>'.$this->l('This module allows you to collects payments by gharpay.').'</b><br /><br /><br />';
	}

	private function _displayForm()
	{
		$this->_html .=
		'<form action="'.Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']).'" method="post">
			<fieldset>
			<legend><img src="../img/admin/submenu-configuration.gif" />'.$this->l('Configuration').'</legend>
				<table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
				<tr><td width="130" style="height: 35px;" valign="top">'.$this->l('Push Notification').'</td>
				<td>
					<span style="text-decoration:underline; font-weight:bold">'.Tools::getShopDomainSsl(true).__PS_BASE_URI__.'modules/gharpay/pushnotification.php?order_id=xx-xx-xxxxxx-xxx&time=xxxx-xx-xx+xx:xx:xx</span>
					<p style="font-size:11px">'.$this->l('Communicate to gharpay to hit this link for push notifications').'</p>
					</td></tr>
					<tr><td width="130" style="height: 35px;" valign="top">'.$this->l('Gharpay URI').'</td><td><input type="text" name="gharpay_uri" value="'.htmlentities(Tools::getValue('gharpay_uri', Configuration::get(strtoupper('gharpay_uri'))), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" />
					<p style="font-size:11px">'.$this->l('Enter Gharpay Service URI, eg. services.gharpay.in. Note: Do not add http://').'</p>
					</td></tr>
					<tr><td colspan="2" height="10"></td></tr>
					<tr><td width="130" style="height: 35px;" valign="top">'.$this->l('Username').'</td><td><input type="text" name="gharpay_username" value="'.htmlentities(Tools::getValue('gharpay_username', Configuration::get(strtoupper('gharpay_username'))), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" />
					<p style="font-size:11px">'.$this->l('Enter your Gharpay Username').'</p>
					</td></tr>
					<tr><td colspan="2" height="10"></td></tr>
					<tr><td width="130" style="height: 35px;" valign="top">'.$this->l('Password').'</td><td><input type="password" name="gharpay_password" value="'.htmlentities(Tools::getValue('gharpay_password', Configuration::get(strtoupper('gharpay_password'))), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" />
					<p style="font-size:11px">'.$this->l('Enter your Gharpay Password').'</p>
					</td></tr>
					<tr><td colspan="2" height="10"></td></tr>
					<tr><td width="130" style="height: 35px;" valign="top">'.$this->l('Description').'</td><td><input type="text" name="gharpay_desc" value="'.htmlentities(Tools::getValue('gharpay_desc', Configuration::get(strtoupper('gharpay_desc'))), ENT_COMPAT, 'UTF-8').'" style="width: 300px;" />
					<p style="font-size:11px">'.$this->l('Description for this payment method to front end display').'</p>
					</td></tr>
					<tr><td colspan="2" height="10"></td></tr>
					<tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="'.$this->l('Update settings').'" type="submit" /></td></tr>
				</table>
			</fieldset>
		</form>';
	}

	public function getContent()
	{
		$this->_html = '<h2>'.$this->displayName.'</h2>';

		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postProcess();
		}
		else
			$this->_html .= '<br />';

		$this->_displayBankWire();
		$this->_displayForm();

		return $this->_html;
	}

	public function hookPayment($params)
	{
		global $smarty,$link;

		if (!$this->active)
			return;
		
		$address = new Address(intval($params['cart']->id_address_invoice));
		
		if(!$this->checkCountry($address))
			return;

		$pincode_valid = 0;
		if($this->checkPincode($address))
			$pincode_valid = 1;
		
		$surcharge = '';
		$surcharge_amount = (int)(Configuration::get(strtoupper('gharpay_surcharge')));
		if($surcharge_amount > 0)
		{
			$surcharge = $surcharge_amount;
		}
		$smarty->assign(array(
			'title' => $this->displayName,
			'description' => Configuration::get(strtoupper('gharpay_desc')),
			'pincode_valid' => $pincode_valid,
			'payment_url' => _MODULE_DIR_.$this->name.'/validation.php'
		));
		
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;

		return $this->display(__FILE__, 'paymentreturn.tpl');
	}
	
	public function hookAdminOrder($params)
	{
		global $smarty;
		if (!$this->active)
			return ;
			
		$id_order = $params['id_order'];
		
		$gharpay_order_id = Db::getInstance()->getValue('SELECT gharpay_order_id FROM '._DB_PREFIX_.'gharpay_orders WHERE id_order='.$id_order);
		$gharpayOrderStatus = Db::getInstance()->getRow('SELECT * from '._DB_PREFIX_.'gharpay_order_states WHERE id_order='.$id_order);
		
		$smarty->assign(array(
			'order_status' => $gharpayOrderStatus['state'],
			'timestamp' => $gharpayOrderStatus['created_at'],
			'gharpay_order_id' => $gharpay_order_id,
		));
		
		return $this->display(__FILE__, 'adminorder.tpl');
	}
	
	function checkCountry($address)
	{
		$country = new Country($address->id_country);
		if($country->iso_code == 'IN')
			return true;
		else 
			return false;
	}
	
	function checkPincode($address)
	{
		include_once 'lib/GharpayAPI.php';
		$postcode = $address->postcode;
		$uri = Configuration::get(strtoupper('gharpay_uri'));
		$username = Configuration::get(strtoupper('gharpay_username'));
		$password = Configuration::get(strtoupper('gharpay_password'));
		
		$gpAPI = new GharpayAPI();
		$gpAPI->setUsername($username);
		$gpAPI->setPassword($password);
		$gpAPI->setURL($uri);

		try 
		{
			return $gpAPI->isPincodePresent($postcode);
		}
		catch (Exception $e)
		{
			return false;
		}
	}
	
	public function saveGharpayOrder($id_cart,$gharpay_order_id,$orderAmount,$surcharge)
	{
		$id_order = $this->currentOrder;
		Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'gharpay_orders(id_cart, id_order, gharpay_order_id,order_total,surcharge) VALUES('.$id_cart.','.$id_order.',"'.$gharpay_order_id.'","'.$orderAmount.'","'.$surcharge.'")');
	}

	public function addGharpayOrderStatus($id_order,$status)
	{
		$id_order = $this->currentOrder;
		Db::getInstance()->Execute('INSERT INTO '._DB_PREFIX_.'gharpay_order_states(id_order, state) VALUES('.$id_order.',"'.$status.'")');
	}
	
	public function updateGharpayOrderStatus($id_order,$status,$timestamp)
	{
		Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'gharpay_order_states SET state="'.$status.'", created_at="'.$timestamp.'" WHERE id_order='.$id_order);
	}
	
	public function getOrderIdByGharpayOrderId($gharpay_order_id)
	{
		return Db::getInstance()->getValue('SELECT id_order from '._DB_PREFIX_.'gharpay_orders WHERE gharpay_order_id="'.$gharpay_order_id.'"');
	}
	
	public function updateOrderStatus($gharpay_order_id,$timestamp)
	{
		include_once 'lib/GharpayAPI.php';
		$uri = Configuration::get(strtoupper('gharpay_uri'));
		$username = Configuration::get(strtoupper('gharpay_username'));
		$password = Configuration::get(strtoupper('gharpay_password'));
		
		$gpAPI = new GharpayAPI();
		$gpAPI->setUsername($username);
		$gpAPI->setPassword($password);
		$gpAPI->setURL($uri);

		try 
		{
			$result = $gpAPI->viewOrderStatus($gharpay_order_id);
			if(!empty($result['status']))
			{
				$id_order = $this->getOrderIdByGharpayOrderId($gharpay_order_id);
				$this->updateGharpayOrderStatus($id_order,$result['status'],$timestamp);
			}
			echo 'Updated';
		}
		catch (Exception $e)
		{
			echo $e->getMessage();
		}
	}
}
