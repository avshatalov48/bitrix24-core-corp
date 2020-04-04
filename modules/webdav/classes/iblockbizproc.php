<?php
use Bitrix\Disk\File;

IncludeModuleLangFile(__FILE__);
if (!CModule::IncludeModule("bizproc"))
	return;

class CBPWebDavCanUserOperateOperation extends CBPCanUserOperateOperation
{
	const DeleteDocument = 10;
}

class CIBlockDocumentWebdav extends CIBlockDocument
{
	public function GetDocumentType($documentId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId));
		}

		return parent::GetDocumentType($documentId);
	}

	public function GetDocumentFieldTypes($documentType)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId())));
		}
		return parent::GetDocumentFieldTypes($documentType);
	}

	public function AddDocumentField($documentType, $arFields)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFields));
		}
		return parent::AddDocumentField($documentType, $arFields);
	}

	public function UpdateDocument($documentId, $arFields)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId, $arFields));
		}
		return parent::UpdateDocument($documentId, $arFields);
	}

	public function LockDocument($documentId, $workflowId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId, $workflowId));
		}
		return parent::LockDocument($documentId, $workflowId);
	}

	public function IsDocumentLocked($documentId, $workflowId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId, $workflowId));
		}

		return parent::IsDocumentLocked($documentId, $workflowId);
	}

	public function DeleteDocument($documentId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId));
		}
		parent::DeleteDocument($documentId);
	}

	public function UnpublishDocument($documentId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			self::proxyToDisk(__FUNCTION__, array($diskId));
		}
		parent::UnpublishDocument($documentId);
	}

	public function GetUsersFromUserGroup($group, $documentId)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($group, $diskId));
		}
		return parent::GetUsersFromUserGroup($group, $documentId);
	}

	public function SetPermissions($documentId, $workflowId, $arPermissions, $bRewrite = true)
	{
		$diskId = self::processGetDiskIdByDocId((int)$documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId, $workflowId, $arPermissions, $bRewrite));
		}

		return parent::SetPermissions($documentId, $workflowId, $arPermissions, $bRewrite);
	}

	function GetFieldInputControl($documentType, $arFieldType, $arFieldName, $fieldValue, $bAllowSelection = false, $publicMode = false)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFieldType, $arFieldName, $fieldValue, $bAllowSelection, $publicMode));
		}
		return parent::GetFieldInputControl($documentType, $arFieldType, $arFieldName, $fieldValue, $bAllowSelection, $publicMode);
	}

	function GetFieldInputValue($documentType, $arFieldType, $arFieldName, $arRequest, &$arErrors)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFieldType, $arFieldName, $arRequest, $arErrors));
		}
		return parent::GetFieldInputValue($documentType, $arFieldType, $arFieldName, $arRequest, $arErrors);
	}

	function GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFieldType, $fieldValue));
		}
		return parent::GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue);
	}

	function GetFieldInputControlOptions($documentType, &$arFieldType, $jsFunctionName, &$value)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arFieldType, $jsFunctionName, $value));
		}
		return parent::GetFieldInputControlOptions($documentType, $arFieldType, $jsFunctionName, $value);
	}

	/**
	* Метод по коду документа возвращает ссылку на страницу документа в административной части.
	*
	* @param string $documentId - код документа.
	* @return string - ссылка на страницу документа в административной части.
	*/
	public function GetDocumentAdminPage($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId));
		}

		$db_res = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
			false,
			false,
			array("ID", "CODE", "EXTERNAL_ID", "IBLOCK_ID", "IBLOCK_TYPE_ID", "IBLOCK_SECTION_ID"));
		if ($db_res && $arElement = $db_res->Fetch())
		{
			$db_res = CIBlock::GetList(array(), array("ID" => $arElement["IBLOCK_ID"], "SITE_ID" => SITE_ID, "CHECK_PERMISSIONS" => "N"));
			if ($db_res && $arIblock = $db_res->Fetch())
			{
				$arr = array(
					"LANG_DIR" => SITE_ID,
					"ID" => $documentId,
					"CODE" => $arElement["CODE"],
					"EXTERNAL_ID" => $arElement["EXTERNAL_ID"],
					"IBLOCK_TYPE_ID" => $arIblock["IBLOCK_TYPE_ID"],
					"IBLOCK_ID" => $arIblock["IBLOCK_ID"],
					"IBLOCK_CODE" => $arIblock["IBLOCK_CODE"],
					"IBLOCK_EXTERNAL_ID" => $arIblock["IBLOCK_EXTERNAL_ID"],
					"SECTION_ID" => $arElement["IBLOCK_SECTION_ID"]
					);
				return CIBlock::ReplaceDetailUrl($arIblock["DETAIL_PAGE_URL"], $arr, true, "E");
			}
		}
		return null;
	}

	private static function proxyToDisk($methodName, array $args = array())
	{
		if(!(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk')))
		{
			return;
		}

		//call_user_func don't like &
		if(strtolower($methodName) == 'getfieldinputvalue')
		{
			list($documentType, $fieldType, $fieldName, $request, $errors) = $args;
			return \Bitrix\Disk\BizProcDocumentCompatible::getFieldInputValue($documentType, $fieldType, $fieldName, $request, $errors);
		}
		if(strtolower($methodName) == 'getfieldinputcontroloptions')
		{
			list($documentType, $arFieldType, $jsFunctionName, $value) = $args;
			return \Bitrix\Disk\BizProcDocumentCompatible::getFieldInputControlOptions($documentType, $arFieldType, $jsFunctionName, $value);
		}

		$className = \Bitrix\Disk\BizProcDocumentCompatible::className();

		return call_user_func_array(array($className, $methodName), $args);
	}

	private static function processGetDiskIdByDocId($documentId)
	{
		$arDocFilter = array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y");
		$dbDoc = CIBlockElement::GetList(
			array(),
			$arDocFilter,
			false,
			false,
			array('IBLOCK_ID')
		);
		if ($arDoc = $dbDoc->Fetch())
		{
			$arDocFilter['IBLOCK_ID'] = $arDoc['IBLOCK_ID']; // required for iblock 2.0
		}

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			$arDocFilter
		);
		if ($objDocument = $dbDocumentList->GetNextElement())
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();
			if($dfile = self::needProxyToDiskByDocProp($arDocumentProperties, $arDocumentFields))
			{
				return $dfile->getId();
			}
		}
		return null;
	}

	/**
	 * @param array $documentProperties
	 * @param array $arDocumentFields
	 * @return File|null
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	private static function needProxyToDiskByDocProp(array $documentProperties, array $arDocumentFields)
	{
		if(!(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk')))
		{
			return false;
		}
		if(empty($arDocumentFields['ID']))
		{
			return false;
		}
		return File::load(array('XML_ID' => $arDocumentFields['ID']));
	}

	/**
	 * @param $iblockId
	 * @return bool|null|static
	 */
	private static function needProxyToDiskByIblockId($iblockId)
	{
		if(!(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk')))
		{
			return false;
		}
		if(empty($iblockId))
		{
			return false;
		}
		$storage = \Bitrix\Disk\Storage::load(array(
			'MODULE_ID' => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
			'ENTITY_TYPE' => \Bitrix\Disk\ProxyType\Common::className(),
			'XML_ID' => $iblockId,
		));

		return $storage;
	}

	private static function needProxyToDiskByDocType($documentType)
	{
		if(!(\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk')))
		{
			return false;
		}
		if(empty($documentType))
		{
			return false;
		}
		$storage = null;
		if(substr($documentType, 0, 7) == 'STORAGE')
		{
			$storageId = (int)substr($documentType, 8);
			if($storageId)
			{
				$storage = \Bitrix\Disk\Storage::loadById($storageId);
			}
			if($storage)
			{
				return $storage;
			}
		}
		list(, $iblockId) = explode('_', $documentType);

		if(!$iblockId)
		{
			return false;

		}
		$storage = \Bitrix\Disk\Storage::load(array(
			'XML_ID' => $iblockId,
			'MODULE_ID' => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
			'ENTITY_TYPE' => \Bitrix\Disk\ProxyType\Common::className(),
		));
		if(!$storage)
		{
			return false;
		}

		return $storage;
	}


	private static function getDiskIdFromDocProp(array $documentProperties)
	{
		return $documentProperties['UF_DISK_FILE_ID']['VALUE'];
	}

	/**
	* Метод возвращает свойства (поля) документа в виде ассоциативного массива вида array(код_свойства => значение, ...). Определены все свойства, которые возвращает метод GetDocumentFields.
	*
	* @param string $documentId - код документа.
	* @return array - массив свойств документа.
	*/
	public function GetDocument($documentId)
	{
		if (!function_exists("__get_user_fullname"))
		{
			function __get_user_fullname($ID)
			{
				$ID = intVal($ID);
				$result = "";
				if ($ID > 0)
				{
					if (!array_key_exists("User".$ID, $GLOBALS["WEBDAV"]["CACHE"]))
					{
						$db = CUser::GetByID($ID);
						if ($db && $res = $db->GetNext())
						{
							$result = CUser::FormatName(CSite::GetNameFormat(false), $res);
							$result = (empty($result) ? $res["LOGIN"] : $result);
						}
						$GLOBALS["WEBDAV"]["CACHE"]["User".$ID] = $result;
					}
					else
					{
						$result = $GLOBALS["WEBDAV"]["CACHE"]["User".$ID];
					}
				}
				return $result;
			}
		}

		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$arResult = null;

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y")
		);
		if ($objDocument = $dbDocumentList->GetNextElement())
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();

			if($dfile = self::needProxyToDiskByDocProp($arDocumentProperties, $arDocumentFields))
			{
				return self::proxyToDisk(__FUNCTION__, array($dfile->getId()));
			}

			foreach ($arDocumentFields as $fieldKey => $fieldValue)
			{
				if ($fieldKey == "MODIFIED_BY" || $fieldKey == "CREATED_BY")
				{
					$arResult[$fieldKey] = "user_".$fieldValue;
					$arResult[$fieldKey."_PRINTABLE"] = __get_user_fullname($fieldValue);
				}
				else
				{
					if (substr($fieldKey, 0, 1) != "~")
						$arResult[$fieldKey] = $fieldValue;
				}
			}

			foreach ($arDocumentProperties as $propertyKey => $propertyValue)
			{
				$valueNew = null;

				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					$arPropertyValue = (is_array($propertyValue["VALUE"]) ? $propertyValue["VALUE"] : array($propertyValue["VALUE"]));
					if ($propertyValue["USER_TYPE"] == "UserID")
					{
						$arUserName = array();
						foreach ($arPropertyValue as $userId)
						{
							$valueNew[$userId] = "user_".$userId;
							$sUserName = __get_user_fullname($userId);
							if (!empty($sUserName))
								$arUserName[] = $sUserName;
						}
						$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = implode(", ", $arUserName);
					}
					else
					{
						$valueNew = $propertyValue["VALUE"];
					}
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arPropertyValue = $propertyValue["VALUE"];
					$arPropertyKey = $propertyValue["VALUE_ENUM_ID"];
					if (!is_array($arPropertyValue))
					{
						$arPropertyValue = array($arPropertyValue);
						$arPropertyKey = array($arPropertyKey);
					}

					for ($i = 0, $cnt = count($arPropertyValue); $i < $cnt; $i++)
						$valueNew[$arPropertyKey[$i]] = $arPropertyValue[$i];
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					$arPropertyValue = $propertyValue["VALUE"];
					if (!is_array($arPropertyValue))
						$arPropertyValue = array($arPropertyValue);

					foreach ($arPropertyValue as $v)
					{
						$ar = CFile::GetFileArray($v);
						if ($ar)
							$valueNew[intval($v)] = $ar["SRC"];
					}
				}
				else
				{
					$valueNew = $propertyValue["VALUE"];
				}

				$arResult["PROPERTY_".$propertyKey] = $valueNew;
			}
		}
		unset($arResult["WF_NEW"]);
		return $arResult;
	}

	/**
	* Метод возвращает массив свойств (полей), которые имеет документ данного типа. Метод GetDocument возвращает значения свойств для заданного документа.
	*
	* @param string $documentType - тип документа.
	* @return array - массив свойств вида array(код_свойства => array("NAME" => название_свойства, "TYPE" => тип_свойства), ...).
	*/
	public function GetDocumentFields($documentType)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId())));
		}

		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$arResult = array(
			"ID" => array(
				"Name" => GetMessage("IBLOCK_FIELD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"TIMESTAMP_X" => array(
				"Name" => GetMessage("IBLOCK_FIELD_TIMESTAMP_X"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY" => array(
				"Name" => GetMessage("IBD_FIELD_MODYFIED").GetMessage("IBD_FIELD_IDENTIFICATOR"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY_PRINTABLE" => array(
				"Name" => GetMessage("IBD_FIELD_MODYFIED").GetMessage("IBD_FIELD_NAME_LASTNAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("IBLOCK_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CREATED_BY" => array(
				"Name" => GetMessage("IBD_FIELD_CREATED").GetMessage("IBD_FIELD_IDENTIFICATOR"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"CREATED_BY_PRINTABLE" => array(
				"Name" => GetMessage("IBD_FIELD_CREATED").GetMessage("IBD_FIELD_NAME_LASTNAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"IBLOCK_ID" => array(
				"Name" => GetMessage("IBLOCK_FIELD_IBLOCK_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"ACTIVE" => array(
				"Name" => GetMessage("IBLOCK_FIELD_ACTIVE"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"BP_PUBLISHED" => array(
				"Name" => GetMessage("IBLOCK_FIELD_BP_PUBLISHED"),
				"Type" => "bool",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"NAME" => array(
				"Name" => GetMessage("IBLOCK_FIELD_FILE_NAME"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => true,
			),
			"FILE_SIZE" => array(
				"Name" => GetMessage("IBLOCK_FIELD_FILE_SIZE"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"PREVIEW_TEXT" => array(
				"Name" => GetMessage("IBLOCK_FIELD_FILE_DESCRIPTION"),
				"Type" => "text",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			)
		);

		$arKeys = array_keys($arResult);
		foreach ($arKeys as $key)
			$arResult[$key]["Multiple"] = false;

		$dbProperties = CIBlockProperty::GetList(
			array("sort" => "asc", "name" => "asc"),
			array("IBLOCK_ID" => $iblockId)
		);
		while ($arProperty = $dbProperties->Fetch())
		{
			if (strlen(trim($arProperty["CODE"])) > 0)
				$key = "PROPERTY_".$arProperty["CODE"];
			else
				$key = "PROPERTY_".$arProperty["ID"];

			$arResult[$key] = array(
				"Name" => $arProperty["NAME"],
				"Filterable" => ($arProperty["FILTRABLE"] == "Y"),
				"Editable" => true,
				"Required" => ($arProperty["IS_REQUIRED"] == "Y"),
				"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
			);

			if (strlen($arProperty["USER_TYPE"]) > 0)
			{
				if ($arProperty["USER_TYPE"] == "UserID")
				{
					$arResult[$key]["Name"] .= GetMessage("IBD_FIELD_IDENTIFICATOR");
					$arResult[$key]["Type"] = "user";
					$arResult[$key."_PRINTABLE"] = array(
						"Name" => $arProperty["NAME"].GetMessage("IBD_FIELD_NAME_LASTNAME"),
						"Type" => "string",
						"Filterable" => ($arProperty["FILTRABLE"] == "Y"),
						"Editable" => false,
						"Required" => ($arProperty["IS_REQUIRED"] == "Y"),
						"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
					);
				}
				elseif ($arProperty["USER_TYPE"] == "DateTime")
					$arResult[$key]["Type"] = "datetime";
				else
					$arResult[$key]["Type"] = "string";
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "L")
			{
				$arResult[$key]["Type"] = "select";

				$arResult[$key]["Options"] = array();
				$dbPropertyEnums = CIBlockProperty::GetPropertyEnum($arProperty["ID"]);
				while ($arPropertyEnum = $dbPropertyEnums->GetNext())
					$arResult[$key]["Options"][$arPropertyEnum["ID"]] = $arPropertyEnum["VALUE"];
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "N")
			{
				$arResult[$key]["Type"] = "int";
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "F")
			{
				$arResult[$key]["Type"] = "file";
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "S")
			{
				$arResult[$key]["Type"] = "string";
			}
			else
			{
				$arResult[$key]["Type"] = "string";
			}
		}

		return $arResult;
	}

	/**
	* Метод возвращает массив произвольной структуры, содержащий всю информацию о документе. По этому массиву документ восстановливается методом RecoverDocumentFromHistory.
	*
	* @param string $documentId - код документа.
	* @return array - массив документа.
	*/
	public function GetDocumentForHistory($documentId, $historyIndex, $update = false)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$arResult = null;

		$arDocFilter = array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y");
		$dbDoc = CIBlockElement::GetList(
			array(),
			$arDocFilter,
			false,
			false,
			array('IBLOCK_ID')
		);
		if ($arDoc = $dbDoc->Fetch())
		{
			$arDocFilter['IBLOCK_ID'] = $arDoc['IBLOCK_ID']; // required for iblock 2.0
		}
		
		$dbDocumentList = CIBlockElement::GetList(
			array(),
			$arDocFilter
		);
		if ($objDocument = $dbDocumentList->GetNextElement())
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();

			if($dfile = self::needProxyToDiskByDocProp($arDocumentProperties, $arDocumentFields))
			{
				return self::proxyToDisk(__FUNCTION__, array($dfile->getId(), $historyIndex, $update));
			}

			$arResult["NAME"] = $arDocumentFields["~NAME"];

			$arResult["FIELDS"] = array();
			foreach ($arDocumentFields as $fieldKey => $fieldValue)
			{
				if ($fieldKey == "~PREVIEW_PICTURE" || $fieldKey == "~DETAIL_PICTURE")
				{
					$arResult["FIELDS"][substr($fieldKey, 1)] = CBPDocument::PrepareFileForHistory(
						array("webdav", "CIBlockDocumentWebdav", $documentId),
						$fieldValue,
						$historyIndex
					);
				}
				elseif (substr($fieldKey, 0, 1) == "~")
				{
					$arResult["FIELDS"][substr($fieldKey, 1)] = $fieldValue;
				}
			}

			$arResult["PROPERTIES"] = array();
			foreach ($arDocumentProperties as $propertyKey => $propertyValue)
			{
				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE_ENUM_ID"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F" && $propertyKey == 'FILE') // primary webdav file
				{
					$arDocID = $documentId;
					if (!is_array($documentId))
						$arDocID = array("webdav", "CIBlockDocumentWebdav", $documentId);

					$arResult['PROPERTIES'][$propertyKey] = CWebdavDocumentHistory::GetFileForHistory($arDocID, $propertyValue, $historyIndex);

					if ($update)
						$historyGlueState = CWebdavDocumentHistory::GetHistoryState($arDocID, null, null, array('CHECK_TIME'=>'Y'));
					else
						$historyGlueState = CWebdavDocumentHistory::GetHistoryState($arDocID, null, null, array('NEW'=>'Y', 'CHECK_TIME'=>'Y'));

					$arResult['PROPERTIES'][$propertyKey]['HISTORYGLUE'] = $historyGlueState;
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => CBPDocument::PrepareFileForHistory(
							array("webdav", "CIBlockDocumentWebdav", $documentId),
							$propertyValue["VALUE"],
							$historyIndex
						),
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				else
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
			}
		}

		return $arResult;
	}

	/**
	* Метод разблокирует указанный документ. При разблокировке вызываются обработчики события вида "Сущность_OnUnlockDocument", которым входящим параметром передается код документа.
	*
	* @param string $documentId - код документа
	* @param string $workflowId - код рабочего потока
	* @return bool - если удалось разблокировать документ, то возвращается true, иначе - false.
	*/
	public function UnlockDocument($documentId, $workflowId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId, $workflowId));
		}

		global $DB;
		$strSql = "
			SELECT * FROM b_iblock_element_lock
			WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
		";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		if($z->Fetch())
		{
			$strSql = "
				DELETE FROM b_iblock_element_lock
				WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
				AND (LOCKED_BY = '".$DB->ForSQL($workflowId, 32)."' OR '".$DB->ForSQL($workflowId, 32)."' = '')
			";
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$result = $z->AffectedRowsCount();
		}
		else
		{//Success unlock when there is no locks at all
			$result = 1;
		}

		if ($result > 0)
		{
			$db_events = GetModuleEvents("webdav", "CIBlockDocumentWebdav_OnUnlockDocument");
			while ($arEvent = $db_events->Fetch())
				ExecuteModuleEventEx($arEvent, array("webdav", "CIBlockDocumentWebdav", $documentId));
		}

		return $result > 0;
	}

	function GetUserGroups($documentType = null, $documentId = null, $userId = 0)
	{
		static $arUserGroups = array();
		static $arDocumentInfo = array();
		$documentType = ($documentType == null || empty($documentType) ? null : $documentType);
		if ($documentType != null)
			$documentType = trim(is_array($documentType) ? $documentType[2] : $documentType);

		$userId = intVal($userId);
		$documentIdReal = $documentId = (is_array($documentId) ? $documentId[2] : $documentId);
		$documentId = intVal($documentId);

		if (!array_key_exists($userId, $arUserGroups))
			$arUserGroups[$userId] = ($userId == $GLOBALS["USER"]->GetID() ?
				$GLOBALS["USER"]->GetUserGroupArray() : CUser::GetUserGroup($userId));

		$result = $arUserGroups[$userId];

		if ($documentId > 0 && $userId > 0)
		{
			if (!array_key_exists($documentId, $arDocumentInfo))
			{
				$dbElementList = CIBlockElement::GetList(
					array(),
					array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
					false,
					false,
					array("ID", "IBLOCK_ID", "CREATED_BY")
				);
				$arDocumentInfo[$documentId] = $dbElementList->Fetch();
			}
			if ($arDocumentInfo[$documentId]["CREATED_BY"] == $userId)
				$result[] = "author";
		}
		return $result;
	}

	static function GetIBRights($type, $iblockID, $id = 0)
	{
		static $arRightModes = array();

		if (!isset($arRightModes[$iblockID]))
			$arRightModes[$iblockID] = CIBlock::GetArrayByID($iblockID, "RIGHTS_MODE");

		if (($type == 'SECTION') && ($id == 0))
			$type = 'IBLOCK';

		$result = false;
		if ($arRightModes[$iblockID] === "E")
		{
			if ($type == 'IBLOCK')
				$id = $iblockID;
			$result = CWebDavIblock::GetPermissions($type, $id, $iblockID);
		}
		else
		{
			$result = CIBlock::GetPermission($iblockID);
		}

		if ($GLOBALS['USER']->CanDoOperation('webdav_change_settings'))
			$result = 'X';

		return $result;
	}

	/**
	* Метод проверяет права на выполнение операций над заданным документом. Проверяются операции 0 - просмотр данных рабочего потока, 1 - запуск рабочего потока, 2 - право изменять документ, 3 - право смотреть документ.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных параметров. Используется для того, чтобы не рассчитывать заново те вычисляемые значения, которые уже известны на момент вызова метода. Стандартными являются ключи массива DocumentStates - массив состояний рабочих потоков данного документа, WorkflowId - код рабочего потока (если требуется проверить операцию на одном рабочем потоке). Массив может быть дополнен другими произвольными ключами.
	* @return bool
	*/
	function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		static $arElements = array();
		$documentId = trim($documentId);
		if (strlen($documentId) <= 0)
			return false;

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($operation, $userId, $diskId, $arParameters));
		}

		// Если нам явно не сказали, а нам нужно, то узнаем код инфоблока и автора элемента
		if (!array_key_exists("IBlockId", $arParameters) && (!array_key_exists("IBlockPermission", $arParameters) ||
			!array_key_exists("DocumentStates", $arParameters))
			||
				(!array_key_exists("CreatedBy", $arParameters) && !array_key_exists("AllUserGroups", $arParameters))
			||
				($operation == CBPWebDavCanUserOperateOperation::ReadDocument))
		{
			if (!array_key_exists($documentId, $arElements))
			{
				$dbElementList = CIBlockElement::GetList(
					array(),
					array("ID" => $documentId, "SHOW_NEW" => "Y", "SHOW_HISTORY" => "Y"),
					false,
					false,
					array("ID", "IBLOCK_ID", "CREATED_BY", "WF_STATUS_ID", "WF_PARENT_ELEMENT_ID")
				);
				$arElements[$documentId] = $dbElementList->Fetch();
			}
			$arElement = $arElements[$documentId];

			if (!$arElement)
				return false;

			$arParameters["IBlockId"] = $arElement["IBLOCK_ID"];
			$arParameters["CreatedBy"] = $arElement["CREATED_BY"];
			$arParameters["Published"] = ((intVal($arElement["WF_STATUS_ID"]) == 1 && intVal($arElement["WF_PARENT_ELEMENT_ID"]) <= 0) ? "Y" : "N");
		}

		// Если нам явно не сказали, то узнаем инфоблочные права
		if (!array_key_exists("IBlockPermission", $arParameters))
		{
			//$arParameters["IBlockPermission"] = CIBlock::GetPermission($arParameters["IBlockId"], $userId);
			$arParameters["IBlockPermission"] = CIBlockDocumentWebdav::GetIBRights('ELEMENT', $arParameters["IBlockId"], $documentId);
		}

		if (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_read") < "R")
			return false;
		elseif ($operation != CBPWebDavCanUserOperateOperation::DeleteDocument && CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") >= "W")
			return true;
		elseif ($operation == CBPWebDavCanUserOperateOperation::DeleteDocument && CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_delete") >= "X")
			return true;
		elseif ($operation == CBPWebDavCanUserOperateOperation::ReadDocument && $arParameters["Published"] == "Y")
			return true;

		// Если мы тут, то инфоблочные права равны U

		// Если нам явно не сказали, то узнаем группы пользователя
		$userId = intval($userId);
		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
				$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			if ($userId == $arParameters["CreatedBy"])
				$arParameters["AllUserGroups"][] = "Author";
		}

		// Если нам явно не сказали, то узнаем текущие статусы документа
		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("webdav", "CIBlockDocumentWebdav", "iblock_".$arParameters["IBlockId"]),
				array("webdav", "CIBlockDocumentWebdav", $documentId)
			);
		}

		// Если нужно проверить только для одного рабочего потока
		if (array_key_exists("WorkflowId", $arParameters) && !empty($arParameters["WorkflowId"]))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		// $arAllowableOperations == null - поток не является автоматом
		// $arAllowableOperations == array() - в автомате нет допустимых операций
		// $arAllowableOperations == array("read", ...) - допустимые операции

		if (!is_array($arAllowableOperations))
			return false;

		$r = false;

		switch ($operation)
		{
			case CBPWebDavCanUserOperateOperation::ViewWorkflow:
				// право на просмотр бизнес-процесса есть только у пользователей, которым разрешено читать
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_read") > "U" || in_array("read", $arAllowableOperations));
				break;
			case CBPWebDavCanUserOperateOperation::StartWorkflow:
				// право на запуск бизнес-процесса есть у имеющих право "W",
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_bizproc_start") > "U" || in_array("write", $arAllowableOperations));
				// если задан конкретный шаблон потока, то проверяем его
				if ($r && CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_bizproc_start") <= "U" && $arParameters["WorkflowTemplateId"] > 0)
				{
					// Выбираем все шаблоны для того, чтобы определить тип шаблона
					// так как для последовательных процессов мы даем право на запуск
					// бизнес-процесса без проверки прав на запись.
					if (!array_key_exists("WorkflowTemplateList".$arParameters["IBlockId"], $GLOBALS["WEBDAV"]["CACHE"]))
					{
						if (array_key_exists("WorkflowTemplateList", $arParameters) && is_array($arParameters["WorkflowTemplateList"]))
						{
							$GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplateList".$arParameters["IBlockId"]] = array();
							foreach ($arParameters["WorkflowTemplateList"] as $res)
								$GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplateList".$arParameters["IBlockId"]][$res["ID"]] = $res;
						}
						else
						{
							$res = array();
							$db_res = CBPWorkflowTemplateLoader::GetList(
								array(),
								array("DOCUMENT_TYPE" => array("webdav", "CIBlockDocumentWebdav", "iblock_".$arParameters["IBlockId"]), "ACTIVE"=>"Y"),
								false,
								false,
								array("ID", "NAME", "DESCRIPTION", "TEMPLATE", "PARAMETERS")
							);
							while ($arWorkflowTemplate = $db_res->GetNext())
							{
								$res[$arWorkflowTemplate["ID"]] = $arWorkflowTemplate;
							}
							$GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplateList".$arParameters["IBlockId"]] = $res;
						}
					}
					$arWorkflowTemplateList = $GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplateList".$arParameters["IBlockId"]];


					if (array_key_exists($arParameters["WorkflowTemplateId"], $arWorkflowTemplateList))
					{
						$arTemplate = $arWorkflowTemplateList[$arParameters["WorkflowTemplateId"]];
						// Если это машина состояний, то проверяем первое состояние
						if ($arTemplate["TEMPLATE"][0]["Type"] == "StateMachineWorkflowActivity")
						{
							// Выбираем парметры конкретного шаблона рабочего потока
							if (array_key_exists($arParameters["WorkflowTemplateId"], $arParameters["DocumentStates"]))
							{
								$arDocumentStates = $arParameters["DocumentStates"][$arParameters["WorkflowTemplateId"]];
							}
							else
							{
								if (!array_key_exists("WorkflowTemplate".$arParameters["WorkflowTemplateId"], $GLOBALS["WEBDAV"]["CACHE"]))
									$GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplate".$arParameters["WorkflowTemplateId"]] =
										CBPWorkflowTemplateLoader::GetTemplateState($arParameters["WorkflowTemplateId"]);
								$arDocumentStates = $GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplate".$arParameters["WorkflowTemplateId"]];
							}
							$arAllowableOperations = CBPDocument::GetAllowableOperations(
								$userId,
								$arParameters["AllUserGroups"],
								array($arParameters["WorkflowTemplateId"] => $arDocumentStates)
							);
							$r = (is_array($arAllowableOperations) && in_array("write", $arAllowableOperations));
						}
					}
				}
				break;
			case CBPWebDavCanUserOperateOperation::CreateWorkflow:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U");
				break;
			case CBPWebDavCanUserOperateOperation::WriteDocument:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U" || in_array("write", $arAllowableOperations));
				break;
			case CBPWebDavCanUserOperateOperation::DeleteDocument:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_delete") >= "X" || in_array("delete", $arAllowableOperations));
				break;
			case CBPWebDavCanUserOperateOperation::ReadDocument:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U" || in_array("read", $arAllowableOperations) || in_array("write", $arAllowableOperations));
				break;
			default:
				$r = false;
		}

		return $r;
	}

	/**
	* Метод проверяет права на выполнение операций над документами заданного 
	* типа. Проверяются операции 4 - право изменять шаблоны рабочий потоков для 
	* данного типа документа.
	*
	* @param int $operation - операция.
	* @param int $userId - код пользователя, для которого проверяется право на выполнение операции.
	* @param string $documentId - код типа документа, к которому применяется операция.
	* @param array $arParameters - ассициативный массив вспомогательных 
	* параметров. Используется для того, чтобы не рассчитывать заново те 
	* вычисляемые значения, которые уже известны на момент вызова метода. 
	* Стандартными являются ключи массива DocumentStates - массив состояний 
	* рабочих потоков данного документа, WorkflowId - код рабочего потока (если 
	* требуется проверить операцию на одном рабочем потоке). Массив может быть 
	* дополнен другими произвольными ключами.
	* @return bool
	*/
	function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array($operation, $userId, \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId()), $arParameters));
		}

		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);


		$arParameters["IBlockId"] = intval(substr($documentType, strlen("iblock_")));

		// Если нам явно не сказали, то узнаем инфоблочные права
		if (!array_key_exists("IBlockPermission", $arParameters))
		{
			if (isset($arParameters['SectionId']))
			{
				$arParameters['SectionId'] = intval($arParameters['SectionId']);
				$arParameters["IBlockPermission"] = CIBlockDocumentWebdav::GetIBRights('SECTION', $arParameters["IBlockId"], $arParameters['SectionId']);
			}
			else
			{
				$arParameters["IBlockPermission"] = CIBlockDocumentWebdav::GetIBRights('IBLOCK', $arParameters["IBlockId"]);
			}
		}

		if (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_read") < "R")
			return false;
		elseif (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") >= "W")
			return true;

		// Если мы тут, то инфоблочные права равны U

		// Если нам явно не сказали, то узнаем группы пользователя
		$userId = intval($userId);
		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
				$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			$arParameters["AllUserGroups"][] = "Author";
		}

		// Если нам явно не сказали, то узнаем текущие статусы документа
		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("webdav", "CIBlockDocumentWebdav", "iblock_".$arParameters["IBlockId"]),
				null
			);
		}

		// Если нужно проверить только для одного рабочего потока
		if (array_key_exists("WorkflowId", $arParameters) && !empty($arParameters["WorkflowId"]))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		// $arAllowableOperations == null - поток не является автоматом
		// $arAllowableOperations == array() - в автомате нет допустимых операций
		// $arAllowableOperations == array("read", ...) - допустимые операции
		if (!is_array($arAllowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case CBPCanUserOperateOperation::ViewWorkflow:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_read") > "U" || in_array("read", $arAllowableOperations));
				break;
			case CBPCanUserOperateOperation::StartWorkflow:
				// право на запуск бизнес-процесса есть у имеющих право "W",
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_bizproc_start") > "U" || in_array("write", $arAllowableOperations));
				// если задан конкретный шаблон потока, то проверяем его
				if ($r && CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_bizproc_start") <= "U" && $arParameters["WorkflowTemplateId"] > 0)
				{
					// Выбираем все шаблоны для того, чтобы определить тип шаблона
					// так как для последовательных процессов мы даем право на запуск
					// бизнес-процесса без проверки прав на запись.
					if (!array_key_exists("WorkflowTemplateList".$arParameters["IBlockId"], $GLOBALS["WEBDAV"]["CACHE"]))
					{
						if (array_key_exists("WorkflowTemplateList", $arParameters))
						{
							$GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplateList".$arParameters["IBlockId"]] = array();
							foreach ($arParameters["WorkflowTemplateList"] as $res)
								$GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplateList".$arParameters["IBlockId"]][$res["ID"]] = $res;
						}
						else
						{
							$res = array();
							$db_res = CBPWorkflowTemplateLoader::GetList(
								array(),
								array("DOCUMENT_TYPE" => array("webdav", "CIBlockDocumentWebdav", "iblock_".$arParameters["IBlockId"]), "ACTIVE"=>"Y"),
								false,
								false,
								array("ID", "NAME", "DESCRIPTION", "TEMPLATE", "PARAMETERS")
							);
							while ($arWorkflowTemplate = $db_res->GetNext())
							{
								$res[$arWorkflowTemplate["ID"]] = $arWorkflowTemplate;
							}
							$GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplateList".$arParameters["IBlockId"]] = $res;
						}
					}
					$arWorkflowTemplateList = $GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplateList".$arParameters["IBlockId"]];


					if (array_key_exists($arParameters["WorkflowTemplateId"], $arWorkflowTemplateList))
					{
						$arTemplate = $arWorkflowTemplateList[$arParameters["WorkflowTemplateId"]];
						// Если это машина состояний, то проверяем первое состояние
						if ($arTemplate["TEMPLATE"][0]["Type"] == "StateMachineWorkflowActivity")
						{
							// Выбираем парметры конкретного шаблона рабочего потока
							if (array_key_exists($arParameters["WorkflowTemplateId"], $arParameters["DocumentStates"]))
							{
								$arDocumentStates = $arParameters["DocumentStates"][$arParameters["WorkflowTemplateId"]];
							}
							else
							{
								if (!array_key_exists("WorkflowTemplate".$arParameters["WorkflowTemplateId"], $GLOBALS["WEBDAV"]["CACHE"]))
									$GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplate".$arParameters["WorkflowTemplateId"]] =
										CBPWorkflowTemplateLoader::GetTemplateState($arParameters["WorkflowTemplateId"]);
								$arDocumentStates = $GLOBALS["WEBDAV"]["CACHE"]["WorkflowTemplate".$arParameters["WorkflowTemplateId"]];
							}
							$arAllowableOperations = CBPDocument::GetAllowableOperations(
								$userId,
								$arParameters["AllUserGroups"],
								array($arParameters["WorkflowTemplateId"] => $arDocumentStates)
							);
							$r = (is_array($arAllowableOperations) && in_array("write", $arAllowableOperations));
						}
					}
				}
				break;
			case CBPCanUserOperateOperation::CreateWorkflow:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U");
				break;
			case CBPCanUserOperateOperation::WriteDocument:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U" || in_array("write", $arAllowableOperations));
				break;
			case CBPWebDavCanUserOperateOperation::ReadDocument:
				$r = (CWebDavIblock::CheckRight($arParameters["IBlockPermission"], "element_edit") > "U" || in_array("read", $arAllowableOperations));
				break;
			default:
				$r = false;
		}

		return $r;
	}

	/**
	* Метод публикует документ. То есть делает его доступным в публичной части сайта.
	*
	* @param string $documentId - код документа.
	*/
	public function PublishDocument($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$diskId = self::processGetDiskIdByDocId($documentId);
		if($diskId !== null)
		{
			return self::proxyToDisk(__FUNCTION__, array($diskId));
		}

		global $DB;
		$ID = intval($documentId);
		$db_element = CIBlockElement::GetList(array(), array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false,
			array(
				"ID",
				"NAME",
				"WF_PARENT_ELEMENT_ID",
			)
		);
		$PARENT_ID = 0; $arParent = array();
		if($ar_element = $db_element->Fetch())
		{
			$PARENT_ID = intval($ar_element["WF_PARENT_ELEMENT_ID"]);
			if ($PARENT_ID > 0)
			{
				CBPDocument::MergeDocuments(
					array("webdav", "CIBlockDocumentWebdav", $PARENT_ID),
					array("webdav", "CIBlockDocumentWebdav", $documentId));
				$db_res = CIBlockElement::GetList(
					array(),
					array("ID" => $PARENT_ID, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
					false,
					false,
					array("IBLOCK_ID", "ID", "NAME"));
				$arParent = $db_res->Fetch();
			}
		}
		parent::PublishDocument($documentId);
		if ($PARENT_ID > 0)
		{
			CBPDocument::AddDocumentToHistory(
				array("webdav", "CIBlockDocumentWebdav", $PARENT_ID),
				str_replace(
					array("#PARENT_ID#", "#PARENT_NAME#", "#ID#", "#NAME#"),
					array($PARENT_ID, $arParent["NAME"], $documentId, $ar_element["NAME"]),
					GetMessage("IBD_TEXT_001")),
				$GLOBALS["USER"]->GetID());
		}


		if ($ar_element)
		{
			$rsEvents = GetModuleEvents("webdav", "OnBizprocPublishDocument");
			while ($arEvent = $rsEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($ar_element['ID']));
			}
		}

		return $PARENT_ID > 0 ? $PARENT_ID : $documentId;
	}
	/**
	* Метод клонирует документ.
	*
	* @param string $documentId - ID документа.
	* @param string $arFields - поля для замены.
	*/
	public function CloneElement($ID, $arFields = array())
	{
		global $DB;
		$ID = intval($ID);

		$CHILD_ID = parent::CloneElement($ID, $arFields);
		if ($CHILD_ID > 0)
		{
			$db_res = CIBlockElement::GetList(
				array(),
				array("ID" => $ID, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
				false,
				false,
				array("IBLOCK_ID", "ID", "NAME"));
			$arParent = $db_res->Fetch();
			CBPDocument::AddDocumentToHistory(
				array("webdav", "CIBlockDocumentWebdav", $CHILD_ID),
				str_replace(
					array("#ID#", "#NAME#", "#PARENT_ID#", "#PARENT_NAME#"),
					array($CHILD_ID, $arFields["NAME"], $ID, $arParent["NAME"]),
					GetMessage("IBD_TEXT_002")),
				$GLOBALS["USER"]->GetID());
		}
		return $CHILD_ID;
	}

	// array("1" => "Админы", 2 => "Гости", 3 => ..., "Author" => "Афтар")
	public function GetAllowableUserGroups($documentType, $withExtended = false)
	{
		if($storage = self::needProxyToDiskByDocType($documentType))
		{
			return self::proxyToDisk(__FUNCTION__, array(\Bitrix\Disk\BizProcDocumentCompatible::generateDocumentType($storage->getId())));
		}

		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$iblockId = intval(substr($documentType, strlen("iblock_")));

		$arResult = array("Author" => GetMessage("IBD_DOCUMENT_AUTHOR"));

		$arRes = array(1);

		if(CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$obRights = new CIBlockRights($iblockId);
			foreach($obRights->GetGroups("element_bizproc_start") as $GROUP_CODE)
				if(preg_match("/^G(\\d+)\$/", $GROUP_CODE, $match))
					$arRes[] = $match[1];
		}
		else
		{
			$arGroups = CIBlock::GetGroupPermissions($iblockId);
			foreach ($arGroups as $groupId => $perm)
			{
				if ($perm >= "R")
					$arRes[] = $groupId;
			}
		}

		//Crutch for Bitrix24 context (user group management is not suppotted)
		if(IsModuleInstalled('bitrix24'))
		{
			$siteID = CAllSite::GetDefSite();
			$dbResult = CGroup::GetList(
				($by = ''),
				($order = ''),
				array('STRING_ID' => 'EMPLOYEES_'.$siteID,
				'STRING_ID_EXACT_MATCH' => 'Y')
			);
			if($arEmployeeGroup = $dbResult->Fetch())
			{
				$employeeGroupID = intval($arEmployeeGroup['ID']);
				if(!in_array($employeeGroupID, $arRes, true))
				{
					$arRes[] = $employeeGroupID;
				}
			}
		}


		$dbGroupsList = CGroup::GetListEx(array("NAME" => "ASC"), array("ID" => $arRes));
		while ($arGroup = $dbGroupsList->Fetch())
			$arResult[$arGroup["ID"]] = $arGroup["NAME"];

		return $arResult;
	}

	/**
	* Метод - просто агрегатор действий по проверке корректности входных данных
	*
	* @param string $documentType - тип документа.
	* @param array $arDocumentStates - массив шаблонов БП документа.
	* @param array $arBizProcParametersValues - массив входных параметров для шаблонов.
	* @param array $arErrors - массив ошибок.
	*/
	public function StartWorkflowsParametersValidate($documentType, $arDocumentStates, &$arBizProcParametersValues, &$arErrors)
	{
		$arBizProcParametersValues = array();
		$arDocumentStates = (is_array($arDocumentStates) ? $arDocumentStates : array());

		foreach ($arDocumentStates as $arDocumentState)
		{
			if (strlen($arDocumentState["ID"]) <= 0)
			{
				$arErrorsTmp = array();
				$arBizProcParametersValues[$arDocumentState["TEMPLATE_ID"]] =
					CBPDocument::StartWorkflowParametersValidate(
						$arDocumentState["TEMPLATE_ID"],
						$arDocumentState["TEMPLATE_PARAMETERS"],
						$documentType,
						$arErrorsTmp
					);

				foreach ($arErrorsTmp as $e)
					$arErrors[] = array("id" => "bizproc_validate", "text" => $e["message"]);
			}
		}
		return empty($arErrors);
	}

	/**
	* Метод - просто агрегатор действий, запускающий БП и выполняющий события
	*
	* @param string $documentId - код документа.
	* @param array $arDocumentStates - массив шаблонов БП документа.
	* @param array $arBizProcParametersValues - массив входных параметров для шаблонов.
	* @param array $arUserGroups - массив групп пользователей.
	* @param array $arErrors - массив ошибок.
	*/
	public static function StartWorkflowsExecuting($documentId, $arDocumentStates, $arBizProcParametersValues, $arUserGroups, &$arErrors, $wfParams = array())
	{
		$arErrors = array();
		$arBizProcWorkflowId = array();
		$arDocumentStates = (is_array($arDocumentStates) ? $arDocumentStates : array());
		$arBizProcParametersValues = (is_array($arBizProcParametersValues) ? $arBizProcParametersValues : array());
		$arUserGroups = (is_array($arUserGroups) ? $arUserGroups : array());

		foreach ($arDocumentStates as $arDocumentState)
		{
			if (strlen($arDocumentState["ID"]) <= 0)
			{
				$arErrorsTmp = array();
				$arBizProcWorkflowId[$arDocumentState["TEMPLATE_ID"]] = CBPDocument::StartWorkflow(
					$arDocumentState["TEMPLATE_ID"],
					$documentId,
					$arBizProcParametersValues[$arDocumentState["TEMPLATE_ID"]],
					$arErrorsTmp);

				foreach ($arErrorsTmp as $e)
					$arError[] = array(
						"id" => "bizproc_start_workflow",
						"text" => $e["message"]);
			}
		}

		if (empty($arError) && intval($_REQUEST["bizproc_index"]) > 0)
		{
			if (empty($arUserGroups))
			{
				$arUserGroups = call_user_func_array(
					array($wfParams['DOCUMENT_TYPE'][1], "GetUserGroups"),
					array(null, $documentId, $GLOBALS["USER"]->GetID()));
			}

			$bizprocIndex = intval($_REQUEST["bizproc_index"]);
			for ($i = 1; $i <= $bizprocIndex; $i++)
			{
				$bpId = trim($_REQUEST["bizproc_id_".$i]);
				$bpTemplateId = intval($_REQUEST["bizproc_template_id_".$i]);
				$bpEvent = trim($_REQUEST["bizproc_event_".$i]);
				if (strlen($bpEvent) > 0)
				{
					if (strlen($bpId) > 0)
					{
						if (!array_key_exists($bpId, $arDocumentStates))
							continue;
					}
					else
					{
						if (!array_key_exists($bpTemplateId, $arDocumentStates))
							continue;
						$bpId = $arBizProcWorkflowId[$bpTemplateId];
					}
					$arErrorTmp = array();
					CBPDocument::SendExternalEvent(
						$bpId,
						$bpEvent,
						array("Groups" => $arUserGroups, "User" => $GLOBALS["USER"]->GetID()),
						$arErrorTmp);
					foreach ($arErrorsTmp as $e)
						$arError[] = array(
							"id" => "bizproc_send_external_event",
							"text" => $e["message"]);
				}
			}
		}
		return empty($arError);
	}

	public static function TruncateHistory($arDocType, $docID)
	{
		$maxCount = COption::GetOptionInt("webdav", "bp_history_size", 50);
		if ($maxCount <= 0)
			return;

		$documentId = array($arDocType[0], $arDocType[1], $docID);
		$history = new CBPHistoryService();
		$db_res = $history->GetHistoryList(
			array("ID" => "DESC"),
			array("DOCUMENT_ID" => $documentId),
			false,
			false,
			array("ID")
		);

		$count = 1;
		if ($db_res)
		{
			while ($arr = $db_res->Fetch())
			{
				if ($count++ > $maxCount)
					CBPHistoryService::Delete($arr["ID"], $documentId);
			}
		}
	}

	// RegisterModuleDependences("bizproc", "OnAddToHistory", "webdav", "CIBlockDocumentWebdav", "OnAddToHistory", 100);
	public static function OnAddToHistory($arParams)
	{
		$docType = $arParams['DOCUMENT_ID'];
		if (!(($docType[0] === 'webdav') && (strpos($docType[1], "Webdav") !== false)))
			return;

		CIBlockDocumentWebdav::TruncateHistory($docType, $docType[2]);
	}

	// RegisterModuleDependences("iblock", "OnAfterIBlockElementDelete", "webdav", "CIBlockDocumentWebdav", "OnAfterIBlockElementDelete", 100);
	public static function OnAfterIBlockElementDelete($arFields)
	{
		CBPDocument::OnDocumentDelete(array("webdav", "CIBlockDocumentWebdav", $arFields["ID"]), $arErrorsTmp);
		CBPDocument::OnDocumentDelete(array("webdav", "CIBlockDocumentWebdavSocnet", $arFields["ID"]), $arErrorsTmp);
	}
}
