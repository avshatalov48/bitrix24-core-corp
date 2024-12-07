<?php

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\Security\Random;

/**
 * Class QueueTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Queue_Query query()
 * @method static EO_Queue_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Queue_Result getById($id)
 * @method static EO_Queue_Result getList(array $parameters = [])
 * @method static EO_Queue_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_Queue createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_Queue_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_Queue wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_Queue_Collection wakeUpCollection($rows)
 */
class QueueTable extends Entity\DataManager
{
	public const HASH_LENGTH = 32;

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_queue';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			(new Entity\StringField('HASH', [
				'required' => true,
			]))
				->configureDefaultValue([__CLASS__, 'generateHash'])
				->configureSize(self::HASH_LENGTH)
			,
			new Entity\StringField('ENGINE_CLASS', [
				'required' => true,
			]),
			new Entity\StringField('ENGINE_CODE'),
			(new ArrayField('ENGINE_CUSTOM_SETTINGS'))
				->configureSerializationJson(),
			new Entity\StringField('PAYLOAD_CLASS', [
				'required' => true,
			]),
			new Entity\StringField('PAYLOAD', [
				'required' => true,
				'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
				'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
			]),
			new Entity\StringField('CONTEXT'),
			(new ArrayField('PARAMETERS'))
				->configureSerializationJson(),
			new Entity\StringField('HISTORY_WRITE'),
			new Entity\IntegerField('HISTORY_GROUP_ID'),
			new Entity\StringField('CACHE_HASH', [
				'required' => true,
			]),
			new Entity\DatetimeField('DATE_CREATE'),
		];
	}

	/**
	 * Generates hash for the queue item.
	 * @return string
	 */
	public static function generateHash(): string
	{
		return Random::getString(self::HASH_LENGTH, true);
	}
}
