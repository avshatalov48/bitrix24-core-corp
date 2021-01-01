<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Category\DealCategoryChangeError;
use Bitrix\Crm\Integration\Socialnetwork\Livefeed\CrmDeal;
use Bitrix\Crm\Recurring;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\UserField\Visibility\VisibilityManager;
use Bitrix\Crm\Filter;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\Date;

class Deal extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::DealName;
	}

	public function getStatusEntityId(): string
	{
		return DealCategory::getStatusEntityID($this->getCategoryId());
	}

	public function getItemsSelectPreset(): array
	{
		return ['ID', 'STAGE_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'EXCH_RATE', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'IS_REPEATED_APPROACH', 'IS_RETURN_CUSTOMER', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'ASSIGNED_BY', 'ORDER_STAGE'];
	}

	public function getGridId(): string
	{
		return 'CRM_DEAL_LIST_V12_C_' . $this->getCategoryId();
	}

	protected function getFilter(): Filter\Filter
	{
		if(!$this->filter)
		{
			$this->filter = Filter\Factory::createEntityFilter(
				new Filter\DealSettings([
					'ID' => $this->getGridId(),
					'categoryID' => $this->getCategoryId(),
				])
			);
		}

		return $this->filter;
	}

	public function getFilterPresets(): array
	{
		$user = $this->getCurrentUserInfo();

		return [
			'filter_in_work' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_DPR_WORK'),
				'default' => true,
				'fields' => [
					'STAGE_SEMANTIC_ID' => [
						\Bitrix\Crm\PhaseSemantics::PROCESS
					]
				]
			],
			'filter_my' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_DPR_WORK_MY'),
				'disallow_for_all' => true,
				'fields' => [
					'ASSIGNED_BY_ID_name' => $user['name'],
					'ASSIGNED_BY_ID' => $user['id'],
					'STAGE_SEMANTIC_ID' => [
						\Bitrix\Crm\PhaseSemantics::PROCESS
					]
				]
			],
			'filter_won' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_DPR_WON'),
				'fields' => [
					'STAGE_SEMANTIC_ID' => [
						\Bitrix\Crm\PhaseSemantics::SUCCESS,
					]
				]
			],
		];
	}

	public function isCategoriesSupported(): bool
	{
		return true;
	}

	public function isRestPlacementSupported(): bool
	{
		return true;
	}

	public function isActivityCountersFilterSupported(): bool
	{
		return true;
	}

	public function isRecurringSupported(): bool
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
		return $this->getAdditionalEditFieldsFromOptions();
	}

	public function getStageFieldName(): string
	{
		return 'STAGE_ID';
	}

	protected function getDetailComponentName(): ?string
	{
		return 'bitrix:crm.deal.details';
	}

	public function getPermissionParameters(\CCrmPerms $permissions): array
	{
		$result = parent::getPermissionParameters($permissions);

		$result['ACCESS_IMPORT'] = \CCrmDeal::CheckImportPermission($permissions);

		return $result;
	}

	protected function hasStageDependantRequiredFields(): bool
	{
		return true;
	}

	protected function getAddItemToStagePermissionType(string $stageId, \CCrmPerms $userPermissions): ?string
	{
		return \CCrmDeal::getStageCreatePermissionType(
			$stageId, $userPermissions, $this->categoryId
		);
	}

	public function getTableAlias(): string
	{
		return \CCrmDeal::TABLE_ALIAS;
	}

	public function prepareItemCommonFields(array $item): array
	{
		$item['PRICE'] = $item['OPPORTUNITY'];
		$item['DATE'] = $item['DATE_CREATE'];

		$item = parent::prepareItemCommonFields($item);

//		if ($item['DATE_CREATE'] instanceof Date)
//		{
//			$item['DATE_UNIX'] = $item['DATE_CREATE']->getTimestamp();
//		}
//		else
//		{
//			$item['DATE_UNIX'] = \MakeTimeStamp($item['DATE_CREATE']);
//		}

		return $item;
	}

	public function updateItemsCategory(array $ids, int $categoryId, \CCrmPerms $permissions): Result
	{
		$result = new Result();

		$categoryPermissions = \CCrmDeal::GetPermittedToReadCategoryIDs($permissions);
		if(!in_array($categoryId, $categoryPermissions))
		{
			return $result->addError(new Error('Access Denied'));
		}

		foreach($ids as $id)
		{
			if(!(
				$id > 0 &&
				\CCrmDeal::checkUpdatePermission($id, $permissions) &&
				\CCrmDeal::CheckCreatePermission($permissions, $categoryId)
			))
			{
				continue;
			}
			$recurringData = Recurring\Manager::getList(
				[
					'filter' => ['DEAL_ID' => $id],
					'limit' => 1
				],
				Recurring\Manager::DEAL
			);
			$options = null;
			if ($recurringData->fetch())
			{
				$options = ['REGISTER_STATISTICS' => false];
			}
			$error = \CCrmDeal::moveToCategory($id, $categoryId, $options);
			if ($error === DealCategoryChangeError::NONE)
			{
				$dbResult = \CCrmDeal::GetListEx(
					array(),
					array('=ID' => $id, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					['STAGE_ID', 'CATEGORY_ID']
				);
				$newFields = $dbResult->Fetch();
				$this->runAutomationOnUpdate($id, $newFields);
			}
		}

		return $result;
	}

	public function getCategories(\CCrmPerms $permissions): array
	{
		$result = [];

		$categoryPermissions = array_fill_keys(
			\CCrmDeal::GetPermittedToReadCategoryIDs($permissions),
			true
		);
		foreach (DealCategory::getAll(true) as $id => $category)
		{
			if (isset($categoryPermissions[$category['ID']]))
			{
				$result[$id] = $category;
			}
		}

		return $result;
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): Result
	{
		$result = new Result();

		$item = $this->loadedItems[$id] ?? $this->getItem($id);
		if(!$item)
		{
			return $result->addError(new Error('Deal not found'));
		}

		$stageCategoryID = (int) DealCategory::resolveFromStageID($stageId);
		$dealCategoryID = (int) $item['CATEGORY_ID'];
		if($dealCategoryID !== $stageCategoryID)
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_DEAL_STAGE_MISMATCH')));
		}

		return parent::updateItemStage($id, $stageId, $newStateParams, $stages);
	}

	public function getFilterLazyLoadParams(): ?array
	{
		$path = '/bitrix/components/bitrix/crm.deal.list/filter.ajax.php'
			. '?filter_id='.urlencode($this->getGridId()) . '&category_id=' . $this->getCategoryId() . '&is_recurring=N&siteID=' . SITE_ID . '&' . bitrix_sessid_get();

		return [
			'GET_LIST' => $path . '&action=list',
			'GET_FIELD' => $path . '&action=field'
		];
	}

	/**
	 * @param array $data
	 * @return string
	 */
	protected function getColumnId(array $data): string
	{
		return $data['STAGE_ID'];
	}
}