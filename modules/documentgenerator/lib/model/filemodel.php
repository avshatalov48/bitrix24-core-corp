<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main;
use Bitrix\Main\ORM\Event;

abstract class FileModel extends DataManager
{
	protected static $fileFieldNames = [
		'FILE_ID',
	];
	protected static $filesToDelete = [];

	/**
	 * @param Event $event
	 * @return Main\Entity\EventResult
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function onBeforeUpdate(Event $event)
	{
		$parameters = $event->getParameters();
		$newFields = $parameters['fields'];
		$oldFields = static::getById($parameters['primary']['ID'])->fetch();
		foreach(static::$fileFieldNames as $name)
		{
			if(array_key_exists($name, $newFields) && $newFields[$name] != $oldFields[$name])
			{
				static::$filesToDelete[] = $oldFields[$name];
			}
		}
		return new Main\Entity\EventResult();
	}

	/**
	 * @param Event $event
	 * @return Main\EventResult
	 */
	public static function onBeforeDelete(Event $event)
	{
		$result = new Main\Entity\EventResult();
		$eventData = $event->getParameters();
		$data = static::getById($eventData['primary']['ID'])->fetch();
		foreach(static::$fileFieldNames as $name)
		{
			if($data[$name])
			{
				static::$filesToDelete[] = $data[$name];
			}
		}
		return $result;
	}

	/**
	 * @param Event $event
	 * @return Main\Entity\EventResult
	 * @throws \Exception
	 */
	public static function onAfterUpdate(Event $event)
	{
		return static::deleteFiles();
	}

	/**
	 * @param Event $event
	 * @return Main\Entity\EventResult
	 * @throws \Exception
	 */
	public static function onAfterDelete(Event $event)
	{
		return static::deleteFiles();
	}

	/**
	 * @return Main\Entity\EventResult
	 * @throws \Exception
	 */
	protected static function deleteFiles()
	{
		$result = new Main\Entity\EventResult();
		foreach(static::$filesToDelete as $fileId)
		{
			if($fileId > 0)
			{
				$deleteResult = FileTable::delete($fileId);
				if(!$deleteResult->isSuccess())
				{
					$result->setErrors($deleteResult->getErrors());
				}
			}
		}
		static::$filesToDelete = [];
		return $result;
	}
}