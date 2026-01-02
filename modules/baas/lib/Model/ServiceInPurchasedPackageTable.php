<?php

namespace Bitrix\Baas\Model;

use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Baas;

/**
 * Class ServiceInPurchasedPackageTable. This class for now is a copy of ServiceInPurchasesTable due to the fact that
 * the Baas does not have a separate table for purchase and purchased packages. ServiceInPurchasedPackageTable should
 * replace ServiceInPurchasesTable.
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ServiceInPurchasedPackage_Query query()
 * @method static EO_ServiceInPurchasedPackage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ServiceInPurchasedPackage_Result getById($id)
 * @method static EO_ServiceInPurchasedPackage_Result getList(array $parameters = [])
 * @method static EO_ServiceInPurchasedPackage_Entity getEntity()
 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage createObject($setDefaultValues = true)
 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection createCollection()
 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage wakeUpObject($row)
 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchasedPackage_Collection wakeUpCollection($rows)
 */
class ServiceInPurchasedPackageTable extends ORM\Data\DataManager
{
	use Traits\DeleteBatch;
	use Traits\UpdateBatch;
	use Traits\InsertUpdate;

	public static function getTableName(): string
	{
		return 'b_baas_service_in_purchased_pack';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configureTitle('ID')
				->configureAutocomplete()
			,
			(new ORM\Fields\StringField('PURCHASED_PACKAGE_CODE'))
				->configurePrimary()
				->configureTitle('Purchased package code')
			,
			(new ORM\Fields\StringField('SERVICE_CODE'))
				->configurePrimary()
				->configureTitle('Service code')
			,
			(new ORM\Fields\IntegerField('CURRENT_VALUE'))
				->configureTitle('Value')
				->configureRequired()
			,
			(new ORM\Fields\IntegerField('STATE_NUMBER'))
				->configureTitle('This is a marker to update consumed values. It is actual for b24 only')
			,
			new Main\ORM\Fields\Relations\Reference(
				'SERVICE',
				ServiceTable::class,
				Main\ORM\Query\Join::on('this.SERVICE_CODE', 'ref.CODE'),
			),
			(new Main\ORM\Fields\Relations\Reference(
				'PURCHASED_PACKAGE',
				PurchasedPackageTable::class,
				Main\ORM\Query\Join::on('this.PURCHASED_PACKAGE_CODE', 'ref.CODE'),
			))->configureJoinType('inner'),
			(new Main\ORM\Fields\Relations\Reference(
				'PACKAGE',
				PackageTable::class,
				Main\ORM\Query\Join::on('this.PURCHASED_PACKAGE.PACKAGE_CODE', 'ref.CODE'),
			))->configureJoinType('inner'),
			(new Main\ORM\Fields\Relations\Reference(
				'SERVICES_IN_PACKAGE',
				ServiceInPackageTable::class,
				Main\ORM\Query\Join::on('this.PACKAGE.CODE', 'ref.PACKAGE_CODE')
					->whereColumn('this.SERVICE_CODE', 'ref.SERVICE_CODE'),
			))->configureJoinType('inner'),
		];
	}
}
