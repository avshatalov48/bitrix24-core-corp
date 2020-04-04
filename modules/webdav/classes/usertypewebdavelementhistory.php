<?php

class CUserTypeWebdavElementHistory extends CUserTypeWebdavElement
{
	function GetUserTypeDescription()
	{
		\Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/webdav/classes/usertypewebdav.php");

		return array(
			"USER_TYPE_ID" => "webdav_element_history",
			"CLASS_NAME" => __CLASS__,
			"DESCRIPTION" => GetMessage("USER_TYPE_WEBDAV_FILE_HISTORY_DESCRIPTION"),
			"BASE_TYPE" => "string",
		);
	}

	function GetDBColumnType($arUserField)
	{
		global $DB;
		switch(strtolower($DB->type))
		{
			case "mysql":
				return "text";
			case "oracle":
				return "varchar2(2000 char)";
			case "mssql":
				return "varchar(2000)";
		}
	}

	function PrepareSettings($arUserField)
	{
		$iblockID = intval($arUserField["SETTINGS"]["IBLOCK_ID"]);
		$sectionID = intval($arUserField["SETTINGS"]["SECTION_ID"]);

		return array(
			"IBLOCK_ID" => $iblockID,
			"SECTION_ID" => $sectionID,
		);
	}

	function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
	{
		return "&nbsp;";
	}

	function OnBeforeSave($arUserField, $value)
	{
		$file = static::getDataFromValue($value);
		if(empty($file) || empty($file[0]['id']))
		{
			return '';
		}
		if(empty($file[0]['v']))
		{
			static::rewritePreviousHistoryComment($file);
		}

		return static::genData($file[0], $file[1]);
	}

	protected function getPostIdForComment()
	{
		return !empty($_POST['comment_post_id'])? $_POST['comment_post_id'] : false;
	}

	protected static function rewritePreviousHistoryComment(array $file)
	{
		if(!CModule::IncludeModule('blog'))
		{
			return;
		}
		$dbComments = CBlogComment::GetList(array('ID' => 'DESC'), array('POST_ID' => static::getPostIdForComment(), 'UF_BLOG_COMMENT_FH' => static::getStringForLike($file[0])), false, false, array('ID', 'POST_ID', 'BLOG_ID', 'UF_BLOG_COMMENT_FH'));
		if($dbComments && ($lastComment = $dbComments->Fetch()))
		{
			$entityType = static::getEntityType($file[0]['ib_code']);
			$documentId = static::getEntityIdDocumentData($entityType, array('ELEMENT_ID' => $file[0]['id']));
			$filter  = array(
				"DOCUMENT_ID" => $documentId,
			);
			$historyDoc = static::getIdHistoryDocument($filter);
			if(!empty($historyDoc['ID']))
			{
				$newFileData = static::getDataFromValue($lastComment['UF_BLOG_COMMENT_FH']);
				$newFileData[0]['v'] = $historyDoc['ID'];
				CBlogComment::Update($lastComment['ID'], array('UF_BLOG_COMMENT_FH' => static::genData($newFileData[0], $newFileData[1]), 'HAS_PROPS' => 'Y'));
			}
		}
	}

	/**
	 * @param $iblockCode
	 * @return string
	 */
	protected static function getEntityType($iblockCode)
	{
		$entityType = explode('_', $iblockCode);
		$entityType = strtolower(array_shift($entityType));

		return $entityType;
	}

	protected static function getEntityIdDocumentData($entityType, $params = array())
	{
		if ($entityType == 'group')
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdavSocnet',
				$params['ELEMENT_ID']
			);

		}
		elseif ($entityType == 'shared')
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdav',
				$params['ELEMENT_ID']
			);
		}
		else
		{
			$documentId  = array(
				'webdav',
				'CIBlockDocumentWebdavSocnet',
				$params['ELEMENT_ID']
			);
		}
		return $documentId;

	}

	protected static function getIdHistoryDocument(array $filter)
	{
		if(!CModule::IncludeModule('bizproc'))
		{
			return array();
		}

		$by      = "modified";
		$order   = "desc";
		$history = new CBPHistoryService();
		$dbDocumentHistory = $history->GetHistoryList(
			array(strtoupper($by) => strtoupper($order)),
			$filter,
			false,
			array('nTopCount' => 1),
			array(
				"ID",
				"DOCUMENT_ID",
				"NAME",
				"MODIFIED",
				"USER_ID",
				"USER_NAME",
				"USER_LAST_NAME",
				"USER_LOGIN",
				"DOCUMENT",
				"USER_SECOND_NAME"
			)
		);

		if($res = $dbDocumentHistory->fetch())
		{
			return $res;
		}
		else
		{
			//if not exists second entry, then original is same webdav element.
		}

		return array();
	}

	function _fileUnserialize($data)
	{
		$data = static::getDataFromValue($data);
		return !empty($data[0]['id'])? $data[0] : array();
	}

	/**
	 * @param $value
	 * @return array|mixed|string
	 */
	public static function getDataFromValue($value)
	{
		if(is_string($value) && CheckSerializedData($value))
		{
			$value = @unserialize($value);
			if($value === false)
			{
				return array();
			}
		}

		if (empty($value[0]['id']))
		{
			return array();
		}

		return $value;
	}

	/**
	 *
	 * @param array $file - "const data by file (iblock_element, iblock_code) - by this data we searched history file in comment
	 * (rewritePreviousHistoryComment())
	 * @param array $data - variable data
	 * @return string
	 */
	public static function genData(array $file, array $data = array())
	{
		ksort($file);
		return serialize(array($file, $data));
	}

	public static function getStringForLike(array $file, array $data = array())
	{
		ksort($file);
		return 'a:2:{i:0;' . serialize($file) . '%';
	}

	public static function OnAfterFetch($arUserField, $value)
	{
		if(!empty($value['VALUE']))
		{
			return static::getDataFromValue($value['VALUE']);
		}

		return array();
	}

}