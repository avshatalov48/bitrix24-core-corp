<?php

namespace Bitrix\Baas\Model;

use Bitrix\Main;
use Bitrix\Main\ORM;

/**
 * Class ServiceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ServiceInPackage_Query query()
 * @method static EO_ServiceInPackage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ServiceInPackage_Result getById($id)
 * @method static EO_ServiceInPackage_Result getList(array $parameters = [])
 * @method static EO_ServiceInPackage_Entity getEntity()
 * @method static \Bitrix\Baas\Model\EO_ServiceInPackage createObject($setDefaultValues = true)
 * @method static \Bitrix\Baas\Model\EO_ServiceInPackage_Collection createCollection()
 * @method static \Bitrix\Baas\Model\EO_ServiceInPackage wakeUpObject($row)
 * @method static \Bitrix\Baas\Model\EO_ServiceInPackage_Collection wakeUpCollection($rows)
 */
class ServiceInPackageTable extends ORM\Data\DataManager
{
	use Traits\DeleteBatch;

	public static function getTableName(): string
	{
		return 'b_baas_service_in_package';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configureTitle('ID')
				->configureAutocomplete()
			,
			(new ORM\Fields\StringField('PACKAGE_CODE'))
				->configurePrimary()
				->configureTitle('Package string ID')
			,
			(new ORM\Fields\StringField('SERVICE_CODE'))
				->configurePrimary()
				->configureTitle('Service string ID')
			,
			(new ORM\Fields\IntegerField('VALUE'))
				->configureTitle('Maximal value')
				->configureDefaultValue(0)
			,
			new Main\ORM\Fields\Relations\Reference(
				'SERVICE',
				ServiceTable::class,
				['=this.SERVICE_CODE' => 'ref.CODE'],
				['join_type' => 'LEFT'],
			),
			new Main\ORM\Fields\Relations\Reference(
				'PACKAGE',
				PackageTable::class,
				['=this.PACKAGE_CODE' => 'ref.CODE'],
				['join_type' => 'LEFT'],
			),
		];
	}
}
