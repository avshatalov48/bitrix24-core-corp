<?php
namespace Bitrix\Sign\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Type\BlankScenario;

Loc::loadMessages(__FILE__);

/**
 * Class BlankTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Blank_Query query()
 * @method static EO_Blank_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Blank_Result getById($id)
 * @method static EO_Blank_Result getList(array $parameters = [])
 * @method static EO_Blank_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Blank createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\BlankCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Blank wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\BlankCollection wakeUpCollection($rows)
 */
class BlankTable extends Entity\DataManager
{

	public static function getObjectClass(): string
	{
		return Blank::class;
	}

	public static function getCollectionClass(): string
	{
		return BlankCollection::class;
	}

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_blank';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 */
	public static function getMap(): array
	{
		return [
			'ID' => new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID'
			]),
			'TITLE' => new Entity\StringField('TITLE', [
				'title' => 'Blank title',
				'required' => true
			]),
			'EXTERNAL_ID' => new Entity\IntegerField('EXTERNAL_ID', [
				'title' => 'External ID'
			]),
			'HOST' => new Entity\StringField('HOST', [
				'title' => 'Portal host'
			]),
			'STATUS' => new Entity\StringField('STATUS', [
				'title' => 'Blank status'
			]),
			'FILE_ID' => (new \Bitrix\Main\ORM\Fields\ArrayField('FILE_ID', [
				'title' => 'File ids',
				'required' => true
			]))->configureSerializationJson(),
			'CONVERTED' => new Entity\StringField('CONVERTED', [
				'title' => 'File was converted in correct format',
				'required' => true,
				'default_value' => 'N'
			]),
			'SCENARIO' => (new Entity\IntegerField('SCENARIO'))
				->configureDefaultValue(BlankScenario::getMap()[BlankScenario::B2B])
			,
			'CREATED_BY_ID' => new Entity\IntegerField('CREATED_BY_ID', [
				'title' => 'Created by user ID',
				'required' => true
			]),
			'MODIFIED_BY_ID' => new Entity\IntegerField('MODIFIED_BY_ID', [
				'title' => 'Modified by user ID',
				'required' => true
			]),
			'DATE_CREATE' => new Entity\DatetimeField('DATE_CREATE', [
				'title' => 'Created on',
				'required' => true
			]),
			'DATE_MODIFY' => new Entity\DatetimeField('DATE_MODIFY', [
				'title' => 'Modified on',
				'required' => true
			]),
			'FOR_TEMPLATE' => (new Entity\BooleanField('FOR_TEMPLATE'))
				->configureValues(0, 1)
				->configureDefaultValue(false)
			,
		];
	}

	/**
	 * Before delete handler.
	 * @param Entity\Event $event Event instance.
	 * @return Entity\EventResult
	 */
	public static function onBeforeDelete(Entity\Event $event): Entity\EventResult
	{
		$result = new Entity\EventResult();
		$primary = $event->getParameter('primary');

		if ($primary['ID'] ?? null)
		{
			// check there are not documents linked to this blank
			$res = \Bitrix\Sign\Document::getList([
				'select' => [
					'ID'
				],
				'filter' => [
					'BLANK_ID' => $primary['ID']
				],
				'limit' => 1
			]);
			if ($res->fetch())
			{
				$result->addError(new \Bitrix\Main\ORM\EntityError(
					Loc::getMessage('SIGN_CORE_INT_BLANK_ERROR_BLANK_CONTAINS_DOCUMENTS'),
					'BLANK_CONTAINS_DOCUMENTS'
				));

				return $result;
			}

			// delete blank files
			$res = self::getList([
				'select' => [
					'FILE_ID'
				],
				'filter' => [
					'ID' => $primary['ID']
				]
			]);
			if ($row = $res->fetch())
			{
				foreach ((array)$row['FILE_ID'] as $fId)
				{
					\Bitrix\Sign\File::delete($fId);
				}
			}

			// delete linked entities
			$entities = [
				BlockTable::class,
				Integration\FormTable::class
			];
			/** @var \Bitrix\Main\Entity\DataManager $entity */
			foreach ($entities as $entity)
			{
				$res = $entity::getList([
					'select' => [
						'ID'
					],
					'filter' => [
						'BLANK_ID' => $primary['ID']
					]
				]);
				while ($row = $res->fetch())
				{
					$resDel = $entity::delete($row['ID']);

					if (!$resDel->isSuccess())
					{
						$error = $resDel->getErrors()[0];
						$result->addError(new \Bitrix\Main\ORM\EntityError(
							$error->getMessage(),
							$error->getCode()
						));

						break 2;
					}
				}
			}
		}

		return $result;
	}
}
