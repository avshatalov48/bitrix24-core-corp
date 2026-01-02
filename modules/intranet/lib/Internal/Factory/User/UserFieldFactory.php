<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Factory\User;

use Bitrix\Intranet\Exception\UserFieldTypeException;
use Bitrix\Intranet\Internal\Entity\User\Field\Field;
use Bitrix\Intranet\Internal\Entity\User\Field\MultipleField;
use Bitrix\Intranet\Internal\Entity\User\Field\SingleField;
use Bitrix\Main\ArgumentException;

class UserFieldFactory
{
	public function __construct(
		private UserFieldTypeMapper $typeMapper,
	)
	{
	}

	public static function createByDefault(): UserFieldFactory
	{
		return new UserFieldFactory(
			new UserFieldTypeMapper(),
		);
	}

	/**
	 * @throws ArgumentException
	 * @throws UserFieldTypeException
	 */
	public function createUserFieldByArray(array $fieldInfo, mixed $value = null): Field
	{
		if (
			empty($fieldInfo['type'])
			|| empty($fieldInfo['name'])
			|| empty($fieldInfo['title'])
		)
		{
			throw new ArgumentException("User field required type, name, title");
		}

		if ($fieldInfo['type'] === 'userField')
		{
			$userFieldInfo = $fieldInfo['data']['fieldInfo'] ?? [];
			$value = $this->getValueFromUserFieldValue($value);

			$isMultiple = isset($userFieldInfo['MULTIPLE']) && $userFieldInfo['MULTIPLE'] === 'Y';

			return $this->createUserFieldByArray(
				[
					'type' => $userFieldInfo['USER_TYPE_ID'] ?? '',
					'name' => $userFieldInfo['FIELD'] ?? '',
					'title' => $fieldInfo['title'],
					'editable' => $fieldInfo['editable'] ?? false,
					'showAlways' => $fieldInfo['showAlways'] ?? false,
					'data' => $fieldInfo['data'] ?? [],
					'multiple' => $isMultiple,
				],
				$value,
			);
		}

		$singleField = $this->createSingleFieldByArray($fieldInfo, $value);

		if (isset($fieldInfo['multiple']) && $fieldInfo['multiple'])
		{
			return new MultipleField($singleField);
		}

		return $singleField;
	}

	/**
	 * @throws ArgumentException
	 * @throws UserFieldTypeException
	 */
	protected function createSingleFieldByArray(array $fieldInfo, mixed $value): SingleField
	{
		$fieldClassName = $this->typeMapper->getClassByFieldInfo($fieldInfo);

		if (!isset($fieldClassName))
		{
			$type = $fieldInfo['type'];
			throw new UserFieldTypeException("Wrong user field type: $type");
		}

		return $fieldClassName::createByData($fieldInfo, $value);
	}

	protected function getValueFromUserFieldValue(mixed $value): mixed
	{
		if (!is_array($value))
		{
			return $value;
		}

		if (isset($value['IS_EMPTY']) && $value['IS_EMPTY'])
		{
			return null;
		}

		if (isset($value['VALUE']))
		{
			return $value['VALUE'];
		}

		return $value;
	}
}
