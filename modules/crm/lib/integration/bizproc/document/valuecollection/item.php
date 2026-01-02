<?php

namespace Bitrix\Crm\Integration\BizProc\Document\ValueCollection;

use Bitrix\Crm;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;

class Item extends Base
{
	protected $item;

	protected function processField(string $fieldId): bool
	{
		if ($fieldId === 'CONTACTS')
		{
			$this->loadContacts();

			return true;
		}

		return false;
	}

	protected function getItem(): ?Crm\Item
	{
		if ($this->item === null)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory($this->typeId);
			if ($factory)
			{
				$this->item = $factory->getItem($this->id, $this->select);
			}
		}

		return $this->item;
	}

	protected function loadEntityValues(): void
	{
		if (isset($this->document['ID']))
		{
			return;
		}

		$this->prepareFieldGroups();
		$this->normalizeSelectFields();
		$this->addContactCompanyFields();
		$item = $this->getItem();
		$this->document = array_merge($this->document, isset($item) ? $item->getCompatibleData() : []);
		$this->loadAdditionalValues();
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

	protected function loadContacts(): void
	{
		$this->document['CONTACTS'] = [];

		$this->select = array_merge($this->select, ['CONTACTS']);
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
		$culture = Application::getInstance()->getContext()->getCulture();

		$factory = Crm\Service\Container::getInstance()->getFactory($this->typeId);
		if ($factory)
		{
			$fieldsMap = $factory->getFieldsMap();
			$dateCreate = $this->document[$fieldsMap['CREATED_TIME'] ?? \Bitrix\Crm\Item::FIELD_NAME_CREATED_TIME];
			$isCorrectDate = isset($dateCreate) && is_string($dateCreate) && DateTime::isCorrect($dateCreate);
			if ($isCorrectDate && $culture)
			{
				$dateCreateObject = new DateTime($dateCreate);
				$this->document['TIME_CREATE'] = $dateCreateObject->format($culture->getShortTimeFormat());
			}
		}
	}

	protected function processFieldDependencies(): void
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->typeId);
		if ($factory)
		{
			$fieldsMap = $factory->getFieldsMap();
			$dependencies = [
				'TIME_CREATE' => $fieldsMap['CREATED_TIME'] ?? \Bitrix\Crm\Item::FIELD_NAME_CREATED_TIME,
				'CREATED_BY_PRINTABLE' => $fieldsMap['CREATED_BY'] ?? \Bitrix\Crm\Item::FIELD_NAME_CREATED_BY,
				'CREATED_BY' => $fieldsMap['CREATED_BY'] ?? \Bitrix\Crm\Item::FIELD_NAME_CREATED_BY,
			];

			foreach ($dependencies as $field => $requiredField)
			{
				if (in_array($field, $this->fieldGroups['common'], true))
				{
					$this->select[] = $requiredField;
				}
			}

			$assignedFields = $this->extractFieldsByPrefix('ASSIGNED_BY');
			if ($assignedFields)
			{
				foreach ($assignedFields as $field)
				{
					$key = array_search('ASSIGNED_BY' . $field, $this->select, true);
					unset($this->select[$key]);
				}

				if (!in_array('ASSIGNED_BY_ID', $this->select, true))
				{
					$this->select[] = 'ASSIGNED_BY_ID';
				}
			}
		}
	}

	protected function loadAdditionalValues(): void
	{
		$this->appendDefaultUserPrefixes();
		$this->loadFmValues();
		$this->normalizeEntityBindings(['COMPANY_ID', 'CONTACT_ID']);
		$this->loadUserFieldValues();

		if (!$this->optimizationEnabled || in_array('CONTACTS', $this->select, true))
		{
			$this->loadContacts();
		}

		$this->loadCommonFieldValues();
	}

	protected function loadCreatedByPrintable(): void
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->typeId);
		if ($factory)
		{
			$fieldsMap = $factory->getFieldsMap();
			$createdByField = $fieldsMap['CREATED_BY'] ?? \Bitrix\Crm\Item::FIELD_NAME_CREATED_BY;
			if (isset($this->document[$createdByField]))
			{
				$user = $this->getUserValues($this->document[$createdByField]);
				if (!$user)
				{
					return;
				}

				$this->document['CREATED_BY_PRINTABLE'] = \CUser::FormatName(
					\CSite::GetNameFormat(false),
					[
						'LOGIN' => $user['LOGIN'] ?? '',
						'NAME' => $user['NAME'] ?? '',
						'LAST_NAME' => $user['LAST_NAME'] ?? '',
						'SECOND_NAME' => $user['SECOND_NAME'] ?? '',
					],
					true,
					false
				);
			}
		}
	}
}
