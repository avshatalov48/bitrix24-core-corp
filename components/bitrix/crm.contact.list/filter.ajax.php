<?php

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

$siteID = isset($_REQUEST['site'])? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Crm;
use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

if(!Main\Loader::includeModule('crm'))
{
	$result = ['ERROR' => Main\Localization\Loc::getMessage('CRM_MODULE_NOT_INSTALLED')];
}
elseif(!(\CCrmContact::CheckReadPermission() && check_bitrix_sessid()))
{
	$result = ['ERROR' => Main\Localization\Loc::getMessage('CRM_ACCESS_DENIED')];
}
else
{
	$action = $_REQUEST['action'] ?? '';
	if($action === '')
	{
		$action = 'list';
	}

	$filterFlags = Crm\Filter\ContactSettings::FLAG_NONE;
	$enableOutmodedFields = Crm\Settings\ContactSettings::getCurrent()->areOutmodedRequisitesEnabled();
	if($enableOutmodedFields)
	{
		$filterFlags |= Crm\Filter\ContactSettings::FLAG_ENABLE_ADDRESS;
	}

	$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
		new \Bitrix\Crm\Filter\ContactSettings([
			'ID'         => $_REQUEST['filter_id'] ?? 'CRM_CONTACT_LIST_V12',
			'flags'      => $filterFlags,
			'categoryID' => $_REQUEST['category_id'] ?? 0,
		])
	);

	if($action === 'field')
	{
		$fieldID = $_REQUEST['id'] ?? '';
		$field = $filter->getField($fieldID);
		$result = $field
			? Main\UI\Filter\FieldAdapter::adapt($field->toArray())
			: ['ERROR' => Main\Localization\Loc::getMessage('CRM_FILTER_FIELD_NOT_FOUND')];
	}
	elseif($action === 'list')
	{
		$result = [];
		foreach($filter->getFields() as $field)
		{
			$result[] = Main\UI\Filter\FieldAdapter::adapt($field->toArray(['lightweight' => true]));
		}
	}
	else
	{
		$result = ['ERROR' => Main\Localization\Loc::getMessage('CRM_FILTER_ACTION_NOT_SUPPORTED')];
	}
}

$response = Main\Context::getCurrent()->getResponse()->copyHeadersTo(new Main\Engine\Response\Json($result));
Main\Application::getInstance()->end(0, $response);
