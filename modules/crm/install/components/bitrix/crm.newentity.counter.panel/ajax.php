<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE'
 * 'GET_FORMATTED_SUM'
 */
global $DB, $APPLICATION;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmNewEntityCounterEndJsonResponse'))
{
	function __CrmNewEntityCounterEndJsonResponse($result)
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

if (!CModule::IncludeModule('crm'))
{
	__CrmNewEntityCounterEndJsonResponse(array('ERROR'=>'CRM MODULE IS NOT ISTALLED!'));
}

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmNewEntityCounterEndJsonResponse(array('ERROR'=>'ACTION IS NOT DEFINED'));
}

CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$currentUserPermissions =  CCrmPerms::GetCurrentUserPermissions();

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === '' && isset($_POST['MODE']))
{
	$action = $_POST['MODE'];
}
if($action === '')
{
	__CrmNewEntityCounterEndJsonResponse(array('ERROR'=>'ACTION IS NOT DEFINED'));
}
if($action === 'GET_NEW_ENTITY_IDS')
{
	$lastEntityID = isset($_POST['LAST_ENTITY_ID']) ? (int)$_POST['LAST_ENTITY_ID'] : 0;
	$entityTypeID = isset($_POST['ENTITY_TYPE_ID']) ? (int)$_POST['ENTITY_TYPE_ID'] : 0;
	$categoryId = isset($_POST['CATEGORY_ID']) && $_POST['CATEGORY_ID']!=='' ? (int)$_POST['CATEGORY_ID'] : null;
	$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeID);
	if ($factory && !$factory->isCategoriesEnabled())
	{
		$categoryId = null;
	}

	if($lastEntityID <= 0 || !CCrmOwnerType::IsDefined($entityTypeID))
	{
		__CrmNewEntityCounterEndJsonResponse(array('ERROR'=>'INVALID PARAMS'));
	}

	$entity = \Bitrix\Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
	$count = count(
		$entity->getNewIDs(
			$lastEntityID,
			'DESC',
			100,
			\CCrmSecurityHelper::GetCurrentUserID(),
			true,
			$categoryId
		)
	);

	__CrmNewEntityCounterEndJsonResponse(
		array(
			'DATA' => array('NEW_ENTITY_COUNT' => $count)
		)
	);
}
if($action === 'GET_LAST_ENTITY_ID')
{
	$entityTypeID = isset($_POST['ENTITY_TYPE_ID']) ? (int)$_POST['ENTITY_TYPE_ID'] : 0;
	if(!CCrmOwnerType::IsDefined($entityTypeID))
	{
		__CrmNewEntityCounterEndJsonResponse(array('ERROR'=>'INVALID PARAMS'));
	}

	$entity = \Bitrix\Crm\Entity\EntityManager::resolveByTypeID($entityTypeID);
	$lastEntityID = $entity->getLastID(\CCrmSecurityHelper::GetCurrentUserID(), true);

	__CrmNewEntityCounterEndJsonResponse(
		array(
			'DATA' => array('LAST_ENTITY_ID' => $lastEntityID)
		)
	);
}



