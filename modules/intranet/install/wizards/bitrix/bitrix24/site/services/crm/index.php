<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm'))
{
	return;
}

// desktop on CRM index page
//$sOptions = 'a:1:{s:7:"GADGETS";a:7:{s:19:"CRM_LEAD_LIST@27424";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:9:"STATUS_ID";s:3:"NEW";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:10:"LEAD_COUNT";s:1:"5";}}s:18:"CRM_DEAL_LIST@9562";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:8:"STAGE_ID";s:3:"WON";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_MODIFY";s:7:"SORT_BY";s:4:"DESC";s:10:"DEAL_COUNT";s:1:"5";}}s:19:"CRM_LEAD_LIST@12470";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:9:"STATUS_ID";s:0:"";s:7:"ONLY_MY";s:1:"Y";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:10:"LEAD_COUNT";s:2:"10";}}s:19:"CRM_EVENT_LIST@9504";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:3:{s:9:"TITLE_STD";s:1:" ";s:15:"EVENT_TYPE_LIST";s:0:"";s:11:"EVENT_COUNT";s:2:"10";}}s:15:"desktop-actions";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:21:"CRM_CONTACT_LIST@2435";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:7:"TYPE_ID";s:0:"";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:13:"CONTACT_COUNT";s:1:"5";}}s:21:"CRM_COMPANY_LIST@8538";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:7:"TYPE_ID";s:0:"";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:13:"COMPANY_COUNT";s:1:"5";}}}}';
$sOptions = 'a:1:{s:7:"GADGETS";a:7:{s:19:"CRM_LEAD_LIST@27424";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:9:"STATUS_ID";s:3:"NEW";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:10:"LEAD_COUNT";s:1:"5";}}s:19:"CRM_LEAD_LIST@12470";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:9:"STATUS_ID";s:0:"";s:7:"ONLY_MY";s:1:"Y";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:10:"LEAD_COUNT";s:2:"10";}}s:18:"CRM_DEAL_LIST@9562";a:4:{s:6:"COLUMN";i:0;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:8:"STAGE_ID";s:3:"WON";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_MODIFY";s:7:"SORT_BY";s:4:"DESC";s:10:"DEAL_COUNT";s:1:"5";}}s:19:"CRM_EVENT_LIST@9504";a:4:{s:6:"COLUMN";i:1;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:3:{s:9:"TITLE_STD";s:1:" ";s:15:"EVENT_TYPE_LIST";s:0:"";s:11:"EVENT_COUNT";s:2:"10";}}s:15:"desktop-actions";a:3:{s:6:"COLUMN";i:2;s:3:"ROW";i:0;s:4:"HIDE";s:1:"N";}s:21:"CRM_CONTACT_LIST@2435";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:1;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:7:"TYPE_ID";s:0:"";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:13:"CONTACT_COUNT";s:1:"5";}}s:21:"CRM_COMPANY_LIST@8538";a:4:{s:6:"COLUMN";i:2;s:3:"ROW";i:2;s:4:"HIDE";s:1:"N";s:8:"SETTINGS";a:6:{s:9:"TITLE_STD";s:1:" ";s:7:"TYPE_ID";s:0:"";s:7:"ONLY_MY";s:1:"N";s:4:"SORT";s:11:"DATE_CREATE";s:7:"SORT_BY";s:4:"DESC";s:13:"COMPANY_COUNT";s:1:"5";}}}}';
$arOptions = unserialize($sOptions, ["allowed_classes" => false]);
$arOptions['GADGETS']['CRM_LEAD_LIST@27424']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_NEW_LEAD_TITLE');
$arOptions['GADGETS']['CRM_DEAL_LIST@9562']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_CLOSED_DEAL_TITLE');
$arOptions['GADGETS']['CRM_LEAD_LIST@12470']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_MY_LEAD_TITLE');
$arOptions['GADGETS']['CRM_EVENT_LIST@9504']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_LAST_EVENT_TITLE');
$arOptions['GADGETS']['CRM_CONTACT_LIST@2435']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_NEW_CONTACT_TITLE');
$arOptions['GADGETS']['CRM_COMPANY_LIST@8538']['SETTINGS']['TITLE_STD'] = GetMessage('CRM_GADGET_NEW_COMPANY_TITLE');
WizardServices::SetUserOption('intranet', '~gadgets_crm', $arOptions, $common = true);

$CCrmRole = new CCrmRole();

