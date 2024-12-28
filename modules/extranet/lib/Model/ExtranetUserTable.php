<?php

namespace Bitrix\Extranet\Model;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM;
use Bitrix\Main\SystemException;
use Bitrix\Extranet\Enum;

/**
 * Class ExtranetUserTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ExtranetUser_Query query()
 * @method static EO_ExtranetUser_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ExtranetUser_Result getById($id)
 * @method static EO_ExtranetUser_Result getList(array $parameters = [])
 * @method static EO_ExtranetUser_Entity getEntity()
 * @method static \Bitrix\Extranet\Model\EO_ExtranetUser createObject($setDefaultValues = true)
 * @method static \Bitrix\Extranet\Model\EO_ExtranetUser_Collection createCollection()
 * @method static \Bitrix\Extranet\Model\EO_ExtranetUser wakeUpObject($row)
 * @method static \Bitrix\Extranet\Model\EO_ExtranetUser_Collection wakeUpCollection($rows)
 */
class ExtranetUserTable extends ORM\Data\DataManager
{
	public static function getTableName(): string
	{
		return 'b_extranet_user';
	}

	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configureTitle('ID')
				->configurePrimary()
				->configureAutocomplete(),
			(new ORM\Fields\IntegerField('USER_ID'))
				->configureTitle('ExtranetUser ID')
				->configureUnique()
				->configureRequired(),
			(new ORM\Fields\BooleanField('CHARGEABLE'))
				->configureTitle('Chargeable')
				->configureValues('N', 'Y')
				->configureDefaultValue('Y'),
			(new ORM\Fields\EnumField('ROLE'))
				->configureTitle('Role')
				->configureValues(Enum\User\ExtranetRole::getValues())
				->configureDefaultValue(Enum\User\ExtranetRole::Extranet->value),
			(new ORM\Fields\Relations\Reference(
				'USER',
				\Bitrix\Main\UserTable::class,
				['=this.USER_ID' => 'ref.ID'],
				['join_type' => 'INNER'],
			)),
		];
	}

	public static function onAfterAdd(ORM\Event $event): void
	{
		self::clearAllCache();
	}

	public static function onAfterDelete(ORM\Event $event): void
	{
		self::clearAllCache();
	}

	public static function onAfterUpdate(ORM\Event $event): void
	{
		self::clearAllCache();
	}

	private static function clearAllCache(): void
	{
		ServiceContainer::getInstance()->getCollaberService()->clearCache();
		ServiceContainer::getInstance()->getUserService()->clearCache();
	}
}
