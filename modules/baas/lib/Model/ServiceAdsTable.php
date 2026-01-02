<?php

namespace Bitrix\Baas\Model;

use Bitrix\Main\ORM;

/**
 * Class ServiceTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ServiceAds_Query query()
 * @method static EO_ServiceAds_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ServiceAds_Result getById($id)
 * @method static EO_ServiceAds_Result getList(array $parameters = [])
 * @method static EO_ServiceAds_Entity getEntity()
 * @method static \Bitrix\Baas\Model\EO_ServiceAds createObject($setDefaultValues = true)
 * @method static \Bitrix\Baas\Model\EO_ServiceAds_Collection createCollection()
 * @method static \Bitrix\Baas\Model\EO_ServiceAds wakeUpObject($row)
 * @method static \Bitrix\Baas\Model\EO_ServiceAds_Collection wakeUpCollection($rows)
 */
class ServiceAdsTable extends ORM\Data\DataManager
{
	use Traits\DeleteBatch;

	public static function getTableName(): string
	{
		return 'b_baas_service_ads';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\StringField('SERVICE_CODE'))
				->configurePrimary()
				->configureTitle('Service code as ID')
			,
			(new ORM\Fields\StringField('LANGUAGE_ID'))
				->configurePrimary()
				->configureTitle('Language Id')
			,
			(new ORM\Fields\StringField('TITLE'))
				->configureTitle('Title')
			,
			(new ORM\Fields\StringField('SUBTITLE'))
				->configureTitle('Subtitle')
			,
			(new ORM\Fields\StringField('SUBTITLE_DESCRIPTION'))
				->configureTitle('Additional description after subtitle')
			,
			(new ORM\Fields\StringField('ICON_URL'))
				->configureTitle('Icon')
			,
			(new ORM\Fields\StringField('ICON_FILE_TYPE'))
				->configureTitle('Icon')
			,
			(new ORM\Fields\StringField('VIDEO_URL'))
				->configureTitle('Icon')
			,
			(new ORM\Fields\StringField('VIDEO_FILE_TYPE'))
				->configureTitle('Icon')
			,
			(new ORM\Fields\StringField('FEATURE_PROMOTION_CODE'))
				->configureTitle('Code for landings')
			,
			(new ORM\Fields\StringField('HELPER_CODE'))
				->configureSize(255)
			,
		];
	}
}
