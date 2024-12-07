<?php

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\Fields\ArrayField;

/**
 * Class HistoryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_History_Query query()
 * @method static EO_History_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_History_Result getById($id)
 * @method static EO_History_Result getList(array $parameters = [])
 * @method static EO_History_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_History createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_History_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_History wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_History_Collection wakeUpCollection($rows)
 */
class HistoryTable extends Entity\DataManager
{
	use DeleteByFilterTrait;
	
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_ai_history';
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
			new Entity\StringField('CONTEXT_MODULE', [
				'required' => true,
			]),
			new Entity\StringField('CONTEXT_ID', [
				'required' => true,
			]),
			new Entity\StringField('ENGINE_CLASS', [
				'required' => true,
			]),
			new Entity\StringField('ENGINE_CODE'),
			new Entity\StringField('PAYLOAD_CLASS', [
				'required' => true,
			]),
			new Entity\StringField('PAYLOAD', [
				'required' => true,
				'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
				'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
			]),
			(new ArrayField('PARAMETERS'))
				->configureSerializationJson(),
			new Entity\IntegerField('GROUP_ID', [
				'required' => true,
			]),
			new Entity\StringField('REQUEST_TEXT', [
				'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
				'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
			]),
			new Entity\StringField('RESULT_TEXT', [
				'required' => true,
				'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
				'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
			]),
			new Entity\StringField('CONTEXT', [
				'save_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getSaveModificator'],
				'fetch_data_modification' => ['\Bitrix\Main\Text\Emoji', 'getFetchModificator'],
			]),
			(new Entity\BooleanField('CACHED'))
				->configureValues(0, 1)
				->configureDefaultValue(0),
			new Entity\DatetimeField('DATE_CREATE'),
			new Entity\IntegerField('CREATED_BY_ID', [
				'required' => true,
			]),
		];
	}
}
