<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;

/**
 * Class SupersetScopeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SupersetScope_Query query()
 * @method static EO_SupersetScope_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SupersetScope_Result getById($id)
 * @method static EO_SupersetScope_Result getList(array $parameters = [])
 * @method static EO_SupersetScope_Entity getEntity()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection createCollection()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\EO_SupersetScope_Collection wakeUpCollection($rows)
 */
class SupersetScopeTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_biconnector_superset_scope';
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

			(new Fields\StringField('SCOPE_CODE'))
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
