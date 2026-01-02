<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Factory\User;

use Bitrix\Intranet\Internal\Entity\User\Field\EmailField;
use Bitrix\Intranet\Internal\Entity\User\Field\PhoneField;
use Bitrix\Intranet\Internal\Entity\User\Field\DateField;
use Bitrix\Intranet\Internal\Entity\User\Field\SelectField;
use Bitrix\Intranet\Internal\Entity\User\Field\StringField;

class UserFieldTypeMapper
{
	/**
	 * @return class-string|null
	 */
	public function getClassByFieldInfo(array $fieldInfo): ?string
	{
		return $this->getClassByName($fieldInfo['name'])
			?? $this->getClassByType($fieldInfo['type']);
	}

	/**
	 * @return class-string|null
	 */
	public function getClassByType(string $type): ?string
	{
		return match($type)
		{
			'text', 'link', 'string', 'string_formatted' => StringField::class,
			'phone' => PhoneField::class,
			'date', 'datetime' => DateField::class,
			'list', 'enumeration' => SelectField::class,
			default => null,
		};
	}

	/**
	 * @return class-string|null
	 */
	public function getClassByName(string $name): ?string
	{
		return match ($name)
		{
			'EMAIL' => EmailField::class,
			'PERSONAL_PHONE', 'WORK_PHONE' => PhoneField::class,
			default => null,
		};
	}
}
