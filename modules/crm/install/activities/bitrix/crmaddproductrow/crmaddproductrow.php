<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;

class CBPCrmAddProductRow extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
		];

		foreach (self::getPropertiesMap() as $key => $property)
		{
			$this->arProperties[$key] = $property['Default'] ?? null;
		}
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($this->GetDocumentId());

		if ($entityTypeId !== \CCrmOwnerType::Deal)
		{
			$this->WriteToTrackingService(GetMessage('CRM_APR_DOCUMENT_ERROR'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$id = $this->getProductId();
		$product = $this->getProduct($id);

		if (!$product)
		{
			$this->WriteToTrackingService(GetMessage('CRM_APR_GET_PRODUCT_ERROR'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$row = [
			'PRODUCT_ID' => $id,
			'QUANTITY' => (float)$this->RowQuantity,
		];

		$discountRate = $this->RowDiscountRate;
		if (!CBPHelper::isEmptyValue($discountRate))
		{
			$row['DISCOUNT_TYPE_ID'] = Crm\Discount::PERCENTAGE;
			$row['DISCOUNT_RATE'] = (float)$discountRate;
		}

		$taxRate = $this->RowTaxRate;
		if (!CBPHelper::isEmptyValue($taxRate))
		{
			$row['TAX_RATE'] = (float)$taxRate;
			$row['TAX_INCLUDED'] = $this->RowTaxIncluded === 'Y' ? 'Y' : 'N';
		}

		$price = $this->RowPriceAccount;
		if (CBPHelper::isEmptyValue($price))
		{
			$price = $product['PRICE'];
		}

		$row['PRICE'] = $price;

		$addResult = \CCrmDeal::addProductRows($entityId, [$row], [], false);

		if (!$addResult)
		{
			$this->WriteToTrackingService(GetMessage('CRM_APR_ADD_ERROR'), 0, CBPTrackingType::Error);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function getProductId(): int
	{
		$id = $this->ProductId;
		if (is_array($id))
		{
			$id = current(CBPHelper::MakeArrayFlat($id));
		}

		return (int)$id;
	}

	private function getProduct(int $id): ?array
	{
		$dbProduct = CCrmProduct::GetList(
			[],
			['ID' => $id],
			['ID', 'NAME', 'PRICE']
		);

		$product = $dbProduct->fetch();

		return  $product ?: null;
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

		$dialog->setMap(self::getPropertiesMap());

		return $dialog;
	}

	private static function getPropertiesMap(): array
	{
		return [
			'ProductId' => [
				'Name' => GetMessage('CRM_APR_PRODUCT_ID'),
				'FieldName' => 'product_id',
				'Type' => 'int',
				'Required' => true,
				'AllowSelection' => true,
			],
			'RowPriceAccount' => [
				'Name' => GetMessage('CRM_APR_ROW_PRICE_ACCOUNT'),
				'FieldName' => 'row_price_account',
				'Type' => 'double',
				'AllowSelection' => true,
			],
			'RowQuantity' => [
				'Name' => GetMessage('CRM_APR_ROW_QUANTITY'),
				'FieldName' => 'row_quantity',
				'Type' => 'double',
				'Default' => 1,
				'AllowSelection' => true,
			],
			'RowDiscountRate' => [
				'Name' => GetMessage('CRM_APR_ROW_DISCOUNT_RATE'),
				'FieldName' => 'row_discount_rate',
				'Type' => 'double',
				'AllowSelection' => true,
			],
			'RowTaxRate' => [
				'Name' => GetMessage('CRM_APR_ROW_TAX_RATE'),
				'FieldName' => 'row_tax_rate',
				'Type' => 'select',
				'Options' => self::getTaxRateOptions(),
			],
			'RowTaxIncluded' => [
				'Name' => GetMessage('CRM_APR_ROW_TAX_INCLUDED'),
				'FieldName' => 'row_tax_included',
				'Type' => 'bool',
				'Default' => 'N',
				'AllowSelection' => true,
			],
		];
	}

	private static function getTaxRateOptions(): array
	{
		$options = [];

		foreach (CCrmTax::GetVatRateInfos() as $vatRow)
		{
			$options[$vatRow['VALUE']] = $vatRow['VALUE'] . ' %';
		}

		asort($options, SORT_NUMERIC);

		return $options;
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];
		$properties = [];

		$documentService = CBPRuntime::GetRuntime(true)->getDocumentService();

		foreach (self::getPropertiesMap() as $key => $property)
		{
			$properties[$key] = $documentService->GetFieldInputValue(
				$documentType,
				$property,
				$property['FieldName'],
				$arCurrentValues,
				$errors
			);
		}

		if ($errors)
		{
			return false;
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));

		if ($errors)
		{
			return false;
		}

		$activity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$activity['Properties'] = $properties;

		return true;
	}
}
