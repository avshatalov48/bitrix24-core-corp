<?php
namespace Bitrix\Sign\Internal\Blank;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;
use Bitrix\Main\ORM\EntityError;

/**
 * Class MemberTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_Resource_Query query()
 * @method static EO_Resource_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Resource_Result getById($id)
 * @method static EO_Resource_Result getList(array $parameters = [])
 * @method static EO_Resource_Entity getEntity()
 * @method static \Bitrix\Sign\Internal\Blank\Resource createObject($setDefaultValues = true)
 * @method static \Bitrix\Sign\Internal\Blank\ResourceCollection createCollection()
 * @method static \Bitrix\Sign\Internal\Blank\Resource wakeUpObject($row)
 * @method static \Bitrix\Sign\Internal\Blank\ResourceCollection wakeUpCollection($rows)
 */
class ResourceTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

	public static function getObjectClass(): string
	{
		return \Bitrix\Sign\Internal\Blank\Resource::class;
	}

	public static function getCollectionClass(): string
	{
		return \Bitrix\Sign\Internal\Blank\ResourceCollection::class;
	}

	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'b_sign_blank_resource';
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
			'BLANK_ID' => new Entity\IntegerField('BLANK_ID', [
				'title' => 'Blank id',
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
		$id = (is_array($primary) && isset($primary['ID'])) ? (int)$primary['ID'] : 0;

		if ($id)
		{
			try
			{
				$row = self::getById($id)->fetchObject();
				if ($row !== null && $row->getFileId())
				{
					\CFile::delete($row->getFileId());
				}
			}
			catch (\Throwable $throwable)
			{
				$result->addError(new EntityError($throwable->getMessage()));
			}
		}

		return $result;
	}
}
