<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule('crm'))
	return;

// desktop on CRM index page
//$sOptions = 'a:1:{s:7:"GADGETS";a:7:{s:19:"CRM_LEAD_LIST@27424";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:9:"STATUS_ID";s:3:"NEW";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:10:"LEAD_COUNT";s:1:"5";}}s:18:"CRM_DEAL_LIST@9562";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:8:"STAGE_ID";s:3:"WON";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_MODIFY";s:7:"SORT_BY";s:4:"DESC";s:10:"DEAL_COUNT";s:1:"5";}}s:19:"CRM_LEAD_LIST@12470";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:9:"STATUS_ID";s:0:"";s:7:"ONLY_MY";s:1:"Y";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:10:"LEAD_COUNT";s:2:"10";}}s:19:"CRM_EVENT_LIST@9504";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:3:{s:9:"TITLE_STD";s:1:" ";s:15:"EVENT_TYPE_LIST";s:0:"";s:11:"EVENT_COUNT";s:2:"10";}}s:15:"desktop-actions";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:21:"CRM_CONTACT_LIST@2435";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:7:"TYPE_ID";s:0:"";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:13:"CONTACT_COUNT";s:1:"5";}}s:21:"CRM_COMPANY_LIST@8538";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:7:"TYPE_ID";s:0:"";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:13:"COMPANY_COUNT";s:1:"5";}}}}';
$sOptions = 'a:1:{s:7:"GADGETS";a:7:{s:19:"CRM_LEAD_LIST@27424";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:9:"STATUS_ID";s:3:"NEW";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:10:"LEAD_COUNT";s:1:"5";}}s:19:"CRM_LEAD_LIST@12470";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:9:"STATUS_ID";s:0:"";s:7:"ONLY_MY";s:1:"Y";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:10:"LEAD_COUNT";s:2:"10";}}s:18:"CRM_DEAL_LIST@9562";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:8:"STAGE_ID";s:3:"WON";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_MODIFY";s:7:"SORT_BY";s:4:"DESC";s:10:"DEAL_COUNT";s:1:"5";}}s:19:"CRM_EVENT_LIST@9504";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:3:{s:9:"TITLE_STD";s:1:" ";s:15:"EVENT_TYPE_LIST";s:0:"";s:11:"EVENT_COUNT";s:2:"10";}}s:15:"desktop-actions";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:21:"CRM_CONTACT_LIST@2435";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:7:"TYPE_ID";s:0:"";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:13:"CONTACT_COUNT";s:1:"5";}}s:21:"CRM_COMPANY_LIST@8538";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:7:"TYPE_ID";s:0:"";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:13:"COMPANY_COUNT";s:1:"5";}}}}';
$arOptions = unserialize($sOptions);
$arOptions['GADGETS']['CRM_LEAD_LIST@27424']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_NEW_LEAD_TITLE');
$arOptions['GADGETS']['CRM_DEAL_LIST@9562']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_CLOSED_DEAL_TITLE');
$arOptions['GADGETS']['CRM_LEAD_LIST@12470']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_MY_LEAD_TITLE');
$arOptions['GADGETS']['CRM_EVENT_LIST@9504']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_LAST_EVENT_TITLE');
$arOptions['GADGETS']['CRM_CONTACT_LIST@2435']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_NEW_CONTACT_TITLE');
$arOptions['GADGETS']['CRM_COMPANY_LIST@8538']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_NEW_COMPANY_TITLE');
WizardServices::SetUserOption('intranet', '~gadgets_crm', $arOptions, $common = true);

