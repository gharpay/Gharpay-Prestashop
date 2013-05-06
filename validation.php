<?php
include_once(dirname(__FILE__).'/../../config/config.inc.php');
include_once(dirname(__FILE__).'/../../init.php');
include_once(dirname(__FILE__).'/../../header.php');
include_once(dirname(__FILE__).'/gharpay.php');
include_once(dirname(__FILE__).'/lib/GharpayAPI.php');

$id_customer = $cart->id_customer;
$id_address_invoice = $cart->id_address_invoice;

$customer = new Customer($id_customer);
$invoiceAddress = new Address($id_address_invoice);

$uri = Configuration::get(strtoupper('gharpay_uri'));
$username = Configuration::get(strtoupper('gharpay_username'));
$password = Configuration::get(strtoupper('gharpay_password'));

$fullAddress = $invoiceAddress->address1;
if(!empty($invoiceAddress->address2))
{
	$fullAddress .=','.$invoiceAddress->address2;
}
$fullAddress .=','.$invoiceAddress->city;
if(!empty($invoiceAddress->id_state))
{
	$state = new State($invoiceAddress->id_state);
	$fullAddress .=','.$state->iso_code;
}
$country = new Country($invoiceAddress->id_country);
$fullAddress .=','.$country->name;

$contactNo = $invoiceAddress->phone_mobile;
if(empty($contactNo))
{
	$contactNo = $invoiceAddress->phone;
}
	
$customerDetails = array(
		"address"=>$fullAddress,
		"contactNo"=>$contactNo,
		"email"=>$customer->email,
		"firstName"=>$customer->firstname,
		"lastName"=>$customer->lastname
);

$productDetails = array();
$i=0;
foreach ($cart->getProducts() as $product) 
{

	$productDetails[$i] = array(
		"productID"  => $product['id_product'],
		"productQuantity"=> $product['cart_quantity'],
		"unitCost" => $product['price'],
		"productDescription"=> $product['name'],
	);			
	$i++;
}

//Tax Amount
$orderAmountWithTax = (float)Tools::ps_round((float)$cart->getOrderTotal(true, Cart::BOTH), 2);
$orderAmountWithoutTax = (float)Tools::ps_round((float)$cart->getOrderTotal(false, Cart::BOTH), 2);
$orderTax = $orderAmountWithTax - $orderAmountWithoutTax;
if($orderTax > 0)
{
	$productDetails[$i] = array(
			"productID"  => '-',
			"productQuantity"=> '1',
			"unitCost" => $orderTax,
			"productDescription"=> 'Tax',
	);	
	$i++;
}
//Shipping cost	
$orderShippingAmountWithoutTax = (float)Tools::ps_round((float)$cart->getOrderTotal(false, Cart::ONLY_SHIPPING), 2);
if($orderTax > 0)
{
	$productDetails[$i] = array(
			"productID"  => '-',
			"productQuantity"=> '1',
			"unitCost" => $orderShippingAmountWithoutTax,
			"productDescription"=> 'Shipping',
	);		
}

$paymentMode = '';
if(isset($_REQUEST['payment_option']) && !empty($_REQUEST['payment_option']))
{
	$paymentMode = $_REQUEST['payment_option'];
}

$orderAmount = (float)Tools::ps_round((float)$cart->getOrderTotal(true, Cart::BOTH), 2);

$time = mktime(date('h'),date('i'),date('s'),date('m'),date('d')+1,date('Y'));
$orderDetails = array(
	"pincode"=>$invoiceAddress->postcode,
	"clientOrderID"=>$cart->id,
	"deliveryDate"=>date('d-m-Y',$time),
	"orderAmount"=>$orderAmount,
	'paymentMode'=>$paymentMode
);

$id_cart = $cart->id;

$errors = '';
$result = false;
$gharpay = new Gharpay();

$gpAPI = new GharpayAPI();
$gpAPI->setUsername($username);
$gpAPI->setPassword($password);
$gpAPI->setURL($uri);

try 
{
	$result = $gpAPI->createOrder($customerDetails, $orderDetails,$productDetails);
	
	$clientId=$result['clientOrderId'];
	if($clientId==$cart->id)
	{
		$gharpayId = $result['gharpayOrderId'];
		if($gharpay->validateOrder($cart->id, Configuration::get(strtoupper('gharpay_payment_status')), $orderAmount, $gharpay->displayName))
		{
			$id_order = $gharpay->currentOrder;
			$gharpay->saveGharpayOrder($clientId,$gharpayId,$orderAmount,$surcharge);
			$gharpay->addGharpayOrderStatus($id_order,'Pending');
			
			if (_PS_VERSION_ >= '1.5')
			{
				Tools::redirect('index.php?controller=order-confirmation&id_cart='.$clientId.'&id_module='.$gharpay->id.'&id_order='.$id_order.'&key='.$customer->secure_key);
			}
			else
			{
				Tools::redirect('order-confirmation.php?id_cart='.$clientId.'&id_module='.$gharpay->id.'&id_order='.$id_order.'&key='.$customer->secure_key);
			}
		}
		else
		{
			$error = $gharpay->l('Order creation failed.');
	    	throw new Exception($error); 
		}
	}
	else
	{
		$error = $gharpay->l('Cart id passed to Gharpay and current cart id does not match.');
	    throw new Exception($error); 
	}
}
catch (Exception $e)
{
	$smarty->assign('error',$e->getMessage());
	$smarty->assign('id_order',$gharpay->currentOrder);
	$smarty->assign('orderPage',__PS_BASE_URI__.'order.php');
	$smarty->assign('historyPage',__PS_BASE_URI__.'history.php');
	$smarty->assign('isGuest',$customer->is_guest);
	echo $gharpay->display('gharpay', 'paymentfailed.tpl');
	include_once(dirname(__FILE__).'/../../footer.php');
}
?>