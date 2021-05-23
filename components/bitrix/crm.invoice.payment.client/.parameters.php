<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!Bitrix\Main\Loader::includeModule('crm'))
	return false;
if(!Bitrix\Main\Loader::includeModule('sale'))
	return false;

Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$paySystemList = array();
$paySystemByUser = Bitrix\Sale\PaySystem\Manager::getHandlerList();

foreach($paySystemByUser as $user)
{
	$paySystemList = array_merge($paySystemList, $user);
}
$innerId = Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId();
unset($paySystemList[$innerId]);
	
$arComponentParameters = Array(
	'PARAMETERS' => array(		
		'EXCLUDED_ACTION_LIST' => array(
			'PARENT' => 'BASE',
			'NAME' => Bitrix\Main\Localization\Loc::getMessage('CIPC_EXCLUDED_ACTION_LIST'),
			'TYPE' => 'LIST',
			'VALUES' => $paySystemList,
			"DEFAULT" => array('bill', 'billde', 'billen', 'billla', 'billua', 'billkz', 'billby', 'billbr', 'billfr', 'invoicedocument'),
			"MULTIPLE"=>"Y",
			"SIZE" => 7,
			"COLS"=>25,
		),
	)	
);
?>