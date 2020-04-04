<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'GET_BY_ID' - get deal by ID
 */

global $DB, $APPLICATION;

CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$CCrmDeal = new CCrmDeal();
if ($CCrmDeal->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	echo CUtil::PhpToJSObject(
		array('ERROR' => 'Access denied!')
	);
	die();
}

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if(strlen($action) == 0)
{
	echo CUtil::PhpToJSObject(
		array('ERROR' => 'Invalid data!')
	);
	die();
}

if($action == 'GET_ENTITY')
{
	$ID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;

	if($ID <= 0)
	{
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Invalid parameters!')
		);
		die();
	}

	$dbFields = CCrmContact::GetListEx(array(), array('ID' => $ID));
	$item = $obFields->GetNext();

	$formatParams = isset($_POST['FORMAT_PARAMS']) ? $_POST['FORMAT_PARAMS'] : array();
	CCrmMobileHelper::PrepareContactItem($item, $formatParams);

	echo CUtil::PhpToJSObject(
		array(
			'DATA' => array(
				'ENTITY' => CCrmMobileHelper::PrepareContactData($item)
			)
		)
	);
}
