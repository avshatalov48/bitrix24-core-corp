<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Contract;

class Owner implements Contract\Item
{
	public string $name;
	public string $companyName;
	public ?string $channelType = null;
	public ?string $channelValue = null;
	public ?string $address = null;

	public function __construct(
		string $name,
		string $companyName,
		?string $channelType = null,
		?string $channelValue = null,
		?string $address = null,
	)
	{
		$this->name = $name;
		$this->companyName = $companyName;
		$this->channelType = $channelType;
		$this->channelValue = $channelValue;
		$this->address = $address;
	}
}