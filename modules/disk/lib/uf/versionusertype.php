<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\SystemUser;
use Bitrix\Disk\Version;
use Bitrix\Main\Application;
use Bitrix\Main\DB\MssqlConnection;
use Bitrix\Main\DB\MysqlCommonConnection;
use Bitrix\Main\DB\OracleConnection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;

Loc::loadMessages(__FILE__);

final class VersionUserType
{
	const ERROR_COULD_NOT_FIND_ATTACHED_OBJECT = 'DISK_VUT_22002';

	const USER_TYPE_ID = 'disk_version';
	const TYPE_NEW_OBJECT = 2;
	const TYPE_ALREADY_ATTACHED = 3;
	/** @var File[]  */
	protected static $loadedVersions = array();
	/** @var AttachedObject[] */
	protected static $loadedAttachedObjects = array();

	public static function getUserTypeDescription()
	{
		return array(
			"USER_TYPE_ID" => static::USER_TYPE_ID,
			"CLASS_NAME" => __CLASS__,
			"DESCRIPTION" => Loc::getMessage('DISK_VERSION_USER_TYPE_NAME'),
			"BASE_TYPE" => "int",
		);
	}

	public static function getDBColumnType($userField)
	{
		$connection = Application::getConnection();
		if($connection instanceof MysqlCommonConnection)
		{
			return 'int(11)';
		}
		if($connection instanceof OracleConnection)
		{
			return 'number(18)';
		}
		if($connection instanceof MssqlConnection)
		{
			return 'int';
		}

		throw new NotSupportedException("The '{$connection->getType()}' is not supported in current context");
	}

	public static function prepareSettings($userField)
	{
		$iblockID = intval($userField["SETTINGS"]["IBLOCK_ID"]);
		$sectionID = intval($userField["SETTINGS"]["SECTION_ID"]);

		return array(
			"IBLOCK_ID" => $iblockID,
			"SECTION_ID" => $sectionID,
			"UF_TO_SAVE_ALLOW_EDIT" => $userField["SETTINGS"]["UF_TO_SAVE_ALLOW_EDIT"],
		);
	}

	public static function getSettingsHTML($userField = false, $htmlControl, $varsFromForm)
	{
		return "&nbsp;";
	}

