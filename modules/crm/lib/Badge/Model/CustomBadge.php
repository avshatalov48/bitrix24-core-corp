<?php

namespace Bitrix\Crm\Badge\Model;

use Bitrix\Main\Web\Json;
use JsonSerializable;

class CustomBadge extends EO_CustomBadge implements JsonSerializable
{

	public function jsonSerialize(): array
	{
		return [
			'code' => $this->getCode(),
			'title' => $this->getPreparedFieldValue($this->getTitle()),
			'value' => $this->getPreparedFieldValue($this->getValue()),
			'type' => $this->getType(),
		];
	}

	private function getPreparedFieldValue(?string $value)
	{
		try
		{
			return Json::decode($value);
		}
		catch (\Exception $e)
		{
			return $value;
		}
	}
}
