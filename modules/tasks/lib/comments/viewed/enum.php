<?php

namespace Bitrix\Tasks\Comments\Viewed;

final class Enum
{
	const UNDEFINED = 0;
	const USER = 1;
	const PROJECT = 2;
	const SCRUM = 3;

	const USER_NAME = 'USER';
	const PROJECT_NAME = 'PROJECT';
	const SCRUM_NAME = 'SCRUM';

	static function resolveTypeById(int $type): ?string
	{
		if ($type === Enum::USER)
		{
			return Enum::USER_NAME;
		}
		else if($type === Enum::PROJECT)
		{
			return Enum::PROJECT_NAME;
		}
		else if($type === Enum::SCRUM)
		{
			return Enum::SCRUM_NAME;
		}

		return null;
	}

	static function resolveIdByName(string $name): int
	{
		if ($name === Enum::USER_NAME)
		{
			return Enum::USER;
		}
		else if($name === Enum::PROJECT_NAME)
		{
			return Enum::PROJECT;
		}
		else if($name === Enum::SCRUM_NAME)
		{
			return Enum::SCRUM;
		}
		else
		{
			return Enum::UNDEFINED;
		}
	}

}