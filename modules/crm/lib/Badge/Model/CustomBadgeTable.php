<?php

namespace Bitrix\Crm\Badge\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\UniqueValidator;
use Closure;

/**
 * Class CustomBadgeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CustomBadge_Query query()
 * @method static EO_CustomBadge_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CustomBadge_Result getById($id)
 * @method static EO_CustomBadge_Result getList(array $parameters = [])
 * @method static EO_CustomBadge_Entity getEntity()
 * @method static \Bitrix\Crm\Badge\Model\CustomBadge createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Badge\Model\EO_CustomBadge_Collection createCollection()
 * @method static \Bitrix\Crm\Badge\Model\CustomBadge wakeUpObject($row)
 * @method static \Bitrix\Crm\Badge\Model\EO_CustomBadge_Collection wakeUpCollection($rows)
 */
class CustomBadgeTable extends \Bitrix\Main\Entity\DataManager
{
	public const TYPE_SUCCESS = 'success';
	public const TYPE_FAILURE = 'failure';
	public const TYPE_WARNING = 'warning';
	public const TYPE_PRIMARY = 'primary';
	public const TYPE_SECONDARY = 'secondary';

	private const ALLOWED_TYPES = [
		self::TYPE_SUCCESS,
		self::TYPE_FAILURE,
		self::TYPE_WARNING,
		self::TYPE_PRIMARY,
		self::TYPE_SECONDARY,
	];

	public static function getTableName(): string
	{
		return 'b_crm_custom_badge';
	}

	public static function getObjectClass()
	{
		return CustomBadge::class;
	}

	public static function getByCode(string $code): ?CustomBadge
	{
		if ($code === '')
		{
			return null;
		}

		$object = self::query()
			->where('CODE', $code)
			->setLimit(1)
			->fetchObject()
		;

		return $object ? $object : null;
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new StringField('CODE', [
				'required' => true,
				'validation' => fn() => [
					new UniqueValidator('Code must be unique'),
				],
			])),
			(new StringField('TITLE', [
				'required' => true,
				'validation' => self::getStringOrArrayValidator(),
				'save_data_modification' => self::getSaveDataModification(),
			])),
			(new StringField('VALUE', [
				'required' => true,
				'validation' => self::getStringOrArrayValidator(),
				'save_data_modification' => self::getSaveDataModification(),
			])),
			(new EnumField('TYPE', [
				'required' => true,
				'values' => self::ALLOWED_TYPES,
			])),
		];
	}

	private static function getStringOrArrayValidator(): Closure
	{
		return static fn() => [
			static function ($value, $primary, $row, $field) {
				if (is_string($value) || is_array($value))
				{
					return true;
				}

				return $field . ' must be a string or an array';
			}
		];
	}

	private static function getSaveDataModification(): Closure
	{
		return static fn() => [
			fn($value) => \Bitrix\Main\Web\Json::encode($value),
		];
	}

	public static function onBeforeDelete(Event $event)
	{
		$result = new \Bitrix\Main\ORM\EventResult();
		$id = $event->getParameter('id')['ID'] ?? 0;
		$badgeData = static::getById($id)->fetch();
		$existedBadge = BadgeTable::query()
			->where('VALUE', $badgeData['CODE'])
			->where('TYPE', \Bitrix\Crm\Badge\Badge::REST_APP_TYPE)
			->setSelect(['ID'])
			->setLimit(1)
			->fetch();

		if ($existedBadge)
		{
			$result->addError(new \Bitrix\Main\ORM\EntityError('There are entities with this badge. Delete them first.', 'ENTITY_WITH_BADGE_EXISTS'));
		}

		return $result;
	}
}
