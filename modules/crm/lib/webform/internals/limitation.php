<?php

namespace Bitrix\Crm\WebForm\Internals;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Type;
use Bitrix\Main;

class LimitationTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_crm_webform_limit';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
				]
			),
			new DatetimeField(
				'DATE_STAT',
				[
					'required' => true,
				]
			),
			new StringField(
				'TYPE',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 25),
						];
					},
				]
			),
			new StringField(
				'CODE',
				[
					'required' => true,
					'validation' => function()
					{
						return[
							new LengthValidator(null, 25),
						];
					},
				]
			),
			new IntegerField(
				'VALUE',
				[
					'default' => 0,
				]
			),
			new Main\ORM\Fields\BooleanField(
				'NOTIFIED',
				[
					'required' => false,
					'default' => false,
				]
			),
		];
	}

	public static function getCurrent(string $code, string $type): ?array
	{
		$current = self::query()
			->setSelect(['ID', 'VALUE', 'DATE_STAT'])
			->where('DATE_STAT', new Type\Date)
			->where('TYPE', $type)
			->where('CODE', $code)
			->fetch()
		;

		if (!is_array($current))
		{
			return null;
		}

		return $current;
	}

	public static function incrementByValue(string $code, string $type, int $value): Main\Result
	{
		$result = new Main\Result();

		$current = self::getCurrent($code, $type);
		if (empty($current))
		{
			return self::add([
				'fields' => [
					'DATE_STAT' => new Type\Date,
					'CODE' => $code,
					'TYPE' => $type,
					'VALUE' => $value
				]
			]);
		}

		return self::update($current['ID'], [
			'fields' => [
				'VALUE' => (int) $current['VALUE'] + $value,
				'DATE_STAT' => $current['DATE_STAT'],
			]
		]);
	}
}