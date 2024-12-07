<?php

namespace Bitrix\Sign\UserFields;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserField\Types\StringType;
use Bitrix\Sign\Helper\SnilsValidator;
use Bitrix\Sign\Type\FieldType;

Loc::loadMessages(__FILE__);

final class SnilsUserType
{
	public const USER_TYPE_ID = FieldType::SNILS;

	public static function OnUserTypeBuildList(): array
	{
		return [
			'USER_TYPE_ID' => self::USER_TYPE_ID,
			'CLASS_NAME' => __CLASS__,
			'DESCRIPTION' => Loc::getMessage('SIGN_USER_FIELD_SNILS'),
			'BASE_TYPE' => 'string',
			'EDIT_CALLBACK' => [self::class, 'getPublicEditHtml'],
			'VIEW_CALLBACK' => [self::class, 'getPublicViewHtml'],
			'USE_FIELD_COMPONENT' => false,
		];
	}

	public static function getDbColumnType(): string
	{
		return \Bitrix\Main\Application::getConnection()
			->getSqlHelper()
			->getColumnTypeByField(
				new \Bitrix\Main\ORM\Fields\StringField('x', [
					'size' => 30,
					'validation' => static function ()
					{
						return [
							new \Bitrix\Main\Entity\Validator\Length(null, 30),
						];
					},
				])
			)
		;
	}

	/**
	 * This function is validator.
	 * Called from the CheckFields method of the $ USER_FIELD_MANAGER object,
	 * which can be called from the Add / Update methods of the property owner entity.
	 *
	 * @param array $userField
	 * @param string|array $value
	 *
	 * @return array
	 */
	public static function checkFields(array $userField, $value): array
	{
		$msg = [];

		$validator = new SnilsValidator((string)$value);
		if ($value !== '' && !$validator->isValid())
		{
			$msg[] = [
				'id' => $userField['FIELD_NAME'],
				'text' => Loc::GetMessage('SIGN_USER_FIELD_VALIDATION_ERROR'),
			];
		}

		return $msg;
	}

	public static function getPublicViewHtml(?array $userField, ?array $additionalParameters = []): ?string
	{
		$userField = self::replaceValueToFormatted($userField);

		return StringType::getPublicView($userField, $additionalParameters);
	}

	public static function getPublicEditHtml(?array $userField, ?array $additionalParameters = []): ?string
	{
		$userField = self::replaceValueToFormatted($userField);

		return StringType::getPublicEdit($userField, $additionalParameters);
	}

	public static function onBeforeSave($arUserField, $value)
	{
		$validator = new SnilsValidator((string)$value);

		return $validator->getNormalizedValue();
	}

	private static function replaceValueToFormatted(?array $userField): ?array
	{
		if (isset($userField['VALUE']))
		{
			$userField['VALUE'] = self::getFormattedValue((string)$userField['VALUE']);
		}

		return $userField;
	}

	private static function getFormattedValue(string $value): string
	{
		$validator = new SnilsValidator($value);
		if (!$validator->isValid())
		{
			return $value;
		}

		$value = $validator->getNormalizedValue();
		$parts = mb_str_split($value, 3);
		$lastPart = array_pop($parts);

		return implode('-', $parts) . " $lastPart";
	}
}
