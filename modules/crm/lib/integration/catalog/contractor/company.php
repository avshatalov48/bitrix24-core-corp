<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Catalog\v2\Contractor\Provider\IContractor;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm;

/**
 * Class Company
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 */
class Company implements IContractor
{
	/** @var Crm\Item\Company */
	private $company;

	/** @var array|null */
	private $requisite;

	/** @var bool */
	private $requisiteLoaded = false;

	/**
	 * @param Crm\Item\Company $company
	 */
	public function __construct(Crm\Item\Company $company)
	{
		$this->company = $company;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): int
	{
		return $this->company->getId();
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return (string)$this->company->getTitle();
	}

	/**
	 * @inheritDoc
	 */
	public function getContactPersonFullName(): ?string
	{
		$contactFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
		if (!$contactFactory)
		{
			return null;
		}

		$contacts = $contactFactory->getItems([
			'filter' => [
				'=COMPANY_ID' => $this->company->getId(),
			],
		]);
		if (empty($contacts))
		{
			return null;
		}

		return $contacts[0]->getName();
	}

	/**
	 * @inheritDoc
	 */
	public function getPhone(): ?string
	{
		$result = null;

		$multiFields = $this->company->getFm()->getAll();
		foreach ($multiFields as $multiField)
		{
			if (
				$multiField->getTypeId() === Phone::ID
				&& $multiField->getValueType() === Phone::VALUE_TYPE_WORK
			)
			{
				$result = $multiField->getValue();
				break;
			}
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function getInn(): ?string
	{
		$this->loadRequisite();

		if (!$this->requisite)
		{
			return null;
		}

		return $this->requisite['RQ_INN'];
	}

	/**
	 * @inheritDoc
	 */
	public function getKpp(): ?string
	{
		$this->loadRequisite();

		if (!$this->requisite)
		{
			return null;
		}

		return $this->requisite['RQ_KPP'];
	}

	/**
	 * @inheritDoc
	 */
	public function getAddress(): ?string
	{
		$this->loadRequisite();

		if (!$this->requisite)
		{
			return null;
		}

		$address = EntityRequisite::getAddresses($this->requisite['ID'])[EntityAddressType::Registered] ?? null;
		if (!$address)
		{
			return null;
		}

		return $address['ADDRESS_2'] ?? '';
	}

	private function loadRequisite(): void
	{
		if ($this->requisiteLoaded)
		{
			return;
		}

		$this->requisite = EntityRequisite::getSingleInstance()->getList([
			'filter' => [
				'=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
				'=ENTITY_ID' => $this->company->getId(),
			]
		])->fetch();

		$this->requisiteLoaded = true;
	}
}
