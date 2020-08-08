<?php

namespace Bitrix\Disk;

use Bitrix\Main\Localization\Loc;
use CBPArgumentNullException;

class BizProcDocumentCompatible extends BizProcDocument
{
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
				$ufFileRow[$fieldKey] = $fieldData['VALUE'];
				$ufFileRow[$fieldData['XML_ID']] = $fieldData['VALUE'];
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
			"SIZE" => $fileRow["SIZE"],
			"CODE" => $fileRow["CODE"],
			"TIMESTAMP_X" => $fileRow["UPDATE_TIME"],
			"MODIFIED_BY" => $fileRow["CREATED_BY"],
			"MODIFIED_BY_PRINTABLE" => $fileRow['UPDATE_USERREF_NAME'].' '.$fileRow['UPDATE_USERREF_LAST_NAME'],
			"DATE_CREATE" => $fileRow["CREATE_TIME"],
			"FILE_SIZE" => $fileRow["SIZE"],
		), $ufFileRow);
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
				}
			}

			$fieldTitle = trim($userField['EDIT_FORM_LABEL']) !== '' ? $userField['EDIT_FORM_LABEL'] : $userField['FIELD_NAME'];

			//this means uf converted from iblock property (webdav)
			if(mb_strpos($userField['XML_ID'], 'PROPERTY_') === 0)
			{
				$fields[$userField['XML_ID']] = array(
					'Name' => $fieldTitle,
					'Options' => $editable,
					'Type' => $type,
					'Filterable' => $userField['MULTIPLE'] != 'Y',
					'Editable' => true,
					'Multiple' => $userField['MULTIPLE'] == 'Y',
					'Required' => $userField['MANDATORY'] == 'Y',
				);
			}

			if($userField['USER_TYPE']['USER_TYPE_ID'] === 'enumeration')
			{
				$fields[$fieldName . '_PRINTABLE'] = array(
					'Name' => $fieldTitle . ' (' . (isset($arOptions['PRINTABLE_SUFFIX']) ? $arOptions['PRINTABLE_SUFFIX'] : 'text') . ')',
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
				$fields[$fieldName] = array(
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
			"TIMESTAMP_X" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_UPDATE_TIME"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"UPDATED_BY" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_UPDATED_BY") . Loc::getMessage("DISK_BZ_D_IDENTIFICATOR"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"MODIFIED_BY" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_UPDATED_BY") . Loc::getMessage("DISK_BZ_D_IDENTIFICATOR"),
				"Type" => "user",
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
			"MODIFIED_BY_PRINTABLE" => array(
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
			"DATE_CREATE" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_CREATE_TIME"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"CREATED_BY" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_CREATED_BY") . Loc::getMessage("DISK_BZ_D_IDENTIFICATOR"),
				"Type" => "user",
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
				"Type" => "user",
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
			"SIZE" => array(
				"Name" => Loc::getMessage("DISK_BZ_D_FIELD_SIZE"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
				"Multiple" => false,
			),
			"FILE_SIZE" => array(
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

	/**
	 * @return string
	 */
	protected static function getPrefixForCustomType()
	{
		return 'S:';
	}
}