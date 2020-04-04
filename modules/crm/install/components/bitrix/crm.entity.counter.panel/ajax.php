<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Counter\EntityCounterFactory;

if (!CModule::IncludeModule('crm'))
{
	return;
}

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'RECALCULATE'
 */
global $DB, $APPLICATION, $USER_FIELD_MANAGER;
Loc::loadMessages(__FILE__);
if(!function_exists('__CrmEntityCounterPanelEndJsonResonse'))
{
	function __CrmEntityCounterPanelEndJsonResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}


if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

CUtil::JSPostUnescape();
$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$currentUserPermissions =  CCrmPerms::GetCurrentUserPermissions();

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === '')
{
	__CrmEntityCounterPanelEndJsonResonse(array('ERROR'=>'ACTION IS NOT DEFINED!'));
}

if($action === 'RECALCULATE')
{
	$entityTypes = isset($_POST['ENTITY_TYPES']) && is_array($_POST['ENTITY_TYPES'])
		? $_POST['ENTITY_TYPES'] : array();

	$extras = isset($_POST['EXTRAS']) && is_array($_POST['EXTRAS'])
		? $_POST['EXTRAS'] : array();

	$data = array();
	foreach($entityTypes as $entityTypeName)
	{
		$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
		if($entityTypeID === CCrmOwnerType::Undefined)
		{
			continue;
		}

		$entityData = array();
		$total = 0;
		foreach(EntityCounterType::getAll(false) as $typeID)
		{
			$counter = EntityCounterFactory::create($entityTypeID, $typeID, $currentUserID, $extras);
			$value = $counter->getValue(true);
			$entityData[$counter->getCode()] = $value;

			$total += $value;
		}

		foreach(EntityCounterType::getGroupings() as $typeID)
		{
			$counter = EntityCounterFactory::create($entityTypeID, $typeID, $currentUserID);
			$counter->synchronize();

			if(!empty($extras))
			{
				$counter = EntityCounterFactory::create($entityTypeID, $typeID, $currentUserID, $extras);
				$counter->synchronize();
			}
		}

		$entityData['total'] = array(
			'value' => $total,
			'caption' => \Bitrix\Crm\MessageHelper::prepareEntityNumberDeclension($entityTypeID, $total)
		);
		$data[$entityTypeName] = $entityData;
	}
	__CrmEntityCounterPanelEndJsonResonse(array('DATA' => $data));
}