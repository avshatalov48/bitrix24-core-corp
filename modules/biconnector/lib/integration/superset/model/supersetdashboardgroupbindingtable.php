<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class SupersetScopeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SupersetDashboardGroupBinding_Query query()
 * @method static EO_SupersetDashboardGroupBinding_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SupersetDashboardGroupBinding_Result getById($id)
 * @method static EO_SupersetDashboardGroupBinding_Result getList(array $parameters = [])
 * @method static EO_SupersetDashboardGroupBinding_Entity getEntity()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardGroupBinding createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardGroupBinding_Collection createCollection()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardGroupBinding wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetDashboardGroupBinding_Collection wakeUpCollection($rows)
 */
class SupersetDashboardGroupBindingTable extends DataManager
{
	use DeleteByFilterTrait;
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_biconnector_superset_dashboard_group_binding';
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

			(new Fields\IntegerField('GROUP_ID'))
				->configureRequired()
			,

			(new ReferenceField(
				'GROUP',
				SupersetDashboardGroupTable::class,
				Join::on('this.GROUP_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_LEFT)
			,

			(new Fields\IntegerField('DASHBOARD_ID'))
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
