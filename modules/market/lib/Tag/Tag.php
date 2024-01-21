<?php

namespace Bitrix\Market\Tag;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Entity\Event;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class TagTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TYPE string(1) mandatory
 * <li> MODULE_ID string(32) mandatory
 * <li> CODE string(32) mandatory
 * <li> DATE_VALUE date mandatory
 * <li> VALUE string(32) mandatory
 * </ul>
 *
 * @package Bitrix\Market\Tag
 **/
class TagTable extends DataManager
{
	public const TYPE_DEFAULT = 'D';
	public const TYPE_INCREMENT = 'I';
	public const TYPE_INCREMENT_DAILY = 'E';

	public const MARKET_INDEX_TAG = 'market_index';

	private static bool $holdInserting = false;

	public static function getTableName(): string
	{
		return 'b_market_tag';
	}

	public static function getMap(): array
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
				]
			),
			new StringField(
				'TYPE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateType'],
				]
			),
			new StringField(
				'MODULE_ID',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateModuleId'],
				]
			),
			new StringField(
				'CODE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateCode'],
				]
			),
			new DateField(
				'DATE_VALUE',
				[
					'required' => true,
				]
			),
			new StringField(
				'VALUE',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateValue'],
				]
			),
		];
	}

	public static function validateType(): array
	{
		return [
			new LengthValidator(null, 1),
		];
	}

	public static function validateModuleId(): array
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	public static function validateCode(): array
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	public static function validateValue(): array
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	public static function blockSave()
	{
		self::$holdInserting = false;
	}

	public static function unblockSave()
	{
		self::$holdInserting = true;
	}

	public static function onBeforeAdd(Event $event): EventResult
	{
		return self::check();
	}

	public static function onBeforeUpdate(Event $event): EventResult
	{
		return self::check();
	}

	protected static function check(): EventResult
	{
		$result = new EventResult();

		if (self::$holdInserting === false) {
			$result->addError(new EntityError('Use \Bitrix\Market\Tag\Manager'));
		}

		return $result;
	}

	public static function getAll(): array
	{
		$result = [];

		$dbTag = TagTable::getList();
		while ($tag = $dbTag->fetch()) {
			$result[$tag['CODE']] = $tag['VALUE'];
		}

		$result['date_create'] = Option::get('main', '~controller_date_create', 0);

		return $result;
	}
}