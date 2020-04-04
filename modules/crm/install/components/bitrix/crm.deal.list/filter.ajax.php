<?
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

$siteID = isset($_REQUEST['site']) ? substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main;
use Bitrix\Crm;

Main\Localization\Loc::loadMessages(__FILE__);

if(!Main\Loader::includeModule('crm'))
{
	$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
}
elseif(!(\CCrmDeal::CheckReadPermission() && check_bitrix_sessid()))
{
	$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_ACCESS_DENIED'));
}
else
{
	$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	if($action === '')
	{
		$action = 'list';
	}

	$userPermissions = CCrmPerms::GetCurrentUserPermissions();

	$categoryID = isset($_REQUEST['category_id']) ? (int)$_REQUEST['category_id'] : -1;
	$categoryAccess = array(
		'CREATE' => \CCrmDeal::GetPermittedToCreateCategoryIDs($userPermissions),
		'READ' => \CCrmDeal::GetPermittedToReadCategoryIDs($userPermissions),
		'UPDATE' => \CCrmDeal::GetPermittedToUpdateCategoryIDs($userPermissions)
	);

	$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
		new \Bitrix\Crm\Filter\DealSettings(
			array(
				'ID' => isset($_REQUEST['filter_id']) ? $_REQUEST['filter_id'] : 'CRM_DEAL_LIST_V12',
				'categoryID' =>$categoryID,
				'categoryAccess' => $categoryAccess,
				'flags' => isset($_REQUEST['is_recurring']) && $_REQUEST['is_recurring'] === 'Y'
					? \Bitrix\Crm\Filter\DealSettings::FLAG_RECURRING : \Bitrix\Crm\Filter\DealSettings::FLAG_NONE
			)
		)
	);

	if($action === 'field')
	{
		$fieldID = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
		$field = $filter->getField($fieldID);
		if($field)
		{
			$result = Main\UI\Filter\FieldAdapter::adapt($field->toArray());
		}
		else
		{
			$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_FILTER_FIELD_NOT_FOUND'));
		}
	}
	elseif($action === 'list')
	{
		$result = array();
		foreach($filter->getFields() as $field)
		{
			$result[] = Main\UI\Filter\FieldAdapter::adapt($field->toArray(array('lightweight' => true)));
		}
	}
	else
	{
		$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_FILTER_ACTION_NOT_SUPPORTED'));
	}
}

$response = new Main\HttpResponse(Main\Application::getInstance()->getContext());
$response->addHeader('Content-Type', 'application/json');
$response->flush(Main\Web\Json::encode($result));

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die();