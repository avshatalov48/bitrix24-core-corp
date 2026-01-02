<?php

namespace Bitrix\BIConnector\Integration\Superset\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\EntityError;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Class SupersetScopeTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_SupersetDashboardGroup_Query query()
 * @method static EO_SupersetDashboardGroup_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_SupersetDashboardGroup_Result getById($id)
 * @method static EO_SupersetDashboardGroup_Result getList(array $parameters = [])
 * @method static EO_SupersetDashboardGroup_Entity getEntity()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroup createObject($setDefaultValues = true)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupCollection createCollection()
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroup wakeUpObject($row)
 * @method static \Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardGroupCollection wakeUpCollection($rows)
 */
class SupersetDashboardGroupTable extends DataManager
{
	use DeleteByFilterTrait;

	public const GROUP_TYPE_SYSTEM = 'SYSTEM';
	public const GROUP_TYPE_CUSTOM = 'CUSTOM';
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_biconnector_superset_dashboard_group';
	}

	public static function getObjectClass()
	{
		return SupersetDashboardGroup::class;
	}

	public static function getCollectionClass()
	{
		return SupersetDashboardGroupCollection::class;
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

			(new Fields\StringField('CODE'))
				->configureRequired()
			,

			(new Fields\StringField('NAME'))
				->configureRequired()
			,

			(new Fields\EnumField('TYPE'))
				->configureRequired()
				->configureValues([
					self::GROUP_TYPE_SYSTEM,
					self::GROUP_TYPE_CUSTOM,
				])
				->configureDefaultValue(self::GROUP_TYPE_CUSTOM)
			,

			(new Fields\IntegerField('OWNER_ID'))
				->configureNullable(),

			(new Fields\DatetimeField('DATE_CREATE'))
				->configureRequired()
				->configureDefaultValue(fn() => new DateTime()),

			(new Fields\DatetimeField('DATE_MODIFY'))
				->configureRequired()
				->configureDefaultValue(fn() => new DateTime()),

			(new Fields\Relations\ManyToMany(
				'DASHBOARDS',
				SupersetDashboardTable::class,
			))
				->configureMediatorTableName('b_biconnector_superset_dashboard_group_binding')
				->configureLocalPrimary('ID', 'GROUP_ID')
				->configureRemotePrimary('ID', 'DASHBOARD_ID')
			,

			(new Fields\Relations\OneToMany(
				'SCOPE',
				SupersetDashboardGroupScopeTable::class,
				'GROUP',
			))
				->configureJoinType(Join::TYPE_LEFT)
				->configureCascadeDeletePolicy(Fields\Relations\CascadePolicy::NO_ACTION)
			,
		];
	}

	/**
	 * Deletes related dashboard and scope
	 *
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onAfterDelete(Event $event): EventResult
	{
		$result = new EventResult();

		$object = $event->getParameter('object');
		$id = $object->getId();

		SupersetDashboardGroupBindingTable::deleteByFilter([
			'GROUP_ID' => $id,
		]);

		SupersetDashboardGroupScopeTable::deleteByFilter([
			'GROUP_ID' => $id,
		]);

		return $result;
	}

	public static function onBeforeUpdate(Event $event): EventResult
	{
		$result = new EventResult();

		$fields = $event->getParameter('fields');
		$fields['DATE_MODIFY'] = new DateTime();

		$result->modifyFields($fields);

		return $result;
	}
}
