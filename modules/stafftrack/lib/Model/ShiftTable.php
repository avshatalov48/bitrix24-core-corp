<?php
namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\CascadePolicy;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

/**
 * Class ShiftTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> SHIFT_DATE date mandatory
 * <li> DATE_CREATE datetime optional default current datetime
 * <li> STATUS int mandatory
 * <li> LOCATION text optional
 * </ul>
 *
 * @package Bitrix\Stafftrack
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Shift_Query query()
 * @method static EO_Shift_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Shift_Result getById($id)
 * @method static EO_Shift_Result getList(array $parameters = [])
 * @method static EO_Shift_Entity getEntity()
 * @method static \Bitrix\StaffTrack\Model\Shift createObject($setDefaultValues = true)
 * @method static \Bitrix\StaffTrack\Model\ShiftCollection createCollection()
 * @method static \Bitrix\StaffTrack\Model\Shift wakeUpObject($row)
 * @method static \Bitrix\StaffTrack\Model\ShiftCollection wakeUpCollection($rows)
 */

class ShiftTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_stafftrack_shift';
	}

	/**
	 * @return string
	 */
	public static function getObjectClass(): string
	{
		return Shift::class;
	}

	/**
	 * @return string
	 */
	public static function getCollectionClass(): string
	{
		return ShiftCollection::class;
	}

	/**
	 * @return string[]
	 */
	public static function getFields(): array
	{
		return [
			'ID',
			'USER_ID',
			'SHIFT_DATE',
			'DATE_CREATE',
			'LOCATION',
			'STATUS',
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap()
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('SHIFT_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'USER_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_ENTITY_USER_ID_FIELD'),
				]
			),
			new DateField(
				'SHIFT_DATE',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_ENTITY_SHIFT_DATE_FIELD'),
				]
			),
			new DatetimeField(
				'DATE_CREATE',
				[
					'default' => function()
					{
						return new DateTime();
					},
					'title' => Loc::getMessage('SHIFT_ENTITY_DATE_CREATE_FIELD'),
				]
			),
			new IntegerField(
				'STATUS',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_ENTITY_STATUS_FIELD'),
				]
			),
			new TextField(
				'LOCATION',
				[
					'title' => Loc::getMessage('SHIFT_ENTITY_LOCATION_FIELD'),
				]
			),
			(new Reference(
				'GEO',
				ShiftGeoTable::class,
				Join::on('this.ID', 'ref.SHIFT_ID')
			))
				->configureJoinType(Join::TYPE_LEFT)
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
			,
			(new Reference(
				'CANCELLATION',
				ShiftCancellationTable::class,
				Join::on('this.ID', 'ref.SHIFT_ID')
			))
				->configureJoinType(Join::TYPE_LEFT)
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
			,
			(new Reference(
				'GEO_INNER',
				ShiftGeoTable::class,
				Join::on('this.ID', 'ref.SHIFT_ID')
			))
				->configureJoinType(Join::TYPE_INNER)
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
			,
			(new OneToMany(
				'MESSAGES',
				ShiftMessageTable::class,
				'SHIFT',
			))
				->configureJoinType(Join::TYPE_LEFT)
				->configureCascadeDeletePolicy(CascadePolicy::NO_ACTION)
		];
	}
}
