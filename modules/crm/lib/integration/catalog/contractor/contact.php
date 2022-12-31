<?php

namespace Bitrix\Crm\Integration\Catalog\Contractor;

use Bitrix\Catalog\v2\Contractor\Provider\IContractor;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm;

/**
 * Class Contact
 *
 * @package Bitrix\Crm\Integration\Catalog\Contractor
 */
class Contact implements IContractor
{
	/** @var Crm\Item\Contact */
	private $contact;

	/** @var array|null */
	private $requisite;

	/** @var bool */
	private $requisiteLoaded = false;

	/**
	 * @param Crm\Item\Contact $contact
	 */
	public function __construct(Crm\Item\Contact $contact)
	{
		$this->contact = $contact;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): int
	{
		return $this->contact->getId();
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string
	{
		return $this->getContactPersonFullName();
	}

	/**
	 * @inheritDoc
	 */
	public function getContactPersonFullName(): ?string
	{
		$result = $this->contact->getFullName() ?: $this->contact->getName();

		return (string)$result;
	}

	/**
	 * @inheritDoc
	 */
	public function getPhone(): ?string
	{
		$result = null;

		$multiFields = $this->contact->getFm()->getAll();
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
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getKpp(): ?string
	{
		return null;
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

		$address = EntityRequisite::getAddresses($this->requisite['ID'])[EntityAddressType::Primary] ?? null;
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
				'=ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
				'=ENTITY_ID' => $this->contact->getId(),
			]
		])->fetch();

		$this->requisiteLoaded = true;
	}
}
