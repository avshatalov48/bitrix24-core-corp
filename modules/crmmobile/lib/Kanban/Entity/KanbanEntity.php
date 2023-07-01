<?php


namespace Bitrix\CrmMobile\Kanban\Entity;


use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\EntityBadge;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\CrmMobile\Kanban\ClientDataProvider;
use Bitrix\CrmMobile\Kanban\Dto\Badge;
use Bitrix\CrmMobile\Kanban\GridId;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\CrmMobile\Kanban\Entity;
use Bitrix\CrmMobile\Kanban\Kanban;
use Bitrix\Main\UI\Filter\Options;

abstract class KanbanEntity extends Entity
{
	public function isUseColumns(): bool
	{
		return true;
	}

	public function updateItemStage(int $id, int $stageId): Result
	{
		$kanban = $this->getKanbanInstance();
		$stages = $kanban->getStatuses(true);

		$statusId = null;
		foreach ($stages as $stage)
		{
			if ((int)$stage['ID'] === $stageId)
			{
				$statusId = $stage['STATUS_ID'];
				break;
			}
		}

		if (!$statusId)
		{
			$result = new Result();
			$result->addError(new Error('Column: ' . $stageId . ' not found'));
			return $result;
		}

		return $kanban->getEntity()->updateItemStage($id, $statusId, [], $stages);
	}

	public function getList(): array
	{
		$contactDataProvider = new ClientDataProvider(\CCrmOwnerType::Contact);
		$companyDataProvider = new ClientDataProvider(\CCrmOwnerType::Company);

		$contactDataProvider->addFieldsToSelect([
			'EMAIL',
			'PHONE',
			'IM'
		]);
		$companyDataProvider->addFieldsToSelect([
			'EMAIL',
			'PHONE',
			'IM'
		]);

		$kanban = $this
			->getKanbanInstance()
			->setFieldsContext(Field::MOBILE_CONTEXT)
			->setContactEntityDataProvider($contactDataProvider)
			->setCompanyEntityDataProvider($companyDataProvider)
		;

		$filterParams = ($this->params['filterParams'] ?? []);
		$statusId = ($filterParams['stageId'] ?? null);
		$searchContent = (string)($this->params['filter']['search'] ?? '');
		$filter = ['SEARCH_CONTENT' => $searchContent];

		$entityTypeId = $this->getEntityTypeId();
		$entity = $kanban->getEntity()->setGridIdInstance(new GridId($entityTypeId), $entityTypeId);
		$stageFieldName = $entity->getStageFieldName();

		if($statusId)
		{
			$filter[$stageFieldName] = $statusId;
		}
		elseif (empty($filterParams['FILTER_PRESET_ID']))
		{
			$filterParams['FORCE_FILTER'] = 'Y';
		}

		if (isset($filterParams['ID']))
		{
			$filter['@ID'] = $filterParams['ID'];
		}

		$this->prepareFilter($entity);

		$currentPage = ($this->pageNavigation ? $this->pageNavigation->getCurrentPage() : 1);
		$itemsResult = $kanban->getItems($filter, $currentPage, ['filter' => $filterParams]);
		$this->prepareItemsResult($itemsResult['ITEMS'], $kanban);

		$entityAttributes = $this->getEntityAttributes($itemsResult['ITEMS']);
		$this->prepareActivityCounters($itemsResult['ITEMS']);
		$this->prepareItemsBadges($itemsResult['ITEMS']);

		/*
		 * Temporarily hidden the ability to render time under the option for demo.
		 * Most likely this will not be required and the time will not be rendered constantly
		 */
		$renderLastActivityTime = (Option::get('crmmobile', 'render_last_activity_time_in_kanban_items', 'N') === 'Y');

		$items = [];
		foreach ($itemsResult['ITEMS'] as $item)
		{
			$preparedItem = $this->prepareItem($item, [
				'permissionEntityAttributes' => $entityAttributes,
				'renderLastActivityTime' => $renderLastActivityTime,
			]);
			$items[] = $this->buildItemDto($preparedItem);
		}

		return [
			'items' => $items,
		];
	}

	protected function prepareItemsResult(array &$items, Kanban $kanban): void
	{

	}

	public function prepareFilter(\Bitrix\Crm\Kanban\Entity $entity): void
	{
		$filterParams = ($this->params['filterParams'] ?? []);
		$presetId = ($filterParams['FILTER_PRESET_ID'] ?? null);

		if ($presetId === null)
		{
			return;
		}

		$this->setFilterPreset($presetId, $entity->getFilterOptions());
	}

	protected function prepareItemsBadges(array &$items): void
	{
		if (empty($items))
		{
			return;
		}

		$entityBadges = new EntityBadge(
			$this->getEntityTypeId(),
			array_keys($items)
		);
		$entityBadges->appendToEntityItems($items);
	}

	protected function getItemId(array $item): int
	{
		return $item['id'];
	}

