<?php

namespace Bitrix\Crm\MessageSender\Channel\Correspondents;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Multifield;

final class To implements \JsonSerializable
{
	private ItemIdentifier $rootSource;
	private ItemIdentifier $addressSource;
	private Multifield\Value $address;

	public function __construct(ItemIdentifier $rootSource, ItemIdentifier $addressSource, Multifield\Value $address)
	{
		$this->rootSource = $rootSource;
		$this->addressSource = $addressSource;
		$this->address = $address;
	}

	public function getRootSource(): ItemIdentifier
	{
		return $this->rootSource;
	}

	public function getAddressSource(): ItemIdentifier
	{
		return $this->addressSource;
	}

	public function getAddress(): Multifield\Value
	{
		return $this->address;
	}

	public function jsonSerialize()
	{
		return [
			'rootSource' => $this->getRootSource(),
			'addressSource' => $this->getAddressSource(),
			'address' => $this->getAddress(),
		];
	}
}
