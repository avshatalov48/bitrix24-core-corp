<?php

namespace Bitrix\Crm\Timeline\Entity;

use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Timeline\Entity\Object\CustomIcon;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\UniqueValidator;

/**
 * Class CustomIconTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CustomIcon_Query query()
 * @method static EO_CustomIcon_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CustomIcon_Result getById($id)
 * @method static EO_CustomIcon_Result getList(array $parameters = [])
 * @method static EO_CustomIcon_Entity getEntity()
 * @method static \Bitrix\Crm\Timeline\Entity\Object\CustomIcon createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_CustomIcon_Collection createCollection()
 * @method static \Bitrix\Crm\Timeline\Entity\Object\CustomIcon wakeUpObject($row)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_CustomIcon_Collection wakeUpCollection($rows)
 */
class CustomIconTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_timeline_custom_icon';
	}

	public static function getMap()
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new StringField('CODE', [
				'required' => true,
				'validation' => fn() => [
					new UniqueValidator('Code must be unique'),
					self::checkIsSystemCodeValidator(),
				],
			])),
			(new IntegerField('FILE_ID', [
				'required' => true,
			]))
		];
	}

	public static function getObjectClass()
	{
		return CustomIcon::class;
	}

	private static function checkIsSystemCodeValidator(): \Closure
	{
		return static function ($value) {

			$systemCodes = Icon::getSystemIcons();
			if (!in_array($value, $systemCodes, true))
			{
				return true;
			}

			return $value . ' is reserved word and cannot be used';
		};
	}

	public static function onAfterDelete(Event $event): void
	{
		$object = $event->getParameter('object');

		if ($object)
		{
			$fileId = $object->getFileId();
			if ($fileId)
			{
				\CFile::Delete($fileId);
			}
		}
	}

	public static function getByCode(string $code): ?CustomIcon
	{
		return self::query()
			->addSelect('*')
			->where('CODE', $code)
			->fetchObject()
		;
	}
}
