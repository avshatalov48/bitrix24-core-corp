<?php

namespace Bitrix\Disk;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use CBPArgumentNullException;
use CBPArgumentOutOfRangeException;
use CBPCanUserOperateOperation;
use CBPDocument;
use CBPHelper;
use CFile;

Loc::loadMessages(__FILE__);

if (!Integration\BizProcManager::isAvailable())
{
	return;
}

class BizProcDocument
{
	const DOCUMENT_TYPE_PREFIX = 'STORAGE_';

	/**
	 * @return string the fully qualified name of this class.
	 */
	public static function className()
	{
		return get_called_class();
	}

	public static function generateDocumentType($storageId)
	{
		$storageId = (int)$storageId;
		return self::DOCUMENT_TYPE_PREFIX . $storageId;
	}

	public static function generateDocumentComplexType($storageId)
	{
		return array(
			Driver::INTERNAL_MODULE_ID,
			get_called_class(),
			self::generateDocumentType($storageId)
		);
	}

	public static function getDocumentComplexId($documentId)
	{
		return array(
			Driver::INTERNAL_MODULE_ID,
			get_called_class(),
			$documentId
		);
	}

	public static function getStorageIdByType($documentType)
	{
		$items = explode('_', $documentType);
		if(count($items) != 2)
		{
			return null;
		}
		$storageId = (int)$items[1];
		if($storageId <= 0)
		{
			return null;
		}
		return $storageId;
	}

	public static function getDocumentAdminPage($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
		{
			throw new CBPArgumentNullException("documentId");
		}

		$file = File::loadById($documentId);
		if(!$file)
		{
			return '';
		}

		$urlManager = Driver::getInstance()->getUrlManager();

		return $urlManager->encodeUrn($urlManager->getPathFileDetail($file));
	}

	protected static function getFieldNameForUfField(array $userFieldData)
	{
		if($userFieldData['USER_TYPE_ID'] === 'enumeration')
		{
			return $userFieldData['FIELD_NAME'] . '_PRINTABLE';
		}
		return $userFieldData['FIELD_NAME'];
	}

	public static function getDocument($documentId)
	{
		$documentId = (int)$documentId;
		if ($documentId <= 0)
		{
			throw new CBPArgumentNullException("documentId");
		}

		$file = File::loadById($documentId);
		if(!$file)
		{
			return null;
		}
		$ufFields = Driver::getInstance()->getUserFieldManager()->getFieldsForObject($file);
		$ufFileRow = array();
		if(!empty($ufFields))
		{
			foreach($ufFields as $fieldKey => $fieldData)
			{
				$ufFileRow[static::getFieldNameForUfField($fieldData)] = $fieldData['VALUE'];
			}
		}

		$fileRow = File::getList(array(
			'with' => array(
				'CREATE_USER', 'UPDATE_USER', 'DELETE_USER',
			),
			'filter' => array(
				'ID' => $documentId
			),
		))->fetch();

		if(!$fileRow)
		{
			return null;
		}

		if(empty($fileRow["CODE"]))
		{
			$fileRow["CODE"] = Loc::getMessage("DISK_BZ_D_NAME_NOT_CODE");
		}

		return array_merge(array(
			"ID" => $fileRow["ID"],
			"CREATE_TIME" => $fileRow["CREATE_TIME"],
			"CREATED_BY" => $fileRow["CREATED_BY"],
			"CREATED_BY_PRINTABLE" => $fileRow['CREATE_USERREF_NAME'].' '.$fileRow['CREATE_USERREF_LAST_NAME'],
			"UPDATE_TIME" => $fileRow["UPDATE_TIME"],
			"UPDATED_BY" => $fileRow["UPDATED_BY"],
			"UPDATED_BY_PRINTABLE" => $fileRow['UPDATE_USERREF_NAME'].' '.$fileRow['UPDATE_USERREF_LAST_NAME'],
			"DELETE_TIME" => $fileRow["DELETE_TIME"],
			"DELETED_BY" => $fileRow["DELETED_BY"],
			"DELETED_BY_PRINTABLE" => $fileRow['DELETE_USERREF_NAME'].' '.$fileRow['DELETE_USERREF_LAST_NAME'],
			"STORAGE_ID" => $fileRow["STORAGE_ID"],
			"NAME" => $fileRow["NAME"],
			"DETAIL_PAGE_URL" => Driver::getInstance()->getUrlManager()->getPathFileDetail($file),
			"SIZE" => $fileRow["SIZE"],
			"CODE" => $fileRow["CODE"]
		), $ufFileRow);
	}

	/**
	 * Gets name of document.
	 * @param int $documentId Id of document.
	 * @return string|null
	 * @throws CBPArgumentNullException
	 */
	public static function getDocumentName($documentId)
	{
		$documentId = (int)$documentId;
		if ($documentId <= 0)
		{
			throw new CBPArgumentNullException('documentId');
		}
		$file = File::loadById($documentId);
		return $file? $file->getName() : null;
	}

