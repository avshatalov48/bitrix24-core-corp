<?php

namespace Bitrix\SalesCenter\Model;

use Bitrix\Main;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

/**
 * Class MetaTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Meta_Query query()
 * @method static EO_Meta_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_Meta_Result getById($id)
 * @method static EO_Meta_Result getList(array $parameters = array())
 * @method static EO_Meta_Entity getEntity()
 * @method static \Bitrix\SalesCenter\Model\Meta createObject($setDefaultValues = true)
 * @method static \Bitrix\SalesCenter\Model\EO_Meta_Collection createCollection()
 * @method static \Bitrix\SalesCenter\Model\Meta wakeUpObject($row)
 * @method static \Bitrix\SalesCenter\Model\EO_Meta_Collection wakeUpCollection($rows)
 */
class MetaTable extends Main\ORM\Data\DataManager
{
	const HASH_LENGTH = 8;

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_salescenter_meta';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new StringField('HASH', [
				'unique' => true,
				'required' => true,
			]),
			new IntegerField('HASH_CRC', [
				'required' => true,
			]),
			new IntegerField('USER_ID', [
				'required' => true,
			]),
			new Main\ORM\Fields\TextField('META'),
			new IntegerField('META_CRC', [
				'required' => true,
			]),
		];
	}

	/**
	 * @return Main\ORM\Objectify\EntityObject|string
	 */
	public static function getObjectClass()
	{
		return Meta::class;
	}

	/**
	 * @param Event $event
	 * @return Main\ORM\EventResult
	 */
	public static function onBeforeAdd(Event $event)
	{
		$result = new Main\ORM\EventResult();

		$hash = static::getHash();
		$result->modifyFields([
			'HASH' => $hash,
			'HASH_CRC' => static::getCrc($hash),
			'META_CRC' => static::getCrc($event->getParameter('fields')['META']),
		]);

		return $result;
	}

	/**
	 * @param Event $event
	 * @return Main\ORM\EventResult
	 */
	public static function onBeforeUpdate(Event $event)
	{
		$result = new Main\ORM\EventResult();

		$result->unsetField('HASH');
		$result->unsetField('HASH_CRC');
		$result->modifyFields([
			'META_CRC' => static::getCrc($event->getParameter('fields')['META']),
		]);

		return $result;
	}

	protected static function getHash()
	{
		do
		{
			$hash = randString(static::HASH_LENGTH);
			$isNew = true;

			$meta = Meta::getByHash($hash);
			if($meta)
			{
				$isNew = false;
			}
		}
		while (!$isNew);

		return $hash;
	}

	/**
	 * @param string $str
	 * @return int
	 */
	public static function getCrc($str)
	{
		return \CBXShortUri::Crc32($str);
	}
}