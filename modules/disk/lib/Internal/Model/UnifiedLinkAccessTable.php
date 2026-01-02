<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Model;


use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;
use \Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\AddStrategy\Trait\AddMergeTrait;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Validators\ForeignValidator;
use Bitrix\Main\SystemException;

/**
 * Class UnifiedLinkAccessTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_UnifiedLinkAccess_Query query()
 * @method static EO_UnifiedLinkAccess_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_UnifiedLinkAccess_Result getById($id)
 * @method static EO_UnifiedLinkAccess_Result getList(array $parameters = [])
 * @method static EO_UnifiedLinkAccess_Entity getEntity()
 * @method static \Bitrix\Disk\Internal\Model\EO_UnifiedLinkAccess createObject($setDefaultValues = true)
 * @method static \Bitrix\Disk\Internal\Model\EO_UnifiedLinkAccess_Collection createCollection()
 * @method static \Bitrix\Disk\Internal\Model\EO_UnifiedLinkAccess wakeUpObject($row)
 * @method static \Bitrix\Disk\Internal\Model\EO_UnifiedLinkAccess_Collection wakeUpCollection($rows)
 */
class UnifiedLinkAccessTable extends DataManager
{
	use AddMergeTrait;

	public static function getTableName(): string
	{
		return 'b_disk_unified_link_access';
	}

	/**
	 * @return array
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new IntegerField('OBJECT_ID'))
				->configureRequired()
				->configureUnique()
				->addValidator(new ForeignValidator(ObjectTable::getEntity()->getField('ID')))
			,
			(new EnumField('ACCESS_LEVEL'))
				->configureRequired()
				->configureValues(array_column(UnifiedLinkAccessLevel::cases(), 'value'))
			,
		];
	}
}