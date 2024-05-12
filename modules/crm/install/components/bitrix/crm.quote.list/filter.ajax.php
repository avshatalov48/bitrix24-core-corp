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
elseif(!(\CCrmQuote::CheckReadPermission() && check_bitrix_sessid()))
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

	$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
		new \Bitrix\Crm\Filter\QuoteSettings(
			array('ID' => isset($_REQUEST['filter_id']) ? $_REQUEST['filter_id'] : 'CRM_QUOTE_LIST_V12')
		)
	);

	/**
	 * @deprecated since crm 24.0.0. See action === 'fields'
	 */
	if ($action === 'field')
	{
		$fieldID = $_REQUEST['id'] ?? '';
		$field = $filter->getField($fieldID);
		if ($field)
		{
			$result = Main\UI\Filter\FieldAdapter::adapt($field->toArray());
		}
		else
		{
			$result = ['ERROR' => Main\Localization\Loc::getMessage('CRM_FILTER_FIELD_NOT_FOUND')];
		}
	}
	elseif ($action === 'fields')
	{
		$ids = $_REQUEST['ids'] ?? '';
		$fieldIds = is_array($ids) ? $ids : [$ids];

		$fields = [];
		foreach ($fieldIds as $fieldId)
		{
			$field = $filter->getField($fieldId);
			if ($field)
			{
				$fields[] = $field;
			}
			else
			{
				$result = [
					'ERROR' => Main\Localization\Loc::getMessage('CRM_FILTER_FIELD_NOT_FOUND') . ': ' . $fieldId,
				];
			}
		}

		if (empty($result))
		{
			foreach ($fields as $field)
			{
				$result[] = Main\UI\Filter\FieldAdapter::adapt($field->toArray());
			}
		}
	}
	elseif ($action === 'list')
	{
		$result = [];
		foreach ($filter->getFields() as $field)
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
