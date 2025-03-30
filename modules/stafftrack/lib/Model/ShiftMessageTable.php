<?php

namespace Bitrix\StaffTrack\Model;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;

/**
 * Class ShiftMessageTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> SHIFT_ID int mandatory
 * <li> MESSAGE_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Stafftrack
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ShiftMessage_Query query()
 * @method static EO_ShiftMessage_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ShiftMessage_Result getById($id)
 * @method static EO_ShiftMessage_Result getList(array $parameters = [])
 * @method static EO_ShiftMessage_Entity getEntity()
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftMessage createObject($setDefaultValues = true)
 * @method static \Bitrix\StaffTrack\Model\ShiftMessageCollection createCollection()
 * @method static \Bitrix\StaffTrack\Model\EO_ShiftMessage wakeUpObject($row)
 * @method static \Bitrix\StaffTrack\Model\ShiftMessageCollection wakeUpCollection($rows)
 */
class ShiftMessageTable extends DataManager
{
	use DeleteByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_stafftrack_shift_message';
	}


	/**
	 * @return string
	 */
	public static function getCollectionClass(): string
	{
		return ShiftMessageCollection::class;
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			new IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('SHIFT_MESSAGE_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'SHIFT_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_MESSAGE_ENTITY_SHIFT_ID_FIELD'),
				]
			),
			new IntegerField(
				'MESSAGE_ID',
				[
					'required' => true,
					'title' => Loc::getMessage('SHIFT_MESSAGE_ENTITY_MESSAGE_ID_FIELD'),
				]
			),
			(new Reference(
				'SHIFT',
				ShiftTable::class,
				Join::on('this.SHIFT_ID', 'ref.ID')
			))
				->configureJoinType(Join::TYPE_INNER)
			,
		];
	}
}
