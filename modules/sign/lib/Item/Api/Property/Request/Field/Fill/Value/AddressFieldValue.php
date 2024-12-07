<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Field\Fill\Value;

class AddressFieldValue extends BaseFieldValue
{
	public string $city;

	public function __construct(string $city)
	{
		$this->city = $city;
	}
}