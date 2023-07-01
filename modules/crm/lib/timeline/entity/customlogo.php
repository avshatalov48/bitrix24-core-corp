<?php

namespace Bitrix\Crm\Timeline\Entity;

use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Crm\Timeline\Entity\Object\CustomLogo;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\UniqueValidator;

/**
 * Class CustomLogoTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_CustomLogo_Query query()
 * @method static EO_CustomLogo_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_CustomLogo_Result getById($id)
 * @method static EO_CustomLogo_Result getList(array $parameters = [])
 * @method static EO_CustomLogo_Entity getEntity()
 * @method static \Bitrix\Crm\Timeline\Entity\Object\CustomLogo createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_CustomLogo_Collection createCollection()
 * @method static \Bitrix\Crm\Timeline\Entity\Object\CustomLogo wakeUpObject($row)
 * @method static \Bitrix\Crm\Timeline\Entity\EO_CustomLogo_Collection wakeUpCollection($rows)
 */
class CustomLogoTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_crm_timeline_custom_logo';
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
		return CustomLogo::class;
	}

	private static function checkIsSystemCodeValidator(): \Closure
	{
		return static function ($value) {

			$systemCodes = Logo::getSystemLogoCodes();
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

	public static function getByCode(string $code): ?CustomLogo
	{
		return self::query()
			->addSelect('*')
			->where('CODE', $code)
			->fetchObject()
			;
	}
}
