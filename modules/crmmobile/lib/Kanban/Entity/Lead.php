<?php

namespace Bitrix\CrmMobile\Kanban\Entity;

use Bitrix\CrmMobile\Kanban\Kanban;

class Lead extends KanbanEntity
{
	public function getEntityType(): string
	{
		return \CCrmOwnerType::LeadName;
	}

	protected function prepareItemsResult(array &$items, Kanban $kanban): void
	{
		$kanban->getEntity()->appendMultiFieldData($items, $kanban->getAllowedFmTypes());

		foreach ($items as &$item)
		{
			if (!empty($item['client']))
			{
				continue;
			}

			$contactTypes = ['phone', 'email', 'im'];
			$hasContacts = false;
			foreach ($contactTypes as $contactType)
			{
				if (!empty($item[$contactType]))
				{
					$hasContacts = true;
					break;
				}
			}

			if (!$hasContacts)
			{
				return;
			}

			$data = [
				'title' => $item['name'],
				'type' => strtolower($kanban->getEntity()->getTypeName()),
				'hidden' => false,
				'phone' => [],
				'email' => [],
				'im' => [],
			];

			foreach ($contactTypes as $contactType)
			{
				if (empty($item[$contactType]))
				{
					continue;
				}

				foreach ($item[$contactType] as $contactItem)
				{
					$data[$contactType][] = [
						'value' => $contactItem['value'],
						'complexName' => $contactItem['title'],
					];
				}
			}

			$item['client']['lead'] = [$data];
		}

		unset($item);
	}
}
