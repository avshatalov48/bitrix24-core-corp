<?php

namespace Bitrix\Crm\Model;

use Bitrix\Crm\FieldMultiTable;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class FieldMultiPhoneCountryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_FieldMultiPhoneCountry_Query query()
 * @method static EO_FieldMultiPhoneCountry_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_FieldMultiPhoneCountry_Result getById($id)
 * @method static EO_FieldMultiPhoneCountry_Result getList(array $parameters = [])
 * @method static EO_FieldMultiPhoneCountry_Entity getEntity()
 * @method static \Bitrix\Crm\Model\EO_FieldMultiPhoneCountry createObject($setDefaultValues = true)
 * @method static \Bitrix\Crm\Model\EO_FieldMultiPhoneCountry_Collection createCollection()
 * @method static \Bitrix\Crm\Model\EO_FieldMultiPhoneCountry wakeUpObject($row)
 * @method static \Bitrix\Crm\Model\EO_FieldMultiPhoneCountry_Collection wakeUpCollection($rows)
 */
class FieldMultiPhoneCountryTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'b_crm_field_multi_phone_country';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new IntegerField('FM_ID'))
				->configureRequired(),
			(new StringField('COUNTRY_CODE'))
				->configureRequired()
				->configureSize(2),

			(new Reference('FM', FieldMultiTable::class, Join::on('this.FM_ID', 'ref.ID')))
				->configureCascadeDeletePolicy(CascadePolicy::FOLLOW)
			,
		];
	}

	public static function getDataByMultiFieldId(array $fmIds): array
	{
		return static::getList([
			'select' => ['ID', 'FM_ID', 'COUNTRY_CODE'],
			'filter' => [
				'@FM_ID' => $fmIds
			]
		])->fetchAll();
	}

	public static function deleteByByMultiFieldId(int $fmId): void
	{
		$connection = Application::getConnection();
		$helper = $connection->getSqlHelper();

		$connection->query(
			sprintf(
				'DELETE FROM %s WHERE FM_ID = %d',
				$helper->quote(static::getTableName()),
				$helper->convertToDbInteger($fmId)
			)
		);
		self::cleanCache();
	}
}
