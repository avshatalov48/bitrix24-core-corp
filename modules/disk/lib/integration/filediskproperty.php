<?php

namespace Bitrix\Disk\Integration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Disk\Internals\Error\ErrorCollection;
use Bitrix\Disk\File;
use Bitrix\Disk\AttachedObject;
use Bitrix\Main\Application;
use Bitrix\Disk\SystemUser;

Loc::loadMessages(__FILE__);

class FileDiskProperty
{
	public static function getUserTypeDescription()
	{
		$className = get_called_class();
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" => "DiskFile",
			"DESCRIPTION" => Loc::getMessage("DISK_IBLOCK_PROPERTY_DISK_FILE"),
			"GetPublicEditHTML" => array($className, "getPublicEditHTML"),
			"GetPublicViewHTML" => array($className, "getPublicViewHTML"),
			"GetLength" => array($className, "getLength"),
			"CheckFields" => array($className, "checkFields"),
			"ConvertToDB" => array($className, "convertToDB"),
			"ConvertFromDB" => array($className, "convertFromDB"),
			"AttachFilesWorkflow" => array($className, "attachFilesWorkflow"),
			"DeleteAttachedFiles" => array($className, "deleteAttachedFiles"),
			"GetUrlAttachedFileWorkflow" => array($className, "getUrlAttachedFileWorkflow"),
			"GetUrlAttachedFileElement" => array($className, "getUrlAttachedFileElement"),
			"DeleteAllAttachedFiles" => array($className, "deleteAllAttachedFiles"),
			"GetObjectId" => array($className, "getObjectId"),
		);
	}

	public static function convertToDB($property, $value)
	{
		$listId = self::prepareValue($value);

		if(empty($property['ELEMENT_ID']))
		{
			$value['VALUE'] = implode(',', $listId);
			return $value;
		}

		global $USER;
		if($USER instanceof \CUser && $USER->getId())
		{
			$userId = $USER->getId();
		}
		else
		{
			$userId = SystemUser::SYSTEM_USER_ID;
		}

		if(isset($value['DESCRIPTION']) && $value['DESCRIPTION'] == 'workflow')
		{
			$workFlow = true;
		}
		else
		{
			$workFlow = false;
		}

		$value['VALUE'] = array();
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType("iblock_element");

		foreach($listId as $id)
		{
			list($type, $realId) = FileUserType::detectType($id);
			if($type == FileUserType::TYPE_NEW_OBJECT)
			{
				$errorCollection = new ErrorCollection();
				$fileModel = File::loadById($realId, array('STORAGE'));
				if(!$fileModel)
				{
					continue;
				}

				if($workFlow)
				{
					$canUpdate = true;
				}
				else
				{
					$securityContext = $fileModel->getStorage()->getSecurityContext($userId);
					if(!$fileModel->canRead($securityContext))
					{
						continue;
					}
					$canUpdate = $fileModel->canUpdate($securityContext);
				}

				$attachedModel = AttachedObject::add(array(
					'MODULE_ID' => $moduleId,
					'OBJECT_ID' => $fileModel->getId(),
					'ENTITY_ID' => $property['ELEMENT_ID'],
					'ENTITY_TYPE' => $connectorClass,
					'IS_EDITABLE' => (int) $canUpdate,
					'ALLOW_EDIT' => (int) $canUpdate,
					'CREATED_BY' => $userId,
				), $errorCollection);
				if(!$attachedModel || $errorCollection->hasErrors())
				{
					continue;
				}
				$value['VALUE'][] = $attachedModel->getId();
			}
			else
			{
				$value['VALUE'][] = $realId;
			}
		}

		$query = \CIblockElement::getPropertyValues($property['IBLOCK_ID'], array('ID' => $property['ELEMENT_ID']));
		$oldPropertyValues = array();
		if($propertyValues = $query->fetch())
		{
			if(is_array($propertyValues[$property['ID']]) && !empty($propertyValues[$property['ID']]))
			{
				$oldValues = current($propertyValues[$property['ID']]);
			}
			else
			{
				$oldValues = $propertyValues[$property['ID']];
			}

			if(!empty($oldValues))
			{
				$oldPropertyValues = explode(',', $oldValues);
			}
		}
		$attachedIdForDelete = array_diff($oldPropertyValues, $value['VALUE']);

		if(!empty($attachedIdForDelete))
		{
			foreach($attachedIdForDelete as $idAttached)
			{
				list($type, $realId) = FileUserType::detectType($idAttached);
				if($type == FileUserType::TYPE_ALREADY_ATTACHED)
				{
					$attachedModel = AttachedObject::loadById($realId);
					if(!$attachedModel)
					{
						continue;
					}
					if($userFieldManager->belongsToEntity($attachedModel, "iblock_element", $property['ELEMENT_ID']))
					{
						$attachedModel->delete();
					}
				}
			}
		}

		$value['VALUE'] = implode(',', $value['VALUE']);

		return $value;
	}

	/**
	 * @param $iblockId
	 * @param string $fileId disk file with the prefix 'n'
	 * @return int|null
	 */
	public static function attachFilesWorkflow($iblockId, $fileId)
	{
		if(!(int)$iblockId)
		{
			return null;
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType("iblock_workflow");
		list($type, $realId) = FileUserType::detectType($fileId);

		if($type == FileUserType::TYPE_ALREADY_ATTACHED)
		{
			$attachedModel = AttachedObject::loadById($realId);
			if(!$attachedModel)
			{
				return null;
			}
			else
			{
				return $realId;
			}
		}

		$errorCollection = new ErrorCollection();
		$fileModel = File::loadById($realId, array('STORAGE'));
		if(!$fileModel)
		{
			return null;
		}

		$attachedModel = AttachedObject::load(array(
			'OBJECT_ID' => $fileModel->getId(),
			'=ENTITY_TYPE' => $connectorClass,
			'=ENTITY_ID' => $iblockId,
			'=MODULE_ID' => $moduleId
		));
		if($attachedModel)
		{
			return $fileModel->getId();
		}

		global $USER;
		if($USER instanceof \CUser && $USER->getId())
		{
			$userId = $USER->getId();
		}
		else
		{
			$userId = SystemUser::SYSTEM_USER_ID;
		}
		$securityContext = $fileModel->getStorage()->getSecurityContext($userId);
		if(!$fileModel->canRead($securityContext))
		{
			return null;
		}
		$canUpdate = $fileModel->canUpdate($securityContext);

		$attachedModel = AttachedObject::add(array(
			'MODULE_ID' => $moduleId,
			'OBJECT_ID' => $fileModel->getId(),
			'ENTITY_ID' => $iblockId,
			'ENTITY_TYPE' => $connectorClass,
			'IS_EDITABLE' => (int)$canUpdate,
			'ALLOW_EDIT' => (int) $canUpdate,
			'CREATED_BY' => $userId,
		), $errorCollection);
		if(!$attachedModel || $errorCollection->hasErrors())
		{
			return null;
		}

		return $fileModel->getId();
	}

	/**
	 * @param $iblockId
	 * @param array $listAttachedId
	 */
	public static function deleteAttachedFiles($iblockId, $listAttachedId)
	{
		if(!(int)$iblockId || !is_array($listAttachedId))
		{
			return;
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType("iblock_workflow");
		foreach($listAttachedId as $value)
		{
			$attachedModel = AttachedObject::load(array(
				'OBJECT_ID' => $value,
				'=ENTITY_TYPE' => $connectorClass,
				'=ENTITY_ID' => $iblockId,
				'=MODULE_ID' => $moduleId
			));
			if(!$attachedModel)
			{
				continue;
			}

			if($userFieldManager->belongsToEntity($attachedModel, "iblock_workflow", $iblockId))
			{
				$attachedModel->delete();
			}
		}
	}

	/**
	 * @param $iblockId
	 * @param $objectId
	 * @return string
	 */
	public static function getUrlAttachedFileWorkflow($iblockId, $objectId)
	{
		if(!(int)$iblockId || !(int)$objectId)
		{
			return '';
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType("iblock_workflow");

		$attachedModel = AttachedObject::load(array(
			'OBJECT_ID' => $objectId,
			'=ENTITY_TYPE' => $connectorClass,
			'=ENTITY_ID' => $iblockId,
			'=MODULE_ID' => $moduleId
		));
		if(!$attachedModel)
		{
			return '';
		}

		$file = $attachedModel->getFile();
		if(!$file)
		{
			return '';
		}

		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		return '[url='.$urlManager->getUrlUfController('download', array('attachedId' => $attachedModel->getId())
			).']'.htmlspecialcharsbx($file->getName()).'[/url]';
	}

	public static function getUrlAttachedFileElement($elementId, $objectId)
	{
		if(!(int)$elementId || !(int)$objectId)
		{
			return '';
		}

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType("iblock_element");

		$attachedModel = AttachedObject::load(array(
			'OBJECT_ID' => $objectId,
			'=ENTITY_TYPE' => $connectorClass,
			'=ENTITY_ID' => $elementId,
			'=MODULE_ID' => $moduleId
		));

		if(!$attachedModel)
		{
			return '';
		}

		$file = $attachedModel->getFile();
		if(!$file)
		{
			return '';
		}

		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();

		return '[url='.$urlManager->getUrlUfController('download', array('attachedId' => $attachedModel->getId())
		).']'.htmlspecialcharsbx($file->getName()).'[/url]';
	}

	public static function deleteAllAttachedFiles($entityId)
	{
		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType("iblock_element");
		AttachedObject::detachByFilter(
			array('=ENTITY_ID' => $entityId, "=ENTITY_TYPE" => $connectorClass, "=MODULE_ID" => $moduleId)
		);
	}

	public static function getObjectId($attachedId)
	{
		$attachedId = (int)$attachedId;
		$attachedModel = AttachedObject::loadById($attachedId, array('OBJECT'));

		if(!$attachedModel)
		{
			return null;
		}
		$objectId = $attachedModel->getObjectId();
		if(!$objectId)
		{
			return null;
		}

		return $objectId;
	}

	public static function convertFromDB($property, $value)
	{
		if(!empty($value['VALUE']))
		{
			$value['VALUE'] = explode(',', $value['VALUE']);
		}

		return $value;
	}

	private static function prepareValue($value)
	{
		if (is_array($value['VALUE']))
		{
			$value['VALUE'] = array_diff($value['VALUE'], array(''));
			foreach ($value['VALUE'] as $key => $internalValue)
			{
				if (is_string($internalValue))
				{
					$explodeResult = explode(',',$internalValue);
					if (count($explodeResult) > 1)
					{
						$value['VALUE'] = array_merge($value['VALUE'], $explodeResult);
						unset($value['VALUE'][$key]);
					}
				}
			}
		}
		else
		{
			$value['VALUE'] = explode(',', $value['VALUE']);
		}

		return $value['VALUE'];
	}

	public static function checkFields($property, $value)
	{
		$result = array();

		$value['VALUE'] = self::prepareValue($value);

		$errors = false;
		foreach($value['VALUE'] as $val)
		{
			if($val && !(int)$val && $val[0] != 'n')
			{
				$errors = true;
			}
		}

		if($errors)
		{
			$result[] = Loc::getMessage('DISK_IBLOCK_PROPERTY_FORMAT_ERROR');
		}

		return $result;
	}

	public static function getLength($property, $value)
	{
		if(is_array($value['VALUE']))
		{
			$value['VALUE'] = array_diff($value['VALUE'], array(''));
			$value['VALUE'] = implode(',', $value['VALUE']);
			return mb_strlen(trim($value['VALUE'], "\n\r\t"));
		}
		else
		{
			return mb_strlen(trim($value['VALUE'], "\n\r\t"));
		}
	}

	public static function getPublicEditHTML($property, $value, $controlSettings)
	{
		global $APPLICATION;

		$value['VALUE'] = self::prepareValue($value);

		$fieldName = isset($controlSettings['VALUE']) ? $controlSettings['VALUE'] : '';
		$fieldDescription = isset($controlSettings['DESCRIPTION']) ? $controlSettings['DESCRIPTION'] : '';

		$userFieldManager = Driver::getInstance()->getUserFieldManager();
		list($connectorClass, $moduleId) = $userFieldManager->getConnectorDataByEntityType("iblock_workflow");

		foreach($value['VALUE'] as $idKey => $id)
		{
			$attachedModel = AttachedObject::loadById($id);
			if(!$attachedModel)
			{
				$fileModel = File::loadById($id, array('STORAGE'));
				if($fileModel)
				{
					$attachedModel = AttachedObject::load(array(
						'OBJECT_ID' => $fileModel->getId(),
						'=ENTITY_TYPE' => $connectorClass,
						'=ENTITY_ID' => $property['IBLOCK_ID'],
						'=MODULE_ID' => $moduleId
					));
					if ($attachedModel)
					{
						$value['VALUE'][$idKey] = $attachedModel->getId();
					}
					else
					{
						$value['VALUE'][$idKey] = FileUserType::NEW_FILE_PREFIX.$id;
					}
				}
			}
		}

		$userField = array(
			'ENTITY_ID' => 'DISK_FILE_'.$property['IBLOCK_ID'],
			'FIELD_NAME' => $fieldName,
			'USER_TYPE_ID' => 'disk_file',
			'SORT' => 100,
			'MULTIPLE' => 'Y',
			'MANDATORY' => $property['IS_REQUIRED'],
			'EDIT_IN_LIST' => 'Y',
			'EDIT_FORM_LABEL' => $fieldDescription,
			'VALUE' => $value['VALUE'],
			'USER_TYPE' => $property['PROPERTY_USER_TYPE']
		);
		ob_start();
		$APPLICATION->includeComponent(
			'bitrix:system.field.edit',
			'disk_file',
			array(
				'arUserField' => $userField,
				'HIDE_SELECT_DIALOG' => 'Y',
				'HIDE_CHECKBOX_ALLOW_EDIT' => 'Y',
				'bVarsFromForm' => true,
				'form_name' => $controlSettings['FORM_NAME'],
				'FILE_SHOW_POPUP' => true,
				'DISABLE_MOD_ZIP' => 'Y'
			),
			false,
			array('HIDE_ICONS' => 'Y')
		);
		$html = ob_get_contents();
		ob_end_clean();
		return  $html;
	}

	public static function getPublicViewHTML($property, $value, $controlSettings)
	{
		global $APPLICATION;

		$value['VALUE'] = self::prepareValue($value);

		$fieldName = isset($controlSettings['VALUE']) ? $controlSettings['VALUE'] : '';
		$fieldDescription = isset($controlSettings['DESCRIPTION']) ? $controlSettings['DESCRIPTION'] : '';

		if(isset($controlSettings['MODE']))
		{
			switch($controlSettings['MODE'])
			{
				case 'CSV_EXPORT':
				case 'EXCEL_EXPORT':
					$listFileName = array();
					foreach($value['VALUE'] as $attachedId)
					{
						list($type, $realId) = FileUserType::detectType($attachedId);
						if($type == FileUserType::TYPE_ALREADY_ATTACHED)
						{
							$attachedModel = AttachedObject::loadById($realId);
							if(!$attachedModel)
							{
								continue;
							}
							$fileModel = File::loadById($attachedModel->getObjectId(), array('STORAGE'));
							if(!$fileModel)
							{
								continue;
							}
							$listFileName[] = $fileModel->getName();
						}
					}
					return implode(',', $listFileName);
			}
		}

		$html = '';
		$userField = array(
			'ENTITY_ID' => 'DISK_FILE_'.$property['IBLOCK_ID'],
			'FIELD_NAME' => $fieldName,
			'USER_TYPE_ID' => 'disk_file',
			'SORT' => 100,
			'MULTIPLE' => 'Y',
			'MANDATORY' => $property['IS_REQUIRED'],
			'EDIT_FORM_LABEL' => $fieldDescription,
			'VALUE' => $value['VALUE'],
			'USER_TYPE' => $property['PROPERTY_USER_TYPE']
		);
		ob_start();
		$APPLICATION->includeComponent(
			'bitrix:disk.uf.file',
			'',
			array(
				'PARAMS' => array(
					'arUserField' => $userField,
					'DISABLE_MOD_ZIP' => 'Y'
				),
				'EXTENDED_PREVIEW' => 'Y',
				'INLINE' => 'N'
			),
			false,
			array("HIDE_ICONS" => "Y")
		);
		$html .= ob_get_contents();
		ob_end_clean();

		return $html;
	}
}