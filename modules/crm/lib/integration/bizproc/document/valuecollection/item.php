<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;

class Item extends Base
{
	protected $item;

	protected function loadValue(string $fieldId): void
	{
		if ($fieldId === 'CONTACTS')
		{
			$this->loadContactValues();
		}
		else
		{
			$this->loadEntityValues();
		}
	}

	protected function getItem(): ?Crm\Item
	{
		if ($this->item === null)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory($this->typeId);
			$this->item = $factory->getItem($this->id);
		}

		return $this->item;
	}

	protected function loadEntityValues(): void
	{
		if (isset($this->document['ID']))
		{
			return;
		}

		$item = $this->getItem();

		$this->document = array_merge($this->document, isset($item) ? $item->getCompatibleData() : []);

		$this->appendDefaultUserPrefixes();
		$this->loadFmValues();
		$this->loadUserFieldValues();
	}

	protected function appendDefaultUserPrefixes(): void
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->typeId);
		if (isset($factory))
		{
			foreach ($factory->getFieldsInfo() as $fieldId => $field)
			{
				if ($field['TYPE'] === Crm\Field::TYPE_USER && isset($this->document[$fieldId]))
				{
					$this->document[$fieldId] = 'user_' . $this->document[$fieldId];
				}
			}
		}
	}

	protected function loadContactValues(): void
	{
		$this->document['CONTACTS'] = [];

		$item = $this->getItem();

		if ($item)
		{
			foreach ($item->getContacts() as $contact)
			{
				$this->document['CONTACTS'][] = $contact->getId();
			}
		}
	}
}