$CCrmRole = new CCrmRole();
$arRoles = array(
	'adm' => array(
		'NAME' => GetMessage('CRM_ROLE_ADMIN'),
		'RELATION' => array(
			'LEAD' => array(
				'READ' => array('-' => 'X'),
				'ADD' => array('-' => 'X'),
				'WRITE' => array('-' => 'X'),
				'DELETE' => array('-' => 'X')
			),
			'DEAL' => array(
				'READ' => array('-' => 'X'),
				'ADD' => array('-' => 'X'),
				'WRITE' => array('-' => 'X'),
				'DELETE' => array('-' => 'X')
			),
			'CONTACT' => array(
				'READ' => array('-' => 'X'),
				'ADD' => array('-' => 'X'),
				'WRITE' => array('-' => 'X'),
				'DELETE' => array('-' => 'X')
			),
			'COMPANY' => array(
				'READ' => array('-' => 'X'),
				'ADD' => array('-' => 'X'),
				'WRITE' => array('-' => 'X'),
				'DELETE' => array('-' => 'X'),
			),
			'INVOICE' => array(
				'READ' => array('-' => 'X'),
				'ADD' => array('-' => 'X'),
				'WRITE' => array('-' => 'X'),
				'DELETE' => array('-' => 'X')
			),
			'QUOTE' => array(
				'READ' => array('-' => 'X'),
				'ADD' => array('-' => 'X'),
				'WRITE' => array('-' => 'X'),
				'DELETE' => array('-' => 'X')
			),
			'WEBFORM' => array(
				'READ' => array('-' => 'X'),
				'WRITE' => array('-' => 'X')
			),
			'BUTTON' => array(
				'READ' => array('-' => 'X'),
				'WRITE' => array('-' => 'X')
			),
			'EXCLUSION' => array(
				'READ' => array('-' => 'X'),
				'WRITE' => array('-' => 'X')
			),
			'CONFIG' => array(
				'WRITE' => array('-' => 'X'),
			)
		)
	),
	'man' => array(
		'NAME' => GetMessage('CRM_ROLE_MAN'),
		'RELATION' => array(
			'LEAD' => array(
				'READ' => array('-' => 'A'),
				'ADD' => array('-' => 'A'),
				'WRITE' => array('-' => 'A'),
				'DELETE' => array('-' => 'A')
			),
			'DEAL' => array(
				'READ' => array('-' => 'A'),
				'ADD' => array('-' => 'A'),
				'WRITE' => array('-' => 'A'),
				'DELETE' => array('-' => 'A')
			),
			'CONTACT' => array(
				'READ' => array('-' => 'A'),
				'ADD' => array('-' => 'A'),
				'WRITE' => array('-' => 'A'),
				'DELETE' => array('-' => 'A')
			),
			'COMPANY' => array(
				'READ' => array('-' => 'X'),
				'ADD' => array('-' => 'X'),
				'WRITE' => array('-' => 'X'),
				'DELETE' => array('-' => 'X'),
			),
			'INVOICE' => array(
				'READ' => array('-' => 'A'),
				'ADD' => array('-' => 'A'),
				'WRITE' => array('-' => 'A'),
				'DELETE' => array('-' => 'A')
			),
			'QUOTE' => array(
				'READ' => array('-' => 'A'),
				'ADD' => array('-' => 'A'),
				'WRITE' => array('-' => 'A'),
				'DELETE' => array('-' => 'A')
			),
			'WEBFORM' => array(
				'READ' => array('-' => 'X'),
				'WRITE' => array('-' => 'X')
			),
			'BUTTON' => array(
				'READ' => array('-' => 'X'),
				'WRITE' => array('-' => 'X')
			),
			'EXCLUSION' => array(
				'READ' => array('-' => 'X'),
				'WRITE' => array('-' => 'X')
			)
		)
	)
);

$iRoleIDAdm = $iRoleIDMan = 0;
$obRole = CCrmRole::GetList(array(), array());
while ($arRole = $obRole->Fetch())
{
	if ($arRole['NAME'] == GetMessage('CRM_ROLE_ADMIN'))
		$iRoleIDAdm = $arRole['ID'];
	else if ($arRole['NAME'] == GetMessage('CRM_ROLE_MAN'))
		$iRoleIDMan = $arRole['ID'];
}

$arRel = array();
if ($iRoleIDAdm <= 0)
	$iRoleIDAdm = $CCrmRole->Add($arRoles['adm']);

if ($iRoleIDMan <= 0)
	$iRoleIDMan = $CCrmRole->Add($arRoles['man']);

$arRel['G1'] = array($iRoleIDAdm);
if (WIZARD_DIRECTION_GROUP > 0)
	$arRel['G'.WIZARD_DIRECTION_GROUP] = array($iRoleIDAdm);
if (WIZARD_EMPLOYEES_GROUP > 0)
	$arRel['G'.WIZARD_EMPLOYEES_GROUP] = array($iRoleIDAdm);

$CCrmRole->SetRelation($arRel);

if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	\CBitrix24::setCrmRecaptchaOptions();
}

//Automation presets
\Bitrix\Crm\Automation\Demo\Wizard::installOnNewPortal();

//crm without leads by default
\Bitrix\Crm\Settings\LeadSettings::enableLead(false);

if (IsModuleInstalled('extranet'))
{
	\Bitrix\Main\Config\Option::set("crm", "crm_shop_enabled", "Y");

	if (\Bitrix\Main\Loader::includeModule('intranet'))
	{
		\CIntranetUtils::clearMenuCache();
	}
}