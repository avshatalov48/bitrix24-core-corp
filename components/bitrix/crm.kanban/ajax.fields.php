<?php
/** @deprecated
 * @see \KanbanAjaxController::getFieldsAction()
 * This file is deprecated and will be removed soon.
 */
/**
 * Full copy from crm.lead.list/filter.ajax.php
 * with some changes from crm.deal.list/filter.ajax.php
 */
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

// init request section
$siteID = isset($_REQUEST['site']) ? $_REQUEST['site'] : '';
$entityType = isset($_REQUEST['entityType']) ? $_REQUEST['entityType'] : '';
$viewType = isset($_REQUEST['viewType']) ? $_REQUEST['viewType'] : '';
$filterId = isset($_REQUEST['filter_id']) ? $_REQUEST['filter_id'] : 'CRM_LEAD_LIST_V12';
$siteID = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $siteID), 0, 2);
$entityType = mb_strtolower($entityType);
$viewType = mb_strtolower($viewType);

if ($siteID !== '')
{
	define('SITE_ID', $siteID);
}
if (!in_array($entityType, ['lead', 'deal', 'order']))
{
	$entityType = 'lead';
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use \Bitrix\Main;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('crm'))
{
	$result = ['ERROR' => 'Module is not installed'];
}
elseif (!check_bitrix_sessid())
{
	$result = ['ERROR' => 'Access denied'];
}
elseif (
	($entityType == 'lead' && !(\CCrmLead::checkReadPermission())) ||
	($entityType == 'deal' && !(\CCrmDeal::checkReadPermission()))||
	($entityType == 'order' && !(\Bitrix\Crm\Order\Permissions\Order::checkReadPermission()))
)
{
	$result = ['ERROR' => 'Access denied'];
}
else
{
	// additional fields, which we want to add
	$generalFields = [
		'OBSERVER' => [
			'ID' => 'field_OBSERVER',
			'NAME' => 'OBSERVER',
			'LABEL' => Loc::getMessage('CRM_KANBAN_FIELD_OBSERVER')
		],
		'SOURCE_DESCRIPTION' => [
			'ID' => 'field_SOURCE_DESCRIPTION',
			'NAME' => 'SOURCE_DESCRIPTION',
			'LABEL' => Loc::getMessage('CRM_KANBAN_FIELD_SOURCE_DESCRIPTION')
		]
	];
	/*if ($viewType == 'edit' && $entityType == 'deal')
	{
		$generalFields['RECURRING'] = [
			'ID' => 'field_RECURRING',
			'NAME' => 'RECURRING',
			'LABEL' => Loc::getMessage('CRM_KANBAN_FIELD_RECURRING')
		];
	}*/

	// get filter's fields as etalon of fields array
	if ($entityType == 'lead')
	{
		$settings = new \Bitrix\Crm\Filter\LeadSettings(
			['ID' => $filterId]
		);
	}
	elseif ($entityType == 'order')
	{
		$settings = new \Bitrix\Crm\Filter\OrderSettings(
			['ID' => $filterId]
		);
	}
	else
	{
		$settings = new \Bitrix\Crm\Filter\DealSettings([
			'ID' => $filterId,
			'flags' => \Bitrix\Crm\Filter\DealSettings::FLAG_ENABLE_CLIENT_FIELDS,
		]);
	}
	$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter($settings);

	// prepare fields
	$result = [];
	foreach ($filter->getFields() as $field)
	{
		if ($generalFields && $entityType !== 'order')
		{
			if (mb_substr($field->getId(), 0, 3) == 'UF_')
			{
				$result = array_merge(
					$result,
					$generalFields
				);
				$generalFields = [];
			}
		}

		if ($entityType === 'order' && preg_match("/^PROPERTY_/", $field->getId()))
		{
			continue;
		}
		$result[$field->getId()] = Main\UI\Filter\FieldAdapter::adapt($field->toArray(
			['lightweight' => true]
		));
	}

	// fill result with user fields
	$userTypeManager = new \CCrmUserType(
		$USER_FIELD_MANAGER,
		'CRM_'.mb_strtoupper($entityType)
	);
	$labelCodes = [
		'LIST_FILTER_LABEL', 'LIST_COLUMN_LABEL', 'EDIT_FORM_LABEL'
	];
	foreach ($userTypeManager->getFields() as $fieldName => $userField)
	{
		// if field does't exist, put it
		if (!isset($result[$fieldName]))
		{
			// detect field's label
			$fieldLabel = '';
			foreach ($labelCodes as $code)
			{
				if (isset($userField[$code]))
				{
					$fieldLabel = trim($userField[$code]);
					if ($fieldLabel)
					{
						break;
					}
				}
			}
			if (!$fieldLabel)
			{
				$fieldLabel = $fieldName;
			}
			// add to the result
			$result[$fieldName] =  [
				'ID' => 'field_' . $fieldName,
				'NAME' => $fieldName,
				'LABEL' => $fieldLabel
			];
		}
		// delete uf-fields by some types
		if (
			isset($userField['USER_TYPE_ID']) &&
			isset($result[$fieldName])
		)
		{
			if ($userField['USER_TYPE_ID'] == 'resourcebooking')
			{
				unset($result[$fieldName]);
				continue;
			}
			if (
				$viewType == 'edit' &&
				$userField['USER_TYPE_ID'] == 'money'
			)
			{
				unset($result[$fieldName]);
				continue;
			}
		}
	}

	if ($entityType == 'order')
	{
		$additionalFields = [
			'TITLE' =>  Loc::getMessage('CRM_KANBAN_FIELD_ORDER_TITLE'),
			'PAYMENT' =>  Loc::getMessage('CRM_KANBAN_FIELD_ORDER_PAYMENTS'),
			'SHIPMENT' =>  Loc::getMessage('CRM_KANBAN_FIELD_ORDER_SHIPMENTS'),
			'PROBLEM_NOTIFICATION' =>  Loc::getMessage('CRM_KANBAN_FIELD_ORDER_PROBLEM_NOTIFICATION'),
		];
		foreach ($additionalFields as $fieldName => $fieldLabel)
		{
			$result[$fieldName] =  [
				'ID' => 'field_' . $fieldName,
				'NAME' => $fieldName,
				'LABEL' => $fieldLabel
			];
		}

		$propertiesRaw = \Bitrix\Crm\Order\Property::getList(
			array(
				'filter' => array(
					'=ACTIVE' => 'Y',
					'=TYPE' => ['STRING', 'NUMBER', 'Y/N', 'ENUM', 'DATE']
				),
				'order' => array(
					"PERSON_TYPE_ID" => "ASC", "SORT" => "ASC"
				),
				'select' => array(
					"ID", "NAME", "PERSON_TYPE_NAME" => "PERSON_TYPE.NAME", "LID" => "PERSON_TYPE.LID", "PERSON_TYPE_ID"
				),
			)
		);

		while ($property = $propertiesRaw->fetch())
		{
			$fieldName = 'PROPERTY_'.$property['ID'];
			$fieldLabel = htmlspecialcharsbx("{$property['NAME']} ({$property['PERSON_TYPE_NAME']}) [{$property['LID']}]");
			$result[$fieldName] =  [
				'ID' => 'field_' . $fieldName,
				'NAME' => $fieldName,
				'LABEL' => $fieldLabel
			];
		}
	}

	// replace some fields
	if (isset($result['OPPORTUNITY']))
	{
		$result['OPPORTUNITY']['LABEL'] = Loc::getMessage('CRM_KANBAN_FIELD_OPPORTUNITY_WITH_CURRENCY');
	}

	// remove some common fields
	$unAcceptableFields = [
		'STAGE_ID', 'STATUS', 'STATUS_ID'
	];

	if ($entityType == 'order')
	{
		$unAcceptableFields = array_merge($unAcceptableFields, [
			'COUPON', 'PAY_SYSTEM', 'DELIVERY_SERVICE', 'CREATED_BY', 'SHIPMENT_TRACKING_NUMBER', 'SHIPMENT_DELIVERY_DOC_DATE'
		]);
	}

	foreach ($unAcceptableFields as $code)
	{
		if (isset($result[$code]))
		{
			unset($result[$code]);
		}
	}
}

$response = Main\Context::getCurrent()->getResponse()->copyHeadersTo(new Main\Engine\Response\Json(array_values($result)));
Main\Application::getInstance()->end(0, $response);
