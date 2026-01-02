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
 * @method static EO_Service_Query query()
 * @method static EO_Service_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Service_Result getById($id)
 * @method static EO_Service_Result getList(array $parameters = [])
 * @method static EO_Service_Entity getEntity()
 * @method static \Bitrix\Baas\Model\EO_Service createObject($setDefaultValues = true)
 * @method static \Bitrix\Baas\Model\EO_Service_Collection createCollection()
 * @method static \Bitrix\Baas\Model\EO_Service wakeUpObject($row)
 * @method static \Bitrix\Baas\Model\EO_Service_Collection wakeUpCollection($rows)
 */
class ServiceTable extends ORM\Data\DataManager
{
	use Traits\DeleteBatch;
	use Traits\UpdateBatch;

	public static function getTableName(): string
	{
		return 'b_baas_services';
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
				->configureTitle('Service string ID')
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
			(new ORM\Fields\StringField('TITLE'))
				->configureTitle('Title')
			,
			(new ORM\Fields\StringField('ACTIVE_SUBTITLE'))
				->configureTitle('Subtitle when service is active')
			,
			(new ORM\Fields\StringField('INACTIVE_SUBTITLE'))
				->configureTitle('Subtitle when service is not active')
			,
			new ORM\Fields\StringField('DESCRIPTION')
			,
			(new ORM\Fields\StringField('FEATURE_PROMOTION_CODE'))
				->configureTitle('Code for landings')
			,
			(new ORM\Fields\StringField('HELPER_CODE'))
				->configureTitle('Code for help article')
			,
			(new ORM\Fields\StringField('ADVERTISING_STRATEGY'))
				->configureTitle('The advertising strategy for the service from market or from landing page')
			,
			(new ORM\Fields\IntegerField('CURRENT_VALUE'))
				->configureTitle('Value')
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new ORM\Fields\IntegerField('MINIMAL_VALUE'))
				->configureTitle('Minimal value')
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new ORM\Fields\IntegerField('MAXIMAL_VALUE'))
				->configureTitle('Maximal value')
				->configureRequired()
				->configureDefaultValue(0)
			,
			(new ORM\Fields\DateField('EXPIRATION_DATE'))
				->configureTitle('Expiration date')
				->configureRequired()
				->configureDefaultValue(function() { return (new Main\Type\Date())->add('-1 day'); })
			,
			(new ORM\Fields\IntegerField('SORT'))
				->configureTitle('Sort order')
			,
			(new ORM\Fields\BooleanField('RENEWABLE'))
				->configureValues('N', 'Y')
				->configureDefaultValue('N')
			,
			(new Main\ORM\Fields\ArrayField('LANGUAGE_INFO'))
				->configureSerializationJson()
			,
			(new ORM\Fields\IntegerField('STATE_NUMBER'))
				->configureTitle('This is a marker to update consumed values')
			,
		];
	}
}
