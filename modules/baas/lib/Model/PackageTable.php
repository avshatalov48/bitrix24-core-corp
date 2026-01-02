<?php

namespace Bitrix\Baas\Model;

use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class PackageTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Package_Query query()
 * @method static EO_Package_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Package_Result getById($id)
 * @method static EO_Package_Result getList(array $parameters = [])
 * @method static EO_Package_Entity getEntity()
 * @method static \Bitrix\Baas\Model\EO_Package createObject($setDefaultValues = true)
 * @method static \Bitrix\Baas\Model\EO_Package_Collection createCollection()
 * @method static \Bitrix\Baas\Model\EO_Package wakeUpObject($row)
 * @method static \Bitrix\Baas\Model\EO_Package_Collection wakeUpCollection($rows)
 */
class PackageTable extends ORM\Data\DataManager
{
	use Traits\DeleteBatch;

	public static function getTableName(): string
	{
		return 'b_baas_package';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configureTitle('ID')
				->configureAutocomplete()
			,
			(new ORM\Fields\StringField('CODE'))
				->configurePrimary()
				->configureTitle('Package string ID')
			,
			(new Main\ORM\Fields\ArrayField('ICON_STYLE'))
				->configureTitle('Icon style')
				->configureSerializationJson()
			,
			(new ORM\Fields\StringField('ICON_CLASS'))
				->configureTitle('Icon className in the css design token terms')
			,
			(new ORM\Fields\StringField('ICON_COLOR'))
				->configureTitle('Icon color in the css design token terms')
			,
			(new ORM\Fields\StringField('PURCHASE_URL'))
				->configureTitle('Purchase url')
			,
			(new ORM\Fields\StringField('TITLE'))
				->configureTitle('Title')
			,
			(new ORM\Fields\StringField('DESCRIPTION'))
				->configureTitle('Package description')
			,
			(new ORM\Fields\DecimalField('PRICE_VALUE'))
				->configureTitle('Price value')
				->configureRequired()
			,
			(new ORM\Fields\StringField('PRICE_CURRENCY_ID'))
				->configureSize(3)
			,
			(new ORM\Fields\StringField('PRICE_DESCRIPTION'))
				->configureSize(1000)
			,
			(new ORM\Fields\EnumField('ACTIVE'))
				->configureValues(['Y', 'N'])
				->configureDefaultValue('Y')
			,
			(new ORM\Fields\StringField('FEATURE_PROMOTION_CODE'))
				->configureTitle('Code for landings')
			,
			(new ORM\Fields\StringField('HELPER_CODE'))
				->configureSize(50)
			,
			(new ORM\Fields\StringField('DISTRIBUTION_STRATEGY'))
				->configureTitle('The plan distribution strategy for the package from market or from checkout page')
			,
			(new ORM\Fields\IntegerField('SORT'))
				->configureTitle('Sort order')
			,
			(new Main\ORM\Fields\ArrayField('LANGUAGE_INFO'))
				->configureSerializationJson()
			,
			(new Main\ORM\Fields\Relations\OneToMany(
				'SERVICE_IN_PACKAGE',
				ServiceInPackageTable::class,
				'PACKAGE',
			))->configureJoinType(Join::TYPE_LEFT),
		];
	}
}