if (method_exists(\Bitrix\Crm\Security\Role\RolePreset::class, 'GetDefaultRolesPreset'))
{
	$existedRoles = [];
	$existedRolesIterator = CCrmRole::GetList();
	while ($existedRole = $existedRolesIterator->Fetch())
	{
		$existedRoles[$existedRole['NAME'] . '|' . ($existedRole['GROUP_CODE'] ?? '')] = $existedRole;
	}

	$roles = \Bitrix\Crm\Security\Role\RolePreset::GetDefaultRolesPreset();

	$rolesRelations = [];
	foreach ($roles as $role)
	{
		if (!isset($existedRoles[$role['NAME'] . '|' . ($role['GROUP_CODE'] ?? '')]))
		{
			$roleId = $CCrmRole->Add($role);
			if ($roleId && $role['RELATIONS'] ?? false)
			{
				foreach ($role['RELATIONS'] as $relation)
				{
					if (!isset($rolesRelations[$relation]))
					{
						$rolesRelations[$relation] = [];
					}
					$rolesRelations[$relation][] = $roleId;
				}
			}
		}
	}
	if (!empty($rolesRelations))
	{
		$CCrmRole->SetRelation($rolesRelations);
	}
}
else
{
	$arRoles = [
		'ADMIN' => [
			'NAME' => GetMessage('CRM_ROLE_ADMIN'),
			'RELATION' => [
				'LEAD' => [
					'READ' => ['-' => 'X'],
					'ADD' => ['-' => 'X'],
					'WRITE' => ['-' => 'X'],
					'DELETE' => ['-' => 'X']
				],
				'DEAL' => [
					'READ' => ['-' => 'X'],
					'ADD' => ['-' => 'X'],
					'WRITE' => ['-' => 'X'],
					'DELETE' => ['-' => 'X']
				],
				'CONTACT' => [
					'READ' => ['-' => 'X'],
					'ADD' => ['-' => 'X'],
					'WRITE' => ['-' => 'X'],
					'DELETE' => ['-' => 'X']
				],
				'COMPANY' => [
					'READ' => ['-' => 'X'],
					'ADD' => ['-' => 'X'],
					'WRITE' => ['-' => 'X'],
					'DELETE' => ['-' => 'X'],
				],
				'INVOICE' => [
					'READ' => ['-' => 'X'],
					'ADD' => ['-' => 'X'],
					'WRITE' => ['-' => 'X'],
					'DELETE' => ['-' => 'X']
				],
				'QUOTE' => [
					'READ' => ['-' => 'X'],
					'ADD' => ['-' => 'X'],
					'WRITE' => ['-' => 'X'],
					'DELETE' => ['-' => 'X']
				],
				'WEBFORM' => [
					'READ' => ['-' => 'X'],
					'WRITE' => ['-' => 'X']
				],
				'BUTTON' => [
					'READ' => ['-' => 'X'],
					'WRITE' => ['-' => 'X']
				],
				'EXCLUSION' => [
					'READ' => ['-' => 'X'],
					'WRITE' => ['-' => 'X']
				],
				'CONFIG' => [
					'WRITE' => ['-' => 'X'],
				]
			]
		],
		'MANAGER' => [
			'NAME' => GetMessage('CRM_ROLE_MAN'),
			'RELATION' => [
				'LEAD' => [
					'READ' => ['-' => 'A'],
					'ADD' => ['-' => 'A'],
					'WRITE' => ['-' => 'A'],
					'DELETE' => ['-' => 'A']
				],
				'DEAL' => [
					'READ' => ['-' => 'A'],
					'ADD' => ['-' => 'A'],
					'WRITE' => ['-' => 'A'],
					'DELETE' => ['-' => 'A']
				],
				'CONTACT' => [
					'READ' => ['-' => 'A'],
					'ADD' => ['-' => 'A'],
					'WRITE' => ['-' => 'A'],
					'DELETE' => ['-' => 'A']
				],
				'COMPANY' => [
					'READ' => ['-' => 'X'],
					'ADD' => ['-' => 'X'],
					'WRITE' => ['-' => 'X'],
					'DELETE' => ['-' => 'X'],
				],
				'INVOICE' => [
					'READ' => ['-' => 'A'],
					'ADD' => ['-' => 'A'],
					'WRITE' => ['-' => 'A'],
					'DELETE' => ['-' => 'A']
				],
				'QUOTE' => [
					'READ' => ['-' => 'A'],
					'ADD' => ['-' => 'A'],
					'WRITE' => ['-' => 'A'],
					'DELETE' => ['-' => 'A']
				],
				'WEBFORM' => [
					'READ' => ['-' => 'X'],
					'WRITE' => ['-' => 'X']
				],
				'BUTTON' => [
					'READ' => ['-' => 'X'],
					'WRITE' => ['-' => 'X']
				],
				'EXCLUSION' => [
					'READ' => ['-' => 'X'],
					'WRITE' => ['-' => 'X']
				]
			]
		]
	];

	$iRoleIDAdm = $iRoleIDMan = 0;
	$obRole = CCrmRole::GetList(array(), array());
	while ($arRole = $obRole->Fetch())
	{
		if ($arRole['NAME'] == $arRoles['ADMIN']['NAME'])
			$iRoleIDAdm = $arRole['ID'];
		else if ($arRole['NAME'] == $arRoles['MANAGER']['NAME'])
			$iRoleIDMan = $arRole['ID'];
	}

	$arRel = array();
	if ($iRoleIDAdm <= 0)
		$iRoleIDAdm = $CCrmRole->Add($arRoles['ADMIN']);

	if ($iRoleIDMan <= 0)
		$iRoleIDMan = $CCrmRole->Add($arRoles['MANAGER']);

	$arRel['G1'] = array($iRoleIDAdm);
	if (defined('WIZARD_EMPLOYEES_GROUP') && WIZARD_EMPLOYEES_GROUP > 0)
		$arRel['G' . WIZARD_EMPLOYEES_GROUP] = array($iRoleIDAdm);

	$CCrmRole->SetRelation($arRel);
}
if (\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	\CBitrix24::setCrmRecaptchaOptions();
}

//Automation presets
\Bitrix\Crm\Automation\Demo\Wizard::installOnNewPortal();

//crm without leads by default
\Bitrix\Crm\Settings\LeadSettings::enableLead(false);

if (\Bitrix\Main\Loader::includeModule('intranet'))
{
	\CIntranetUtils::clearMenuCache();
}
