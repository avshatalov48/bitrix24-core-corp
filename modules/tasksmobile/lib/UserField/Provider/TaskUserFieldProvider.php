<?php

namespace Bitrix\TasksMobile\UserField\Provider;

use Bitrix\Tasks\Util\UserField;
use Bitrix\TasksMobile\UserField\FieldFactory;
use Bitrix\TasksMobile\UserField\Type;

class TaskUserFieldProvider
{
	private static bool $isRestricted;
	private static array $userFieldScheme;

	public function getUserFields(array $taskData = []): array
	{
		if ($this->isRestricted())
		{
			return [];
		}

		$userFields = [];
		$userFieldsScheme = static::getUserFieldScheme();

		foreach ($userFieldsScheme as $ufCode => $ufDesc)
		{
			if (
				!$this->isAutoUserField($ufCode)
				|| !$this->isUserFieldTypeSupported($ufDesc['USER_TYPE_ID'])
			)
			{
				unset($userFieldsScheme[$ufCode]);
				continue;
			}

			$userField = FieldFactory::createField([
				...$ufDesc,
				'VALUE' => ($taskData[$ufCode] ?? null),
			]);
			$userFields[] = $userField->toDto();
		}

		return $userFields;
	}

	private function isRestricted(): bool
	{
		if (!isset(self::$isRestricted))
		{
			self::$isRestricted = !UserField\Restriction::canUse(UserField\Task::getEntityCode());
		}

		return self::$isRestricted;
	}

	private function getUserFieldScheme(): array
	{
		if (!isset(self::$userFieldScheme))
		{
			self::$userFieldScheme = UserField\Task::getScheme();
		}

		return self::$userFieldScheme;
	}

	private function isAutoUserField(string $field): bool
	{
		return str_starts_with($field, 'UF_AUTO_');
	}

	private function isUserFieldTypeSupported(string $type): bool
	{
		return Type::tryFrom($type) !== null;
	}
}
