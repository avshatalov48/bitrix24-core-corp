<?php
namespace Bitrix\Controller;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;

/**
 * Class GroupMapTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CONTROLLER_GROUP_ID int optional
 * <li> REMOTE_GROUP_CODE string(30) optional
 * <li> LOCAL_GROUP_CODE string(30) optional
 * <li> CONTROLLER_GROUP reference to {@link \Bitrix\Controller\GroupTable}
 * </ul>
 *
 * @package Bitrix\Controller
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_GroupMap_Query query()
 * @method static EO_GroupMap_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_GroupMap_Result getById($id)
 * @method static EO_GroupMap_Result getList(array $parameters = array())
 * @method static EO_GroupMap_Entity getEntity()
 * @method static \Bitrix\Controller\EO_GroupMap createObject($setDefaultValues = true)
 * @method static \Bitrix\Controller\EO_GroupMap_Collection createCollection()
 * @method static \Bitrix\Controller\EO_GroupMap wakeUpObject($row)
 * @method static \Bitrix\Controller\EO_GroupMap_Collection wakeUpCollection($rows)
 */

class GroupMapTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_controller_group_map';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Fields\IntegerField(
				'ID',
				[
					'primary' => true,
					'autocomplete' => true,
					'title' => Loc::getMessage('GROUP_MAP_ENTITY_ID_FIELD'),
				]
			),
			new Fields\IntegerField(
				'CONTROLLER_GROUP_ID',
				[
					'title' => Loc::getMessage('GROUP_MAP_ENTITY_CONTROLLER_GROUP_ID_FIELD'),
				]
			),
			new Fields\StringField(
				'REMOTE_GROUP_CODE',
				[
					'validation' => [__CLASS__, 'validateRemoteGroupCode'],
					'title' => Loc::getMessage('GROUP_MAP_ENTITY_REMOTE_GROUP_CODE_FIELD'),
				]
			),
			new Fields\StringField(
				'LOCAL_GROUP_CODE',
				[
					'validation' => [__CLASS__, 'validateLocalGroupCode'],
					'title' => Loc::getMessage('GROUP_MAP_ENTITY_LOCAL_GROUP_CODE_FIELD'),
				]
			),
			new Fields\Relations\Reference(
				'CONTROLLER_GROUP',
				'Bitrix\Controller\GroupTable',
				['=this.CONTROLLER_GROUP_ID' => 'ref.ID']
			),
		];
	}

	/**
	 * Returns validators for REMOTE_GROUP_CODE field.
	*
	* @return array
	*/
	public static function validateRemoteGroupCode(): array
	{
		return [
			new Fields\Validators\LengthValidator(null, 30),
		];
	}

	/**
	 * Returns validators for LOCAL_GROUP_CODE field.
	*
	* @return array
	*/
	public static function validateLocalGroupCode(): array
	{
		return [
			new Fields\Validators\LengthValidator(null, 30),
		];
	}

	/**
	 * Returns true if the mapping is exists.
	 *
	 * @param array $fields Filter array.
	 * @return bool
	 * @throws Main\ArgumentException
	 */
	public static function isExists($fields)
	{
		$filter = [];
		foreach ($fields as $name => $value)
		{
			$filter['=' . $name] = $value;
		}

		$match = false;
		$list = self::getList(['filter' => $filter]);
		while (($result = $list->fetch()) && !$match)
		{
			$match = true;
			foreach ($fields as $name => $value)
			{
				$match = $match && ($result[$name] === $value);
			}
		}

		return $match;
	}

	/**
	 * Returns array of mapping arrays in form of array("FROM"=>NN, "TO"=>MM).
	 *
	 * @param string $from Mapping field.
	 * @param string $to Mapping field.
	 * @return array
	 * @throws Main\ArgumentException
	 */
	public static function getMapping($from, $to)
	{
		$result = [];
		$filter = [
			'!=' . $from => false,
			'!=' . $to => false,
		];
		$list = self::getList(['filter' => $filter]);
		while ($item = $list->fetch())
		{
			$result[] = [
				'FROM' => $item[$from],
				'TO' => $item[$to],
			];
		}
		return $result;
	}
}
