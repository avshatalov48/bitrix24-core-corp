<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Invoice extends Entity
{
	public function getTitle(): string
	{
		return \CCrmOwnerType::GetCategoryCaption($this->getTypeId());
	}

	public function getTypeName(): string
	{
		return \CCrmOwnerType::InvoiceName;
	}

	public function getStatusEntityId(): string
	{
		return 'INVOICE_STATUS';
	}

	public function getItemsSelectPreset(): array
	{
		return ['ID', 'STATUS_ID', 'DATE_INSERT', 'DATE_INSERT_FORMAT', 'PAY_VOUCHER_DATE', 'DATE_BILL', 'ORDER_TOPIC', 'PRICE', 'CURRENCY', 'UF_CONTACT_ID', 'UF_COMPANY_ID', 'RESPONSIBLE_ID'];
	}

	public function getGridId(): string
	{
		return 'KANBAN_V11_INVOICE';
	}

	public function getBaseFields(): array
	{
		// @todo move to \Bitrix\Crm\Service\Factory\Invoice::getFieldsInfo() when it will appear
		return \CCrmInvoice::GetFieldsInfo();
	}

	public function getFilterPresets(): array
	{
		$user = $this->getCurrentUserInfo();

		return [
			'filter_inv1_my' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_INV_MY'),
				'disallow_for_all' => true,
				'default' => true,
				'fields' => [
					'RESPONSIBLE_ID' => $user['id'],
					'RESPONSIBLE_ID_name' => $user['name'],
					'OVERDUE' => '',
					'DATE_PAY_BEFORE' => '',
					'PRICE' => '',
				],
			],
			'filter_inv2_overdue' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_INV_OVERDUE'),
				'disallow_for_all' => true,
				'fields' => [
					'RESPONSIBLE_ID' => $user['id'],
					'RESPONSIBLE_ID_name' => $user['name'],
					'OVERDUE' => 'Y',
					'DATE_PAY_BEFORE' => '',
					'PRICE' => ''
				]
			],
		];
	}

	public function getGridFilter(?string $filterId = null): array
	{
		$filter = [];

		$filter['OVERDUE'] = [
			'id' => 'OVERDUE',
			'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_OVERDUE_INVOICE'),
			'default' => true,
			'type' => 'list',
			'items' => [
				'Y' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_YES'),
				'N' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_NO')
			]
		];
		$filter['DATE_PAY_BEFORE'] = [
			'id' => 'DATE_PAY_BEFORE',
			'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_DATE_PAY_INVOICE'),
			'default' => true,
			'type' => 'date'
		];
		$filter['PRICE'] = [
			'id' => 'PRICE',
			'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_PRICE'),
			'default' => true,
			'type' => 'number'
		];
		$filter['RESPONSIBLE_ID'] = [
			'id' => 'RESPONSIBLE_ID',
			'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_ASSIGNED_BY_ID'),
			'default' => true,
			'type' => 'dest_selector',
			'params' => [
				'context' => 'CRM_KANBAN_FILTER_RESPONSIBLE_ID',
				'multiple' => 'Y',
				'contextCode' => 'U',
				'enableAll' => 'N',
				'enableSonetgroups' => 'N',
				'allowEmailInvitation' => 'N',
				'allowSearchEmailUsers' => 'N',
				'departmentSelectDisable' => 'Y',
				'isNumeric' => 'Y',
				'prefix' => 'U',
			]
		];
		$columns = PhaseSemantics::getListFilterInfo(false, null, true);
		$filter['STATUS_SEMANTIC_ID'] = array_merge(
			[
				'id' => 'STATUS_SEMANTIC_ID',
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_FLT_STATUS_SEMANTIC_ID'),
				'default' => false,
				'params' => [
					'multiple' => 'Y'
				]
			],
			$columns
		);

		return $filter;
	}

	public function getCurrency(): ?string
	{
		return \CCrmCurrency::getInvoiceDefault();
	}

	public function isInlineEditorSupported(): bool
	{
		return false;
	}

	public function isEntitiesLinksInFilterSupported(): bool
	{
		return true;
	}

	public function isRecurringSupported(): bool
	{
		return true;
	}

	public function isActivityCountersSupported(): bool
	{
		return false;
	}

	public function getCloseDateFieldName(): ?string
	{
		return 'DATE_PAY_BEFORE';
	}

	protected function getTotalSumFieldName(): string
	{
		return 'PRICE';
	}

	public function getAssignedByFieldName(): string
	{
		return 'RESPONSIBLE_ID';
	}

	public function hasOpenedField(): bool
	{
		return false;
	}

	protected function getDataToCalculateTotalSums(string $fieldSum, array $filter, array $runtime): array
	{
		$data = [];
		$res = \CCrmInvoice::GetList(
			[],
			$filter,
			['STATUS_ID', 'SUM' => $fieldSum],
			false,
			['STATUS_ID', $fieldSum]
		);
		while ($row = $res->fetch())
		{
			$data[] = $row;
		}

		return $data;
	}

	public function getFilterFieldNameByEntityTypeName(string $typeName): string
	{
		return 'UF_' . $typeName . '_ID';
	}

	public function prepareItemCommonFields(array $item): array
	{
		$item['TITLE'] = $item['ORDER_TOPIC'];
		$item['FORMAT_TIME'] = false;
		$item['DATE'] = $item['PAY_VOUCHER_DATE'] ?: $item['DATE_BILL'];
		$item['CONTACT_ID'] = $item['UF_CONTACT_ID'];
		$item['COMPANY_ID'] = $item['UF_COMPANY_ID'];
		$item['CURRENCY_ID'] = $item['CURRENCY'];

		$item = parent::prepareItemCommonFields($item);

		$item['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($item['PRICE'], $item['CURRENCY']);

		return $item;
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$statusParams = [];
		$statusParams['REASON_MARKED'] = $newStateParams['comment'] ?? '';
		$statusParams[$stageId === 'P' ? 'PAY_VOUCHER_DATE' : 'DATE_MARKED'] = $newStateParams['date'] ?? '';
		$statusParams['PAY_VOUCHER_NUM'] = $newStateParams['docnum'] ?? '';

		$result = new Result();

		$provider = '\CCrm'.$this->getTypeName();
		$entity = new $provider(false);
		$entity->SetStatus(
			$id,
			$stageId,
			$statusParams,
			[
				'SYNCHRONIZE_LIVE_FEED' => true
			]
		);
		if (!empty($entity->LAST_ERROR))
		{
			$result->addError(new Error($entity->LAST_ERROR));
		}

		return $result;
	}

	public function getFilterLazyLoadParams(): ?array
	{
		return null;
	}

	public function getTypeInfo(): array
	{
		return array_merge(
			parent::getTypeInfo(),
			[
				'canUseCallListInPanel' => true,
				'hasRestictionToMoveToWinColumn' => true,
				'useRequiredVisibleFields' => true,
				'showPersonalSetStatusNotCompletedText' => true,
				'kanbanItemClassName' => 'crm-kanban-item crm-kanban-item-invoice',
			]
		);
	}

	public function canAddItemToStage(string $stageId, \CCrmPerms $userPermissions, string $semantics = PhaseSemantics::UNDEFINED): bool
	{
		return ($this->getAddItemToStagePermissionType($stageId, $userPermissions) !== BX_CRM_PERM_NONE);
	}

	protected function getAddItemToStagePermissionType(string $stageId, \CCrmPerms $userPermissions): ?string
	{
		return $userPermissions->GetPermType(
			$this->getTypeName(),
			'ADD',
			["STATUS_ID{$stageId}"]
		);
	}

	public function isCategoriesSupported(): bool
	{
		return false;
	}

	public function havePermissionToDisplayColumnSum(string $stageId, \CCrmPerms $userPermissions): bool
	{
		return true;
	}
}
