<?php

namespace Bitrix\HumanResources\Model\HcmLink;

use Bitrix\AI\Model\RoleTable;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Up\Model\CourseTable;

/**
 * Class FieldTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Field_Query query()
 * @method static EO_Field_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Field_Result getById($id)
 * @method static EO_Field_Result getList(array $parameters = [])
 * @method static EO_Field_Entity getEntity()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Field createObject($setDefaultValues = true)
 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldCollection createCollection()
 * @method static \Bitrix\HumanResources\Model\HcmLink\Field wakeUpObject($row)
 * @method static \Bitrix\HumanResources\Model\HcmLink\FieldCollection wakeUpCollection($rows)
 */
class FieldTable extends ORM\Data\DataManager
{
	use  DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return Field::class;
	}

	public static function getCollectionClass(): string
	{
		return FieldCollection::class;
	}

	public static function getTableName(): string
	{
		return 'b_hr_hcmlink_field';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
				->configureTitle('ID')
			,
			(new ORM\Fields\IntegerField('COMPANY_ID'))
				->configureRequired()
				->configureTitle('B24 company id')
			,
			(new ORM\Fields\Relations\Reference(
				'COMPANY',
				CompanyTable::class,
				Join::on('this.COMPANY_ID', 'ref.ID')
			))
			,
			(new ORM\Fields\StringField('CODE'))
				->configureRequired()
				->configureTitle('External company id')
			,
			(new ORM\Fields\IntegerField('TYPE'))
				->configureRequired()
				->configureTitle('Type')
			,
			(new ORM\Fields\IntegerField('ENTITY_TYPE'))
				->configureRequired()
				->configureTitle('Entity Type')
			,
			(new ORM\Fields\IntegerField('TTL'))
				->configureTitle('TTL in seconds')
			,
			(new ORM\Fields\StringField('TITLE'))
				->configureRequired()
				->configureTitle('Field title')
			,
		];
	}
}