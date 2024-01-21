<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\AppTable;

/**
 * Class DashboardTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> EXTERNAL_ID int
 * <li> STATUS char(1)
 * <li> DATE_FILTER_START date mandatory
 * <li> DATE_FILTER_END date mandatory
 * <li> TYPE enum mandatory
 * <li> APP_ID string(128)
 * <li> SOURCE_ID int
 * </ul>
 *
 * @package Bitrix\BIConnector
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SupersetDashboard_Query query()
 * @method static EO_SupersetDashboard_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SupersetDashboard_Result getById($id)
 * @method static EO_SupersetDashboard_Result getList(array $parameters = [])
 * @method static EO_SupersetDashboard_Entity getEntity()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection createCollection()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboard_Collection wakeUpCollection($rows)
 */

final class SupersetDashboardTable extends DataManager
{
	public const DASHBOARD_TYPE_SYSTEM = 'SYSTEM';
	public const DASHBOARD_TYPE_CUSTOM = 'CUSTOM';
	public const DASHBOARD_TYPE_MARKET = 'MARKET';

	public const DASHBOARD_STATUS_LOAD = 'L';
	public const DASHBOARD_STATUS_READY = 'R';
	public const DASHBOARD_STATUS_FAILED = 'F';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_biconnector_superset_dashboard';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			(new Fields\IntegerField('ID'))
				->configurePrimary(),
			(new Fields\IntegerField('EXTERNAL_ID')),

			(new Fields\EnumField('STATUS'))
				->configureRequired()
				->configureValues([
					self::DASHBOARD_STATUS_LOAD,
					self::DASHBOARD_STATUS_READY,
					self::DASHBOARD_STATUS_FAILED,
				])
				->configureDefaultValue(self::DASHBOARD_STATUS_READY)
			,

			(new Fields\StringField('TITLE'))
				->configureSize(128)
			,

			(new Fields\DateField('DATE_FILTER_START'))
				->configureNullable()
			,

			(new Fields\DateField('DATE_FILTER_END'))
				->configureNullable()
			,

			(new Fields\EnumField('TYPE'))
				->configureRequired()
				->configureValues([
					self::DASHBOARD_TYPE_SYSTEM,
					self::DASHBOARD_TYPE_CUSTOM,
					self::DASHBOARD_TYPE_MARKET,
				])
				->configureDefaultValue(self::DASHBOARD_TYPE_CUSTOM),

			(new Fields\StringField('FILTER_PERIOD')),

			(new Fields\StringField('APP_ID'))
				->configureSize(128)
			,

			(new ReferenceField(
				'APP',
				AppTable::class,
				Join::on('this.APP_ID', 'ref.CODE')
			))->configureJoinType(Join::TYPE_LEFT),
			(new Fields\IntegerField('SOURCE_ID')),

			(new ReferenceField(
				'SOURCE',
				self::class,
				Join::on('this.SOURCE_ID', 'ref.ID')
			))->configureJoinType(Join::TYPE_LEFT),

			(new Fields\DateField('DATE_CREATE'))
				->configureRequired()
				->configureDefaultValue(fn() => new DateTime()),

			(new Fields\IntegerField('CREATED_BY_ID'))
				->configureNullable(),
		];
	}
}
