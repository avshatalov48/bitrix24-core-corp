<?
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

$siteID = isset($_REQUEST['site'])? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if($siteID !== '')
{
	define('SITE_ID', $siteID);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

if(!Main\Loader::includeModule('crm'))
{
	$result = array('ERROR' => Main\Localization\Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
}
elseif(!(\CCrmInvoice::CheckReadPermission() && check_bitrix_sessid()))
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

	$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
		new \Bitrix\Crm\Filter\InvoiceSettings(
			array(
				'ID' => isset($_REQUEST['filter_id']) ? $_REQUEST['filter_id'] : 'CRM_INVOICE_LIST_V12',
				'flags' => isset($_REQUEST['is_recurring']) && $_REQUEST['is_recurring'] === 'Y'
					? \Bitrix\Crm\Filter\InvoiceSettings::FLAG_RECURRING : \Bitrix\Crm\Filter\InvoiceSettings::FLAG_NONE
			)
		)
	);

	if($action === 'field')
	{
		/*
		 * @deprecated
		 */
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
	elseif ($action === 'fields')
	{
		$ids = $_REQUEST['ids'] ?? [];
		$fieldIds = is_array($ids) ? $ids : [$ids];

		$fieldsResult = (new \Bitrix\Crm\Grid\Filter($filter))->getFields($fieldIds);
		if ($fieldsResult->isSuccess())
		{
			$result = $fieldsResult->getData();
		}
		else
		{
			$result = [
				'ERROR' => implode(', ', $fieldsResult->getErrorMessages()),
			];
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

$response = Main\Context::getCurrent()->getResponse()->copyHeadersTo(new Main\Engine\Response\Json($result));
Main\Application::getInstance()->end(0, $response);
