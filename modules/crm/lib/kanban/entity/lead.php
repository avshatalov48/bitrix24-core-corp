<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Component\EntityList\FieldRestrictionManager;
use Bitrix\Crm\Component\EntityList\FieldRestrictionManagerTypes;
use Bitrix\Crm\Filter;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main\Result;
use Bitrix\Main\UI\Filter\Options;

class Lead extends Entity
{
	private FieldRestrictionManager $leadFieldRestrictionManager;

	public function __construct()
	{
		parent::__construct();

		$this->leadFieldRestrictionManager = new FieldRestrictionManager(
			FieldRestrictionManager::MODE_KANBAN,
			[FieldRestrictionManagerTypes::OBSERVERS],
			\CCrmOwnerType::Lead
		);
	}

	public function getTypeName(): string
	{
		return \CCrmOwnerType::LeadName;
	}

	public function isContactCenterSupported(): bool
	{
		return true;
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

	public function getFilterOptions(): Options
	{
		$options = parent::getFilterOptions();

		$this->leadFieldRestrictionManager->removeRestrictedFields($options);

		return $options;
	}

	public function getFieldsRestrictionsEngine(): string
	{
		$parentFieldsRestrictions = parent::getFieldsRestrictionsEngine();
		$leadFieldsRestrictions = $this->leadFieldRestrictionManager->fetchRestrictedFieldsEngine(
			$this->getGridId(),
			[],
			$this->getFilter()
		);

		return implode("\n", [$parentFieldsRestrictions, $leadFieldsRestrictions]);
	}

	public function getFieldsRestrictions(): array
	{
		$parentFieldsRestrictions = parent::getFieldsRestrictions();

		$leadFieldsRestrictions = $this->leadFieldRestrictionManager->getFilterFields(
			$this->getGridId(),
			[],
			$this->getFilter()
		);

		return [...$parentFieldsRestrictions, ...$leadFieldsRestrictions];
	}

	public function getItemsSelectPreset(): array
	{
		return [
			'ID',
			'STATUS_ID',
			'TITLE',
			'DATE_CREATE',
			'OPPORTUNITY',
			'OPPORTUNITY_ACCOUNT',
			'CURRENCY_ID',
			'ACCOUNT_CURRENCY_ID',
			'CONTACT_ID',
			'COMPANY_ID',
			'MODIFY_BY_ID',
			'IS_RETURN_CUSTOMER',
			'ASSIGNED_BY',
			Item::FIELD_NAME_LAST_ACTIVITY_TIME,
			Item::FIELD_NAME_LAST_ACTIVITY_BY,
		];
	}

	public function getFilterPresets(): array
	{
		return (new Filter\Preset\Lead())
			->setDefaultValues($this->getFilter()->getDefaultFieldIDs())
			->getDefaultPresets()
			;
	}

	public function isRestPlacementSupported(): bool
	{
		return true;
	}

	public function isActivityCountersFilterSupported(): bool
	{
		return $this->factory->isCountersEnabled();
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
		$result['CONVERSION_CONFIG'] = \Bitrix\Crm\Conversion\LeadConversionConfig::load();
		if ($result['CONVERSION_CONFIG'] === null)
		{
			$result['CONVERSION_CONFIG'] = \Bitrix\Crm\Conversion\LeadConversionConfig::getDefault();
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
		$item['PRICE'] = $item['OPPORTUNITY'] ?? null;
		$item['DATE'] = $item['DATE_CREATE'];
		$item['OBSERVER'] = $item['OBSERVER'] ?? null;

		return parent::prepareItemCommonFields($item);
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$result = $this->getItemViaLoadedItems($id);
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($stages[$stageId]['PROGRESS_TYPE'] === 'WIN')
		{
			return $result;
		}

		return parent::updateItemStage($id, $stageId, $newStateParams, $stages);
	}

	/**
	 * @return array
	 */
	public function getSemanticIds(): array
	{
		return [
			PhaseSemantics::PROCESS,
			PhaseSemantics::SUCCESS,
			PhaseSemantics::FAILURE,
		];
	}

	public function canAddItemToStage(string $stageId, \CCrmPerms $userPermissions, string $semantics = PhaseSemantics::UNDEFINED): bool
	{
		if ($semantics === PhaseSemantics::SUCCESS)
		{
			return false;
		}

		return parent::canAddItemToStage($stageId, $userPermissions, $semantics);
	}

	final protected function isItemsAssignedNotificationSupported(): bool
	{
		return true;
	}

	public function getPopupFields(string $viewType): array
	{
		$fields = parent::getPopupFields($viewType);
		foreach ($fields as $i => $field)
		{
			if (mb_strpos($field['NAME'], 'ACTIVITY_FASTSEARCH_') === 0)
			{
				unset($fields[$i]);
			}
		}

		return $fields;
	}

	protected function getHideSumForStagePermissionType(string $statusId, \CCrmPerms $userPermissions): ?string
	{
		return $userPermissions->GetPermType(
			$this->getTypeName(),
			'HIDE_SUM',
			["STATUS_ID{$statusId}"]
		);
	}

	public function skipClientField(array $row, string $code): bool
	{
		if (empty($row['CONTACT_TYPE']))
		{
			return false;
		}

		$clientFields = [
			Item::FIELD_NAME_NAME,
			Item::FIELD_NAME_SECOND_NAME,
			Item::FIELD_NAME_LAST_NAME,
			Item::FIELD_NAME_BIRTHDATE,
			\Bitrix\Crm\Item\Lead::FIELD_NAME_COMPANY_TITLE,
			Item::FIELD_NAME_POST,
		];

		return in_array($code, $clientFields, true);
	}
}