	protected static function getUserTypeFields($storageId)
	{
		$fields = array();
		/** @var \CAllUserTypeManager */
		global $USER_FIELD_MANAGER;
		foreach($USER_FIELD_MANAGER->getUserFields('DISK_FILE_' . $storageId, 0, LANGUAGE_ID) as $fieldName => $userField)
		{
			$editable = array();

			if($userField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
			{
				$type = static::getPrefixForCustomType() . "boolean";
				$editable = $userField['SETTINGS'];
			}
			else
			{
				if(in_array($userField['USER_TYPE']['USER_TYPE_ID'], array(
						'string',
						'double',
						'boolean',
						'integer',
						'datetime',
						'file',
					)))
				{
					if($userField['USER_TYPE']['BASE_TYPE'] == 'enum')
					{
						$userField['USER_TYPE']['BASE_TYPE'] = 'select';
					}
					$type = $userField['USER_TYPE']['USER_TYPE_ID'];

					if($type === 'datetime')
					{
						$userField['SETTINGS']['EDIT_IN_LIST'] = $userField['EDIT_IN_LIST'];
						$editable = $userField['SETTINGS'];
					}
				}
				else
				{
					$userTypeId = $userField['USER_TYPE']['USER_TYPE_ID'];
					if($userTypeId == 'enumeration')
					{
						$type = 'select';
					}
					else
					{
						$type = static::getPrefixForCustomType() . $userTypeId;
					}
					$editable = array();
					if('iblock_element' == $userTypeId || 'iblock_section' == $userTypeId)
					{
						$editable = $userField['SETTINGS'];
					}
					elseif (is_callable(array($userField['USER_TYPE']['CLASS_NAME'], 'GetList')))
					{
						$enumQuery = call_user_func_array(array($userField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($userField));
						while($enumRow = $enumQuery->getNext())
						{
							$editable[$enumRow['XML_ID']] = $enumRow['VALUE'];
						}
					}

				}
			}

			$fieldTitle = trim($userField['EDIT_FORM_LABEL']) !== '' ? $userField['EDIT_FORM_LABEL'] : $userField['FIELD_NAME'];

			if($userField['USER_TYPE']['USER_TYPE_ID'] === 'enumeration')
			{
				$fields[static::getFieldNameForUfField($userField)] = array(
					'Name' => $fieldTitle . ' (text)',
					'Options' => $editable,
					'Type' => $type,
					'Filterable' => $userField['MULTIPLE'] != 'Y',
					'Editable' => true,
					'Multiple' => $userField['MULTIPLE'] == 'Y',
					'Required' => false,
				);

				$fields[$userField['FIELD_NAME']] = array(
					'Name' => $fieldTitle,
					'Options' => $editable,
					'Type' => $type,
					'Filterable' => $userField['MULTIPLE'] != 'Y',
					'Editable' => true,
					'Multiple' => $userField['MULTIPLE'] == 'Y',
					'Required' => false,
				);
			}
			else
			{
				$fields[static::getFieldNameForUfField($userField)] = array(
					'Name' => $fieldTitle,
					'Options' => $editable,
					'Type' => $type,
					'Filterable' => $userField['MULTIPLE'] != 'Y',
					'Editable' => true,
					'Multiple' => $userField['MULTIPLE'] == 'Y',
					'Required' => $userField['MANDATORY'] == 'Y',
				);
			}
		}

		return $fields;
	}

	public static function getDocumentFields($documentType)
	{
		$storageId = self::getStorageIdByType($documentType);
		if(!$storageId)
		{
			throw new CBPArgumentNullException('documentType');
		}

		return array_merge(array(
			"ID" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"UPDATE_TIME" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_UPDATE_TIME"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"UPDATED_BY" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_UPDATED_BY") . Loc::getMessage("DISK_BZ_D_IDENTIFICATOR"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"UPDATED_BY_PRINTABLE" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_UPDATED_BY") . Loc::getMessage("DISK_BZ_D_NAME_LASTNAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"CREATE_TIME" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_CREATE_TIME"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"CREATED_BY" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_CREATED_BY") . Loc::getMessage("DISK_BZ_D_IDENTIFICATOR"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"CREATED_BY_PRINTABLE" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_CREATED_BY") . Loc::getMessage("DISK_BZ_D_NAME_LASTNAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"DELETE_TIME" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_DELETE_TIME"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"DELETED_BY" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_DELETED_BY") . Loc::getMessage("DISK_BZ_D_IDENTIFICATOR"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"DELETED_BY_PRINTABLE" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_DELETED_BY") . Loc::getMessage("DISK_BZ_D_NAME_LASTNAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"STORAGE_ID" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_STORAGE_ID") . Loc::getMessage("DISK_BZ_D_IDENTIFICATOR"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"NAME" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_NAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => true,
				"Multiple" => false,
			),
			"DETAIL_PAGE_URL" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_DETAIL_PAGE_URL"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"SIZE" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_SIZE"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"CODE" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_CODE"),
				"Type" => "text",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
		), self::getUserTypeFields($storageId));
	}

	public static function getDocumentType($documentId)
	{
		if (mb_substr($documentId, 0, mb_strlen(self::DOCUMENT_TYPE_PREFIX)) == self::DOCUMENT_TYPE_PREFIX)
		{
			return $documentId;
		}

		$documentId = intval($documentId);
		if ($documentId <= 0)
		{
			throw new CBPArgumentNullException("documentId");
		}
		$fileRow = File::getList(array('select' => array('ID', 'STORAGE_ID'), 'filter' => array('ID' => $documentId)))->fetch();
		if (!$fileRow || empty($fileRow['STORAGE_ID']))
		{
			throw new SystemException("Element is not found");
		}

		return self::generateDocumentType($fileRow['STORAGE_ID']);
	}

	public static function getDocumentFieldTypes($documentType)
	{
		global $USER_FIELD_MANAGER;
		$storageId = self::getStorageIdByType($documentType);
		if(!$storageId)
		{
			throw new CBPArgumentNullException('documentType');
		}

		$result = array(
			"string" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_STRING"), "BaseType" => "string"),
			"text" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_TEXT"), "BaseType" => "text"),
			"int" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_INT"), "BaseType" => "int"),
			"double" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_DOUBLE"), "BaseType" => "double"),
			"select" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_SELECT"), "BaseType" => "select", "Complex" => true),
			"bool" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_BOOL"), "BaseType" => "bool"),
			"date" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_DATA"), "BaseType" => "date"),
			"datetime" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_DATETIME"), "BaseType" => "datetime"),
			"user" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_USER"), "BaseType" => "user"),
			"file" => array("Name" => Loc::getMessage("DISK_DOC_TYPE_FILE"), "BaseType" => "file"),
		);

		$ignoredUserTypes = array(
			'string',
			'double',
			'boolean',
			'crm',
			'crm_status',
			'integer',
			'datetime',
			'file',
			'enumeration',
			'video',
			'string_formatted',
			'webdav_element_history',
			'iblock_element',
			'iblock_section',
			'hlblock',
			'disk_file',
			'disk_version',
			'date',
			'vote',
			'url_preview',
			'snils',
		);

		$types = $USER_FIELD_MANAGER->getUserType();
		foreach ($types as $type)
		{
			if(in_array($type['USER_TYPE_ID'], $ignoredUserTypes))
			{
				continue;
			}
			if(empty($type['USER_TYPE_ID']))
			{
				continue;
			}
			if($type['BASE_TYPE'] === 'enum')
			{
				$type['BASE_TYPE'] = 'select';
			}
			if($type['USER_TYPE_ID'] === 'employee')
			{
				$type['BASE_TYPE'] = 'user';
			}
			$typeId = static::getPrefixForCustomType().$type['USER_TYPE_ID'];
			$result[$typeId] = array(
				'Name' => $type['DESCRIPTION'],
				'BaseType' => $type['BASE_TYPE'],
			);
			if ($type['USER_TYPE_ID'] === 'employee')
			{
				$result[$typeId]['typeClass'] = \Bitrix\Bizproc\BaseType\User::class;
			}
		}
		return $result;
	}

	public static function addDocumentField($documentType, $fields)
	{
		$storageId = self::getStorageIdByType($documentType);
		if(!$storageId)
		{
			throw new CBPArgumentNullException('documentType');
		}

		if(mb_strpos($fields['type'], static::getPrefixForCustomType()) === 0)
		{
			$fields['type'] = mb_substr($fields['type'], 3);
		}

		$fieldsTmp = array(
			'USER_TYPE_ID' => $fields['type'],
			'FIELD_NAME' => 'UF_'.mb_strtoupper($fields['code']),
			'ENTITY_ID' => 'DISK_FILE_' . $storageId,
			'SORT' => 150,
			'MULTIPLE' => $fields['multiple'] == 'Y' ? 'Y' : 'N',
			'MANDATORY' => $fields['required'] == 'Y' ? 'Y' : 'N',
			'SHOW_FILTER' => 'E',
		);

		$fieldsTmp['EDIT_FORM_LABEL'][LANGUAGE_ID] = $fields['name'];
		$fieldsTmp['LIST_COLUMN_LABEL'][LANGUAGE_ID] = $fields['name'];
		$fieldsTmp['LIST_FILTER_LABEL'][LANGUAGE_ID] = $fields['name'];
		switch($fields['type'])
		{
			case 'select':
			case 'enumeration':
			{
				$fieldsTmp['USER_TYPE_ID'] = 'enumeration';

				if(!is_array($fieldsTmp['LIST']))
				{
					$fieldsTmp['LIST'] = array();
				}

				$options = isset($fields['options']) && is_array($fields['options']) ? $fields['options'] : array();
				if(!empty($options))
				{
					$i = 10;
					foreach($options as $k => $v)
					{
						$fieldsTmp['LIST']['n' . $i] = array(
							'XML_ID' => $k,
							'VALUE' => $v,
							'DEF' => 'N',
							'SORT' => $i
						);
						$i = $i + 10;
					}
				}
				break;
			}
			case 'text':
			{
				$fieldsTmp['USER_TYPE_ID'] = 'string';
				break;
			}
			case 'int':
			{
				$fieldsTmp['USER_TYPE_ID'] = 'integer';
				break;
			}
			case 'user':
			{
				$fieldsTmp['USER_TYPE_ID'] = 'employee';
				break;
			}
		}

		$userField = new \CUserTypeEntity();
		$id = $userField->add($fieldsTmp);
		if($id > 0)
		{
			if($fieldsTmp['USER_TYPE_ID'] == 'enumeration' && is_array($fieldsTmp['LIST']))
			{
				$enum = new \CUserFieldEnum();
				$res = $enum->setEnumValues($id, $fieldsTmp['LIST']);
			}
		}

		return $fieldsTmp['FIELD_NAME'];
	}

	public static function updateDocument($documentId, $fields)
	{
		$documentId = (int)$documentId;
		if ($documentId <= 0)
		{
			throw new CBPArgumentNullException("documentId");
		}
		$file = File::loadById($documentId);
		if(!$file)
		{
			return false;
		}

		$ufFields = array();
		foreach($fields as $codeField => $valueField)
		{
			if($codeField == 'NAME')
			{
				$file->rename((string)$valueField);
			}
			$search = 'UF_';
			$res = mb_strpos($codeField, $search);
			if($res === 0)
			{
				$ufFields[$codeField] = $valueField;
			}
		}
		if(!empty($ufFields))
		{
			$userFieldManager = Driver::getInstance()->getUserFieldManager();

			$filesToDelete = array();
			foreach($userFieldManager->getFieldsForObject($file) as $userField)
			{
				if($userField['USER_TYPE_ID'] !== 'file')
				{
					continue;
				}
				if($userField['MULTIPLE'] !== 'N')
				{
					continue;
				}
				if(!isset($ufFields[$userField['FIELD_NAME']]))
				{
					continue;
				}
				if($ufFields[$userField['FIELD_NAME']] != $userField['VALUE'])
				{
					$forkFileId = \CFile::CloneFile($ufFields[$userField['FIELD_NAME']]);
					if($forkFileId)
					{
						$filesToDelete[] = $userField['VALUE'];
						$ufFields[$userField['FIELD_NAME']] = $forkFileId;
					}
				}
				else
				{
					//document already has same value
					unset($ufFields[$userField['FIELD_NAME']]);
				}
			}
			unset($userField);

			global $USER_FIELD_MANAGER;
			if($USER_FIELD_MANAGER->update($userFieldManager->getUfEntityName($file), $documentId, $ufFields))
			{
				foreach($filesToDelete as $fileId)
				{
					CFile::delete($fileId);
				}
				unset($fileId);
			}
		}

		return true;
	}

	public static function createDocument($parentDocumentId, $fields)
	{
		/** @var File $file */
		$file = File::loadById($parentDocumentId, array('STORAGE'));
		if(!$file)
		{
			return false;
		}

		$targetObject = $file->getParent();
		if(!$targetObject)
		{
			return false;
		}

		$uploadFile = $targetObject->addBlankFile(array(
			'NAME' => $fields['NAME'],
			'CREATED_BY' => SystemUser::SYSTEM_USER_ID,
			'MIME_TYPE' => TypeFile::getMimeTypeByFilename($fields['NAME']),
		), array(), true);
		if($uploadFile)
		{
			$ufFields = array();
			foreach($fields as $codeField => $valueField)
			{
				$search = 'UF_';
				$res = mb_strpos($codeField, $search);
				if($res === 0)
				{
					$ufFields[$codeField] = $valueField;
				}
			}
			if(!empty($ufFields))
			{
				global $USER_FIELD_MANAGER;
				$storageId = $uploadFile->getStorageId();
				$USER_FIELD_MANAGER->update('DISK_FILE_' . $storageId, $uploadFile->getId(), $ufFields);
			}
			return $uploadFile->getId();
		}
		else
		{
			return false;
		}
	}

	public static function lockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function unlockDocument($documentId, $workflowId)
	{
		return true;
	}

	public static function isDocumentLocked($documentId, $workflowId)
	{
		return false;
	}

	public static function getDocumentForHistory($documentId, $historyIndex, $update = false)
	{
		return null;
	}

	public static function canUserOperateDocument($operation, $userId, $documentId, $parameters = array())
	{
		$documentId = intval($documentId);
		if($documentId <= 0)
		{
			throw new \CBPArgumentNullException("documentId");
		}

		/** @var File $file */
		$file = File::loadById($documentId, array('STORAGE'));
		if(!$file)
		{
			return false;
		}
		$securityContext = $file->getStorage()->getSecurityContext($userId);
		$parameters["CreatedBy"] = $file->getCreatedBy();

		if($operation === CBPCanUserOperateOperation::ReadDocument)
		{
			return $file->canRead($securityContext);
		}
		elseif($operation === CBPCanUserOperateOperation::WriteDocument)
		{
			return $file->canUpdate($securityContext);
		}
		elseif($operation === CBPCanUserOperateOperation::StartWorkflow)
		{
			return $file->canStartBizProc($securityContext);
		}
		elseif($operation === CBPCanUserOperateOperation::ViewWorkflow)
		{
			return $file->canRead($securityContext);
		}
		elseif($operation === CBPCanUserOperateOperation::CreateWorkflow)
		{
			return CBPDocument::canUserOperateDocumentType(CBPCanUserOperateOperation::CreateWorkflow, $userId, self::generateDocumentComplexType($file->getStorageId()), $parameters);
		}

		return false;
	}

	public static function canUserOperateDocumentType($operation, $userId, $documentType, $parameters = array())
	{
		$storageId = self::getStorageIdByType($documentType);
		if(!$storageId)
		{
			throw new CBPArgumentNullException('documentType');
		}
		/** @var Storage $storage */
		$storage = Storage::loadById($storageId, array('ROOT_OBJECT'));
		if(!$storage)
		{
			throw new CBPArgumentNullException('documentType');
		}
		$securityContext = $storage->getSecurityContext($userId);

		if($operation === CBPCanUserOperateOperation::CreateWorkflow)
		{
			return $storage->canCreateWorkflow($securityContext);
		}
		elseif($operation === CBPCanUserOperateOperation::WriteDocument)
		{
			return $storage->getRootObject()->canAdd($securityContext);
		}
		elseif($operation === CBPCanUserOperateOperation::ViewWorkflow || $operation === CBPCanUserOperateOperation::StartWorkflow)
		{
			if($operation === CBPCanUserOperateOperation::ViewWorkflow)
			{
				return $storage->getRootObject()->canRead($securityContext);
			}

			if($operation === CBPCanUserOperateOperation::StartWorkflow)
			{
				return $storage->canCreateWorkflow($securityContext);
			}
		}

		return false;
	}

	public static function deleteDocument($documentId)
	{
		$documentId = intval($documentId);
		if($documentId <= 0)
		{
			throw new \CBPArgumentNullException("documentId");
		}

		/** @var File $file */
		$file = File::loadById($documentId, array('STORAGE'));
		if(!$file)
		{
			return;
		}
		$file->markDeleted(SystemUser::SYSTEM_USER_ID);
	}

	public static function publishDocument($documentId)
	{
		$documentId = intval($documentId);
		if($documentId <= 0)
		{
			throw new \CBPArgumentNullException("documentId");
		}

		/** @var File $file */
		$file = File::loadById($documentId, array('STORAGE'));
		if(!$file)
		{
			return false;
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$rights = $rightsManager->getAllListNormalizeRights($file);
		if(count($rights) != 1)
		{
			//we have many rights (not only CR). And it means that document already published.
			return $file->getId();
		}
		$aloneRight = array_pop($rights);
		if($aloneRight['ACCESS_CODE'] != 'CR')
		{
			return $file->getId();
		}
		//delete single right with ACCESS_CODE "CR". And file will become inherited rights.
		$rightsManager->set($file, array());

		return $file->getId();
	}

	public static function unPublishDocument($documentId)
	{
		$documentId = intval($documentId);
		if($documentId <= 0)
		{
			throw new \CBPArgumentNullException("documentId");
		}

		/** @var File $file */
		$file = File::loadById($documentId, array('STORAGE'));
		if(!$file)
		{
			return false;
		}

		$rightsManager = Driver::getInstance()->getRightsManager();
		$specificRights = $rightsManager->getSpecificRights($file);
		if(!empty($specificRights))
		{
			return false;
		}
		$newNegativeRights = array();
		//we have only inherited rights. Now we will set alone right CR.
		foreach($rightsManager->getAllListNormalizeRights($file) as $right)
		{
			$newNegativeRights[] = array(
				'ACCESS_CODE' => $right['ACCESS_CODE'],
				'TASK_ID' => $right['TASK_ID'],
				'NEGATIVE' => 1,
			);
		}
		unset($right);
		$newNegativeRights[] = array(
			'ACCESS_CODE' => 'CR',
			'TASK_ID' => $rightsManager->getTaskIdByName($rightsManager::TASK_FULL),
		);
		return $rightsManager->set($file, $newNegativeRights);
	}

	public static function getAllowableUserGroups($documentType)
	{
		$storageId = self::getStorageIdByType($documentType);
		if(!$storageId)
		{
			throw new CBPArgumentNullException('documentType');
		}
		/** @var Storage $storage */
		$storage = Storage::loadById($storageId, array('ROOT_OBJECT'));
		if(!$storage)
		{
			throw new CBPArgumentNullException('documentType');
		}

		if($storage->getProxyType() instanceof ProxyType\Group && Loader::includeModule("socialnetwork"))
		{
			$resultUserGroups = array();
			$resultUserGroups["author"] = Loc::getMessage("DISK_USER_GROUPS_AUTHOR");
			$resultUserGroups[SONET_ROLES_OWNER] = Loc::getMessage("DISK_USER_GROUPS_OWNER");
			$resultUserGroups[SONET_ROLES_MODERATOR] = Loc::getMessage("DISK_USER_GROUPS_MODS");
			$resultUserGroups[SONET_ROLES_USER] = Loc::getMessage("DISK_USER_GROUPS_MEMBERS");

			return $resultUserGroups;
		}
		else
		{
			$resultUserGroups = array('Author' => Loc::getMessage("DISK_USER_GROUPS_AUTHOR"));
			$groupIds = array(1);

			foreach(Driver::getInstance()->getRightsManager()->getSpecificRights($storage->getRootObject()) as $right)
			{
				if(preg_match("/^G(\\d+)\$/", $right['ACCESS_CODE'], $match))
				{
					$groupIds[] = $match[1];
				}
			}
			unset($right);

			//Crutch for Bitrix24 context (user group management is not supported)
			if(ModuleManager::isModuleInstalled('bitrix24'))
			{
				$siteId = \CSite::getDefSite();
				$employeeGroup = \CGroup::getList('', '', array(
					'STRING_ID' => 'EMPLOYEES_' . $siteId,
					'STRING_ID_EXACT_MATCH' => 'Y'
				))->fetch();
				if($employeeGroup)
				{
					$employeeGroupId = (int)$employeeGroup['ID'];
					if(!in_array($employeeGroupId, $groupIds, true))
					{
						$groupIds[] = $employeeGroupId;
					}
				}
			}

			$dbGroupsList = \CGroup::getListEx(array('NAME' => 'ASC'), array('ID' => $groupIds));
			while($group = $dbGroupsList->fetch())
			{
				$resultUserGroups[$group['ID']] = $group['NAME'];
			}
			return $resultUserGroups;
		}
	}

	public static function getUsersFromUserGroup($group, $documentId)
	{
		if(mb_substr($documentId, 0, 8) == "STORAGE_")
		{
			$storageId = self::getStorageIdByType($documentId);
		}
		else
		{
			if(is_array($documentId))
			{
				$documentId = intval($documentId[2]);
			}
			/** @var File $file */
			$file = File::loadById($documentId);
			if(!$file)
			{
				return array();
			}
			$storageId = $file->getStorageId();
		}

		if(mb_strtolower($group) == "author")
		{
			$documentId = intval($documentId);
			if($documentId <= 0)
			{
				return array();
			}
			/** @var File $file */
			$file = File::loadById($documentId);
			if(!$file)
			{
				return array();
			}

			return array($file->getCreatedBy());
		}

		if($storageId)
		{
			$storage = Storage::loadById($storageId, array('ROOT_OBJECT'));
			if($storage->getProxyType() instanceof ProxyType\Group)
			{
				$entityId = $storage->getEntityId();
				$group = mb_strtoupper($group);
				if(Loader::includeModule("socialnetwork"))
				{
					$listUserGroup = array();
					if ($group == SONET_ROLES_OWNER)
					{
						$listGroup = \CSocNetGroup::getByID($entityId);
						if ($listGroup)
						{
							$listUserGroup[] = $listGroup["OWNER_ID"];
						}
					}
					elseif ($group == SONET_ROLES_MODERATOR)
					{
						$dbRes = \CSocNetUserToGroup::getList(
							array(),
							array(
								"GROUP_ID" => $entityId,
								"<=ROLE" => SONET_ROLES_MODERATOR,
								"USER_ACTIVE" => "Y"
							),
							false,
							false,
							array("USER_ID")
						);
						while ($res = $dbRes->fetch())
						{
							$listUserGroup[] = $res["USER_ID"];
						}
					}
					elseif ($group == SONET_ROLES_USER)
					{
						$dbRes = \CSocNetUserToGroup::getList(
							array(),
							array(
								"GROUP_ID" => $entityId,
								"<=ROLE" => SONET_ROLES_USER,
								"USER_ACTIVE" => "Y"
							),
							false,
							false,
							array("USER_ID")
						);
						while ($res = $dbRes->fetch())
						{
							$listUserGroup[] = $res["USER_ID"];
						}
					}
					return $listUserGroup;
				}
			}
		}

		$group = intval($group);
		if($group <= 0)
		{
			return array();
		}

		$userIds = array();

		$filter = ['ACTIVE' => 'Y', 'IS_REAL_USER' => true];
		if($group != 2)
		{
			$filter["GROUPS_ID"] = $group;
		}

		$query = \CUser::getList("ID", "ASC", $filter, ['FIELDS' => ['ID']]);
		while($user = $query->fetch())
		{
			$userIds[] = $user["ID"];
		}
		return $userIds;
	}

	public static function setPermissions($documentId, $workflowId, $permissions, $rewrite = true)
	{
		return;
	}

	public static function getJSFunctionsForFields($documentType, $objectName, $documentFields = array(), $documentFieldTypes = array())
	{
		return '';
	}

	public static function getFieldInputControl($documentType, $fieldType, $fieldName, $fieldValue, $allowSelection = false, $publicMode = false)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;

		$storageId = self::getStorageIdByType($documentType);
		if(!$storageId)
		{
			throw new CBPArgumentNullException('documentType');
		}

		static $documentFieldTypes = array();
		if (!array_key_exists($documentType, $documentFieldTypes))
			$documentFieldTypes[$documentType] = self::getDocumentFieldTypes($documentType);

		$fieldType["BaseType"] = "string";
		$fieldType["Complex"] = false;
		if (array_key_exists($fieldType["Type"], $documentFieldTypes[$documentType]))
		{
			$fieldType["BaseType"] = $documentFieldTypes[$documentType][$fieldType["Type"]]["BaseType"];
			$fieldType["Complex"] = $documentFieldTypes[$documentType][$fieldType["Type"]]["Complex"];
		}

		if (!is_array($fieldValue) || is_array($fieldValue) && CBPHelper::isAssociativeArray($fieldValue))
			$fieldValue = array($fieldValue);

		$customMethodName = "";
		$customMethodNameMulty = "";
		if (mb_strpos($fieldType["Type"], ":") !== false)
		{
			$ar = \CIBlockProperty::getUserType(mb_substr($fieldType["Type"], 2));
			if (array_key_exists("GetPublicEditHTML", $ar))
				$customMethodName = $ar["GetPublicEditHTML"];
			if (array_key_exists("GetPublicEditHTMLMulty", $ar))
				$customMethodNameMulty = $ar["GetPublicEditHTMLMulty"];
		}

		ob_start();
		if ($fieldType['Type'] == 'select')
		{
			$fieldValueTmp = $fieldValue;
			?>
			<select id="id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>" style="width:280px" name="<?= htmlspecialcharsbx($fieldName["Field"]).($fieldType["Multiple"] ? "[]" : "") ?>"<?= ($fieldType["Multiple"] ? ' size="5" multiple' : '') ?>>
				<?
				if (!$fieldType['Required'])
					echo '<option value="">['.Loc::getMessage('DISK_FILED_NOT_SET').']</option>';
				foreach ($fieldType['Options'] as $k => $v)
				{
					$ind = array_search($k, $fieldValueTmp);
					echo '<option value="'.htmlspecialcharsbx($k).'"'.($ind !== false ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
					if ($ind !== false)
						unset($fieldValueTmp[$ind]);
				}
				?>
			</select>
			<?
			if ($allowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($fieldName['Field']) ?>_text" name="<?= htmlspecialcharsbx($fieldName['Field']) ?>_text" value="<?
				if (count($fieldValueTmp) > 0)
				{
					$a = array_values($fieldValueTmp);
					echo htmlspecialcharsbx($a[0]);
				}
				?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($fieldName['Field']) ?>_text', 'select');">
				<?
			}
		}
		elseif ($fieldType['Type'] == 'user' || $fieldType['Type'] == static::getPrefixForCustomType() . 'employee')
		{
			$fieldValue = CBPHelper::usersArrayToString($fieldValue, null, self::generateDocumentComplexType($storageId));
			?>
			<input type="text" size="40" id="id_<?= htmlspecialcharsbx($fieldName['Field']) ?>" name="<?= htmlspecialcharsbx($fieldName['Field']) ?>" value="<?= htmlspecialcharsbx($fieldValue) ?>">
			<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($fieldName['Field']) ?>', 'user');"><?
		}
		elseif ((mb_strpos($fieldType["Type"], ":") !== false)
			&& $fieldType["Multiple"]
			&& (is_array($customMethodNameMulty) && count($customMethodNameMulty) > 0
				|| !is_array($customMethodNameMulty) && $customMethodNameMulty <> ''
			)
		)
		{
			if (!is_array($fieldValue))
				$fieldValue = array();

			if ($allowSelection)
			{
				$fieldValueTmp1 = array();
				$fieldValueTmp2 = array();
				foreach ($fieldValue as $v)
				{
					$vTrim = trim($v);
					if(\CBPDocument::isExpression($vTrim))
					{
						$fieldValueTmp1[] = $vTrim;
					}
					else
					{
						$fieldValueTmp2[] = $v;
					}
				}
			}
			else
			{
				$fieldValueTmp1 = array();
				$fieldValueTmp2 = $fieldValue;
			}

			if ($fieldType["Type"] == "E:EList")
			{
				static $fl = true;
				if ($fl)
					$GLOBALS["APPLICATION"]->addHeadScript('/bitrix/js/iblock/iblock_edit.js');
				$fl = false;
			}

			$fieldValueTmp21 = array();
			foreach ($fieldValueTmp2 as $k => $fld)
			{
				if ($fld === null || $fld === "")
					continue;
				if (is_array($fld) && isset($fld["VALUE"]))
					$fieldValueTmp21[$k] = $fld;
				else
					$fieldValueTmp21[$k] = array("VALUE" => $fld);
			}
			$fieldValueTmp2 = $fieldValueTmp21;

			echo call_user_func_array(
				$customMethodNameMulty,
				array(
					array("LINK_IBLOCK_ID" => $fieldType["Options"]),
					$fieldValueTmp2,
					array(
						"FORM_NAME" => $fieldName["Form"],
						"VALUE" => htmlspecialcharsbx($fieldName["Field"])
					),
					true
				)
			);

			if ($allowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" value="<?
			if (count($fieldValueTmp1) > 0)
			{
				$a = array_values($fieldValueTmp1);
				echo htmlspecialcharsbx($a[0]);
			}
			?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text', 'user');">
			<?
			}
		}
		else
		{
			if (!array_key_exists('CBPVirtualDocumentCloneRowPrinted_'.$documentType, $GLOBALS) && $fieldType['Multiple'])
			{
				$GLOBALS['CBPVirtualDocumentCloneRowPrinted_'.$documentType] = 1;
				?>
				<script>
				function CBPVirtualDocumentCloneRow(tableId)
				{
					var tbl = document.getElementById(tableId);
					var cnt = tbl.rows.length;
					var oRow = tbl.insertRow(cnt);
					var oCell = oRow.insertCell(0);
					var sHTML = tbl.rows[cnt - 1].cells[0].innerHTML;
					var p = 0;
					while (true)
					{
						var s = sHTML.indexOf('[n', p);
						if (s < 0)
							break;
						var e = sHTML.indexOf(']', s);
						if (e < 0)
							break;
						var n = parseInt(sHTML.substr(s + 2, e - s));
						sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);
						p = s + 1;
					}
					var p = 0;
					while (true)
					{
						var s = sHTML.indexOf('__n', p);
						if (s < 0)
							break;
						var e = sHTML.indexOf('_', s + 2);
						if (e < 0)
							break;
						var n = parseInt(sHTML.substr(s + 3, e - s));
						sHTML = sHTML.substr(0, s) + '__n' + (++n) + '_' + sHTML.substr(e + 1);
						p = e + 1;
					}
					oCell.innerHTML = sHTML;
					var patt = new RegExp('<' + 'script' + '>[^\000]*?<' + '\/' + 'script' + '>', 'ig');
					var code = sHTML.match(patt);
					if (code)
					{
						for (var i = 0; i < code.length; i++)
						{
							if (code[i] != '')
							{
								var s = code[i].substring(8, code[i].length - 9);
								BX.evalGlobal(s);
							}
						}
					}
				}
				function CBPVirtualDocumentCloneRowHtml(tableId)
				{
					CBPVirtualDocumentCloneRow(tableId);
					var htmlEditor = BX.findChildrenByClassName(BX(tableId), 'bx-html-editor');
					for(var k in htmlEditor)
					{
						var editorId = htmlEditor[k].getAttribute('id');
						var frameArray = BX.findChildrenByClassName(BX(editorId), 'bx-editor-iframe');
						if(frameArray.length > 1)
						{
							for(var i = 0; i < frameArray.length - 1; i++)
							{
								frameArray[i].parentNode.removeChild(frameArray[i]);
							}
						}

					}
				}
				function createAdditionalHtmlEditor(tableId)
				{
					var tbl = document.getElementById(tableId);
					var cnt = tbl.rows.length-1;
					var name = tableId.replace(/(?:CBPVirtualDocument_)(.*)(?:_Table)/, '$1');
					var idEditor = 'id_'+name+'__n'+cnt+'_';
					var inputNameEditor = name+'[n'+cnt+']';
					window.BXHtmlEditor.Show(
					{
						'id':idEditor,
						'inputName':inputNameEditor,
						'content':'',
						'width':'100%',
						'height':'200',
						'allowPhp':false,
						'limitPhpAccess':false,
						'templates':[],
						'templateId':'',
						'templateParams':[],
						'componentFilter':'',
						'snippets':[],
						'placeholder':'Text here...',
						'actionUrl':'/bitrix/tools/html_editor_action.php',
						'cssIframePath':'/bitrix/js/fileman/html_editor/iframe-style.css?1412693817',
						'bodyClass':'',
						'bodyId':'',
						'spellcheck_path':'/bitrix/js/fileman/html_editor/html-spell.js?v=1412693817',
						'usePspell':'N',
						'useCustomSpell':'Y',
						'bbCode':true,
						'askBeforeUnloadPage':true,
						'settingsKey':'user_settings_1',
						'showComponents':true,
						'showSnippets':true,
						'view':'wysiwyg',
						'splitVertical':false,
						'splitRatio':'1',
						'taskbarShown':false,
						'taskbarWidth':'250',
						'lastSpecialchars':false,
						'cleanEmptySpans':true,
						'lazyLoad':false,
						'showTaskbars':false,
						'showNodeNavi':false,
						'controlsMap':[
							{'id':'Bold','compact':true,'sort':'80'},
							{'id':'Italic','compact':true,'sort':'90'},
							{'id':'Underline','compact':true,'sort':'100'},
							{'id':'Strikeout','compact':true,'sort':'110'},
							{'id':'RemoveFormat','compact':true,'sort':'120'},
							{'id':'Color','compact':true,'sort':'130'},
							{'id':'FontSelector','compact':false,'sort':'135'},
							{'id':'FontSize','compact':false,'sort':'140'},
							{'separator':true,'compact':false,'sort':'145'},
							{'id':'OrderedList','compact':true,'sort':'150'},
							{'id':'UnorderedList','compact':true,'sort':'160'},
							{'id':'AlignList','compact':false,'sort':'190'},
							{'separator':true,'compact':false,'sort':'200'},
							{'id':'InsertLink','compact':true,'sort':'210','wrap':'bx-b-link-'+idEditor},
							{'id':'InsertImage','compact':false,'sort':'220'},
							{'id':'InsertVideo','compact':true,'sort':'230','wrap':'bx-b-video-'+idEditor},
							{'id':'InsertTable','compact':false,'sort':'250'},
							{'id':'Code','compact':true,'sort':'260'},
							{'id':'Quote','compact':true,'sort':'270','wrap':'bx-b-quote-'+idEditor},
							{'id':'Smile','compact':false,'sort':'280'},
							{'separator':true,'compact':false,'sort':'290'},
							{'id':'Fullscreen','compact':false,'sort':'310'},
							{'id':'BbCode','compact':true,'sort':'340'},
							{'id':'More','compact':true,'sort':'400'}],
						'autoResize':true,
						'autoResizeOffset':'40',
						'minBodyWidth':'350',
						'normalBodyWidth':'555'
					});
					var htmlEditor = BX.findChildrenByClassName(BX(tableId), 'bx-html-editor');
					for(var k in htmlEditor)
					{
						var editorId = htmlEditor[k].getAttribute('id');
						var frameArray = BX.findChildrenByClassName(BX(editorId), 'bx-editor-iframe');
						if(frameArray.length > 1)
						{
							for(var i = 0; i < frameArray.length - 1; i++)
							{
								frameArray[i].parentNode.removeChild(frameArray[i]);
							}
						}

					}
				}
				</script>
				<?
			}

			if ($fieldType['Multiple'])
				echo '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="CBPVirtualDocument_'.htmlspecialcharsbx($fieldName["Field"]).'_Table">';

			$fieldValueTmp = $fieldValue;

			$ind = -1;
			foreach ($fieldValue as $key => $value)
			{
				$ind++;
				$fieldNameId = 'id_'.htmlspecialcharsbx($fieldName['Field']).'__n'.$ind.'_';
				$fieldNameName = htmlspecialcharsbx($fieldName['Field']).($fieldType['Multiple'] ? '[n'.$ind.']' : '');

				if ($fieldType['Multiple'])
					echo '<tr><td>';

				if (mb_strpos($fieldType['Type'], static::getPrefixForCustomType()) === 0)
				{
					$value1 = $value;
					if($allowSelection && \CBPDocument::isExpression(trim($value1)))
					{
						$value1 = null;
					}
					else
					{
						unset($fieldValueTmp[$key]);
					}

					$type = str_replace(static::getPrefixForCustomType(), '', $fieldType['Type']);

					$_REQUEST[$fieldName['Field']] = $value1;
					$userFieldType = $USER_FIELD_MANAGER->getUserType($type);

					$userField = array(
						'ENTITY_ID' => 'DISK_FILE_' . $storageId,
						'FIELD_NAME' => $fieldName['Field'],
						'USER_TYPE_ID' => $type,
						'SORT' => 100,
						'MULTIPLE' => $fieldType['Multiple'] ? 'Y' : 'N',
						'MANDATORY' => $fieldType['Required'] ? 'Y' : 'N',
						'EDIT_IN_LIST' => 'Y',
						'EDIT_FORM_LABEL' => $userFieldType['DESCRIPTION'],
						'VALUE' => $value1, //
						'USER_TYPE' => $userFieldType,
						'SETTINGS' => array()
					);

					if (
						$fieldType['Type'] == static::getPrefixForCustomType() . 'iblock_element' ||
						$fieldType['Type'] == static::getPrefixForCustomType() . 'iblock_section' ||
						$fieldType['Type'] == static::getPrefixForCustomType() . 'boolean'
					)
					{
						$options = $fieldType['Options'];
						if(is_string($options))
						{
							$userField['SETTINGS']['IBLOCK_ID'] = $options;
						}
						elseif(is_array($options))
						{
							$userField['SETTINGS']= $options;
						}
					}

					$APPLICATION->includeComponent(
						'bitrix:system.field.edit',
						$type,
						array(
							'arUserField' => $userField,
							'bVarsFromForm' => true,
							'form_name' => $fieldName['Form'],
							'FILE_MAX_HEIGHT' => 400,
							'FILE_MAX_WIDTH' => 400,
							'FILE_SHOW_POPUP' => true
						),
						false,
						array('HIDE_ICONS' => 'Y')
					);
				}
				elseif (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && $customMethodName <> '')
				{
					if($fieldType['Type'] == static::getPrefixForCustomType() . 'HTML')
					{
						if (Loader::includeModule("fileman"))
						{
							$editor = new \CHTMLEditor;
							$res = array_merge(
								array(
									'height' => 200,
									'minBodyWidth' => 350,
									'normalBodyWidth' => 555,
									'bAllowPhp' => false,
									'limitPhpAccess' => false,
									'showTaskbars' => false,
									'showNodeNavi' => false,
									'askBeforeUnloadPage' => true,
									'bbCode' => true,
									'siteId' => SITE_ID,
									'autoResize' => true,
									'autoResizeOffset' => 40,
									'saveOnBlur' => true,
									'controlsMap' => array(
										array('id' => 'Bold',  'compact' => true, 'sort' => 80),
										array('id' => 'Italic',  'compact' => true, 'sort' => 90),
										array('id' => 'Underline',  'compact' => true, 'sort' => 100),
										array('id' => 'Strikeout',  'compact' => true, 'sort' => 110),
										array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 120),
										array('id' => 'Color',  'compact' => true, 'sort' => 130),
										array('id' => 'FontSelector',  'compact' => false, 'sort' => 135),
										array('id' => 'FontSize',  'compact' => false, 'sort' => 140),
										array('separator' => true, 'compact' => false, 'sort' => 145),
										array('id' => 'OrderedList',  'compact' => true, 'sort' => 150),
										array('id' => 'UnorderedList',  'compact' => true, 'sort' => 160),
										array('id' => 'AlignList', 'compact' => false, 'sort' => 190),
										array('separator' => true, 'compact' => false, 'sort' => 200),
										array('id' => 'InsertLink',  'compact' => true, 'sort' => 210, 'wrap' => 'bx-b-link-'.$fieldNameId),
										array('id' => 'InsertImage',  'compact' => false, 'sort' => 220),
										array('id' => 'InsertVideo',  'compact' => true, 'sort' => 230, 'wrap' => 'bx-b-video-'.$fieldNameId),
										array('id' => 'InsertTable',  'compact' => false, 'sort' => 250),
										array('id' => 'Code',  'compact' => true, 'sort' => 260),
										array('id' => 'Quote',  'compact' => true, 'sort' => 270, 'wrap' => 'bx-b-quote-'.$fieldNameId),
										array('id' => 'Smile',  'compact' => false, 'sort' => 280),
										array('separator' => true, 'compact' => false, 'sort' => 290),
										array('id' => 'Fullscreen',  'compact' => false, 'sort' => 310),
										array('id' => 'BbCode',  'compact' => true, 'sort' => 340),
										array('id' => 'More',  'compact' => true, 'sort' => 400)
									)
								),
								array(
									'name' => $fieldNameName,
									'inputName' => $fieldNameName,
									'id' => $fieldNameId,
									'width' => '100%',
									'content' => htmlspecialcharsBack($value),
								)
							);
							$editor->show($res);
						}
						else
						{
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
						}
					}
					else
					{
						$value1 = $value;
						if($allowSelection && \CBPDocument::isExpression(trim($value1)))
						{
							$value1 = null;
						}
						else
						{
							unset($fieldValueTmp[$key]);
						}

						echo call_user_func_array(
							$customMethodName,
							array(
								array("LINK_IBLOCK_ID" => $fieldType["Options"]),
								array("VALUE" => $value1),
								array(
									"FORM_NAME" => $fieldName["Form"],
									"VALUE" => $fieldNameName
								),
								true
							)
						);
					}
				}
				else
				{
					switch ($fieldType['Type'])
					{
						case 'int':
							unset($fieldValueTmp[$key]);
							?><input type="text" size="10" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
							break;
						case 'file':
							if ($publicMode)
							{
								//unset($fieldValueTmp[$key]);
								?><input type="file" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?
							}
							break;
						case 'bool':
							if (in_array($value, array('Y', 'N')))
								unset($fieldValueTmp[$key]);
							?>
							<select id='<?= $fieldNameId ?>' name='<?= $fieldNameName ?>'>
								<?
								if (!$fieldType['Required'])
									echo '<option value="">['.Loc::getMessage("DISK_FILED_NOT_SET").']</option>';
								?>
								<option value="Y"<?= (in_array("Y", $fieldValue) ? ' selected' : '') ?>><?= Loc::getMessage("DISK_YES") ?></option>
								<option value="N"<?= (in_array("N", $fieldValue) ? ' selected' : '') ?>><?= Loc::getMessage("DISK_NO") ?></option>
							</select>
							<?
							break;
						case "date":
						case "datetime":
							$v = "";
							if (!\CBPDocument::isExpression(trim($value)))
							{
								$v = $value;
								unset($fieldValueTmp[$key]);
							}

							$APPLICATION->includeComponent(
								'bitrix:main.calendar',
								'',
								array(
									'SHOW_INPUT' => 'Y',
									'FORM_NAME' => $fieldName['Form'],
									'INPUT_NAME' => $fieldNameName,
									'INPUT_VALUE' => $v,
									'SHOW_TIME' => 'Y'
								),
								false,
								array('HIDE_ICONS' => 'Y')
							);
							break;
						case 'text':
							unset($fieldValueTmp[$key]);
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
							break;
						default:
							unset($fieldValueTmp[$key]);
							?><input type="text" size="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
					}
				}

				if ($allowSelection)
				{
					if (!in_array($fieldType["Type"], array("file", "bool", "date", "datetime", static::getPrefixForCustomType() . "HTML")) && (mb_strpos($fieldType['Type'], static::getPrefixForCustomType()) !== 0))
					{
						?><input type="button" value="..." onclick="BPAShowSelector('<?= $fieldNameId ?>', '<?= $fieldType["BaseType"] ?>');"><?
					}
				}

				if ($fieldType['Multiple'])
					echo '</td></tr>';
			}

			if ($fieldType['Multiple'])
				echo '</table>';

			if ($fieldType["Multiple"] && $fieldType['Type'] != static::getPrefixForCustomType() . 'HTML' && (($fieldType["Type"] != "file") || $publicMode))
			{
				echo '<input type="button" value="'.Loc::getMessage("DISK_ADD").'" onclick="CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$fieldName["Field"].'_Table\');"/><br />';
			}
			elseif($fieldType["Multiple"] && $fieldType['Type'] == static::getPrefixForCustomType() . 'HTML')
			{
				$functionOnclick = 'CBPVirtualDocumentCloneRowHtml(\'CBPVirtualDocument_'.\CUtil::JSEscape($fieldName["Field"]).'_Table\');';
				if(!$publicMode)
					$functionOnclick = 'CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.\CUtil::JSEscape($fieldName["Field"]).'_Table\');createAdditionalHtmlEditor(\'CBPVirtualDocument_'.\CUtil::JSEscape($fieldName["Field"]).'_Table\');';

				echo '<input type="button" value="'.Loc::getMessage("DISK_ADD").'" onclick="'.$functionOnclick.'"/><br />';
			}

			if ($allowSelection)
			{
				if (in_array($fieldType['Type'], array('file', 'bool', "date", "datetime")) || (mb_strpos($fieldType['Type'], static::getPrefixForCustomType()) === 0))
				{
					?>
					<input type="text" id="id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text" value="<?
					if (count($fieldValueTmp) > 0)
					{
						$a = array_values($fieldValueTmp);
						echo htmlspecialcharsbx($a[0]);
					}
					?>">
					<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($fieldName["Field"]) ?>_text', '<?= htmlspecialcharsbx($fieldType["BaseType"]) ?>');">
					<?
				}
			}
		}

		$s = ob_get_contents();
		ob_end_clean();

		return $s;
	}

