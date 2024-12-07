<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class SupersetDashboardTagTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DASHBOARD_ID int
 * <li> TAG_ID int
 * </ul>
 *
 * @package Bitrix\BIConnector
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SupersetDashboardTag_Query query()
 * @method static EO_SupersetDashboardTag_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SupersetDashboardTag_Result getById($id)
 * @method static EO_SupersetDashboardTag_Result getList(array $parameters = [])
 * @method static EO_SupersetDashboardTag_Entity getEntity()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection createCollection()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardTag_Collection wakeUpCollection($rows)
 */

final class SupersetDashboardTagTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_biconnector_superset_dashboard_tag';
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
				->configurePrimary()
				->configureAutocomplete()
			,
			(new Fields\IntegerField('DASHBOARD_ID'))
				->configureRequired()
			,
			(new Fields\IntegerField('TAG_ID'))
				->configureRequired()
			,
			(new ReferenceField(
					'TAG',
					SupersetTagTable::class,
					Join::on('this.TAG_ID', 'ref.ID')
				))
				->configureJoinType(Join::TYPE_LEFT)
			,
			(new ReferenceField(
					'DASHBOARD',
				SupersetDashboardTable::class,
					Join::on('this.DASHBOARD_ID', 'ref.ID')
				))
				->configureJoinType(Join::TYPE_LEFT)
			,
		];
	}
}
