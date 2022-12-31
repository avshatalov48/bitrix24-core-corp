<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Activity\Access\CatalogAccessChecker;

class CBPCrmCopyMoveProductRow extends CBPActivity
{
	private const OP_COPY = 'cp';
	private const OP_MOVE = 'mv';

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			'Title' => '',
			'DstEntityType' => null,
			'DstEntityId' => null,
			'Operation' => self::OP_COPY,
		];
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
		{
			return CBPActivityExecutionStatus::Closed;
		}

		[$entityTypeId, $entityId] = \CCrmBizProcHelper::resolveEntityId($this->GetDocumentId());

		$dstEntity = $this->resolveDstEntityId();
		if (!$dstEntity)
		{
			$this->logDebug($this->DstEntityId);
			$this->WriteToTrackingService(GetMessage('CRM_CMPR_NO_DST_ENTITY'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		[$dstEntityTypeId, $dstEntityId] = $dstEntity;

		$this->logDebug($dstEntityId);

		if ($entityTypeId === $dstEntityTypeId && $entityId === $dstEntityId)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CMPR_SAME_ENTITY_ERROR'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$copyResult = $this->copyRows($entityTypeId, $entityId, $dstEntityTypeId, $dstEntityId);

		if ($copyResult && $this->Operation === self::OP_MOVE)
		{
			$this->deleteRows($entityTypeId, $entityId);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	private function deleteRows($entityTypeId, $entityId): bool
	{
		$complexDocumentId = CCrmBizProcHelper::ResolveDocumentId((int)$entityTypeId, (int)$entityId);
		$entityDocument = $complexDocumentId[1];

		if (class_exists($entityDocument) && method_exists($entityDocument, 'setProductRows'))
		{
			return $entityDocument::setProductRows($complexDocumentId[2], [])->isSuccess();
		}
		else
		{
			$entityType = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId);

			return \CCrmProductRow::SaveRows($entityType, $entityId, [], null, false);
		}
	}

	private function copyRows($entityTypeId, $entityId, $dstEntityTypeId, $dstEntityId): bool
	{
		$sourceRows = \CCrmProductRow::LoadRows(
			\CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId),
			$entityId,
			true
		);

		if (!$sourceRows)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CMPR_NO_SOURCE_PRODUCTS'), 0, CBPTrackingType::Error);

			return CBPActivityExecutionStatus::Closed;
		}

		$this->logDebugRows($this->Operation, array_column($sourceRows, 'ID'));

		foreach ($sourceRows as $i => $product)
		{
			unset($sourceRows[$i]['ID'], $sourceRows[$i]['OWNER_ID'], $sourceRows[$i]['OWNER_TYPE_ID']);
		}

		$saveResult = false;

		$entityDocument = CCrmBizProcHelper::ResolveDocumentName($dstEntityTypeId);
		if ($dstEntityTypeId === \CCrmOwnerType::Deal)
		{
			$saveResult = \CCrmDeal::addProductRows($dstEntityId, $sourceRows, [], false);
		}
		elseif (class_exists($entityDocument) && method_exists($entityDocument, 'addProductRows'))
		{
			$dstDocumentId = CCrmBizProcHelper::ResolveDocumentId($dstEntityTypeId, $dstEntityId);
			$productRows = array_map([Crm\ProductRow::class, 'createFromArray'], $sourceRows);

			$saveResult = $entityDocument::addProductRows($dstDocumentId[2], $productRows)->isSuccess();
		}

		if (!$saveResult)
		{
			$this->WriteToTrackingService(GetMessage('CRM_CDA_COPY_PRODUCTS_ERROR'), 0, CBPTrackingType::Error);
		}

		return $saveResult;
	}

	private function resolveDstEntityId(): ?array
	{
		$entityType = $this->DstEntityType;
		$entityId = $this->DstEntityId;
		if (is_array($entityId))
		{
			$entityId = \CBPHelper::MakeArrayFlat($entityId);
			$entityId = reset($entityId);
		}

		if ($entityType === \CCrmOwnerTypeAbbr::Deal)
		{
			if (\CCrmDeal::Exists($entityId))
			{
				return [\CCrmOwnerType::Deal, (int)$entityId];
			}
		}
		elseif ($entityType === CCrmOwnerTypeAbbr::SmartInvoice)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory(CCrmOwnerType::SmartInvoice);
			$item = isset($factory) ? $factory->getItem((int)$entityId) : null;

			return isset($item) ? [CCrmOwnerType::SmartInvoice, (int)$entityId] : null;
		}
		/*elseif ($entityType === \CCrmOwnerTypeAbbr::Order)
		{
			if (Crm\Order\Order::load($entityId))
			{
				return [\CCrmOwnerType::Order, $entityId];
			}
		}
		elseif ($entityType === \CCrmOwnerTypeAbbr::Invoice)
		{
			if (\CCrmInvoice::Exists($entityId))
			{
				return [\CCrmOwnerType::Invoice, $entityId];
			}
		}*/

		return null;
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

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		if (!CatalogAccessChecker::hasAccess())
		{
			return false;
		}

		$errors = [];

		$properties = [
			'DstEntityType' => $arCurrentValues['dst_entity_type'],
			'DstEntityId' => $arCurrentValues['dst_entity_id'],
			'Operation' => $arCurrentValues['operation'],
		];

		if ($properties['Operation'] !== self::OP_MOVE)
		{
			$properties['Operation'] = self::OP_COPY;
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

	protected static function getPropertiesMap(array $documentType, array $context = []): array
	{
		return [
			'DstEntityType' => [
				'Name' => GetMessage('CRM_CMPR_DST_ENTITY_TYPE'),
				'FieldName' => 'dst_entity_type',
				'Type' => 'select',
				'Default' => \CCrmOwnerTypeAbbr::Deal,
				'Options' => [
					\CCrmOwnerTypeAbbr::Deal => \CCrmOwnerType::GetDescription(\CCrmOwnerType::Deal),
					\CCrmOwnerTypeAbbr::SmartInvoice => \CCrmOwnerType::GetDescription(\CCrmOwnerType::SmartInvoice),
				],
				'Required' => true,
			],
			'DstEntityId' => [
				'Name' => GetMessage('CRM_CMPR_DST_ENTITY_ID'),
				'FieldName' => 'dst_entity_id',
				'Type' => 'int',
				'Required' => true,
				'AllowSelection' => true,
			],
			'Operation' => [
				'Name' => GetMessage('CRM_CMPR_OPERATION'),
				'FieldName' => 'operation',
				'Type' => 'select',
				'Default' => self::OP_COPY,
				'Options' => [
					self::OP_COPY => GetMessage('CRM_CMPR_OPERATION_CP'),
					self::OP_MOVE => GetMessage('CRM_CMPR_OPERATION_MV'),
				],
				'Required' => true,
			],
		];
	}

	private function logDebug($id)
	{
		$debugInfo = $this->getDebugInfo([
			'DstEntityId' => $id,
		]);

		$this->writeDebugInfo($debugInfo);
	}

	private function logDebugRows($operation, array $rowIds)
	{
		$title = $operation === static::OP_COPY
			? GetMessage('CRM_CMPR_COPIED_ROWS')
			: GetMessage('CRM_CMPR_MOVED_ROWS')
		;

		$debugInfo = $this->getDebugInfo(
			['ProductRowId' => $rowIds],
			[
				'ProductRowId' => [
					'Name' => $title,
					'Type' => 'string',
					'Multiple' => true,
				]
			],
		);

		$this->writeDebugInfo($debugInfo);
	}
}
