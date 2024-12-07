<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmTypeMergeResolver extends Base
{
	public function init(): void
	{
		parent::init();

		$this->entityTypeId = $this->getEntityTypeIdFromParams();
		if (!$this->isAvailableEntityTypeId($this->entityTypeId))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($this->entityTypeId));
		}
	}

	public function executeComponent(): void
	{
		$this->init();
		if ($this->getErrors())
		{
			$this->includeComponentTemplate();

			return;
		}

		$this->prepareArResultParameters();
		$this->includeComponentTemplate();
	}

	protected function prepareArResultParameters(): void
	{
		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$this->arResult['PAGE_TITLE'] = $this->getPageTitle();
		$this->arResult['MERGE_GUID'] = $this->getMergeGuid();
		$this->arResult['ENTITY_IDS'] = $this->getEntityIds();
		$this->arResult['PATH_TO_ENTITY_LIST'] = $this->getListUrl();
		$this->arResult['PAGE_MODE_OFF_BACK_URL'] = $this->arResult['PATH_TO_ENTITY_LIST'];
		$this->arResult['HEADER_TEMPLATE'] = $this->getHeaderTemplate();
		$this->arResult['RESULT_LEGEND'] = $this->getResultLegend();
		$this->arResult['RESULT_TITLE'] = $this->getResultTitle();
	}

	protected function getEntityIds(): array
	{
		$ids = $this->request->get('id');
		if (!is_array($ids))
		{
			$ids = [];
		}

		return array_map(static fn(mixed $id) => (int)$id, $ids);
	}

	protected function getMergeGuid(): string
	{
		$lowerEntityName = mb_strtolower($this->getEntityTypeName());

		return "{$lowerEntityName}_merger";
	}

	protected function getPageTitle(): string
	{
		$entityTypeName = $this->getEntityTypeName();

		/**
		 * CRM_TYPE_MERGE_RESOLVER_DYNAMIC_PAGE_TITLE
		 * CRM_TYPE_MERGE_RESOLVER_SMART_INVOICE_PAGE_TITLE
		 * CRM_TYPE_MERGE_RESOLVER_QUOTE_PAGE_TITLE
		 */
		return Loc::getMessage("CRM_TYPE_MERGE_RESOLVER_{$entityTypeName}_PAGE_TITLE");
	}

	protected function getResultLegend(): string
	{
		$entityTypeName = $this->getEntityTypeName();

		/**
		 * CRM_TYPE_MERGE_RESOLVER_DYNAMIC_RESULT_LEGEND
		 * CRM_TYPE_MERGE_RESOLVER_SMART_INVOICE_RESULT_LEGEND
		 * CRM_TYPE_MERGE_RESOLVER_QUOTE_RESULT_LEGEND
		 */
		return Loc::getMessage("CRM_TYPE_MERGE_RESOLVER_{$entityTypeName}_RESULT_LEGEND");
	}

	protected function getHeaderTemplate(): string
	{
		if ($this->isNeuterEntity())
		{
			return Loc::getMessage('CRM_TYPE_MERGE_RESOLVER_NEUTER_HEADER_TEMPLATE');
		}

		return Loc::getMessage('CRM_TYPE_MERGE_RESOLVER_DEFAULT_HEADER_TEMPLATE');
	}

	protected function isNeuterEntity(): bool
	{
		return $this->entityTypeId === CCrmOwnerType::Quote;
	}

	protected function getEntityTypeName(): string
	{
		return CCrmOwnerType::isPossibleDynamicTypeId($this->entityTypeId)
			? CCrmOwnerType::CommonDynamicName
			: CCrmOwnerType::ResolveName($this->entityTypeId)
		;
	}

	protected function isAvailableEntityTypeId(int $entityTypeId): bool
	{
		return $entityTypeId === CCrmOwnerType::SmartInvoice
			|| $entityTypeId === CCrmOwnerType::Quote
			|| CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
		;
	}

	protected function getResultTitle(): ?string
	{
		$entityTypeName = $this->getEntityTypeName();

		/**
		 * CRM_TYPE_MERGE_RESOLVER_DYNAMIC_RESULT_TITLE
		 * CRM_TYPE_MERGE_RESOLVER_SMART_INVOICE_RESULT_TITLE
		 * CRM_TYPE_MERGE_RESOLVER_QUOTE_RESULT_TITLE
		 */
		return Loc::getMessage("CRM_TYPE_MERGE_RESOLVER_{$entityTypeName}_RESULT_TITLE");
	}
}
