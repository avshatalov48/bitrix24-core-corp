<?php

namespace Bitrix\Sign\Service\Providers;

use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use Bitrix\Sign\Type\Field\FrontFieldCategory;
use Bitrix\Sign\Type\FieldType;

final class MemberDynamicFieldInfoProvider extends InfoProvider
{
	public const USER_FIELD_ENTITY_ID = 'SIGN_MEMBER_DYNAMIC';

	/**
	 * @return array{DINAMYC_MEMBER: array{
	 *     CAPTION: string,
	 *     FIELDS: array,
	 *     MODULE_ID: string,
	 *     DYNAMIC_ID: string,
	 * 	}
	 *	}
	 */
	public function getFieldsForSelector(): array
	{
		return [
			FrontFieldCategory::DYNAMIC_MEMBER->value => [
				'CAPTION' => Loc::getMessage('SIGN_SERVICE_PROVIDER_TEMPLATE_DYNAMIC_CAPTION'),
				'FIELDS' => parent::getFieldsForSelector(),
				'MODULE_ID' => 'sign',
				'DYNAMIC_ID' => self::USER_FIELD_ENTITY_ID,
			],
		];
	}

	public function loadFieldData(
		int $memberId,
		string $fieldName,
		string $subFieldName = '',
		bool $isOriginalValue = false,
	): string
	{
		$field = $this->getFieldDescription($fieldName);

		if (empty($field))
		{
			return '';
		}

		return (string)\Bitrix\Sign\Factory\Field::getUserFieldValue($memberId, $field, $subFieldName, $isOriginalValue);
	}

	public function updateFieldData(int $memberId, string $fieldName, string|array $value): Result
	{
		global $USER_FIELD_MANAGER;

		$description = $this->getFieldDescription($fieldName);
		$entityId = $description['entityId'] ?? null;
		if ($entityId === null)
		{
			return (new Result())->addError(new Error('Field has no description'));
		}

		if (
			$description['type'] === FieldType::ADDRESS
			&& Loader::includeModule('fileman')
		)
		{
			$ufValue = $USER_FIELD_MANAGER->GetUserFieldValue(
				entity_id: $description,
				field_id: $fieldName,
				value_id: $memberId,
				LANG: LANGUAGE_ID,
			);
			[,, $addressId] = AddressType::parseValue($ufValue);

			$value = $this->prepareUpdateAddressType($value, (int)($addressId ?? 0));
		}

		$isSuccess = $USER_FIELD_MANAGER->Update($entityId, $memberId, [$fieldName => $value]);
		if (!$isSuccess)
		{
			return (new Result())->addError(new Error('Field update end not successfully'));
		}

		return new Result();
	}

	private function prepareUpdateAddressType(array|string $value, int $addressId): ?string
	{
		try
		{
			return Json::encode([
				'id' => $addressId,
				'languageId' => LANGUAGE_ID,
				'latitude' => 0,
				'longitude' => 0,
				'fieldCollection' => $value,
				'links' => [],
				'location' => null,
			], JSON_UNESCAPED_UNICODE);
		}
		catch (ArgumentException $e)
		{
			return null;
		}
	}

	public function isFieldCodeMemberDynamicField(string $fieldCode): bool
	{
		$dynamicFieldCodePattern = 'UF_' .  self::USER_FIELD_ENTITY_ID;

		return str_starts_with($fieldCode, $dynamicFieldCodePattern);
	}
}