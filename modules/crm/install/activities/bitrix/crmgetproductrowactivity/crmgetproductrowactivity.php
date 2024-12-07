<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Activity\Access\CatalogAccessChecker;

class CBPCrmGetProductRowActivity extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'RowId' => null,
		];

		$returnProperties = $this->getReturnProperties();
		foreach (array_keys($returnProperties) as $propertyId)
		{
			$this->arProperties[$propertyId] = null;
		}
		$this->SetPropertiesTypes($returnProperties);
	}

	protected function ReInitialize()
	{
		parent::ReInitialize();

		foreach (array_keys($this->getReturnProperties()) as $returnProperty)
		{
			$this->{$returnProperty} = null;
		}
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$rowId = $this->RowId;
		if (is_array($rowId))
		{
			$rowId = reset($rowId);
		}

		$rowId = (int)$rowId;
		if ($this->workflow->isDebug())
		{
			$this->writeDebugInfo($this->getDebugInfo(['RowId' => $rowId]));
		}

		$product = Crm\Automation\Connectors\Product::fetchFromTableByFilter(['ID' => $rowId]);

		if (!$product)
		{
			$this->WriteToTrackingService(
				GetMessage('CRM_BP_GPR_ROW_NOT_FOUND', ['#ID#' => $rowId]),
				0,
				CBPTrackingType::Error
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$compatibilityMap = [
			'RowProductId' => 'PRODUCT_ID',
			'RowProductName' => 'PRODUCT_NAME',
			'RowPriceAccount' => 'PRICE_ACCOUNT',
			'RowQuantity' => 'QUANTITY',
			'RowMeasureName' => 'MEASURE_NAME',
			'RowDiscountRate' => 'DISCOUNT_RATE',
			'RowDiscountSum' => 'DISCOUNT_SUM',
			'RowTaxRate' => 'TAX_RATE',
			'RowTaxIncluded' => 'TAX_INCLUDED',
			'RowSumAccount' => 'SUM_ACCOUNT',
			'RowSumAccountMoney' => 'PRINTABLE_SUM_ACCOUNT',
		];
		foreach (array_keys($this->getReturnProperties()) as $propertyId)
		{
			$this->{$propertyId} = $product->get($compatibilityMap[$propertyId] ?? $propertyId);
		}

		$this->logReturnProperties();

		return CBPActivityExecutionStatus::Closed;
	}

	private function logReturnProperties()
	{
		if ($this->workflow->isDebug())
		{
			$runtime = CBPRuntime::GetRuntime();

			$this->writeDebugInfo($this->getDebugInfo([], $runtime->getActivityReturnProperties($this->getCode())));
		}
	}

	private function getCode(): string
	{
		return 'CrmGetProductRowActivity';
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '', $popupWindow = null, $siteId = '')
	{
		if (!CModule::IncludeModule('crm'))
		{
			return '';
		}

		$dialog = new \Bitrix\Bizproc\Activity\PropertiesDialog(__FILE__, [
			'documentType' => $documentType,
			'activityName' => $activityName,
			'workflowTemplate' => $arWorkflowTemplate,
			'workflowParameters' => $arWorkflowParameters,
			'workflowVariables' => $arWorkflowVariables,
			'currentValues' => $arCurrentValues,
			'formName' => $formName,
			'siteId' => $siteId,
		]);

		if (!CatalogAccessChecker::hasAccess())
		{
			$dialog->setRenderer(CatalogAccessChecker::getDialogRenderer());
		}
		else
		{
			$dialog->setMap(static::getPropertiesMap($documentType));
		}

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'RowId' => [
				'Name' => GetMessage('CRM_BP_GPR_ROW_ID_MSGVER_1'),
				'FieldName' => 'row_id',
				'Type' => 'int',
			],
		];
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		if (!CatalogAccessChecker::hasAccess())
		{
			return false;
		}

		$properties = [
			'RowId' => $arCurrentValues['row_id'],
		];

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

		if ($errors)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity['Properties'] = $properties;

		return true;
	}

	private function getReturnProperties(): array
	{
		$compatibilityMap = [
			'PRODUCT_ID' => 'RowProductId',
			'PRODUCT_NAME' => 'RowProductName',
			'PRICE_ACCOUNT' => 'RowPriceAccount',
			'QUANTITY' => 'RowQuantity',
			'MEASURE_NAME' => 'RowMeasureName',
			'DISCOUNT_RATE' => 'RowDiscountRate',
			'DISCOUNT_SUM' => 'RowDiscountSum',
			'TAX_RATE' => 'RowTaxRate',
			'TAX_INCLUDED' => 'RowTaxIncluded',
			'SUM_ACCOUNT' => 'RowSumAccount',
			'PRINTABLE_SUM_ACCOUNT' => 'RowSumAccountMoney',
		];

		$returnPropertiesMap = [];
		foreach (Crm\Automation\Connectors\Product::getFieldsMap() as $fieldId => $field)
		{
			$returnPropertiesMap[$compatibilityMap[$fieldId] ?? $fieldId] = $field;
		}

		CBPRuntime::getRuntime()->getActivityDescription('CBPCrmGetProductRowActivity');
		$returnPropertiesMap['RowSumAccountMoney'] = [
			'Name' => \Bitrix\Main\Localization\Loc::getMessage('CRM_BP_GPR_RETURN_ROW_SUM_ACCOUNT_MONEY'),
			'Type' => \Bitrix\Bizproc\FieldType::STRING,
		];

		return $returnPropertiesMap;
	}
}
