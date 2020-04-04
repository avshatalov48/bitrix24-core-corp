<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$weekMap = array(0,1,2,3,4,5,6);
global $USER;

$arResult['TEMPLATE_DATA']['WEEKDAY_MAP'] = $weekMap;

$mailList = array();
if (is_array($arParams['DATA']['CLIENT_SECONDARY_ENTITY_IDS']) && !empty($arParams['DATA']['CLIENT_SECONDARY_ENTITY_IDS']))
{
	$clientData = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array(
			'ENTITY_ID' => 'CONTACT',
			'ELEMENT_ID' => $arParams['DATA']['CLIENT_SECONDARY_ENTITY_IDS'],
			'TYPE_ID' => 'EMAIL'
		)
	);
	while ($client = $clientData->Fetch())
	{
		$clientMail = array(
			'value' => $client['ID'],
			'text' => $client['VALUE']
		);
		if ($arParams['DATA']['RECURRING_EMAIL_ID'] == $client['ID'])
		{
			array_unshift($mailList, $clientMail);
		}
		else
		{
			$mailList[] = $clientMail;
		}
	}
}

if ((int)$arParams['DATA']['CLIENT_PRIMARY_ENTITY_ID'] > 0)
{
	$companyData = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array(
			'ENTITY_ID' => 'COMPANY',
			'ELEMENT_ID' => (int)$arParams['DATA']['CLIENT_PRIMARY_ENTITY_ID'],
			'TYPE_ID' => 'EMAIL'
		)
	);
	while ($company = $companyData->Fetch())
	{
		$companyMail = array(
			'value' => $company['ID'],
			'text' => $company['VALUE']
		);

		if ($arParams['DATA']['RECURRING_EMAIL_ID'] == $company['ID'])
		{
			array_unshift($mailList, $companyMail);
		}
		else
		{
			$mailList[] = $companyMail;
		}
	}
}

$arResult['EMAIL_LIST'] = $mailList;

$mailTemplateData = \CCrmMailTemplate::GetList(
	array(),
	array(
		"ENTITY_TYPE_ID" => \CCrmOwnerType::Invoice,
		array(
			"LOGIC" => "OR",
			array('=OWNER_ID' => $USER->GetID(), '=SCOPE' => 1),
			array('=SCOPE' => 2),
		)
	)
);
	
$arResult['EMAIL_TEMPLATES'] = array();
$arResult['EMAIL_TEMPLATE_LAST'] = \CCrmMailTemplate::GetLastUsedTemplateID(\CCrmOwnerType::Invoice);
while ($template = $mailTemplateData->Fetch())
{
	$arResult['EMAIL_TEMPLATES'][$template['ID']] = $template['TITLE'];
}

$arResult['PATH_TO_EMAIL_TEMPLATE_ADD'] = rtrim(SITE_DIR, '/').'/crm/configs/mailtemplate/add/';