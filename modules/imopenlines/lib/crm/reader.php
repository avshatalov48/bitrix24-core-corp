<?php

namespace Bitrix\ImOpenLines\Crm;

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;

use Bitrix\Crm\Service;
use Bitrix\Crm\Item;

use Bitrix\ImOpenLines\Error;

class Reader
{
	private const ERROR_NO_CRM = 'IMOL_CRM_READER_ERROR_NO_CRM';

	/** @var ErrorCollection */
	public $errorCollection;
	/** @var array */
	private $typedEntities = [];
	/** @var array */
	private $contactIds = [];
	/** @var array */
	private $companyIds = [];
	/** @var array */
	private $contactsInfo = [];
	/** @var array */
	private $companiesInfo = [];
	/** @var array */
	private $relations = [];
	/** @var array */
	private $multiFields = [];
	/** @var array */
	private $result = [];

	public function __construct()
	{
		$this->errorCollection = new ErrorCollection();
		if (!Loader::includeModule('crm'))
		{
			$this->errorCollection->setError(new Error('CRM module is not installed', self::ERROR_NO_CRM));
		}
	}

	/**
	 * Prepare entities: convert string entity types to number, group by type and extract IDs
	 * @param array $entities
	 *
	 * @return self
	 */
	private function prepareEntities(array $entities): self
	{
		$mappedEntities = array_map(static function($entity){
			if (is_string($entity['ENTITY_TYPE']))
			{
				$entity['ENTITY_TYPE_ID'] = \CCrmOwnerType::ResolveID($entity['ENTITY_TYPE']);
			}
			else
			{
				$entity['ENTITY_TYPE_ID'] = $entity['ENTITY_TYPE'];
			}

			return $entity;
		}, $entities);

		$this->typedEntities = [];
		foreach ($mappedEntities as $entity)
		{
			$this->typedEntities[$entity['ENTITY_TYPE_ID']][$entity['ENTITY_ID']] = $entity['ENTITY_ID'];
		}

		$this->contactIds = $this->typedEntities[\CCrmOwnerType::Contact] ?? [];
		$this->companyIds = $this->typedEntities[\CCrmOwnerType::Company] ?? [];

		return $this;
	}

	/**
	 * Get all types except contacts and companies
	 */
	private function getNonContactsAndCompanies(): self
	{
		foreach ($this->typedEntities as $entityTypeId => $entityIds)
		{
			if (
				$entityTypeId === \CCrmOwnerType::Contact
				|| $entityTypeId === \CCrmOwnerType::Company
			)
			{
				continue;
			}
			$factory = Service\Container::getInstance()->getFactory($entityTypeId);
			if (!$factory)
			{
				continue;
			}
			$items = $factory->getItems([
				'select' => ['*', Item::FIELD_NAME_CONTACT_BINDINGS],
				'filter' => [
					'@ID' => $entityIds,
				],
			]);

			foreach ($items as $item)
			{
				$identifier = \Bitrix\Crm\ItemIdentifier::createByItem($item);
				$compatibleData = $item->getCompatibleData();
				$this->result[$item->getEntityTypeId()][$item->getId()] = $compatibleData;
				foreach ($compatibleData['CONTACT_BINDINGS'] as $contactBinding)
				{
					// add related contacts
					$this->contactIds[] = $contactBinding['CONTACT_ID'];
					$this->relations[\CCrmOwnerType::Contact][$contactBinding['CONTACT_ID']][] = $identifier;
				}
				if ($item->hasField(Item::FIELD_NAME_COMPANY_ID))
				{
					// add related companies
					$companyId = (int)$item->getCompanyId();
					if ($companyId > 0)
					{
						$this->companyIds[] = $companyId;
						$this->relations[\CCrmOwnerType::Company][$companyId][] = $identifier;
					}
				}
				if ($item->getEntityTypeId() === \CCrmOwnerType::Lead)
				{
					$this->multiFields[] = [
						'NAME' => \CCrmOwnerType::LeadName,
						'ID' => $item->getId(),
					];
				}
			}
		}

		return $this;
	}

	/**
	 * Get contacts info
	 * @throws \Exception
	 */
	private function getContacts(): self
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($this->contactIds);
		if (empty($this->contactIds))
		{
			return $this;
		}

		$factory = Service\Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
		if (!$factory)
		{
			throw new \Exception('factory for ' . \CCrmOwnerType::Contact . ' not found');
		}
		$items = $factory->getItems([
			'filter' => [
				'@ID' => $this->contactIds,
			],
		]);
		foreach ($items as $item)
		{
			$compatibleData = $item->getCompatibleData();
			$this->contactsInfo[$item->getId()] = $compatibleData;
			if (isset($this->typedEntities[$item->getEntityTypeId()][$item->getId()]))
			{
				$this->result[$item->getEntityTypeId()][$item->getId()] = $compatibleData;
			}
			$this->multiFields[] = [
				'NAME' => \CCrmOwnerType::ContactName,
				'ID' => $item->getId(),
			];
		}

