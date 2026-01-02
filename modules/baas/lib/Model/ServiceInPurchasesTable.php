<?php

namespace Bitrix\Baas\Model;

use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Baas;

/**
 * Class ServiceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ServiceInPurchases_Query query()
 * @method static EO_ServiceInPurchases_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ServiceInPurchases_Result getById($id)
 * @method static EO_ServiceInPurchases_Result getList(array $parameters = [])
 * @method static EO_ServiceInPurchases_Entity getEntity()
 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchases createObject($setDefaultValues = true)
 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchases_Collection createCollection()
 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchases wakeUpObject($row)
 * @method static \Bitrix\Baas\Model\EO_ServiceInPurchases_Collection wakeUpCollection($rows)
 */
class ServiceInPurchasesTable extends ORM\Data\DataManager
{
	use Traits\DeleteBatch;

	public static function getTableName(): string
	{
		return 'b_baas_purchase_service';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configureTitle('ID')
				->configureAutocomplete()
			,
			(new ORM\Fields\StringField('PURCHASE_CODE'))
				->configurePrimary()
				->configureTitle('Purchase code')
			,
			(new ORM\Fields\StringField('SERVICE_CODE'))
				->configurePrimary()
				->configureTitle('Service code')
			,
			(new ORM\Fields\IntegerField('CURRENT_VALUE'))
				->configureTitle('Value')
				->configureRequired()
			,
			(new ORM\Fields\IntegerField('BILLING_VALUE'))
				->configureTitle('Value from billing center. Uses for information purposes only')
			,
			new Main\ORM\Fields\Relations\Reference(
				'SERVICE',
				ServiceTable::class,
				Main\ORM\Query\Join::on('this.SERVICE_CODE', 'ref.CODE')
			),
			(new Main\ORM\Fields\Relations\Reference(
				'PURCHASE',
				PurchaseTable::class,
				Main\ORM\Query\Join::on('this.PURCHASE_CODE', 'ref.CODE')
			))->configureJoinType('inner'),
			(new Main\ORM\Fields\Relations\Reference(
				'PACKAGE',
				PackageTable::class,
				Main\ORM\Query\Join::on('this.PURCHASE.PACKAGE_CODE', 'ref.CODE')
			))->configureJoinType('inner'),
			(new Main\ORM\Fields\Relations\Reference(
				'SERVICES_IN_PACKAGE',
				ServiceInPackageTable::class,
				Main\ORM\Query\Join::on('this.PACKAGE.CODE', 'ref.PACKAGE_CODE')
					->whereColumn('this.SERVICE_CODE', 'ref.SERVICE_CODE')
			))->configureJoinType('inner'),
		];
	}
}