	public static function getEditFormHTML($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function getFilterHTML($userField, $htmlControl)
	{
		return '&nbsp;';
	}

	public static function getAdminListViewHTML($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function getAdminListEditHTML($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function getAdminListEditHTMLMulty($userField, $htmlControl)
	{
		return "&nbsp;";
	}

	public static function onSearchIndex($userField)
	{
		return false;
	}

	public static function onBeforeSave($userField, $value, $userId = false)
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();

		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($userField['ENTITY_ID']);
		list($type, $realValue) = static::detectType($value);

		if(empty($value))
		{
			$alreadyExistsValues = $userField['VALUE'];
			if(!is_array($alreadyExistsValues))
			{
				$alreadyExistsValues = array($userField['VALUE']);
			}
			AttachedObject::detachByFilter(array('ID' => $alreadyExistsValues));
			return $value;
		}

		if($type == self::TYPE_NEW_OBJECT)
		{
			$errorCollection = new ErrorCollection();
			$version = static::getVersionById($realValue);
			if(!$version)
			{
				return '';
			}

			$file = $version->getObject();
			if($userId === false)
			{
				$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
			}
			else
			{
				$securityContext = $file->getStorage()->getSecurityContext($userId);
			}


			$canUpdate = $allowEdit = false;

			//todo this is great hack for disk_version and sync IS_EDITABLE, ALLOW_EDIT by parent AttachedObject
			$hackData = AttachedObject::getStoredDataByObjectId($file->getId());
			if($hackData !== null)
			{
				if(isset($hackData['IS_EDITABLE']))
				{
					$canUpdate = $hackData['IS_EDITABLE'];
				}
				if(isset($hackData['ALLOW_EDIT']))
				{
					$allowEdit = $hackData['ALLOW_EDIT'];
				}
			}
			$canUpdate = $canUpdate || $file->canUpdate($securityContext);
			$allowEdit = $allowEdit || $canUpdate && (int)Application::getInstance()->getContext()->getRequest()->getPost($userFieldManager->getInputNameForAllowEditByEntityType($userField['ENTITY_ID']));
			$attachedModel = AttachedObject::add(array(
				'MODULE_ID' => $moduleId,
				'OBJECT_ID' => $file->getId(),
				'VERSION_ID' => $version->getId(),
				'ENTITY_ID' => $userField['VALUE_ID'],
				'ENTITY_TYPE' => $connectorClass,
				'IS_EDITABLE' => (int)$canUpdate,
				'ALLOW_EDIT' => (int)$allowEdit,
				'CREATED_BY' => $userId === false? self::getActivityUserId() : $userId,
			), $errorCollection);
			if(!$attachedModel || $errorCollection->hasErrors())
			{
				$errorCollection->add(array(new Error(Loc::getMessage('DISK_VERSION_USER_TYPE_ERROR_COULD_NOT_FIND_ATTACHED_OBJECT'), self::ERROR_COULD_NOT_FIND_ATTACHED_OBJECT)));
				return '';
			}

			return $attachedModel->getId();
		}
		else
		{
			return $realValue;
		}
	}

	public static function onDelete($userField, $value)
	{
		list($type, $realValue) = self::detectType($value);
		if($type != self::TYPE_ALREADY_ATTACHED)
		{
			return;
		}

		$attachedModel = AttachedObject::loadById($realValue);
		if(!$attachedModel)
		{
			return;
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		if(!$userFieldManager->belongsToEntity($attachedModel, $userField['ENTITY_ID'], $userField['ENTITY_VALUE_ID']))
		{
			return;
		}

		AttachedObject::detachByFilter(array('ID' => $realValue));
	}

	public static function getPublicViewHTML($userField, $id, $params = "", $settings = array())
	{
		return "&nbsp;";
	}

	/**
	 * Detect: this is already exists attachedObject or new object
	 * @param $value
	 * @return array
	 */
	protected static function detectType($value)
	{
		if(is_string($value) && $value[0] == 'n')
		{
			return array(self::TYPE_NEW_OBJECT, substr($value, 1));
		}
		return array(self::TYPE_ALREADY_ATTACHED, (int)$value);
	}

	/**
	 * @param      $userField
	 * @param      $value
	 * @param bool $userId False means current user id.
	 * @return array
	 */
	public static function checkFields($userField, $value, $userId = false)
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		$errors = array();

		list($type, $realValue) = static::detectType($value);

		if($type == self::TYPE_ALREADY_ATTACHED)
		{
			$attachedModel = static::getAttachedObjectById($realValue);
			if(!$attachedModel)
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_VERSION_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				);

				return $errors;
			}
			list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType($userField['ENTITY_ID']);

			if(
				!$userFieldManager->belongsToEntity($attachedModel, $userField['ENTITY_ID'], $userField['ENTITY_VALUE_ID']) &&
				!(
					is_subclass_of($connectorClass, 'Bitrix\Disk\Uf\ISupportForeignConnector') ||
					in_array('Bitrix\Disk\Uf\ISupportForeignConnector', class_implements($connectorClass)) //5.3.9
				)
			)
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_VERSION_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				);

				return $errors;
			}
		}
		else
		{
			if($realValue <= 0)
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_VERSION_USER_TYPE_ERROR_INVALID_VALUE'),
				);

				return $errors;
			}
			$version = static::getVersionById($realValue);
			if(!$version)
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_VERSION_USER_TYPE_ERROR_COULD_NOT_FIND_FILE'),
				);

				return $errors;
			}
			$file = $version->getObject();
			if($userId === false)
			{
				$securityContext = $file->getStorage()->getCurrentUserSecurityContext();
			}
			else
			{
				$securityContext = $file->getStorage()->getSecurityContext($userId);
			}

			//we don't check rights on file if version create current user. (uf logic, magic)
			if($version->getCreatedBy() != self::getActivityUserId() && !$file->canRead($securityContext))
			{
				$errors[] = array(
					"id" => $userField["FIELD_NAME"],
					"text" => Loc::getMessage('DISK_VERSION_USER_TYPE_ERROR_BAD_RIGHTS'),
				);

				return $errors;
			}
		}

		return $errors;
	}

	/**
	 * @param $id
	 * @return Version|null
	 */
	protected static function getVersionById($id)
	{
		if(!isset(static::$loadedVersions[$id]))
		{
			static::$loadedVersions[$id] = Version::loadById($id, array('OBJECT.STORAGE'));
		}
		return static::$loadedVersions[$id];
	}

	/**
	 * @param $id
	 * @return AttachedObject|null
	 */
	protected static function getAttachedObjectById($id)
	{
		if(!isset(static::$loadedAttachedObjects[$id]))
		{
			static::$loadedAttachedObjects[$id] = AttachedObject::loadById($id, array('OBJECT'));
		}
		return static::$loadedAttachedObjects[$id];
	}


	private static function getActivityUserId()
	{
		global $USER;
		if($USER && $USER instanceof \CUser)
		{
			$userId = $USER->getId();
			if(is_numeric($userId) && ((int)$userId > 0))
			{
				return $userId;
			}
		}

		return SystemUser::SYSTEM_USER_ID;
	}
}