		return $this;
	}

	/**
	 * Get companies info
	 * @throws \Exception
	 */
	private function getCompanies(): self
	{
		\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($companyIds);
		if (empty($this->companyIds))
		{
			return $this;
		}

		$factory = Service\Container::getInstance()->getFactory(\CCrmOwnerType::Company);
		if (!$factory)
		{
			throw new \Exception('factory for ' . \CCrmOwnerType::Company . ' not found');
		}
		$items = $factory->getItems([
			'filter' => [
				'@ID' => $companyIds,
			],
		]);
		foreach ($items as $item)
		{
			$compatibleData = $item->getCompatibleData();
			$this->companiesInfo[$item->getId()] = $compatibleData;
			if (isset($this->typedEntities[$item->getEntityTypeId()][$item->getId()]))
			{
				$this->result[$item->getEntityTypeId()][$item->getId()] = $compatibleData;
			}
			$this->multiFields[] = [
				'NAME' => \CCrmOwnerType::CompanyName,
				'ID' => $item->getId(),
			];
		}

		return $this;
	}

	/**
	 * Get all multifields
	 */
	private function getMultiFields(): self
	{
		$filter = \Bitrix\Crm\FieldMultiTable::prepareFilter($this->multiFields);
		$multiFieldsCollection = \Bitrix\Crm\FieldMultiTable::getList([
			'filter' => $filter,
		]);
		while ($fieldItem = $multiFieldsCollection->fetch())
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($fieldItem['ENTITY_ID']);
			$itemId = (int)$fieldItem['ELEMENT_ID'];
			if (isset($this->result[$entityTypeId][$itemId]))
			{
				$this->result[$entityTypeId][$itemId]['FM'][] = $fieldItem;
			}
			if (isset($this->relations[$entityTypeId][$itemId]))
			{
				foreach ($this->relations[$entityTypeId][$itemId] as $identifier)
				{
					$this->result[$identifier->getEntityTypeId()][$identifier->getEntityId()]['FM'][] = $fieldItem;
				}
			}
		}

		return $this;
	}

	/**
	 * Extract name, phone and email from result (lead or contact)
	 */
	private function extractFields(): array
	{
		$extractedFields = [
			'FIRST_NAME' => null,
			'LAST_NAME' => null,
			'PHONE' => null,
			'EMAIL' => null
		];

		$entityToExtract = null;
		$isDefaultName = false;
		if (isset($this->result[\CCrmOwnerType::Lead]))
		{
			// first lead
			$entityToExtract = array_values($this->result[\CCrmOwnerType::Lead])[0];
		}
		elseif (isset($this->result[\CCrmOwnerType::Contact]))
		{
			// first contact
			$entityToExtract = array_values($this->result[\CCrmOwnerType::Contact])[0];
			if (
				!empty($entityToExtract['NAME'])
				&& \CAllCrmContact::isDefaultName($entityToExtract['NAME'])
			)
			{
				$isDefaultName = true;
			}
		}

		if (!$entityToExtract)
		{
			return $extractedFields;
		}

		if ($entityToExtract['NAME'] !== '' && !$isDefaultName)
		{
			$extractedFields['FIRST_NAME'] = $entityToExtract['NAME'];
		}
		if ($entityToExtract['LAST_NAME'] !== '')
		{
			$extractedFields['LAST_NAME'] = $entityToExtract['LAST_NAME'];
		}

		if (isset($entityToExtract['FM']) && !empty($entityToExtract['FM']))
		{
			$phoneFieldKey = array_search('PHONE', array_column($entityToExtract['FM'], 'TYPE_ID'), true);
			if (is_numeric($phoneFieldKey))
			{
				$extractedFields['PHONE'] = $entityToExtract['FM'][$phoneFieldKey]['VALUE'];
			}

			$emailFieldKey = array_search('EMAIL', array_column($entityToExtract['FM'], 'TYPE_ID'), true);
			if (is_numeric($emailFieldKey))
			{
				$extractedFields['EMAIL'] = $entityToExtract['FM'][$emailFieldKey]['VALUE'];
			}
		}


		return $extractedFields;
	}

	/**
	 * Get name, phone and email from following structure type:
	 * [
	 *    'ENTITY_TYPE' => CONTACT,
	 *    'ENTITY_ID' => 36
	 * ],
	 * [
	 *    'ENTITY_TYPE' => DEAL,
	 *    'ENTITY_ID' => 73
	 * ]
	 *
	 * @param array $entities
	 *
	 * @return array
	 */
	public function getFieldsFromMixedEntities(array $entities): array
	{
		$fields = [
			'FIRST_NAME' => null,
			'LAST_NAME' => null,
			'PHONE' => null,
			'EMAIL' => null
		];

		try {
			$this->prepareEntities($entities)
				   ->getNonContactsAndCompanies()
				   ->getContacts()
				   ->getCompanies()
				   ->getMultiFields();
		} catch (\Exception $e) {
			return $fields;
		}

		$extractedFields = $this->extractFields();
		$fields['FIRST_NAME'] = $extractedFields['FIRST_NAME'];
		$fields['LAST_NAME'] = $extractedFields['LAST_NAME'];
		$fields['PHONE'] = $extractedFields['PHONE'];
		$fields['EMAIL'] = $extractedFields['EMAIL'];

		return $fields;
	}
}