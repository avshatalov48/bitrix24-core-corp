<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if(!CModule::IncludeModule('crm'))
	return false;

$arGadgetParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arGadgetParams['PATH_TO_LEAD_SHOW'], '/crm/lead/show/#lead_id#/');
$arGadgetParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arGadgetParams['PATH_TO_DEAL_SHOW'], '/crm/deal/show/#deal_id#/');
$arGadgetParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arGadgetParams['PATH_TO_CONTACT_SHOW'], '/crm/contact/show/#contact_id#/');	
$arGadgetParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arGadgetParams['PATH_TO_COMPANY_SHOW'], '/crm/company/show/#company_id#/');
$arGadgetParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arGadgetParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');	
	
if (strlen($arGadgetParams['EVENT_TYPE_LIST']) <= 0)
	$arGadgetParams['EVENT_TYPE_LIST'] = '';
	
if (!is_array($arGadgetParams) || !array_key_exists('EVENT_COUNT', $arGadgetParams) || $arGadgetParams['EVENT_COUNT'] <= 0)
	$arGadgetParams['EVENT_COUNT'] = 5;
	
	


/*if ($bCrm)
{*/
	$APPLICATION->IncludeComponent(
		'bitrix:crm.event.view', 
		'gadget', 
		array(
			'ENTITY_TYPE' => $arGadgetParams['EVENT_TYPE_LIST'],
			'EVENT_COUNT' => $arGadgetParams['EVENT_COUNT'],
			'PATH_TO_LEAD_SHOW' 	=> $arGadgetParams['PATH_TO_LEAD_SHOW'],
			'PATH_TO_DEAL_SHOW'		=> $arGadgetParams['PATH_TO_DEAL_SHOW'],
			'PATH_TO_CONTACT_SHOW'  => $arGadgetParams['PATH_TO_CONTACT_SHOW'],
			'PATH_TO_COMPANY_SHOW'  => $arGadgetParams['PATH_TO_COMPANY_SHOW'],
			'PATH_TO_USER_PROFILE'  => $arGadgetParams['PATH_TO_USER_PROFILE'],
			'EVENT_ENTITY_LINK' => 'Y',
			'GADGET' => 'Y',
		), 
		false,
		array('HIDE_ICONS' => 'Y')							
	);
/*}
else
	echo GetMessage('GD_CRM_FEATURE_INACTIVE');*/
?>