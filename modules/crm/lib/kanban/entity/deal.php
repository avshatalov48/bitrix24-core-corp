<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Category\DealCategoryChangeError;
use Bitrix\Crm\Deal\PaymentsRepository;
use Bitrix\Crm\Deal\ShipmentsRepository;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Recurring;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Filter;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\DealSettings;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Crm\Component\EntityList\ClientDataProvider;
use Bitrix\Crm\Service\Display\Field;

class Deal extends Entity
{
	public function getTypeName(): string
	{
		return \CCrmOwnerType::DealName;
	}

	public function getItemsSelectPreset(): array
	{
		return ['ID', 'STAGE_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'EXCH_RATE', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'IS_REPEATED_APPROACH', 'IS_RETURN_CUSTOMER', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'ASSIGNED_BY'];
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
				'canUseIgnoreItemInPanel' => true,
				'hasPlusButtonTitle' => true,
				'showPersonalSetStatusNotCompletedText' => true,
				'isRecyclebinEnabled' => DealSettings::getCurrent()->isRecycleBinEnabled(),
				'canUseCreateTaskInPanel' => true,
				'canUseCallListInPanel' => true,
				'canUseMergeInPanel' => true,
			]
		);
	}

	public function getFilterOptions(): Options
	{
		$options = parent::getFilterOptions();
		$clientFieldsRestrictionManager = new \Bitrix\Crm\Component\EntityList\ClientFieldRestrictionManager();
		$clientFieldsRestrictionManager->removeRestrictedFieldsFromFilter($options);

		return $options;
	}

	protected function getFilter(): Filter\Filter
	{
		if(!$this->filter)
		{
			$flags = \Bitrix\Crm\Filter\DealSettings::FLAG_NONE | \Bitrix\Crm\Filter\DealSettings::FLAG_ENABLE_CLIENT_FIELDS;
			$this->filter = Filter\Factory::createEntityFilter(
				new Filter\DealSettings([
					'ID' => $this->getGridId(),
					'categoryID' => $this->getCategoryId(),
					'flags' => $flags,
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
			'filter_closed' => [
				'name' => Loc::getMessage('CRM_KANBAN_HELPER_DPR_WON'),
				'fields' => [
					'STAGE_SEMANTIC_ID' => [
						[\Bitrix\Crm\PhaseSemantics::SUCCESS, \Bitrix\Crm\PhaseSemantics::FAILURE]
					]
				]
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
			'PAYMENT_STAGE' => '',
			'DELIVERY_STAGE' => '',
			'CLIENT' => '',
			'PROBLEM_NOTIFICATION' => '',
			'OBSERVER' => '',
		];
	}

	public function getAdditionalEditFields(): array
	{
		return (array)$this->getAdditionalEditFieldsFromOptions();
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

		return $item;
	}

	public function appendRelatedEntitiesValues(array $items, array $selectedFields): array
	{
		$items = parent::appendRelatedEntitiesValues($items, $selectedFields);
		$dealIds = array_keys($items);

		if (in_array('DELIVERY_STAGE', $selectedFields))
		{
			$shipmentStages = (new ShipmentsRepository())->getShipmentStages($dealIds);
			foreach ($items as $itemId => $item)
			{
				if (isset($shipmentStages[$itemId]))
				{
					$items[$itemId]['DELIVERY_STAGE'] = \CCrmViewHelper::RenderDealDeliveryStageControl(
						$shipmentStages[$itemId],
						'crm-kanban-item-status'
					);
				}
			}
		}

		if (in_array('PAYMENT_STAGE', $selectedFields))
		{
			$paymentStages = (new PaymentsRepository())->getPaymentStages($dealIds);
			foreach ($items as $itemId => $item)
			{
				if (isset($paymentStages[$itemId]))
				{
					$items[$itemId]['PAYMENT_STAGE'] = \CCrmViewHelper::RenderDealPaymentStageControl(
						$paymentStages[$itemId],
						'crm-kanban-item-status'
					);
				}
			}
		}

		return $items;
	}

	protected function getExtraDisplayedFields()
	{
		$result = parent::getExtraDisplayedFields();
		$result['DELIVERY_STAGE'] =
			(Field::createByType('string', 'DELIVERY_STAGE'))
				->setDisplayParams(['VALUE_TYPE' => 'html']);

		$result['PAYMENT_STAGE'] =
			(Field::createByType('string', 'PAYMENT_STAGE'))
				->setDisplayParams(['VALUE_TYPE' => 'html']);

		return $result;
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
			$categoryId = $category['ID'];
			if (isset($categoryPermissions[$categoryId]))
			{
				$category['url'] = Container::getInstance()->getRouter()->getKanbanUrl($this->getTypeId(), $categoryId);
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
		return ($data['STAGE_ID'] ?? '');
	}

	public function getClientFieldsRestrictions(): ?array
	{
		$clientFieldsRestrictionManager = new \Bitrix\Crm\Component\EntityList\ClientFieldRestrictionManager();
		if (!$clientFieldsRestrictionManager->hasRestrictions())
		{
			return null;
		}

		return [
			'callback' => $clientFieldsRestrictionManager->getJsCallback(),
			'filterId' =>  $this->getGridId(),
			'filterFields' => $clientFieldsRestrictionManager->getRestrictedFilterFields($this->getFilter()),
		];
	}

	public function getGridFilter(): array
	{
		$result = parent::getGridFilter();

		$filterFieldsValues = $this->getFilterOptions()->GetFilter($result);
		$this->getContactDataProvider()->prepareFilter($result, $filterFieldsValues);
		$this->getCompanyDataProvider()->prepareFilter($result, $filterFieldsValues);

		return $result;
	}

	public function getItems(array $parameters): \CDBResult
	{
		if (isset($parameters['select']))
		{
			$this->getContactDataProvider()->prepareSelect($parameters['select']);
			$this->getCompanyDataProvider()->prepareSelect($parameters['select']);
		}

		return parent::getItems($parameters);
	}

	public function getPopupFields(string $viewType): array
	{
		$fields = parent::getPopupFields($viewType);
		foreach ($fields as $i => $field)
		{
			if (mb_strpos($field['NAME'], 'CONTACT_') === 0 || mb_strpos($field['NAME'], 'COMPANY_') === 0)
			{
				unset($fields[$i]);
			}

			if (
				$viewType === static::VIEW_TYPE_EDIT
				&& in_array(
					$field['NAME'],
					[
						'ORDER_STAGE', 'DELIVERY_STAGE', 'PAYMENT_STAGE', 'PAYMENT_PAID', 'ORDER_SOURCE'
					]
				)
			)
			{
				unset($fields[$i]);
			}
		}

		if ($viewType !== static::VIEW_TYPE_EDIT)
		{
			if (ClientDataProvider::getPriorityEntityTypeId() === \CCrmOwnerType::Contact)
			{
				$firstProvider = $this->getContactDataProvider();
				$secondProvider = $this->getCompanyDataProvider();
			}
			else
			{
				$firstProvider = $this->getCompanyDataProvider();
				$secondProvider = $this->getContactDataProvider();
			}
			$fields = array_merge(
				$fields,
				$firstProvider->getPopupFields(),
				$secondProvider->getPopupFields(),
			);
		}

		return $fields;
	}

	protected function prepareFieldsSections(array $configuration): array
	{
		$sections = parent::prepareFieldsSections($configuration);

		$contactSection = [
			'name' => 'contact_fields',
			'title' => Loc::getMessage('CRM_KANBAN_FIELD_SECTION_CONTACTS'),
			'type' => 'section',
			'elementsRule' => '^CONTACT\_' // js RegExp
		];
		$companySection = [
			'name' => 'company_fields',
			'title' => Loc::getMessage('CRM_KANBAN_FIELD_SECTION_COMPANIES'),
			'type' => 'section',
			'elementsRule' => '^COMPANY\_' // js RegExp
		];
		if (ClientDataProvider::getPriorityEntityTypeId() === \CCrmOwnerType::Contact)
		{
			$sections[] = $contactSection;
			$sections[] = $companySection;
		}
		else
		{
			$sections[] = $companySection;
			$sections[] = $contactSection;
		}

		return $sections;
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
}
