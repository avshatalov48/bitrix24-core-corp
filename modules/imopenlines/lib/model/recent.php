<?php
namespace Bitrix\ImOpenLines\Model;

use Bitrix\Im\V2\Common\MultiplyInsertTrait;
use Bitrix\Im\V2\Common\UpdateByFilterTrait;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;

/**
 * Class RecentTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Recent_Query query()
 * @method static EO_Recent_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Recent_Result getById($id)
 * @method static EO_Recent_Result getList(array $parameters = [])
 * @method static EO_Recent_Entity getEntity()
 * @method static \Bitrix\ImOpenLines\Model\EO_Recent createObject($setDefaultValues = true)
 * @method static \Bitrix\ImOpenLines\Model\EO_Recent_Collection createCollection()
 * @method static \Bitrix\ImOpenLines\Model\EO_Recent wakeUpObject($row)
 * @method static \Bitrix\ImOpenLines\Model\EO_Recent_Collection wakeUpCollection($rows)
 */
class RecentTable extends DataManager
{
	use MultiplyInsertTrait;
	use DeleteByFilterTrait;
	use UpdateByFilterTrait;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_imopenlines_recent';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'USER_ID' => new IntegerField(
				'USER_ID',
				[
					'primary' => true,
					'required' => true,
				]
			),
			'CHAT_ID' => new IntegerField(
				'CHAT_ID',
				[
					'primary' => true,
					'required' => true,
				]
			),
			'MESSAGE_ID' => new IntegerField(
				'MESSAGE_ID',
				[
					'required' => true,
				]
			),
			'SESSION_ID' => new IntegerField(
				'SESSION_ID',
				[
					'required' => true,
				]
			),
			'DATE_CREATE' => new DatetimeField(
				'DATE_CREATE',
				[
					'required' => true,
					'default' => function()
					{
						return new DateTime();
					},
				]
			),
			'CHAT' => [
				'data_type' => 'Bitrix\Im\Model\ChatTable',
				'reference' => ['=this.CHAT_ID' => 'ref.ID'],
				'join_type' => 'LEFT'
			],
			'MESSAGE' => [
				'data_type' => 'Bitrix\Im\Model\MessageTable',
				'reference' => ['=this.MESSAGE_ID' => 'ref.ID'],
				'join_type' => 'LEFT'
			],
			'SESSION' => [
				'data_type' => 'Bitrix\ImOpenLines\Model\SessionTable',
				'reference' => ['=this.SESSION_ID' => 'ref.ID'],
				'join_type' => 'LEFT'
			],
		];
	}
}