	public static function getFieldInputValue($documentType, $fieldType, $fieldName, $request, &$errors)
	{
		$storageId = self::getStorageIdByType($documentType);
		if(!$storageId)
		{
			throw new CBPArgumentOutOfRangeException('documentType');
		}

		$result = array();

		if ($fieldType["Type"] == "user" || $fieldType['Type'] == static::getPrefixForCustomType() . 'employee')
		{
			$value = $request[$fieldName["Field"]];
			if ($value <> '')
			{
				$result = CBPHelper::usersStringToArray($value, self::generateDocumentComplexType($storageId), $errors);
				if (count($errors) > 0)
				{
					foreach ($errors as $e)
						$errors[] = $e;
				}
			}
		}
		elseif (array_key_exists($fieldName["Field"], $request) || array_key_exists($fieldName["Field"]."_text", $request))
		{
			$valueArray = array();
			if (array_key_exists($fieldName["Field"], $request))
			{
				$valueArray = $request[$fieldName["Field"]];
				if (!is_array($valueArray) || is_array($valueArray) && CBPHelper::isAssociativeArray($valueArray))
					$valueArray = array($valueArray);
			}
			if (array_key_exists($fieldName["Field"]."_text", $request))
				$valueArray[] = $request[$fieldName["Field"]."_text"];

			foreach ($valueArray as $value)
			{
				if (is_array($value) || !is_array($value) && !\CBPDocument::isExpression(trim($value)))
				{
					if ($fieldType["Type"] == "int")
					{
						if ($value <> '')
						{
							$value = str_replace(" ", "", $value);
							if ($value."|" == intval($value)."|")
							{
								$value = intval($value);
							}
							else
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => Loc::getMessage("DISK_INVALID1"),
									"parameter" => $fieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($fieldType["Type"] == "double")
					{
						if ($value <> '')
						{
							$value = str_replace(" ", "", str_replace(",", ".", $value));
							if (is_numeric($value))
							{
								$value = doubleval($value);
							}
							else
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => Loc::getMessage("DISK_INVALID1"),
									"parameter" => $fieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($fieldType["Type"] == "select")
					{
						if (!is_array($fieldType["Options"]) || count($fieldType["Options"]) <= 0 || $value == '')
						{
							$value = null;
						}
						else
						{
							$ar = array_values($fieldType["Options"]);
							if (is_array($ar[0]))
							{
								$b = false;
								foreach ($ar as $a)
								{
									if ($a[0] == $value)
									{
										$b = true;
										break;
									}
								}
								if (!$b)
								{
									$value = null;
									$errors[] = array(
										"code" => "ErrorValue",
										"message" => Loc::getMessage("DISK_UNKNOW"),
										"parameter" => $fieldName["Field"],
									);
								}
							}
							else
							{
								if (!array_key_exists($value, $fieldType["Options"]))
								{
									$value = null;
									$errors[] = array(
										"code" => "ErrorValue",
										"message" => Loc::getMessage("DISK_UNKNOW"),
										"parameter" => $fieldName["Field"],
									);
								}
							}
						}
					}
					elseif ($fieldType["Type"] == "bool")
					{
						if ($value !== "Y" && $value !== "N")
						{
							if ($value === true)
							{
								$value = "Y";
							}
							elseif ($value === false)
							{
								$value = "N";
							}
							elseif ($value <> '')
							{
								$value = mb_strtolower($value);
								if (in_array($value, array("y", "yes", "true", "1")))
								{
									$value = "Y";
								}
								elseif (in_array($value, array("n", "no", "false", "0")))
								{
									$value = "N";
								}
								else
								{
									$value = null;
									$errors[] = array(
										"code" => "ErrorValue",
										"message" => Loc::getMessage("DISK_UNKNOW"),
										"parameter" => $fieldName["Field"],
									);
								}
							}
							else
							{
								$value = null;
							}
						}
					}
					elseif ($fieldType["Type"] == "file")
					{
						if (is_array($value) && array_key_exists("name", $value) && $value["name"] <> '')
						{
							if (!array_key_exists("MODULE_ID", $value) || $value["MODULE_ID"] == '')
								$value["MODULE_ID"] = "bizproc";

							$value = CFile::saveFile($value, "bizproc_wf", true, true);
							if (!$value)
							{
								$value = null;
								$errors[] = array(
									"code" => "ErrorValue",
									"message" => Loc::getMessage("DISK_UNKNOW"),
									"parameter" => $fieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif (mb_strpos($fieldType["Type"], ":") !== false)
					{
						global $USER_FIELD_MANAGER;
						$customTypeID = str_replace(static::getPrefixForCustomType(), '', $fieldType['Type']);
						$customType = $USER_FIELD_MANAGER->getUserType($customTypeID);
						if(is_bool($customType))
							$customType = array();
						
						if ($value !== null && array_key_exists("CheckFields", $customType))
						{
							$errorsTmp1 = call_user_func_array(
								$customType["CheckFields"],
								array(
									array("LINK_IBLOCK_ID" => $fieldType["Options"]),
									array("VALUE" => $value)
								)
							);
							if (count($errorsTmp1) > 0)
							{
								$value = null;
								foreach ($errorsTmp1 as $e)
									$errors[] = array(
										"code" => "ErrorValue",
										"message" => $e,
										"parameter" => $fieldName["Field"],
									);
							}
						}

						if (($value !== null)
							&& ($fieldType["Type"] == static::getPrefixForCustomType() . "employee"))
						{
							$value = "user_".$value;
						}

						if (!is_array($value) && ($value == '') || is_array($value) && (count($value) == 0 || count($value) == 1 && isset($value["VALUE"]) && !is_array($value["VALUE"]) && $value["VALUE"] == ''))
							$value = null;
					}
					else
					{
						if (!is_array($value) && $value == '')
							$value = null;
					}
				}

				if ($value !== null)
					$result[] = $value;
			}
		}

		if (!$fieldType["Multiple"])
		{
			if (count($result) > 0)
				$result = $result[0];
			else
				$result = null;
		}

		return $result;
	}

	public static function getFieldInputValuePrintable($documentType, $fieldType, $fieldValue)
	{
		$storageId = self::getStorageIdByType($documentType);
		if(!$storageId)
		{
			throw new CBPArgumentNullException('documentType');
		}

		$result = $fieldValue;
		switch($fieldType['Type'])
		{
			case 'datetime':
				if(is_array($fieldValue))
				{
					$result = array();
					foreach($fieldValue as $_fieldValue)
					{
						$result[] = empty($_fieldValue) ? formatDate('x', MakeTimeStamp($_fieldValue)) : '';
					}
				}
				else
				{
					$result = !empty($fieldValue) ? formatDate('x', MakeTimeStamp($fieldValue)) : '';
				}
				break;

			case "user":
				if(!is_array($fieldValue))
				{
					$fieldValue = array($fieldValue);
				}

				$result = CBPHelper::usersArrayToString($fieldValue, null, self::generateDocumentComplexType($storageId));
				break;

			case "bool":
				if(is_array($fieldValue))
				{
					$result = array();
					foreach($fieldValue as $r)
					{
						$result[] = ((mb_strtoupper($r) != "N" && !empty($r)) ? Loc::getMessage("BPVDX_YES") : Loc::getMessage("BPVDX_NO"));
					}
				}
				else
				{
					$result = ((mb_strtoupper($fieldValue) != "N" && !empty($fieldValue)) ? Loc::getMessage("BPVDX_YES") : Loc::getMessage("BPVDX_NO"));
				}
				break;

			case "select":
				if(is_array($fieldType["Options"]))
				{
					if(is_array($fieldValue))
					{
						$result = array();
						foreach($fieldValue as $r)
						{
							if(array_key_exists($r, $fieldType["Options"]))
							{
								$result[] = $fieldType["Options"][$r];
							}
						}
					}
					else
					{
						if(array_key_exists($fieldValue, $fieldType["Options"]))
						{
							$result = $fieldType["Options"][$fieldValue];
						}
					}
				}
				break;
		}

		if(mb_strpos($fieldType['Type'], static::getPrefixForCustomType()) === 0)
		{
			global $USER_FIELD_MANAGER, $APPLICATION;
			$type = str_replace(static::getPrefixForCustomType(), '', $fieldType['Type']);
			$userFieldType = $USER_FIELD_MANAGER->getUserType($type);
			$userField = array(
				'ENTITY_ID' => 'DISK_FILE_' . $storageId,
				'FIELD_NAME' => 'UF_XXXXXXX',
				'USER_TYPE_ID' => $type,
				'SORT' => 100,
				'MULTIPLE' => $fieldType['Multiple'] ? 'Y' : 'N',
				'MANDATORY' => $fieldType['Required'] ? 'Y' : 'N',
				'EDIT_FORM_LABEL' => $userFieldType['DESCRIPTION'],
				'VALUE' => $fieldValue, //
				'USER_TYPE' => $userFieldType
			);
			$APPLICATION->includeComponent('bitrix:system.field.view', $type, array(
				'arUserField' => $userField,
				'bVarsFromForm' => false,
				'form_name' => "",
				'FILE_MAX_HEIGHT' => 400,
				'FILE_MAX_WIDTH' => 400,
				'FILE_SHOW_POPUP' => true
			), false, array('HIDE_ICONS' => 'Y'));
			$result = ob_get_contents();
			ob_end_clean();
		}

		return $result;
	}

	public static function getFieldInputControlOptions($documentType, &$fieldType, $functionNameJs, &$value)
	{
		$result = "";

		static $documentFieldTypes = array();
		if (!array_key_exists($documentType, $documentFieldTypes))
			$documentFieldTypes[$documentType] = self::getDocumentFieldTypes($documentType);

		if (!array_key_exists($fieldType["Type"], $documentFieldTypes[$documentType])
			|| !$documentFieldTypes[$documentType][$fieldType["Type"]]["Complex"])
		{
			return "";
		}

		if ($fieldType["Type"] == "E:EList")
		{
			if (is_array($value))
			{
				reset($value);
				$valueTmp = intval(current($value));
			}
			else
			{
				$valueTmp = intval($value);
			}

			$iblockId = 0;
			if ($valueTmp > 0)
			{
				$queryResult = \CIBlockElement::getList(array(), array("ID" => $valueTmp), false, false, array("ID", "IBLOCK_ID"));
				if ($fetchResult = $queryResult->fetch())
					$iblockId = $fetchResult["IBLOCK_ID"];
			}
			if ($iblockId <= 0 && intval($fieldType["Options"]) > 0)
				$iblockId = intval($fieldType["Options"]);

			$defaultIBlockId = 0;

			$result .= '<select id="WFSFormOptionsX" onchange="'.htmlspecialcharsbx($functionNameJs).'(this.options[this.selectedIndex].value)">';
			$iblockType = \CIBlockParameters::getIBlockTypes();
			foreach ($iblockType as $iblockTypeId => $iblockTypeName)
			{
				$result .= '<optgroup label="'.$iblockTypeName.'">';

				$dbIBlock = \CIBlock::getList(array("SORT" => "ASC"), array("TYPE" => $iblockTypeId, "ACTIVE" => "Y"));
				while ($iblock = $dbIBlock->getNext())
				{
					$result .= '<option value="'.$iblock["ID"].'"'.(($iblock["ID"] == $iblockId) ? " selected" : "").'>'.$iblock["NAME"].'</option>';
					if (($defaultIBlockId <= 0) || ($iblock["ID"] == $iblockId))
						$defaultIBlockId = $iblock["ID"];
				}

				$result .= '</optgroup>';
			}
			$result .= '</select><!--__defaultOptionsValue:'.$defaultIBlockId.'--><!--__modifyOptionsPromt:'.GetMessage("IBD_DOCUMENT_MOPROMT").'-->';

			$fieldType["Options"] = $defaultIBlockId;
		}
		elseif ($fieldType["Type"] == "select")
		{
			$valueTmp = $fieldType["Options"];
			if (!is_array($valueTmp))
				$valueTmp = array($valueTmp => $valueTmp);

			$str = '';
			foreach ($valueTmp as $k => $v)
			{
				if (is_array($v) && count($v) == 2)
				{
					$v1 = array_values($v);
					$k = $v1[0];
					$v = $v1[1];
				}

				if ($k != $v)
					$str .= '['.$k.']'.$v;
				else
					$str .= $v;

				$str .= "\n";
			}
			$result .= '<textarea id="WFSFormOptionsX" rows="5" cols="30">'.htmlspecialcharsbx($str).'</textarea><br />';
			$result .= Loc::getMessage("DISK_IBD_DOCUMENT_XFORMOPTIONS1").'<br />';
			$result .= Loc::getMessage("DISK_IBD_DOCUMENT_XFORMOPTIONS2").'<br />';
			$result .= '<script>
				function WFSFormOptionsXFunction()
				{
					var result = {};
					var i, id, val, str = document.getElementById("WFSFormOptionsX").value;

					var arr = str.split(/[\r\n]+/);
					var p, re = /\[([^\]]+)\].+/;
					for (i in arr)
					{
						str = arr[i].replace(/^\s+|\s+$/g, \'\');
						if (str.length > 0)
						{
							id = str.match(re);
							if (id)
							{
								p = str.indexOf(\']\');
								id = id[1];
								val = str.substr(p + 1);
							}
							else
							{
								val = str;
								id = val;
							}
							result[id] = val;
						}
					}

					return result;
				}
				</script>';
			$result .= '<input type="button" onclick="'.htmlspecialcharsbx($functionNameJs).'(WFSFormOptionsXFunction())" value="'.Loc::getMessage("DISK_IBD_DOCUMENT_XFORMOPTIONS3").'">';
		}

		return $result;
	}

	public static function getTaskServiceList($taskId, $userId)
	{
		return \CBPTaskService::getList(
			array(),
			array("ID" => $taskId, "USER_ID" => $userId),
			false,
			false,
			array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS")
		);
	}

	public static function runAfterEdit($storageId, $fileId)
	{
		static::startAutoBizProc($storageId, $fileId, \CBPDocumentEventType::Edit);
	}

	public static function runAfterCreate($storageId, $fileId)
	{
		static::startAutoBizProc($storageId, $fileId, \CBPDocumentEventType::Create);
	}

	/**
	 * @param $storageId
	 * @param $fileId
	 * @param $workflowParameters
	 * @deprecated
	 * @internal
	 */
	public static function runAfterEditWithInputParameters($storageId, $fileId, $workflowParameters)
	{
		static::startAutoBizProc($storageId, $fileId, \CBPDocumentEventType::Edit, $workflowParameters);
	}

	/**
	 * @param $storageId
	 * @param $fileId
	 * @param $workflowParameters
	 * @deprecated
	 * @internal
	 */
	public static function runAfterCreateWithInputParameters($storageId, $fileId, $workflowParameters)
	{
		static::startAutoBizProc($storageId, $fileId, \CBPDocumentEventType::Create, $workflowParameters);
	}

	/**
	 * @param $storageId
	 * @param $fileId
	 * @param $autoExecuteType
	 * @param array $workflowParameters When we want to run bizproc without parameters use empty array.
	 */
	private static function startAutoBizProc($storageId, $fileId, $autoExecuteType, array $workflowParameters = array())
	{
		$documentData = array(
			'DISK' => array(
				'DOCUMENT_TYPE' => BizProcDocument::generateDocumentComplexType($storageId),
				'DOCUMENT_ID' => BizProcDocument::getDocumentComplexId($fileId),
			),
			'WEBDAV' => array(
				'DOCUMENT_TYPE' => BizProcDocumentCompatible::generateDocumentComplexType($storageId),
				'DOCUMENT_ID' => BizProcDocumentCompatible::getDocumentComplexId($fileId),
			),
		);

		$error = array();
		foreach($documentData as $nameModule => $data)
		{
			$filter = array(
				"DOCUMENT_TYPE" => $data["DOCUMENT_TYPE"],
				"AUTO_EXECUTE" => $autoExecuteType,
				"ACTIVE" => "Y",
			);

			if ($workflowParameters)
			{
				$filter['!PARAMETERS'] = null;
			}
			else
			{
				$filter['PARAMETERS'] = null;
			}

			$workflowTemplateObject = \CBPWorkflowTemplateLoader::getList(
				array(),
				$filter,
				false,
				false,
				array("ID", "PARAMETERS")
			);
			while ($workflowTemplate = $workflowTemplateObject->getNext())
			{
				$workflowParameter = array();
				foreach($workflowParameters as $idParameter => $valueParameter)
				{
					$search = $workflowTemplate['ID'];
					$res = mb_strpos($idParameter, $search);
					if($res === 7)
					{
						$parameterKey = end(explode('_', $idParameter));
						$workflowParameter[$parameterKey] = $valueParameter;
					}
				}

				$workflowParametersCheck = \CBPWorkflowTemplateLoader::checkWorkflowParameters(
					$workflowTemplate["PARAMETERS"],
					$workflowParameter,
					$data["DOCUMENT_TYPE"],
					$error
				);
				\CBPDocument::startWorkflow($workflowTemplate['ID'], $data["DOCUMENT_ID"], $workflowParametersCheck, $error);
			}
		}
	}

	public static function getAllowableOperations()
	{
		return array();
	}

	public static function deleteWorkflowsFile($fileId)
	{
		$documentData = array(
			'DISK' => BizProcDocument::getDocumentComplexId($fileId),
			'WEBDAV' => BizProcDocumentCompatible::getDocumentComplexId($fileId),
		);
		$errors = array();
		foreach($documentData as $nameModule => $data)
		{
			\CBPDocument::onDocumentDelete($data, $errors);
		}
	}

	public static function getEntityName($entityId)
	{
		return Loc::getMessage("DISK_BZ_ENTITY_NAME");
	}

	public static function getDocumentTypeName($documentType)
	{
		$storageId = self::getStorageIdByType($documentType);
		if ($storageId)
		{
			$storage = Storage::getById($storageId);
			if($storage)
			{
				return '['.$storage->getSiteId().'] '.$storage->getName();
			}
		}

		return $documentType;
	}

	/**
	 * @param string $documentId
	 * @param string $workflowId
	 * @param int $status
	 * @param null|\CBPActivity $rootActivity
	 */
	public static function onWorkflowStatusChange($documentId, $workflowId, $status, $rootActivity)
	{
		if (
			$rootActivity
			&& $status === \CBPWorkflowStatus::Running
			&& !$rootActivity->workflow->isNew()
			&& !\CBPRuntime::isFeatureEnabled()
		)
		{
			throw new \Exception(Loc::getMessage('DISK_BZ_RESUME_RESTRICTED'));
		}
	}

	/**
	 * @return string
	 */
	protected static function getPrefixForCustomType()
	{
		return 'UF:';
	}

	public static function getBizprocEditorUrl($documentType): ?string
	{

		return '/docs/bp_edit/#ID#/';
	}
}