<?php

namespace Bitrix\Baas\Model;

use Bitrix\Main;
use Bitrix\Main\ORM;

/**
 * Class PurchasedPackageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PurchasedPackage_Query query()
 * @method static EO_PurchasedPackage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_PurchasedPackage_Result getById($id)
 * @method static EO_PurchasedPackage_Result getList(array $parameters = [])
 * @method static EO_PurchasedPackage_Entity getEntity()
 * @method static \Bitrix\Baas\Model\EO_PurchasedPackage createObject($setDefaultValues = true)
 * @method static \Bitrix\Baas\Model\EO_PurchasedPackage_Collection createCollection()
 * @method static \Bitrix\Baas\Model\EO_PurchasedPackage wakeUpObject($row)
 * @method static \Bitrix\Baas\Model\EO_PurchasedPackage_Collection wakeUpCollection($rows)
 */
class PurchasedPackageTable extends ORM\Data\DataManager
{
	use Traits\DeleteBatch;
	use Traits\InsertIgnore;
	use Traits\UpdateBatch;

	public static function getTableName(): string
	{
		return 'b_baas_purchased_package';
	}

	public static function getMap(): array
	{
		$connection = Main\Application::getInstance()->getConnection();
		$sqlHelper = $connection->getSqlHelper();

		return [
			(new ORM\Fields\IntegerField('ID'))
				->configureTitle('ID')
				->configureAutocomplete()
			,
			(new ORM\Fields\StringField('CODE'))
				->configurePrimary()
				->configureTitle('Purchased package string ID')
			,
			(new ORM\Fields\StringField('PACKAGE_CODE'))
				->configurePrimary()
				->configureTitle('Package string ID')
			,
			(new ORM\Fields\StringField('PURCHASE_CODE'))
				->configureTitle('Purchase string ID')
				->configureRequired()
			,
			(new ORM\Fields\EnumField('ACTIVE'))
				->configureValues(['Y', 'N'])
				->configureDefaultValue('Y')
			,
			(new ORM\Fields\DateField('START_DATE'))
				->configureTitle('Start activity date')
				->configureRequired()
				->configureDefaultValue(function() { return new Main\Type\Date(); })
			,
			(new ORM\Fields\DateField('EXPIRATION_DATE'))
				->configureTitle('Expiration date')
				->configureRequired()
				->configureDefaultValue(function() { return (new Main\Type\Date())->add('30 days'); })
			,
			new Main\ORM\Fields\Relations\Reference(
				'PACKAGE',
				PackageTable::class,
				['=this.PACKAGE_CODE' => 'ref.CODE'],
				['join_type' => 'LEFT'],
			),
			new Main\ORM\Fields\Relations\Reference(
				'SERVICE_IN_PURCHASED_PACKAGE',
				ServiceInPurchasedPackageTable::class,
				['=this.CODE' => 'ref.PURCHASED_PACKAGE_CODE'],
				['join_type' => 'LEFT'],
			),
			(new Main\ORM\Fields\ExpressionField(
				'ACTUAL',
				'CASE WHEN %s <= ' . $sqlHelper->getCurrentDateFunction()
				. ' AND ' . $sqlHelper->getCurrentDateFunction() . ' <= %s THEN \'Y\' ELSE \'N\' END',
				[
					'START_DATE',
					'EXPIRATION_DATE',
				],
				['values' => ['N', 'Y']],
			))
				->configureValueType(Main\ORM\Fields\BooleanField::class)
				->configureTitle('The purchase is active now or not')
			,
			(new Main\ORM\Fields\ExpressionField(
				'EXPIRED',
				'CASE WHEN %s <= ' . $sqlHelper->getCurrentDateFunction() . ' THEN \'Y\' ELSE \'N\' END',
				[
					'EXPIRATION_DATE',
				],
				['values' => ['N', 'Y']],
			))
				->configureValueType(Main\ORM\Fields\BooleanField::class)
				->configureTitle('The purchase is expired')
			,
		];
	}
}
