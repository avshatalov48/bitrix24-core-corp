<?php

namespace Bitrix\Call\Integration\AI\Outcome;

use Bitrix\Call\Model\EO_CallOutcomeProperty;

use Bitrix\Main\Web\Json;
use Bitrix\Main\ArgumentException;


class Property extends EO_CallOutcomeProperty
{
	public function getStructure(): mixed
	{
		try
		{
			$jsonData = Json::decode($this->getContent() ?: '');
			return $jsonData ?? null;
		}
		catch (ArgumentException)
		{
		}

		return null;
	}
}