	protected function getPermissionEntityTypeName(): string
	{
		$filterParams = ($this->params['filterParams'] ?? []);
		$categoryId = ($filterParams['CATEGORY_ID'] ?? 0);

		return (new PermissionEntityTypeHelper($this->getEntityTypeId()))
			->getPermissionEntityTypeForCategory($categoryId)
		;
	}

	protected function getColumnId(array $item): ?string
	{
		return $item['columnId'];
	}

	protected function getItemDate(array $item): int
	{
		return \CCrmDateTimeHelper::getServerTime(new DateTime($item['dateCreate']))->getTimestamp();
	}

	protected function getItemDateFormatted(array $item): string
	{
		return $item['date'];
	}

	/**
	 * @param array $item
	 * @return bool
	 */
	protected function getItemReturn(array $item): bool
	{
		return ($item['return'] ?? false);
	}

	/**
	 * @param array $item
	 * @return bool
	 */
	protected function getItemReturnApproach(array $item): bool
	{
		return ($item['returnApproach'] ?? false);
	}

	protected function getItemName(array $item): string
	{
		return $item['name'];
	}

	protected function getLastActivityTimestamp(array $item): ?int
	{
		$timestamp = ($item['lastActivity']['timestamp'] ?? null);
		if (!$timestamp)
		{
			return null;
		}

		return \CCrmDateTimeHelper::getServerTime(DateTime::createFromTimestamp($timestamp))->getTimestamp();
	}

	protected function getItemPrice(array $item): ?float
	{
		return $item['price'];
	}

	protected function getMoney(array $item): ?array
	{
		if (!$this->hasVisibleField($item, Item::FIELD_NAME_OPPORTUNITY))
		{
			return null;
		}

		return [
			'amount' => (float)$item['entity_price'],
			'currency' => $item['entity_currency'],
		];
	}

	protected function getSubTitleText(array $item): string
	{
		if ($item['returnApproach'])
		{
			return Loc::getMessage('M_CRM_KANBAN_ENTITY_REPEATED_APPROACH_' . $this->getEntityType());
		}


		if ($item['return'])
		{
			return Loc::getMessage('M_CRM_KANBAN_ENTITY_REPEATED_2_' . $this->getEntityType());
		}

		return '';
	}

	/**
	 * @param array $item
	 * @return array
	 */
	protected function getDescriptionRow(array $item): array
	{
		$descriptionItems = [];

		$client = null;
		if (!empty($item['companyName']))
		{
			$client = $item['companyName'];
		}
		elseif(!empty($item['contactName']))
		{
			$client = $item['contactName'];
		}

		$descriptionItems[] = [
			'type' => 'string',
			'value' => $client,
		];

		$descriptionItems[] = [
			'type' => 'money',
			'value' => $this->getMoney($item),
		];

		return $descriptionItems;
	}

	/**
	 * @param array $item
	 * @param array $params
	 * @return \Bitrix\CrmMobile\Kanban\Dto\Field[]
	 */
	protected function prepareFields(array $item = [], array $params = []): array
	{
		$preparedFields = [];

		$fields = ($item['fields'] ?? []);
		foreach ($fields as $field)
		{
			if ($this->isExcludedField($field['code']) || !isset($field['value']))
			{
				continue;
			}

			$config = ($field['config'] ?? []);
			if (isset($field['icon']))
			{
				$config['titleIcon']['before']['uri'] = $field['icon']['url'];
			}

			$dtoField = new \Bitrix\CrmMobile\Kanban\Dto\Field([
				'name' => $field['code'],
				'title' => $field['title'],
				'type' => $field['type'],
				'value' =>  $field['value'],
				'config' =>  $config,
				'multiple' => $field['isMultiple'],
			]);

			$this->prepareField($dtoField);
			$preparedFields[] = $dtoField;
		}

		return $preparedFields;
	}

	protected function prepareBadges(array $item = [], array $params = []): array
	{
		$badges = ($item['badges'] ?? []);
		return array_map(static fn(array $badge): Badge => new Badge($badge), $badges);
	}

	public function deleteItem(int $id, array $params = []): Result
	{
		$this->getKanbanInstance()->getEntity()->deleteItems([$id], false, null, $params);

		// I think that after switching to a new api in kanban,
		// we will be able to receive the result of the deletion
		return new Result();
	}

	public function changeCategory(array $ids, int $categoryId): Result
	{
		$kanban = $this->getKanbanInstance();
		$userPermissions = $kanban->getCurrentUserPermissions();

		return $kanban->getEntity()->updateItemsCategory($ids, $categoryId, $userPermissions);
	}

	protected function getGridId(): string
	{
		$kanban = $this->getKanbanInstance();
		return $kanban->getEntity()->getGridId();
	}

	/**
	 * @return Kanban
	 */
	protected function getKanbanInstance(): Kanban
	{
		return Kanban::getInstance(
			$this->getEntityType(),
			$this->getFilterParams()
		);
	}

	protected function getAssignedById(array $item): ?int
	{
		return $item['assignedBy'];
	}
}
