<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Disk\Ui;

use Bitrix\Main\EventResult;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Util\User;

abstract class Disk extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'disk';

	/**
	 * Upload a new file into a disk folder. File must be uploaded to the tmp folder
	 * and be accessible through $_FILES
	 *
	 * @param array $file
	 * @param int $userId
	 * @return Result
	 *
	 * @access private
	 */
	public static function uploadFile(array $file, $userId = 0)
	{
		$result = new Result();

		if(!static::includeModule())
		{
			$result->addError('MODULE_NOT_INSTALLED', 'Disk not installed');
			return $result;
		}

		if(!$userId)
		{
			$userId = User::getId();
		}

		$storage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$storage)
		{
			$result->addError('CANT_OBTAIN_STORAGE', 'Could not obtain storage');
			return $result;
		}

		$folder = $storage->getFolderForUploadedFiles();
		if(!$folder)
		{
			$result->addError('CANT_OBTAIN_FOLDER', 'Could not obtain folder');
			return $result;
		}
		$securityContext = $storage->getSecurityContext($userId);
		if(!$folder->canAdd($securityContext))
		{
			$result->addError('ACCESS_DENIED', 'Access denied');
			return $result;
		}
		$file = $folder->uploadFile($file, array(
			'NAME' => $file["name"],
			'CREATED_BY' => $userId
		), array(), true);
		if(!$file)
		{
			$result->getErrors()->add('ACCESS_DENIED', 'Access denied', Error::TYPE_FATAL, array('FOLDER_ERRORS' => $folder->getErrors()));
			return $result;
		}

		$result->setData(array(
			'FILE' => $file,
			'ATTACHMENT_ID' => FileUserType::NEW_FILE_PREFIX.$file->getId()
		));

		return $result;
	}

	/**
	 * Add an existing file into a disk folder
	 *
	 * @param mixed[]|int $file
	 * @param int $userId
	 * @return Result
	 */
	public static function addFile($file, $userId = 0)
	{
		$result = new Result();

		if(!static::includeModule())
		{
			$result->addError('MODULE_NOT_INSTALLED', 'Disk not installed');
			return $result;
		}

		if(!$userId)
		{
			$userId = User::getId();
		}

		$storage = Driver::getInstance()->getStorageByUserId($userId);
		if(!$storage)
		{
			$result->addError('CANT_OBTAIN_STORAGE', 'Could not obtain storage');
			return $result;
		}

		$folder = $storage->getFolderForUploadedFiles();
		if(!$folder)
		{
			$result->addError('CANT_OBTAIN_FOLDER', 'Could not obtain folder');
			return $result;
		}
		$securityContext = $storage->getSecurityContext($userId);
		if(!$folder->canAdd($securityContext))
		{
			$result->addError('ACCESS_DENIED', 'Access denied');
			return $result;
		}

		if(is_array($file))
		{
			$fileId = intval($file['ID']);
			$fileArray = $file;
		}
		else
		{
			$fileId = intval($file);
			$fileArray = \CFile::getFileArray($fileId);
		}

		$file = $folder->addFile(array(
			'NAME' => Ui\Text::correctFilename($fileArray['FILE_NAME']),
			'FILE_ID' => $fileId,
			'CONTENT_PROVIDER' => null,
			'SIZE' => $fileArray['FILE_SIZE'],
			'CREATED_BY' => $userId,
			'UPDATE_TIME' => null,
		), array(), true);
		if(!$file)
		{
			$result->getErrors()->add('ACCESS_DENIED', 'Access denied', Error::TYPE_FATAL, array('FOLDER_ERRORS' => $folder->getErrors()));
			return $result;
		}

		$result->setData(array(
			'FILE' => $file,
			'ATTACHMENT_ID' => FileUserType::NEW_FILE_PREFIX.$file->getId()
		));

		return $result;
	}

	public static function getAttachmentIdByLegacyFileId($fileId, $entityType)
	{
		$fileId = intval($fileId);

		if(!static::includeModule() || !$fileId)
		{
			return 0;
		}

		$entityClass = false;
		$map = static::onBuildConnectorList();
		if(array_key_exists($entityType, $map))
		{
			$entityClass = $map[$entityType];
		}

		$attachment = AttachedObjectTable::getList(array(
			'filter' => array(
				'=OBJECT.FILE_ID' => $fileId,
				'=ENTITY_TYPE' => $entityClass,
			),
			'select' => array('ID'),
			'limit' => 1
		))->fetch();

		return intval($attachment['ID']);
	}

	/**
	 * @param array $attachments
	 * @param $userId
	 * @return array
	 * @deprecated
	 */
	public static function cloneFileAttachment(array $attachments = array(), $userId = 0)
	{
		$clone = static::cloneFileAttachmentHash($attachments, $userId);
		return array_values($clone);
	}

	public static function cloneFileAttachmentHash(array $attachments = array(), $userId = 0)
	{
		$result = array();

		if(!static::includeModule())
		{
			return $result;
		}

		if(!$userId)
		{
			$userId = User::getId();
		}

		// transform UF files
		if(!empty($attachments))
		{
			// find which files are unattached and which are attached
			$attached = array();
			$unattached = array();
			foreach($attachments as $attachmentId)
			{
				if((string) $attachmentId != '')
				{
					if(strpos($attachmentId, FileUserType::NEW_FILE_PREFIX) === 0)
					{
						$unattached[$attachmentId] = $attachmentId;
					}
					else
					{
						$attached[] = $attachmentId;
					}
				}
			}

			// clone all attached files, leave unattached unchanged
			if(!empty($attached))
			{
				$userFieldManager = Driver::getInstance()->getUserFieldManager();
				$clones = $userFieldManager->cloneUfValuesFromAttachedObject($attached, $userId);

				foreach($clones as $i => $clone)
				{
					$unattached[$i] = $clone;
				}
			}

			$result = $unattached;
		}

		return $result;
	}

	/**
	 * Deletes files created with cloneFileAttachment().
	 *
	 * @param array $files List of new files (n1, n23, etc), which were created with cloneFileAttachment.
	 * @param int $userId Id of user.
	 */
	public static function deleteUnattachedFiles(array $files, $userId = 0)
	{
		if(empty($files))
		{
			return;
		}

		if(!static::includeModule())
		{
			return;
		}

		if(!$userId)
		{
			$userId = User::getId();
		}

		foreach($files as $fileValue)
		{
			list($type, $fileValue) = FileUserType::detectType($fileValue);
			if($type != FileUserType::TYPE_NEW_OBJECT)
			{
				continue;
			}

			/** @var File $file */
			$file = File::loadById($fileValue);
			if(!$file)
			{
				continue;
			}

			$securityContext = $file->getStorage()->getSecurityContext($userId);
			if(!$file->canDelete($securityContext))
			{
				continue;
			}

			$file->delete($userId);
		}
		unset($file);
	}

	public static function getAttachmentData(array $valueList)
	{
		$result = array();

		if(!static::includeModule())
		{
			return $result;
		}

		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		foreach ($valueList as $key => $value)
		{
			$attachedObject = AttachedObject::loadById($value, array('OBJECT'));
			if(
				!$attachedObject
				|| !$attachedObject->getFile()
			)
			{
				continue;
			}

			$attachedObjectUrl = $urlManager->getUrlUfController('show', array('attachedId' => $value));

			$result[$value] = array(
				"ID" => $value,
				"OBJECT_ID" => $attachedObject->getFile()->getId(),
				"NAME" => $attachedObject->getFile()->getName(),
				"SIZE" => \CFile::formatSize($attachedObject->getFile()->getSize()),
				"URL" => $attachedObjectUrl,
				"IS_IMAGE" => TypeFile::isImage($attachedObject->getFile())
			);
		}

		return $result;
	}

	/**
	 * Returns available entities for tasks module
	 * @return array
	 */
	public static function onBuildConnectorList()
	{
		return new EventResult(EventResult::SUCCESS, array(
			'TASK' => array(
				'ENTITY_TYPE' => 'tasks_task', // should match entity type from user fields: TASKS_TASK
				'MODULE_ID' => 'tasks',
				'CLASS' => Disk\Connector\Task::className()
			),
			'TASK_TEMPLATE' => array(
				'ENTITY_TYPE' => 'tasks_task_template', // should match entity type from user fields: TASKS_TASK_TEMPLATE
				'MODULE_ID' => 'tasks',
				'CLASS' => Disk\Connector\Task\Template::className()
			),
		));
	}
}