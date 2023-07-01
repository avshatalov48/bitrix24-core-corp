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
use Bitrix\Rest\Api\UserFieldType;

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

	/**
	 * @var bool
	 */
	private static $holdInserting = false;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_market_tag';
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

	/**
	 * Returns validators for TYPE field.
	 *
	 * @return array
	 */
	public static function validateType(): array
	{
		return [
			new LengthValidator(null, 1),
		];
	}

	/**
	 * Returns validators for MODULE_ID field.
	 *
	 * @return array
	 */
	public static function validateModuleId(): array
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode(): array
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	/**
	 * Returns validators for VALUE field.
	 *
	 * @return array
	 */
	public static function validateValue(): array
	{
		return [
			new LengthValidator(null, 32),
		];
	}

	/**
	 * Blocks methods add and update
	 * @return bool
	 */
	public static function blockSave(): bool
	{
		static::$holdInserting = false;

		return true;
	}

	/**
	 * Unblocks methods add and update
	 * @return bool
	 */
	public static function unblockSave(): bool
	{
		static::$holdInserting = true;

		return true;
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	protected static function check(Event $event): EventResult
	{
		$result = new EventResult();
		if (static::$holdInserting === false)
		{
			$result->addError(
				new EntityError(
					'Use \Bitrix\Market\Tag\Manager'
				)
			);
		}

		return $result;
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onBeforeUpdate(Event $event): EventResult
	{
		return static::check($event);
	}

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onBeforeAdd(Event $event): EventResult
	{
		return static::check($event);
	}

	public static function getAll(): array
	{
		$result = [];

		$dbTag = TagTable::getList();
		while ($tag = $dbTag->fetch())
		{
			$result[$tag['CODE']] = $tag['VALUE'];
		}

		$result['date_create'] = Option::get('main', '~controller_date_create', 0);

		return $result;
	}

	public static function getByPlacement($placement): array
	{
		$tag = [];

		if($placement <> '')
		{
			if(mb_substr($placement, 0, 4) === 'CRM_' || $placement === UserFieldType::PLACEMENT_UF_TYPE)
			{
				if($placement !== 'CRM_ROBOT_TRIGGERS')
				{
					$tag[] = 'placement';
				}
				else
				{
					$tag[] = 'automation';
				}

				$tag[] = 'crm';
			}
			elseif(mb_substr($placement, 0, 5) === 'CALL_')
			{
				$tag[] = 'placement';
				$tag[] = 'telephony';
			}
			elseif(mb_substr($placement, 0, 5) === 'TASK_')
			{
				$tag[] = 'placement';
				$tag[] = 'task';
			}
			elseif(mb_substr($placement, 0, 6) === 'SONET_')
			{
				$tag[] = 'placement';
				$tag[] = 'sonet';
			}
		}

		$tag[] = $placement;

		return $tag;
	}
}