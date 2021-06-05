<?php

namespace Bitrix\Disk\Search\Reindex;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Search\ContentManager;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Update\Stepper;

/**
 * @deprecated
 */
final class BaseObjectIndex extends Stepper
{
	const PORTION = 30;

	const STATUS_STOP  = 'N';
	const STATUS_PAUSE = 'P';
	const STATUS_INDEX = 'Y';

	protected $portionSize = self::PORTION;
	protected static $moduleId = 'disk';

	public static function isReady()
	{
		$connection = Application::getConnection();

		return
			self::getStatus() === self::STATUS_STOP &&
			$connection->getTableField(ObjectTable::getTableName(), 'SEARCH_INDEX')
		;
	}

	public static function getStatus()
	{
		return Option::get('disk', 'needBaseObjectIndex', self::STATUS_INDEX);
	}

	public static function pauseExecution()
	{
		Option::set('disk', 'needBaseObjectIndex', self::STATUS_PAUSE);
	}

	public static function stopExecution()
	{
		Option::set('disk', 'needBaseObjectIndex', self::STATUS_STOP);
		Option::delete('disk', array('name' => 'baseobjectindex'));
	}

	public static function continueExecution()
	{
		$status = self::getStatus();
		if ($status === self::STATUS_INDEX || $status === self::STATUS_PAUSE)
		{
			Option::set('disk', 'needBaseObjectIndex', self::STATUS_INDEX);
			self::bind();

			return true;
		}

		return false;
	}

	public static function continueExecutionWithoutAgent($portion = self::PORTION)
	{
		$status = self::getStatus();
		if ($status === self::STATUS_INDEX || $status === self::STATUS_PAUSE)
		{
			Option::set('disk', 'needBaseObjectIndex', self::STATUS_INDEX);

			$resultData = [];
			$indexer = new static();
			$indexer
				->setPortionSize($portion)
				->execute($resultData)
			;

			return true;
		}

		return false;
	}

	public function setPortionSize($portionSize)
	{
		$this->portionSize = $portionSize;

		return $this;
	}

	public static function className()
	{
		return get_called_class();
	}

	public function execute(array &$result)
	{
		$statusAgent = self::getStatus();
		if ($statusAgent === self::STATUS_STOP || $statusAgent === self::STATUS_PAUSE)
		{
			return self::FINISH_EXECUTION;
		}

		$status = $this->loadCurrentStatus();
		if (empty($status['count']) || $status['count'] < 0 || $status['steps'] >= $status['count'])
		{
			self::stopExecution();

			return self::FINISH_EXECUTION;
		}

		$newStatus = array(
			'count' => $status['count'],
			'steps' => $status['steps'],
		);
		$objectRows = ObjectTable::getList(
			array(
				'select' => array('*', 'HAS_SEARCH_INDEX'),
				'filter' => array(
					'>ID' => $status['lastId'],
				),
				'order' => array('ID' => 'ASC'),
				'offset' => 0,
				'limit' => $this->portionSize,
			)
		);

		$indexManager = Driver::getInstance()->getIndexManager();
		$indexManager->disableUsingSearchModule();

		$contentManager = new ContentManager();

		foreach ($objectRows as $objectRow)
		{
			if (empty($objectRow['HAS_SEARCH_INDEX']))
			{
				try
				{
					$object = BaseObject::buildFromArray($objectRow);
					if ($object instanceof Folder)
					{
						$indexManager->indexFolder($object);
					}
					elseif ($object instanceof File)
					{
						$indexManager->indexFile(
							$object,
							array(
								'content' => $contentManager->getFileContentFromIndex($object),
							)
						);
					}
				}
				catch (ArgumentTypeException $exception)
				{
				}
			}

			$newStatus['lastId'] = $objectRow['ID'];
			$newStatus['steps']++;
		}

		if (!empty($newStatus['lastId']))
		{
			Option::set('disk', 'baseobjectindex', serialize($newStatus));
			$result = array(
				'count' => $newStatus['count'],
				'steps' => $newStatus['steps'],
			);

			return self::CONTINUE_EXECUTION;
		}

		self::stopExecution();

		return self::FINISH_EXECUTION;
	}

	public function loadCurrentStatus()
	{
		$status = Option::get('disk', 'baseobjectindex', 'default');
		$status = ($status !== 'default' ? @unserialize($status, ['allowed_classes' => false]) : array());
		$status = (is_array($status) ? $status : array());

		if (empty($status))
		{
			$status = array(
				'lastId' => 0,
				'steps' => 0,
				'count' => ObjectTable::getCount()
			);
		}

		return $status;
	}
}