<?php

namespace Bitrix\Sign\Type;

use Bitrix\Main\Loader;
use Bitrix\Location\Entity\Address;

Loader::includeModule('location');

final class FieldType
{
	public const FILE = 'file';
	public const STRING = 'string';
	public const DOUBLE = 'double';
	public const INTEGER = 'integer';
	public const LIST = 'list';
	public const DATE = 'date';
	public const DATETIME = 'datetime';
	public const EMAIL = 'email';
	public const PHONE = 'phone';
	public const NAME = 'name';
	public const SIGNATURE = 'signature';
	public const STAMP = 'stamp';
	public const ADDRESS = 'address';
	public const SNILS = 'snils';
	public const FIRST_NAME = 'firstname';
	public const LAST_NAME = 'lastname';
	public const PATRONYMIC = 'patronymic';
	public const POSITION = 'position';
	public const ENUMERATION = 'enumeration';

	public const ADDRESS_SUBFIELD_MAP = [
		'ADDRESS_1' => Address\FieldType::ADDRESS_LINE_1,
		'ADDRESS_2' => Address\FieldType::ADDRESS_LINE_2,
		'CITY' => Address\FieldType::LOCALITY,
		'POSTAL_CODE' => Address\FieldType::POSTAL_CODE,
		'REGION' => Address\FieldType::ADM_LEVEL_2,
		'PROVINCE' => Address\FieldType::ADM_LEVEL_1,
		'COUNTRY' => Address\FieldType::COUNTRY,
	];

	/**
	 * @return array<self::*>
	 */
	public static function getAll(): array
	{
		return [
			self::FILE,
			self::STRING,
			self::DOUBLE,
			self::INTEGER,
			self::LIST,
			self::DATE,
			self::EMAIL,
			self::PHONE,
			self::NAME,
			self::SIGNATURE,
			self::STAMP,
			self::ADDRESS,
			self::SNILS,
			self::FIRST_NAME,
			self::LAST_NAME,
			self::PATRONYMIC,
			self::POSITION,
			self::ENUMERATION,
		];
	}
}
