<?php

namespace Bitrix\Crm\Controller\Integration\Booking;

use Bitrix\Crm;

class Contact extends Crm\Controller\Base
{
	public function parseFormattedNameAction(array $fields): array
	{
		$name = $fields['FORMATTED_NAME'] ?? '';

		$fields = [];

		if ($name === \CCrmContact::GetDefaultName())
		{
			$fields['NAME'] = $name;
		}
		else
		{
			Crm\Format\PersonNameFormatter::tryParseName(
				$name,
				Crm\Format\PersonNameFormatter::getFormatID(),
				$fields
			);
		}

		return $fields;
	}

	public function addFormattedNameAction(array $fields): array
	{
		$fields['FORMATTED_NAME'] = \CCrmContact::PrepareFormattedName($fields);

		return $fields;
	}
}
