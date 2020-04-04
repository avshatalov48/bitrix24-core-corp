<?
/**
 * Class implements all further interactions with "disk" module considering rest interface to disk`s attachments.
 *
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 *
 * todo: this file needs to be refactored
 */

namespace Bitrix\Tasks\Integration\Disk\Rest;

use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Tasks\Util\Assert;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Driver;

use Bitrix\Rest\RestException;
use Bitrix\Rest\AccessException;

use Bitrix\Main\Loader;
use Bitrix\Main\UserFieldTable;

abstract class Attachment
{
	public static function getById($id, $parameters = array())
	{
		if(!is_array($parameters))
		{
			$parameters = array();
		}

		$result = array(
			'ATTACHMENT_ID' => $id
		);

		if(!Loader::includeModule('disk'))
		{
			return $result;
		}

		$id = intval($id);
		if(!$id)
		{
			return $result;
		}
		$attachedObject = AttachedObject::getById($id, array('OBJECT'));
		if(!$attachedObject || !$attachedObject->getFile())
		{
			return $result;
		}

		$result['NAME'] = $attachedObject->getFile()->getName();
		$result['SIZE'] = $attachedObject->getFile()->getSize();
		$result['FILE_ID'] = $attachedObject->getObjectId();

		$controllerParams = array('attachedId' => $id);
		if(isset($parameters['SERVER']) && ($parameters['SERVER'] instanceof \CRestServer))
		{
			$controllerParams['auth'] = $parameters['SERVER']->getAuth();
		}

		$result['DOWNLOAD_URL'] = Driver::getInstance()->getUrlManager()->getUrlUfController('download', $controllerParams);
		$result['VIEW_URL'] = Driver::getInstance()->getUrlManager()->getUrlUfController('show', $controllerParams);

		return $result;
	}

	public static function add($ownerId, array $fileParameters, array $parameters)
	{
		global $USER_FIELD_MANAGER;

		$ownerId = Assert::expectStringNotNull($ownerId, '$ownerId');
		$fileParameters['NAME'] = Assert::expectStringNotNull($fileParameters['NAME'], '$fileParameters[NAME]');

		$parameters['USER_ID'] = Assert::expectIntegerPositive($parameters['USER_ID'], '$parameters[USER_ID]');
		$parameters['ENTITY_ID'] = Assert::expectStringNotNull($parameters['ENTITY_ID'], '$parameters[ENTITY_ID]');
		$parameters['FIELD_NAME'] = Assert::expectStringNotNull($parameters['FIELD_NAME'], '$parameters[FIELD_NAME]');

		static::checkFieldExistsThrowException($parameters['ENTITY_ID'], $parameters['FIELD_NAME']);

		$fileId = static::uploadFile($fileParameters['NAME'], $fileParameters['CONTENT'], array('USER_ID' => $parameters['USER_ID']));

		$currentValue = static::getValue($ownerId, $parameters['ENTITY_ID'], $parameters['FIELD_NAME']);
		$currentValue[] = FileUserType::NEW_FILE_PREFIX.$fileId;

		$USER_FIELD_MANAGER->Update($parameters['ENTITY_ID'], $ownerId, array(
			$parameters['FIELD_NAME'] => $currentValue
		), $parameters['USER_ID']);

		return static::getIdByFileId($fileId, $ownerId, $parameters['ENTITY_ID'], $parameters['FIELD_NAME']);
	}

	public static function delete($ownerId, $id, array $parameters = array())
	{
		global $USER_FIELD_MANAGER;

		$ownerId = Assert::expectStringNotNull($ownerId, '$ownerId');
		$id = Assert::expectIntegerPositive($id, '$id');

		$parameters['USER_ID'] = Assert::expectIntegerPositive($parameters['USER_ID'], '$parameters[USER_ID]');
		$parameters['ENTITY_ID'] = Assert::expectStringNotNull($parameters['ENTITY_ID'], '$parameters[ENTITY_ID]');
		$parameters['FIELD_NAME'] = Assert::expectStringNotNull($parameters['FIELD_NAME'], '$parameters[FIELD_NAME]');

		static::checkFieldExistsThrowException($parameters['ENTITY_ID'], $parameters['FIELD_NAME']);

		$currentValue = static::getValue($ownerId, $parameters['ENTITY_ID'], $parameters['FIELD_NAME']);
		$currentValue = array_diff($currentValue, array($id));

		$USER_FIELD_MANAGER->Update($parameters['ENTITY_ID'], $ownerId, array(
			$parameters['FIELD_NAME'] => $currentValue
		), $parameters['USER_ID']);

		return true;
	}

	protected static function uploadFile($name, $content, array $parameters = array())
	{
		static::includeDisk();

		$storage = Driver::getInstance()->getStorageByUserId($parameters['USER_ID']);
		if(!$storage)
		{
			throw new RestException("Could not find storage for user '".$parameters['USER_ID']."'.", RestException::ERROR_NOT_FOUND);
		}

		$folder = $storage->getFolderForUploadedFiles();
		if(!$folder)
		{
			return false;
		}
		$securityContext = $storage->getCurrentUserSecurityContext();
		if(!$folder->canAdd($securityContext))
		{
			throw new AccessException;
		}
		$fileData = \CRestUtil::saveFile($content);
		if(!$fileData)
		{
			throw new RestException('Could not save file');
		}
		$file = $folder->uploadFile($fileData, array(
			'NAME' => $name,
			'CREATED_BY' => $parameters['USER_ID']
		), array(), true);
		if(!$file)
		{
			//$folder->getErrors();
			throw new RestException("Could not upload file to the storage");
		}

		return $file->getId();
	}

	protected static function getIdByFileId($fileId, $ownerId, $entityId, $fieldName)
	{
		static::includeDisk();

		$currentValue = static::getValue($ownerId, $entityId, $fieldName);
		foreach($currentValue as $value)
		{
			$attachedObject = AttachedObject::getById($value, array('OBJECT'));
			if($attachedObject && $attachedObject->getFile())
			{
				$attachmentFileId = $attachedObject->getObjectId();

				if((int) $attachmentFileId == (int) $fileId)
				{
					return $value;
				}
			}
		}

		return false;
	}

	protected static function getValue($ownerId, $entityId, $fieldName)
	{
		global $USER_FIELD_MANAGER;

		$currentValue = $USER_FIELD_MANAGER->GetUserFieldValue($entityId, $fieldName, $ownerId);
		if(!is_array($currentValue))
		{
			$currentValue = array();
		}

		return $currentValue;
	}

	protected static function checkFieldExists($entityId, $fieldName)
	{
		$fld = UserFieldTable::getList(array(
			'filter' => array('=ENTITY_ID' => $entityId, '=FIELD_NAME' => $fieldName),
			'limit' => 1
		))->fetch();

		return is_array($fld) && (string) $fld['FIELD_NAME'] != '';
	}

	protected static function checkFieldExistsThrowException($entityId, $fieldName)
	{
		if(!static::checkFieldExists($entityId, $fieldName))
		{
			throw new RestException("User field ".$fieldName." does not exist for entity ".$entityId);
		}
	}

	protected static function includeDisk()
	{
		if(!Loader::includeModule('disk'))
		{
			throw new RestException('Cannot include module disk');
		}
	}
}