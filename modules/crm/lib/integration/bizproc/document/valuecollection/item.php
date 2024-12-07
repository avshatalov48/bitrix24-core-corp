<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

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
		$this->normalizeEntityBindings(['COMPANY_ID', 'CONTACT_ID']);
		$this->loadUserFieldValues();

		$this->document = Crm\Entity\CommentsHelper::prepareFieldsFromBizProc($this->typeId, $this->id, $this->document);
	}

	protected function appendDefaultUserPrefixes(): void
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->typeId);
		if (isset($factory))
		{
			$fieldMap = $factory->getFieldsMap();
			foreach ($factory->getFieldsInfo() as $fieldId => $field)
			{
				if ($field['TYPE'] === Crm\Field::TYPE_USER)
				{
					if (isset($this->document[$fieldId]))
					{
						$this->document[$fieldId] = 'user_' . $this->document[$fieldId];
					}
					elseif (isset($this->document[$fieldMap[$fieldId]]))
					{
						$this->document[$fieldMap[$fieldId]] = 'user_' . $this->document[$fieldMap[$fieldId]];
					}
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

	protected function loadTimeCreateValues(): void
	{
		$this->loadEntityValues();

		$culture = Application::getInstance()->getContext()->getCulture();

		$dateCreate = $this->document['CREATED_TIME'];
		$isCorrectDate = isset($dateCreate) && is_string($dateCreate) && DateTime::isCorrect($dateCreate);
		if ($isCorrectDate && $culture)
		{
			$dateCreateObject = new DateTime($dateCreate);
			$this->document['TIME_CREATE'] = $dateCreateObject->format($culture->getShortTimeFormat());
		}
	}
}
