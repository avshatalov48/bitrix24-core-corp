<?php
namespace Bitrix\Location\Entity\Address;

use Bitrix\Main\ArgumentNullException;

/**
 * Class AddressLink
 * @package Bitrix\Location\Entity\Address
 * Default implementation of IAddressLink
 */
class AddressLink implements IAddressLink
{
	/** @var string */
	protected $entityId;
	/** @var string  */
	protected $entityType;

	/**
	 * AddressLink constructor.
	 * @param string $entityId
	 * @param string $entityType
	 */
	public function __construct(string $entityId, string $entityType)
	{
		if($entityId == '')
		{
			throw new ArgumentNullException('entityId');
		}

		if($entityType == '')
		{
			throw new ArgumentNullException('entityType');
		}

		$this->entityId = $entityId;
		$this->entityType = $entityType;
	}

	/**
	 * @inheritDoc
	 */
	public function getAddressLinkEntityId(): string
	{
		return $this->entityId;
	}

	/**
	 * @inheritDoc
	 */
	public function getAddressLinkEntityType(): string
	{
		return $this->entityType;
	}
}