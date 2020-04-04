<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Item;

use Bitrix\Tasks\Internals\SystemLogTable;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\Error;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Util\User;

final class SystemLog extends \Bitrix\Tasks\Item
{
	const TYPE_NOTICE = 1;
	const TYPE_WARNING = 2;
	const TYPE_ERROR = 3;

	public static function getDataSourceClass()
	{
		return SystemLogTable::getClass();
	}

	protected static function generateMap(array $parameters = array())
	{
		$map = parent::generateMap(array(
			'EXCLUDE' => array(
				// will be overwritten below
				'ERROR' => true,
			)
		));

		$map->placeFields(array(
			// override some tablet fields
			'ERROR' => new Field\Collection\Error(array(
				'NAME' => 'ERROR',

				'SOURCE' => Field\Scalar::SOURCE_TABLET,
				'DB_READABLE' => true,
				'DB_WRITABLE' => true,
			))
		));

		return $map;
	}

	public function prepareData($result)
	{
		$id = $this->getId();
		if(!$id)
		{
			$now = new \Bitrix\Main\Type\DateTime();

			if(!$this->isFieldModified('CREATED_DATE')) // created date was not set manually
			{
				$this['CREATED_DATE'] = $now;
			}
			if(!$this->isFieldModified('TYPE')) // set type from error collection contents
			{
				$this['TYPE'] = static::TYPE_NOTICE;
				if(!$this['ERROR']->isEmpty())
				{
					$this['TYPE'] = $this['ERROR']->filter(array('TYPE' => Error::TYPE_FATAL))->isEmpty() ? static::TYPE_WARNING : static::TYPE_ERROR;
				}
			}
		}

		return $result;
	}

	/**
	 * Rotate log, remove records that are older than month ago
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	public static function rotate()
	{
		foreach(SystemLogTable::getList(array(
			'filter' => array(
				'<CREATED_DATE' => UI::formatDateTime(User::getTime() - 8640000), // 100 days
			)
		))->fetchAll() as $record)
		{
			SystemLogTable::delete($record['ID']);
		}
	}

	public static function deleteByEntity($entityId, $entityType)
	{
		$match = static::find(array(
			'filter' => array(
				'=ENTITY_ID' => $entityId,
				'=ENTITY_TYPE' => $entityType
			),
			'select' => array(
				'ID'
			)
		));
		foreach($match as $item)
		{
			$item->delete();
		}
	}
}