<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class SupersetDashboardUrlParameterTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SupersetDashboardUrlParameter_Query query()
 * @method static EO_SupersetDashboardUrlParameter_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SupersetDashboardUrlParameter_Result getById($id)
 * @method static EO_SupersetDashboardUrlParameter_Result getList(array $parameters = [])
 * @method static EO_SupersetDashboardUrlParameter_Entity getEntity()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection createCollection()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardUrlParameter_Collection wakeUpCollection($rows)
 */

final class SupersetDashboardUrlParameterTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_biconnector_superset_dashboard_url_parameter';
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
			(new Fields\IntegerField('DASHBOARD_ID')),
			(new Fields\StringField('CODE'))
				->configureRequired()
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
