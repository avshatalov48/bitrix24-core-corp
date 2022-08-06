<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;

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
		$this->writeDebugInfo($this->getDebugInfo(['RowId' => $rowId]));

		$row = Crm\ProductRowTable::getList([
			'select' => [
				'ID',
				'PRODUCT_ID',
				'CP_PRODUCT_NAME',
				'PRICE_ACCOUNT',
				'QUANTITY',
				'MEASURE_NAME',
				'DISCOUNT_RATE',
				'DISCOUNT_SUM',
				'TAX_RATE',
				'TAX_INCLUDED',
				'SUM_ACCOUNT',
			],
			'filter' => [
				'=ID' => $rowId,
			]
		])->fetch();

		if (!$row)
		{
			$this->WriteToTrackingService(
				GetMessage('CRM_BP_GPR_ROW_NOT_FOUND', ['#ID#' => $rowId]),
				0,
				CBPTrackingType::Error
			);

			return CBPActivityExecutionStatus::Closed;
		}

		$currencyId = \CCrmCurrency::GetAccountCurrencyID();

		$this->RowProductId = $row['PRODUCT_ID'];
		$this->RowProductName = $row['CP_PRODUCT_NAME'];
		$this->RowPriceAccount = $row['PRICE_ACCOUNT'];
		$this->RowQuantity = $row['QUANTITY'];
		$this->RowMeasureName = $row['MEASURE_NAME'];
		$this->RowDiscountRate = $row['DISCOUNT_RATE'];
		$this->RowDiscountSum = $row['DISCOUNT_SUM'];
		$this->RowTaxRate = $row['TAX_RATE'];
		$this->RowTaxIncluded = $row['TAX_INCLUDED'];
		$this->RowSumAccount = $row['SUM_ACCOUNT'];
		$this->RowSumAccountMoney = \CCrmCurrency::MoneyToString($row['SUM_ACCOUNT'], $currencyId);
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

		$dialog->setMap(static::getPropertiesMap($documentType));

		return $dialog;
	}

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'RowId' => [
				'Name' => GetMessage('CRM_BP_GPR_ROW_ID'),
				'FieldName' => 'row_id',
				'Type' => 'int',
			],
		];
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
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
		return [
			'RowProductId' => [
				'Type' => 'int',
			],
			'RowProductName' => [
				'Type' => 'string',
			],
			'RowPriceAccount' => [
				'Type' => 'double',
			],
			'RowQuantity' => [
				'Type' => 'double',
			],
			'RowMeasureName' => [
				'Type' => 'string',
			],
			'RowDiscountRate' => [
				'Type' => 'double',
			],
			'RowDiscountSum' => [
				'Type' => 'double',
			],
			'RowTaxRate' => [
				'Type' => 'double',
			],
			'RowTaxIncluded' => [
				'Type' => 'bool',
			],
			'RowSumAccount' => [
				'Type' => 'double',
			],
			'RowSumAccountMoney' => [
				'Type' => 'int',
			],
		];
	}
}
