<?php
namespace Bitrix\Sign\Internal;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

/**
 * Class MemberTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_File_Query query()
 * @method static EO_File_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_File_Result getById($id)
 * @method static EO_File_Result getList(array $parameters = [])
 * @method static EO_File_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\File createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\FileCollection createCollection()
 * @method static \Bitrix\Sign\Internal\File wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\FileCollection wakeUpCollection($rows)
 */
class FileTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return \Bitrix\Sign\Internal\File::class;
	}

	public static function getCollectionClass(): string
	{
		return \Bitrix\Sign\Internal\FileCollection::class;
	}

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_file';
	}

	/**
	 * Returns entity map definition.
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getMap(): array
	{
		return [
			'ID' => new Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID'
			]),
			'ENTITY_TYPE_ID' => new Entity\IntegerField('ENTITY_TYPE_ID', [
				'title' => 'Entity type id',
				'required' => true
			]),
			'ENTITY_ID' => new Entity\IntegerField('ENTITY_ID', [
				'title' => 'Entity id',
				'required' => true
			]),
			'CODE' => new Entity\IntegerField('CODE', [
				'title' => 'Code',
				'required' => true
			]),
			'FILE_ID' => new Entity\IntegerField('FILE_ID', [
				'title' => 'File id',
				'required' => true,
			]),
		];
	}

	/**
	 * Before delete handler.
	 *
	 * @param Entity\Event $event Event instance.
	 *
	 * @return Entity\EventResult
	 */
	public static function onBeforeDelete(Entity\Event $event): Entity\EventResult
	{
		$result = new Entity\EventResult();
		$primary = $event->getParameter('primary');

		// delete referenced file
		if ($primary['ID'] ?? null)
		{
			$res = self::getList([
				'select' => ['FILE_ID'],
				'filter' => [
					'ID' => $primary['ID']
				],
				'limit' => 1
			]);
			if ($row = $res->fetch())
			{
				if ($row['FILE_ID'])
				{
					\Bitrix\Sign\File::delete($row['FILE_ID']);
				}
			}
		}

		return $result;
	}
}
