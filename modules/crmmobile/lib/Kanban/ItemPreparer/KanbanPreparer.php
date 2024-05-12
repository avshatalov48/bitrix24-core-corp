<?php

namespace Bitrix\CrmMobile\Kanban\ItemPreparer;

use Bitrix\Crm\Item;
use Bitrix\CrmMobile\Kanban\Dto\Badge;
use Bitrix\CrmMobile\Kanban\Dto\Field;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

final class KanbanPreparer extends Base
{
	protected function getItemId(array $item): int
	{
		return $item['id'];
	}

	protected function getColumnId(array $item): ?string
	{
		return $item['columnId'];
	}

	protected function getItemName(array $item): string
	{
		return $item['name'];
	}

	protected function getItemDate(array $item): ?int
	{
		if (!isset($item['dateCreate']))
		{
			return null;
		}

		return \CCrmDateTimeHelper::getServerTime(new DateTime($item['dateCreate']))->getTimestamp();
	}

	protected function getItemDateFormatted(array $item): string
	{
		return ($item['date'] ?? '');
	}

	protected function getItemPrice(array $item): ?float
	{
		return $item['price'];
	}

	protected function prepareFields(array $item = [], array $params = []): array
	{
		$preparedFields = [];

		$fields = ($item['fields'] ?? []);
		foreach ($fields as $field)
		{
			if (!isset($field['value']) || $this->isExcludedField($field['code']))
			{
				continue;
			}

			$config = ($field['config'] ?? []);
			if (isset($field['icon']))
			{
				$config['titleIcon']['before']['uri'] = $field['icon']['url'];
			}

			$dtoField = Field::make([
				'name' => $field['code'],
				'title' => $field['title'],
				'type' => $field['type'],
				'value' => $field['value'],
				'config' => $config,
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
		return array_map(static fn(array $badge): Badge => Badge::make($badge), $badges);
	}

	protected function getItemReturn(array $item): bool
	{
		return ($item['return'] ?? false);
	}

	protected function getItemReturnApproach(array $item): bool
	{
		return ($item['returnApproach'] ?? false);
	}

	protected function getSubTitleText(array $item): string
	{
		if ($item['returnApproach'] && $this->entityTypeId === \CCrmOwnerType::Deal)
		{
			return Loc::getMessage('M_CRM_KANBAN_ENTITY_REPEATED_APPROACH_' . $this->getEntityType()) ?? '';
		}

		if (
			$item['return']
			&& ($this->entityTypeId === \CCrmOwnerType::Deal || $this->entityTypeId === \CCrmOwnerType::Lead)
		)
		{
			return Loc::getMessage('M_CRM_KANBAN_ITEM_PREPARER_REPEATED_' . $this->getEntityType()) ?? '';
		}

		return '';
	}

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

	protected function getAssignedById(array $item): ?int
	{
		return $item['assignedBy'];
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
}
