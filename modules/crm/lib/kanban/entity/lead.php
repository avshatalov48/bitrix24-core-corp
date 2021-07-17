<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Settings\LeadSettings;

class Lead extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::LeadName;
	}

	public function getStatusEntityId(): string
	{
		return 'STATUS';
	}

	public function isContactCenterSupported(): bool
	{
		return true;
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}

	public function getTypeInfo(): array
	{
		return array_merge(
			parent::getTypeInfo(),
			[
				'disableMoveToWin' => true,
				'canShowPopupForLeadConvert' => true,
				'showPersonalSetStatusNotCompletedText' => true,
				'hasPlusButtonTitle' => true,
				'hasRestictionToMoveToWinColumn' => true,
				'isRecyclebinEnabled' => LeadSettings::getCurrent()->isRecycleBinEnabled(),

				'canUseIgnoreItemInPanel' => true,
				'canUseCreateTaskInPanel' => true,
				'canUseCallListInPanel' => true,
				'canUseMergeInPanel' => true,

				'stageIdKey' => 'STATUS_ID',
				'defaultQuickFormFields' => [
					'TITLE',
					'CLIENT',
				],
			]
		);
	}

	public function getItemsSelectPreset(): array
	{
		return ['ID', 'STATUS_ID', 'TITLE', 'DATE_CREATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'IS_RETURN_CUSTOMER', 'ASSIGNED_BY'];
	}

	public function getFilterPresets(): array
	{
		$user = $this->getCurrentUserInfo();

		$defaultFilter = [
			'SOURCE_ID' => [],
			'STATUS_ID' => [],
			'COMMUNICATION_TYPE' => [],
			'DATE_CREATE' => '',
			'ASSIGNED_BY_ID' => ''
		];

		return [
			'filter_my_in_work' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_LPR_MY_WORK'),
				'disallow_for_all' => true,
				'fields' => array_merge(
					$defaultFilter,
					[
						'ASSIGNED_BY_ID_name' => $user['name'],
						'ASSIGNED_BY_ID' => $user['id'],
						'STATUS_SEMANTIC_ID' => [
							\Bitrix\Crm\PhaseSemantics::PROCESS
						]
					]
				),
			],
			'filter_in_work' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_LPR_WORK'),
				'default' => true,
				'fields' => array_merge(
					$defaultFilter,
					[
						'STATUS_SEMANTIC_ID' => [
							\Bitrix\Crm\PhaseSemantics::PROCESS
						]
					]
				)
			],
			'filter_closed' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_LPR_CLOSED'),
				'fields' => array_merge(
					$defaultFilter,
					[
						'STATUS_SEMANTIC_ID' => [
							\Bitrix\Crm\PhaseSemantics::SUCCESS,
							\Bitrix\Crm\PhaseSemantics::FAILURE
						]
					]
				)
			],
		];
	}

	public function isRestPlacementSupported(): bool
	{
		return true;
	}

	public function isActivityCountersFilterSupported(): bool
	{
		return true;
	}

	public function isExclusionSupported(): bool
	{
		return true;
	}

	public function isNeedToRunAutomation(): bool
	{
		return true;
	}

	public function isDeleteAfterExclusion(): bool
	{
		return true;
	}

	public function getOwnMultiFieldsClientType(): ?string
	{
		return 'lead';
	}

	protected function getDefaultAdditionalSelectFields(): array
	{
		return [
			'TITLE' => '',
			'OPPORTUNITY' => '',
			'DATE_CREATE' => '',
			'ORDER_STAGE' => '',
			'CLIENT' => '',
			'PROBLEM_NOTIFICATION' => '',
		];
	}

	public function getAdditionalEditFields(): array
	{
		return (array)$this->getAdditionalEditFieldsFromOptions();
	}

	protected function getDetailComponentName(): ?string
	{
		return 'bitrix:crm.lead.details';
	}

	public function getPermissionParameters(\CCrmPerms $permissions): array
	{
		$result = parent::getPermissionParameters($permissions);

		$result['ACCESS_IMPORT'] = \CCrmLead::CheckImportPermission($permissions);
		$result['CAN_CONVERT_TO_CONTACT'] = \CCrmContact::CheckCreatePermission($permissions);
		$result['CAN_CONVERT_TO_COMPANY'] = \CCrmCompany::CheckCreatePermission($permissions);
		$result['CAN_CONVERT_TO_DEAL'] = \CCrmDeal::CheckCreatePermission($permissions);
		$result['CONVERSION_CONFIG'] = \Bitrix\Crm\Conversion\DealConversionConfig::load();
		if ($result['CONVERSION_CONFIG'] === null)
		{
			$result['CONVERSION_CONFIG'] = \Bitrix\Crm\Conversion\DealConversionConfig::getDefault();
		}

		return $result;
	}

	protected function hasStageDependantRequiredFields(): bool
	{
		return true;
	}

	protected function getAddItemToStagePermissionType(string $stageId, \CCrmPerms $userPermissions): ?string
	{
		return \CCrmLead::GetStatusCreatePermissionType(
			$stageId, $userPermissions
		);
	}

	public function getTableAlias(): string
	{
		return \CCrmLead::TABLE_ALIAS;
	}

	public function prepareItemCommonFields(array $item): array
	{
		$item['PRICE'] = $item['OPPORTUNITY'];
		$item['DATE'] = $item['DATE_CREATE'];

		$item = parent::prepareItemCommonFields($item);

		return $item;
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$result = new Result();

		$item = $this->loadedItems[$id] ?? $this->getItem($id);
		if(!$item)
		{
			return $result->addError(new Error('Invoice not found'));
		}
		if ($stages[$item[$this->getStageFieldName()]]['PROGRESS_TYPE'] === 'WIN')
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_LEAD_ALREADY_CONVERTED')));
			return $result;
		}
		if ($stages[$stageId]['PROGRESS_TYPE'] === 'WIN')
		{
			return $result;
		}

		return parent::updateItemStage($id, $stageId, $newStateParams, $stages);
	}

	/**
	 * @param array $data
	 * @return string
	 */
	protected function getColumnId(array $data): string
	{
		return ($data['STATUS_ID'] ?? '');
	}
}
