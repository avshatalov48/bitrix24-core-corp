<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Disk\File;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Storage;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Event;

Loc::loadMessages(__FILE__);

/**
 * Class FileTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_File_Query query()
 * @method static EO_File_Result getByPrimary($primary, array $parameters = array())
 * @method static EO_File_Result getById($id)
 * @method static EO_File_Result getList(array $parameters = array())
 * @method static EO_File_Entity getEntity()
 * @method static \Bitrix\DocumentGenerator\Model\EO_File createObject($setDefaultValues = true)
 * @method static \Bitrix\DocumentGenerator\Model\EO_File_Collection createCollection()
 * @method static \Bitrix\DocumentGenerator\Model\EO_File wakeUpObject($row)
 * @method static \Bitrix\DocumentGenerator\Model\EO_File_Collection wakeUpCollection($rows)
 */
class FileTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_file';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return [
			new Main\Entity\IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new Main\Entity\StringField('STORAGE_TYPE', [
				'required' => true,
				'validation' => function()
				{
					return [
						function($value)
						{
							if(is_a($value, Storage::class, true))
							{
								return true;
							}
							else
							{
								return Loc::getMessage('DOCUMENTGENERATOR_MODEL_FILE_CLASS_VALIDATION', ['#CLASSNAME#' => $value, '#PARENT#' => Storage::class]);
							}
						}
					];
				},
			]),
			new Main\Entity\StringField('STORAGE_WHERE', [
				'required' => true,
			]),
		];
	}

	/**
	 * @param Event $event
	 * @return Main\EventResult
	 */
	public static function onBeforeDelete(Event $event)
	{
		$result = new Main\Entity\EventResult();
		$id = $event->getParameter('primary')['ID'];
		if(DocumentTable::getRow(['filter' => [
			'FILE_ID' => $id,
		]]))
		{
			$result->addError(new Main\Entity\EntityError(Loc::getMessage('DOCUMENTGENERATOR_MODEL_FILE_DOCUMENT_EXISTS')));
		}
		if(TemplateTable::getRow(['filter' => [
			'FILE_ID' => $id,
		]]))
		{
			$result->addError(new Main\Entity\EntityError(Loc::getMessage('DOCUMENTGENERATOR_MODEL_FILE_TEMPLATE_EXISTS')));
		}

		if(!$result->getErrors())
		{
			$data = static::getById($id)->fetch();

			if($data['STORAGE_TYPE'])
			{
				/** @var Storage $storage */
				$storage = new $data['STORAGE_TYPE'];
				$storage->delete($data['STORAGE_WHERE']);
			}
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return bool|string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getContent($id)
	{
		$data = static::getById($id)->fetch();
		if($data)
		{
			/** @var Storage $storage */
			$storage = new $data['STORAGE_TYPE'];
			return $storage->read($data['STORAGE_WHERE']);
		}

		return false;
	}

	/**
	 * @param array $fileArray
	 * @param Storage|null $storage
	 * @return Main\Entity\AddResult|Main\Result
	 * @throws \Exception
	 */
	public static function saveFile(array $fileArray, Storage $storage = null)
	{
		if(!isset($fileArray['MODULE_ID']))
		{
			$fileArray['MODULE_ID'] = Driver::MODULE_ID;
		}
		if(!$storage)
		{
			$storage = Driver::getInstance()->getDefaultStorage();
		}
		$uploadResult = $storage->upload($fileArray);
		if($uploadResult->isSuccess())
		{
			$fileId = $uploadResult->getId();
			$addResult = static::add([
				'STORAGE_TYPE' => get_class($storage),
				'STORAGE_WHERE' => $fileId,
			]);
			if(!$addResult->isSuccess())
			{
				$storage->delete($fileId);
			}

			return $addResult;
		}

		return $uploadResult;
	}

	/**
	 * @param $fileId
	 * @param string $fileName
	 * @return mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function download($fileId, $fileName = '')
	{
		$data = static::getById($fileId)->fetch();
		if($data)
		{
			/** @var Storage $storage */
			$storage = new $data['STORAGE_TYPE'];
			return $storage->download($data['STORAGE_WHERE'], $fileName);
		}

		return false;
	}

	/**
	 * TODO place it somewhere else
	 *
	 * @param $fileId
	 * @return bool|int
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getBFileId($fileId)
	{
		$data = static::getById($fileId)->fetch();
		if($data)
		{
			$storage = new $data['STORAGE_TYPE'];
			if($storage instanceof Storage\Disk && Main\Loader::includeModule('disk'))
			{
				$file = File::loadById($data['STORAGE_WHERE']);
				if($file)
				{
					return (int)$file->getFileId();
				}
			}
			else
			{
				return (int)$data['STORAGE_WHERE'];
			}
		}

		return false;
	}

	/**
	 * @param $fileId
	 * @return false|int
	 */
	public static function getModificationTime($fileId)
	{
		$data = static::getById($fileId)->fetch();
		if($data)
		{
			/** @var Storage $storage */
			$storage = new $data['STORAGE_TYPE'];
			return $storage->getModificationTime($data['STORAGE_WHERE']);
		}

		return false;
	}

	/**
	 * Updates content of the file.
	 *
	 * @param int $id
	 * @param string $content
	 * @param array $options
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws \Exception
	 */
	public static function updateContent($id, $content, array $options = [])
	{
		$data = static::getById($id)->fetch();
		if($data)
		{
			/** @var Storage $storage */
			$storage = new $data['STORAGE_TYPE'];
			$result = $storage->write($content, $options);
			if($result->isSuccess())
			{
				$result = static::update($id, ['STORAGE_WHERE' => $result->getId()]);
				if($result->isSuccess())
				{
					$storage->delete($data['STORAGE_WHERE']);
				}
			}
		}
	}

	/**
	 * @param $id
	 * @return false|int
	 */
	public static function getSize($id)
	{
		$data = static::getById($id)->fetch();
		if($data)
		{
			/** @var Storage $storage */
			$storage = new $data['STORAGE_TYPE'];
			return $storage->getSize($data['STORAGE_WHERE']);
		}

		return false;
	}
}