<?
IncludeModuleLangFile(__FILE__);

class CUserCMLImport
{
	var $next_step = false;
	var $files_dir = false;

	var $bUpdateOnly = false;

	var $arParams = array();

	var $DEPARTMENTS_IBLOCK_ID = 0;
	var $ABSENCE_IBLOCK_ID = 0;

	var $arSectionCache = array();
	var $arPropertiesCache = array();

	var $__user = null;
	var $__ib = null;
	var $__ibs = null;
	var $__ibxml = null;
	var $__event = null;

	var $arUserGroups = false;
	var $arAbsenceTypes = false;

	function Init(&$next_step, $files_dir, $arParams = array())
	{
		$this->next_step = &$next_step;
		$this->files_dir = $files_dir;

		$this->arParams = $arParams;

		//if (is_array($this->next_step['_TEMPORARY']['DEPARTMENTS']))
		$this->arSectionCache = &$this->next_step['_TEMPORARY']['DEPARTMENTS'];

		$this->DEPARTMENTS_IBLOCK_ID = $this->arParams['DEPARTMENTS_IBLOCK_ID'];
		$this->ABSENCE_IBLOCK_ID = $this->arParams['ABSENCE_IBLOCK_ID'];
		$this->STATE_HISTORY_IBLOCK_ID = $this->arParams['STATE_HISTORY_IBLOCK_ID'];

		$dbRes = CIBlock::GetList(
			array(),
			array(
				'TYPE' => $arParams['IBLOCK_TYPE'] ? $arParams['IBLOCK_TYPE'] : 'STRUCTURE',
				'ID' => array($this->DEPARTMENTS_IBLOCK_ID, $this->ABSENCE_IBLOCK_ID, $this->STATE_HISTORY_IBLOCK_ID)
			)
		);

		$bError = false;
		if (intval($dbRes->SelectedRowsCount()) < 3)
		{
			if (ToUpper($GLOBALS['DBType']) != 'MYSQL')
			{
				$i = 0;
				while ($arRes = $dbRes->Fetch()) $i++;
				$bError = $i < 3;
			}
			else
				$bError = true;
		}

		if ($bError)
		{
			$GLOBALS['APPLICATION']->ThrowException(GetMessage('IBLOCK_XML2_USER_ERROR_IBLOCK_MISSING'));
			return false;
		}

		$def_group = COption::GetOptionString("main", "new_user_registration_def_group", "");
		if($def_group != "")
			$this->arUserGroups = explode(",", $def_group);

		return true;
	}

	function CheckUserFields()
	{
		$dbRes = CUserTypeEntity::GetList(
			array(),
			array(
				'ENTITY_ID' => 'USER'
			)
		);

		$arUserTypeList = array('UF_DEPARTMENT' => 0, 'UF_1C' => 0, 'UF_INN' => 0, 'UF_PHONE_INNER' => 0, 'UF_DISTRICT' => 0, 'UF_STATE_FIRST' => 0, 'UF_STATE_LAST' => 0);

		while ($arRes = $dbRes->Fetch())
		{
			unset($arUserTypeList[$arRes['FIELD_NAME']]);
		}

		if (count($arUserTypeList) > 0)
		{
			$ob = new CUserTypeEntity();

			$arUserFields = array();
			require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/install/1c_intranet/user.php');

			foreach ($arUserTypeList as $key => $zero)
			{
				if ($arUserFields[$key])
				{
					if ($key == 'UF_DEPARTMENT')
						$arUserFields[$key]['SETTINGS']['IBLOCK_ID'] = $this->DEPARTMENTS_IBLOCK_ID;

					$FIELD_ID = $ob->Add($arUserFields[$key]);

					if ($FIELD_ID <= 0)
					{
						return false;
					}
				}
			}
		}

		return true;
	}

	function CheckIBlockFields()
	{
		/*
		$arIBlocks = array(
			'ABSENCE' => array('USER' => 0, 'STATE' => 0, 'FINISH_STATE' => 0),
			'STATE_HISTORY' => array('USER' => 0, 'DEPARTMENT' => 0, 'POST' => 0));
		*/

		$arIBlockFields = array();

		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/install/1c_intranet/iblock.php');

		foreach ($arIBlockFields as $IBLOCK => $arIBlockFieldsList)
		{
			$param_name = $IBLOCK.'_IBLOCK_ID';

			$dbRes = CIBlockProperty::GetList(
				array(),
				array(
					'IBLOCK_ID' => $this->$param_name
				)
			);

			while ($arRes = $dbRes->Fetch())
			{
				unset($arIBlockFieldsList[$arRes['CODE']]);
			}

			if (count($arIBlockFieldsList) > 0)
			{
				$ob = new CIBlockProperty();
				foreach ($arIBlockFieldsList as $key => $arField)
				{
					$arField['IBLOCK_ID'] = $this->$param_name;

					if ($key == 'DEPARTMENT')
						$arField['LINK_IBLOCK_ID'] = $this->DEPARTMENTS_IBLOCK_ID;

					if (!$FIELD_ID = $ob->Add($arField))
					{
						$GLOBALS['APPLICATION']->ThrowException($ob->LAST_ERROR);
						return false;
					}
				}
			}
		}
		return true;
	}

	function CheckStructure()
	{
		return $this->CheckIBlockFields() && $this->CheckUserFields();
	}

	function MakeFileArray($file)
	{
		if((strlen($file)>0) && is_file($this->files_dir.$file))
			return CFile::MakeFileArray($this->files_dir.$file);
		else
			return array("tmp_name"=>"", "del"=>"Y");
	}

	function GetSectionByXML_ID($IBLOCK_ID, $XML_ID)
	{
		if (!is_array($this->arSectionCache))
			$this->arSectionCache = array();

		if(!array_key_exists($IBLOCK_ID, $this->arSectionCache))
			$this->arSectionCache[$IBLOCK_ID] = array();
		if(!array_key_exists($XML_ID, $this->arSectionCache[$IBLOCK_ID]))
		{
			if (null == $this->__ibs)
				$this->__ibs = new CIBlockSection;

			$rsSection = $this->__ibs->GetList(array(), array("IBLOCK_ID"=>$IBLOCK_ID, "EXTERNAL_ID"=>$XML_ID), false);

			if($arSection = $rsSection->Fetch())
			{
				$this->arSectionCache[$IBLOCK_ID][$XML_ID] = $arSection["ID"];
			}
			else
				$this->arSectionCache[$IBLOCK_ID][$XML_ID] = false;
		}

		return $this->arSectionCache[$IBLOCK_ID][$XML_ID];
	}

	function CalcPropertyFieldName($XML_ID)
	{
		if (!$XML_ID)
			return false;

		return 'UF_1C_PR'.strtoupper(substr(md5($XML_ID), 0, 12));
	}

	function GetPropertyByXML_ID($XML_ID, $arData = null)
	{
		if (!$this->arPropertiesCache[$XML_ID])
		{
			$dbRes = CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'USER', 'XML_ID' => $XML_ID));
			while ($arRes = $dbRes->Fetch())
			{
				$this->arPropertiesCache[$arRes['XML_ID']] = $arRes['FIELD_NAME'];
			}
		}

		if (null != $arData)
		{
			if (!$this->arPropertiesCache[$XML_ID])
			{
				$bAdd = true;
				$arFields = array(
					'ENTITY_ID' => 'USER',
					'FIELD_NAME' => $this->CalcPropertyFieldName($XML_ID),
					'USER_TYPE_ID' => 'string',
					'XML_ID' => $XML_ID,
					'MULTIPLE' => 'N',
					'MANDATORY' => 'N',
					'SHOW_FILTER' => 'I',
					'SHOW_IN_LIST' => 'Y',
					'EDIT_IN_LIST' => 'Y',
					'IS_SEARCHABLE' => 'Y',
					'SETTINGS' => array('ROWS' => 1),
				);
			}
			else
			{
				$bAdd = false;
				$arFields = array();
			}

			$arFields['EDIT_FORM_LABEL'] = $arFields['LIST_COLUMN_LABEL'] = $arFields['LIST_FILTER_LABEL'] = array('ru' => $arData['NAME']);

			$ob = new CUserTypeEntity();

			if ($bAdd)
			{
				$this->arPropertiesCache[$XML_ID] = $ob->Add($arFields);
			}
			else
			{
				$ob->Update($this->arPropertiesCache[$XML_ID], $arFields);
			}
		}

		return $this->arPropertiesCache[$XML_ID];
	}

	function LoadDepartments($arDepts = null, $PARENT_ID = null)
	{
		if (null == $arDepts)
			$arDepts = $this->arDepartments;

		$obSection = new CIBlockSection();

		foreach ($arDepts as $arDeptData)
		{
			//print_r($arDeptData);

			$XML_ID = $arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_ID')];
			if ($SECTION_ID = $this->GetSectionByXML_ID($this->DEPARTMENTS_IBLOCK_ID, $XML_ID))
			{
				$dbRes = $obSection->GetByID($SECTION_ID);
				$arCurrentSection = $dbRes->Fetch();
			}

			$arFields = array(
				'ACTIVE' => ($arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_STATUS')]===GetMessage('IBLOCK_XML2_USER_VALUE_DELETED')? 'N': 'Y'),
				'IBLOCK_ID' => $this->DEPARTMENTS_IBLOCK_ID,
				'IBLOCK_SECTION_ID' => intval($PARENT_ID),
				'EXTERNAL_ID' => $XML_ID,
				'NAME' => $arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_NAME')],
				//'SORT' => 100,
			);

			$bStoreHead = false;
			if (isset($arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENT_HEAD')]))
			{
				if ($arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENT_HEAD')])
				{
					if ($arUser = $this->GetUserByXML_ID($arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENT_HEAD')]))
					{
						$arFields['UF_HEAD'] = $arUser['ID'];
					}
					else
					{
						$bStoreHead = true;
					}
				}
				else
				{
					$arFields['UF_HEAD'] = '';
				}

			}

			//print_r($arFields);

			if (!$SECTION_ID)
			{
				$arFields['SORT'] = 100;
				$SECTION_ID = $obSection->Add($arFields);
				$res = ($SECTION_ID > 0);
				$this->arSectionCache[$this->DEPARTMENTS_IBLOCK_ID][$XML_ID] = $SECTION_ID;
			}
			else
			{
				$res = $obSection->Update($SECTION_ID, $arFields);
			}

			if (!$res)
			{
				$GLOBALS['APPLICATION']->ThrowException($obSection->LAST_ERROR);
				return false;
			}

			if ($bStoreHead)
			{
				if (!$this->next_step['_TEMPORARY']['DEPARTMENT_HEADS'])
					$this->next_step['_TEMPORARY']['DEPARTMENT_HEADS'] = array();
				if (!$this->next_step['_TEMPORARY']['DEPARTMENT_HEADS'][$arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENT_HEAD')]])
					$this->next_step['_TEMPORARY']['DEPARTMENT_HEADS'][$arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENT_HEAD')]] = array();

				$this->next_step['_TEMPORARY']['DEPARTMENT_HEADS'][$arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENT_HEAD')]][] = $SECTION_ID;
			}

			if (is_array($arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENTS')]))
			{
				if (!$this->LoadDepartments($arDeptData[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENTS')], $SECTION_ID))
					return false;
			}
		}

		// if (!$PARENT_ID)
			// $obSection->ReSort();

		return true;
	}

	function GetStructureRoot()
	{
		$dbs = CIBlockSection::GetList(Array(), Array("IBLOCK_ID"=>$this->DEPARTMENTS_IBLOCK_ID, "SECTION_ID"=>0));
		if ($arRoot = $dbs->Fetch())
		{
			return $arRoot['ID'];
		}

		$company_name = COption::GetOptionString("main", "site_name", "");
		if($company_name == '')
		{
			$dbrs = CSite::GetList($o, $b, Array("DEFAULT"=>"Y"));
			if($ars = $dbrs->Fetch())
				$company_name = $ars["NAME"];
		}

		$arFields = Array(
			"NAME" => $company_name,
			"IBLOCK_ID"=>$this->DEPARTMENTS_IBLOCK_ID
		);

		$ss = new CIBlockSection();
		return $ss->Add($arFields);
	}

	function ImportMetaData($xml_root_id = false)
	{
		global $DB;

		if (!$this->arParams['SKIP_STRUCTURE_CHECK'] && !$this->CheckStructure())
			return false;

		if (null == $this->__ibxml)
			$this->__ibxml = new CIBlockXMLFile();

		$XML_DEPARTMENTS_PARENT = false;
		$XML_PROPERTIES_PARENT = false;

		if ($xml_root_id <= 0)
		{
			$rs = $DB->Query("SELECT MIN(PARENT_ID) MIN_ID FROM b_xml_tree WHERE NAME='".GetMessage('IBLOCK_XML2_USER_TAG_CLASSIFIER')."'");
			$ar = $rs->Fetch();
			$xml_root_id = $ar["MIN_ID"];
		}

		$rs = $DB->Query("SELECT ID, ATTRIBUTES FROM b_xml_tree WHERE PARENT_ID = ".intval($xml_root_id)." AND NAME='".GetMessage('IBLOCK_XML2_USER_TAG_CLASSIFIER')."'");
		if($ar = $rs->Fetch())
		{
			if(strlen($ar["ATTRIBUTES"]) > 0)
			{
				$attrs = unserialize($ar["ATTRIBUTES"]);
				if(is_array($attrs))
				{
					if(array_key_exists(GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY'), $attrs))
						$this->bUpdateOnly = ($attrs[GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY')] == 'true') || (intval($attrs[GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY')]) ? true: false);
				}
			}

			$rs = $DB->Query("select * from b_xml_tree where PARENT_ID = ".$ar["ID"]." order by ID");
			while($ar = $rs->Fetch())
			{
				switch ($ar['NAME'])
				{
					case GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENTS'):
						$XML_DEPARTMENTS_PARENT = $ar['ID'];
					break;
					case GetMessage('IBLOCK_XML2_USER_TAG_PROPERTIES'):
						$XML_PROPERTIES_PARENT = $ar['ID'];
					break;
					default: break;
				}
			}

			if ($XML_DEPARTMENTS_PARENT)
			{
				$this->arDepartments = $this->__ibxml->GetAllChildrenArray($XML_DEPARTMENTS_PARENT);
				if (!$this->LoadDepartments($this->arDepartments, $this->GetStructureRoot())) /////////////////////////////////////////////// put here root department
					return false;

				$this->next_step['_TEMPORARY']['DEPARTMENTS'] = $this->arSectionCache;
			}

			if ($XML_PROPERTIES_PARENT)
			{
				$this->arProperties = $this->__ibxml->GetAllChildrenArray($XML_PROPERTIES_PARENT);

				foreach($this->arProperties as $arPropertyData)
				{
					$this->GetPropertyByXML_ID($arPropertyData[GetMessage('IBLOCK_XML2_USER_TAG_ID')], array('XML_ID' => $arPropertyData[GetMessage('IBLOCK_XML2_USER_TAG_ID')], 'NAME' => $arPropertyData[GetMessage('IBLOCK_XML2_USER_TAG_NAME')]));
				}
				//$this->next_step['_TEMPORARY']['PROPERTIES'] = $this->arProperties;
			}
		}

		return true;
	}

	function ImportUsers($xml_root_id = false, $start_time = false, $interval = 0)
	{
		global $DB;

		$this->__user = new CUser();

		if (null == $this->__ibxml)
			$this->__ibxml = new CIBlockXMLFile();

		if ($start_time === false)
			$start_time = time();

		$counter = array(
			"ADD" => 0,
			"UPD" => 0,
			"DEL" => 0,
			"DEA" => 0,
			"ERR" => 0,
		);

		if(!$this->next_step["XML_ELEMENTS_PARENT"])
		{
			if ($xml_root_id <= 0)
			{
				$rs = $DB->Query("SELECT MIN(PARENT_ID) MIN_ID FROM b_xml_tree WHERE NAME='".GetMessage('IBLOCK_XML2_USER_TAG_STRUCTURE')."'");
				$ar = $rs->Fetch();
				$xml_root_id = $ar["MIN_ID"];
			}

			$query = "SELECT ID, ATTRIBUTES FROM b_xml_tree WHERE PARENT_ID = ".intval($xml_root_id)." AND NAME='".GetMessage('IBLOCK_XML2_USER_TAG_STRUCTURE')."'";
			$rs = $DB->Query($query);
			if($ar = $rs->Fetch())
			{
				if(strlen($ar["ATTRIBUTES"]) > 0)
				{
					$attrs = unserialize($ar["ATTRIBUTES"]);
					if(is_array($attrs))
					{
						if(array_key_exists(GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY'), $attrs))
						{
							$this->bUpdateOnly =
								($attrs[GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY')] == 'true') ||
								(intval($attrs[GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY')]) ? true : false);
							$this->next_step['bUpdateOnly'] = $this->bUpdateOnly;
						}
					}
				}

				$rs = $DB->Query("SELECT ID, ATTRIBUTES FROM b_xml_tree WHERE PARENT_ID = ".intval($ar['ID'])." AND NAME='".GetMessage('IBLOCK_XML2_USER_TAG_USERS')."'");
				if ($ar = $rs->Fetch())
				{
					$this->next_step["XML_ELEMENTS_PARENT"] = $ar['ID'];
				}
			}
		}

		if($this->next_step["XML_ELEMENTS_PARENT"])
		{
			$rsParents = $DB->Query("SELECT ID, LEFT_MARGIN, RIGHT_MARGIN FROM b_xml_tree WHERE PARENT_ID = ".intval($this->next_step["XML_ELEMENTS_PARENT"])." AND ID > ".intval($this->next_step["XML_LAST_ID"])." ORDER BY ID");

			$q = 0;
			while($arParent = $rsParents->Fetch())
			{
				$arXMLElement = $this->__ibxml->GetAllChildrenArray($arParent);

				$ID = $this->LoadUser($arXMLElement, $counter);

				$this->next_step["XML_LAST_ID"] = $arParent["ID"];

				if($interval > 0 && (time()-$start_time) > $interval)
					break;
			}
		}

		unset($this->__user);

		//$this->CleanTempFiles();
		return $counter;
	}

	function GetUserByXML_ID($XML_ID)
	{
		$dbRes = CUser::GetList($by="ID", $order="ASC", array("XML_ID" => $XML_ID));
		return $dbRes->Fetch();
	}

	function LoadUser($arXMLElement, &$counter)
	{
		$start_time = microtime(true);
		static $USER_COUNTER = null;

		static $property_state_final = 0;

		if (!is_array($property_state_final))
		{
			$property_state_final = array();
			$property_state = CIBlockPropertyEnum::GetList(
				Array(),
				Array(
					"IBLOCK_ID" => $this->STATE_HISTORY_IBLOCK_ID,
					"CODE"=>"STATE"
				)
			);
			while($property_state_enum = $property_state->GetNext())
			{
				$property_state_final[ToLower($property_state_enum["VALUE"])] = $property_state_enum["ID"];
			}
		}

		$obUser = &$this->__user;

		// this counter'll be used for generating users login name
		if (null == $USER_COUNTER)
		{
			$dbRes = $GLOBALS['DB']->Query('SELECT MAX(ID) M FROM b_user');
			$ar = $dbRes->Fetch();
			$USER_COUNTER = $ar['M'];
		}

		$CURRENT_USER = false;

		// check user existence
		if ($arCurrentUser = $this->GetUserByXML_ID($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_ID')]))
			$CURRENT_USER = $arCurrentUser['ID'];

		// common user data
		$arFields = array(
			'ACTIVE' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_STATUS')] == GetMessage('IBLOCK_XML2_USER_VALUE_DELETED') ? 'N' : 'Y',
			'UF_1C' => 'Y',
			'XML_ID' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_ID')],
			'LID' => $this->arParams['SITE_ID'],
			'LAST_NAME' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_LAST_NAME')],
			'NAME' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_FIRST_NAME')],
			'SECOND_NAME' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_SECOND_NAME')],
			'PERSONAL_BIRTHDAY' => !empty($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_BIRTH_DATE')]) ? ConvertTimeStamp(MakeTimeStamp($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_BIRTH_DATE')], 'YYYY-MM-DD')) : '',
			'PERSONAL_GENDER' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_GENDER')] == GetMessage('IBLOCK_XML2_USER_VALUE_FEMALE') ? 'F' : 'M',
			'UF_INN' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_INN')],
			'WORK_POSITION' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_POST')],
			'PERSONAL_PROFESSION' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_POST')],
		);

		if (array_key_exists(GetMessage('IBLOCK_XML2_USER_TAG_PHOTO'), $arXMLElement))
		{
			if ($arCurrentUser['PERSONAL_PHOTO'] > 0)
			{
				CFile::Delete($arCurrentUser['PERSONAL_PHOTO']);
			}

			if (strlen($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_PHOTO')]) > 0)
			{
				$arFields['PERSONAL_PHOTO'] = $this->MakeFileArray($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_PHOTO')]);
			}
		}

		// address fields
		if (is_array($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_ADDRESS')]))
		{
			foreach ($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_ADDRESS')] as $key => $arAddressField)
			{
				if (GetMessage('IBLOCK_XML2_USER_TAG_FULLADDRESS') == $key)
					$arFields['PERSONAL_STREET'] = $arAddressField;
				else
				{
					$type = $arAddressField[GetMessage('IBLOCK_XML2_USER_TAG_TYPE')];
					$value = $arAddressField[GetMessage('IBLOCK_XML2_USER_TAG_VALUE')];
					switch($type)
					{
						case GetMessage('IBLOCK_XML2_USER_VALUE_ZIP'):
							$arFields['PERSONAL_ZIP'] = $value;
						break;
						case GetMessage('IBLOCK_XML2_USER_VALUE_STATE'):
							$arFields['PERSONAL_STATE'] = $value;
						break;
						case GetMessage('IBLOCK_XML2_USER_VALUE_DISTRICT'):
							$arFields['UF_DISTRICT'] = $value;
						break;
						case GetMessage('IBLOCK_XML2_USER_VALUE_CITY1'):
						case GetMessage('IBLOCK_XML2_USER_VALUE_CITY2'):
							if ($arFields['PERSONAL_CITY'])
								$arFields['PERSONAL_CITY'] .= ', ';
							$arFields['PERSONAL_CITY'] .= $value;
						break;
						default: break;
					}
				}
			}
		}

		// contact fields
		if (is_array($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_CONTACTS')]))
		{
			foreach ($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_CONTACTS')] as $arContactsField)
			{
				$type = $arContactsField[GetMessage('IBLOCK_XML2_USER_TAG_TYPE')];
				$value = $arContactsField[GetMessage('IBLOCK_XML2_USER_TAG_VALUE')];
				switch ($type)
				{
					case GetMessage('IBLOCK_XML2_USER_VALUE_PHONE_INNER'):
						$arFields['UF_PHONE_INNER'] = $value;
					break;
					case GetMessage('IBLOCK_XML2_USER_VALUE_PHONE_WORK'):
						$arFields['WORK_PHONE'] = $value;
					break;
					case GetMessage('IBLOCK_XML2_USER_VALUE_PHONE_MOBILE'):
						$arFields['PERSONAL_MOBILE'] = $value;
					break;
					case GetMessage('IBLOCK_XML2_USER_VALUE_PHONE_PERSONAL'):
						$arFields['PERSONAL_PHONE'] = $value;
					break;
					case GetMessage('IBLOCK_XML2_USER_VALUE_PAGER'):
						$arFields['PERSONAL_PAGER'] = $value;
					break;
					case GetMessage('IBLOCK_XML2_USER_VALUE_FAX'):
						$arFields['PERSONAL_FAX'] = $value;
					break;
					case GetMessage('IBLOCK_XML2_USER_VALUE_EMAIL'):
						$arFields['EMAIL'] = $value; // b_user.EMAIL
					break;
					case GetMessage('IBLOCK_XML2_USER_VALUE_ICQ'):
						$arFields['PERSONAL_ICQ'] = $value;
					break;
					case GetMessage('IBLOCK_XML2_USER_VALUE_WWW'):
						$arFields['PERSONAL_WWW'] = $value;
					break;
					default: break;
				}
			}
		}

		//departments data
		$arFields['UF_DEPARTMENT'] = array();
		if (is_array($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENTS')]))
		{
			foreach($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENTS')] as $DEPT_XML_ID)
			{
				if ($DEPT_ID = $this->GetSectionByXML_ID($this->DEPARTMENTS_IBLOCK_ID, $DEPT_XML_ID))
				{
					$arFields['UF_DEPARTMENT'][] = $DEPT_ID;
				}
			}
		}

		// state history
		if (is_array($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_STATE_HISTORY')]))
		{
			$last_state_date = 0;
			$first_state_date = 1767132000; //strtotime('2025-12-31')
			$arStateHistory = array();

			foreach($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_STATE_HISTORY')] as $arState)
			{
				$state = $arState[GetMessage('IBLOCK_XML2_USER_TAG_VALUE')];

				$date = intval(MakeTimeStamp($arState[GetMessage('IBLOCK_XML2_USER_TAG_DATE')], 'YYYY-MM-DD'));
				while (is_array($arStateHistory[$date]))
					$date++;

				if (!$last_state_date || doubleval($last_state_date) < doubleval($date))
					$last_state_date = $date;
				if (doubleval($first_state_date) > doubleval($date))
					$first_state_date = $date;

				$DEPARTMENT_ID = $this->GetSectionByXML_ID($this->DEPARTMENTS_IBLOCK_ID, $arState[GetMessage('IBLOCK_XML2_USER_TAG_DEPARTMENT')]);

				$arStateHistory[$date] = array(
					'STATE' => $state,
					'POST' => $arState[GetMessage('IBLOCK_XML2_USER_TAG_POST')],
					'DEPARTMENT' => $DEPARTMENT_ID,
				);
			}

			ksort($arStateHistory);

			// if person's last state is "Fired" - deactivate him.
			if (GetMessage('IBLOCK_XML2_USER_VALUE_FIRED') == $arStateHistory[$last_state_date]['STATE'])
				$arFields['ACTIVE'] = 'N';
			// save data serialized
			//$arFields['UF_1C_STATE_HISTORY'] = serialize($arStateHistory);
		}
		else
		{
			$arStateHistory = array();
			$last_state_date = null;
			$first_state_date = null;
		}

		// properties data
		if (is_array($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_PROPERTY_VALUES')]))
		{
			foreach ($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_PROPERTY_VALUES')] as $arPropertyData)
			{
				$PROP_XML_ID = $arPropertyData[GetMessage('IBLOCK_XML2_USER_TAG_ID')];
				$PROP_VALUE = $arPropertyData[GetMessage('IBLOCK_XML2_USER_TAG_VALUE')];
				$arFields[$this->CalcPropertyFieldName($PROP_XML_ID)] = $PROP_VALUE;
			}
		}

		if (!$arFields['EMAIL'] && $this->arParams['EMAIL_PROPERTY_XML_ID'])
		{
			$arFields['EMAIL'] = $arFields[$this->CalcPropertyFieldName($this->arParams['EMAIL_PROPERTY_XML_ID'])];
		}

		$bEmailExists = true;
		if (!$arFields['EMAIL'] && $this->arParams['DEFAULT_EMAIL'])
		{
			$bEmailExists = false;
			$arFields['EMAIL'] = $this->arParams['DEFAULT_EMAIL'];
		}

		if (!$arFields['EMAIL'])
		{
			$bEmailExists = false;
			$arFields['EMAIL'] = COption::GetOptionString('main', 'email_from', "admin@".$_SERVER['SERVER_NAME']);
		}

		// EMAIL, LOGIN and PASSWORD fields
		if (!$CURRENT_USER)
		{
			// for a new user
			$USER_COUNTER++;

			$arFields['LOGIN'] = '';
			if ($this->arParams['LDAP_ID_PROPERTY_XML_ID'] && $this->arParams['LDAP_SERVER'])
			{
				if ($arFields['LOGIN'] = $arFields[$this->CalcPropertyFieldName($this->arParams['LDAP_ID_PROPERTY_XML_ID'])])
				{
					$arFields['EXTERNAL_AUTH_ID'] = 'LDAP#'.$this->arParams['LDAP_SERVER'];
				}
			}

			if (!$arFields['LOGIN'] && $this->arParams['LOGIN_PROPERTY_XML_ID'])
				$arFields['LOGIN'] = $arFields[$this->CalcPropertyFieldName($this->arParams['LOGIN_PROPERTY_XML_ID'])];
			if (!$arFields['LOGIN'] && $this->arParams['LOGIN_TEMPLATE'])
				$arFields['LOGIN'] = str_replace('#', $USER_COUNTER, $this->arParams['LOGIN_TEMPLATE']);
			if (!$arFields['LOGIN']) $arFields['LOGIN'] = 'user_' . $USER_COUNTER;

			if (!$arFields['EXTERNAL_AUTH_ID'])
			{
				if ($this->arParams['PASSWORD_PROPERTY_XML_ID'])
					$arFields['PASSWORD'] = $arFields['CONFIRM_PASSWORD'] =
						$arFields[$this->CalcPropertyFieldName($this->arParams['PASSWORD_PROPERTY_XML_ID'])];

				if (!$arFields['PASSWORD'])
					$arFields['PASSWORD'] = $arFields['CONFIRM_PASSWORD'] =
						RandString($this->arParams['PASSWORD_LENGTH'] ? $this->arParams['PASSWORD_LENGTH'] : 7);
			}

			if (!$bEmailExists && $arFields['EMAIL'] && $this->arParams['UNIQUE_EMAIL'] != 'N')
				$arFields['EMAIL'] = preg_replace('/@/', '_'.$USER_COUNTER.'@', $arFields['EMAIL'], 1);

			// set user groups list to default from main module setting
			if (is_array($this->arUserGroups))
				$arFields['GROUP_ID'] = $this->arUserGroups;
		}
		else
		{
			// for an existing user
			if ($this->arParams['UPDATE_LOGIN'])
			{
				$arFields['LOGIN'] = $arFields[$this->CalcPropertyFieldName($this->arParams['LOGIN_PROPERTY_XML_ID'])];
				if (strlen($arFields['LOGIN']) <= 0) unset($arFields['LOGIN']);
			}

			if ($this->arParams['UPDATE_PASSWORD'])
			{
				$arFields['PASSWORD'] = $arFields['CONFIRM_PASSWORD'] = $arFields[$this->CalcPropertyFieldName($this->arParams['PASSWORD_PROPERTY_XML_ID'])];
				if (strlen($arFields['PASSWORD']) <= 0) { unset($arFields['PASSWORD']); unset($arFields['CONFIRM_PASSWORD']); }
			}

			if (!$this->arParams['UPDATE_EMAIL'] || strlen($arFields['EMAIL']) <= 0) unset($arFields['EMAIL']);
		}

		$bNew = $CURRENT_USER <= 0;

		if (!$bNew)
		{
			foreach ($arFields as $key => $value)
			{
				if ($key !== 'ACTIVE' && !in_array($key, $this->arParams['UPDATE_PROPERTIES']))
					unset($arFields[$key]);
			}

			// update existing user
			if ($res = $obUser->Update($CURRENT_USER, $arFields))
				$counter[$arFields['ACTIVE'] == 'Y' ? 'UPD' : 'DEA']++;
		}
		else
		{
			$group_id = $arFields['GROUP_ID'];
			unset($arFields['GROUP_ID']);
			// create new user
			if ($CURRENT_USER = $obUser->Add($arFields))
			{
				$counter['ADD']++;

				CUser::SetUserGroup($CURRENT_USER, $group_id);

				if (isset($this->next_step['_TEMPORARY']['DEPARTMENT_HEADS'][$arFields['XML_ID']]))
				{
					$obSection = new CIBlockSection();
					foreach ($this->next_step['_TEMPORARY']['DEPARTMENT_HEADS'][$arFields['XML_ID']] as $dpt)
					{
						$obSection->Update($dpt, array('UF_HEAD' => $CURRENT_USER), false, false);
					}
				}

				if ($this->arParams['EMAIL_NOTIFY'] == 'Y' || ($this->arParams['EMAIL_NOTIFY'] == 'E') && $bEmailExists)
				{
					$arFields['ID'] = $CURRENT_USER;

					//$this->__event->Send("USER_INFO", SITE_ID, $arFields);
					//echo CEvent::Send("USER_INFO", 's1', $arFields);

					$this->__user->SendUserInfo(
						$CURRENT_USER,
						$this->arParams['SITE_ID'],
						'',
						$this->arParams['EMAIL_NOTIFY_IMMEDIATELY'] == 'Y'
					);
				}
			}

			if (!$res = ($CURRENT_USER > 0))
			{
				$USER_COUNTER--;
			}
		}

		if (!$res)
		{
			$counter['ERR']++;
			$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/cml2-import-user.log', 'a');
			fwrite($fp, "==============================================================\r\n");
			fwrite($fp, $obUser->LAST_ERROR."\r\n");
			fwrite($fp, print_r($arFields, true));
			fwrite($fp, "==============================================================\r\n");
			fclose($fp);
		}
		elseif (is_array($arStateHistory) && count($arStateHistory) > 0)
		{
			if (null == $this->__ib)
				$this->__ib = new CIBlockElement();

			if (!$bNew)
			{
				$dbRes = $this->__ib->GetList(
					array(),
					array(
						'PROPERTY_USER' => $CURRENT_USER,
						'IBLOCK_ID' => $this->STATE_HISTORY_IBLOCK_ID
					),
					false,
					false,
					array('ID', 'IBLOCK_ID')
				);
				while ($arRes = $dbRes->Fetch())
				{
					$this->__ib->Delete($arRes['ID']);
				}
			}

			foreach ($arStateHistory as $date => $arState)
			{
				$arStateFields = array(
					'IBLOCK_SECTION' => false,
					'IBLOCK_ID' => $this->STATE_HISTORY_IBLOCK_ID,
					'DATE_ACTIVE_FROM' => ConvertTimeStamp($date, 'SHORT'),
					'ACTIVE' => 'Y',
					'NAME' => $arState['STATE'].' - '.$arFields['LAST_NAME'].' '.$arFields['NAME'],
					'PREVIEW_TEXT' => $arState['STATE'],
					'PROPERTY_VALUES' => array(
						'POST' => $arState['POST'],
						'USER' => $CURRENT_USER,
						'DEPARTMENT' => $arState['DEPARTMENT'],
						'STATE' => array("VALUE" => $property_state_final[ToLower($arState['STATE'])])
					),
				);

				if (!$this->__ib->Add($arStateFields, false, false))
				{
					$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/cml2-import-state.log', 'a');
					fwrite($fp, "==============================================================\r\n");
					fwrite($fp, $this->__ib->LAST_ERROR."\r\n");
					fwrite($fp, print_r($arStateFields, true));
					fwrite($fp, "==============================================================\r\n");
					fclose($fp);
				}
			}
		}

		return $CURRENT_USER;
	}

	function ImportAbsence($xml_root_id = false, $start_time = false, $interval = 0)
	{
		global $DB;

		if (null == $this->__ib)
			$this->__ib = new CIBlockElement();

		if (null == $this->__ibxml)
			$this->__ibxml = new CIBlockXMLFile();

		if ($start_time === false)
			$start_time = time();

		$counter = array(
			"ADD" => 0,
			"UPD" => 0,
			"DEL" => 0,
			"DEA" => 0,
			"ERR" => 0,
		);

		if(!$this->next_step["XML_ABSENCE_PARENT"])
		{
			if ($xml_root_id <= 0)
			{
				$rs = $DB->Query("SELECT MIN(PARENT_ID) MIN_ID FROM b_xml_tree WHERE NAME='".GetMessage('IBLOCK_XML2_USER_TAG_ABSENCE')."'");
				$ar = $rs->Fetch();
				$xml_root_id = $ar["MIN_ID"];
			}

			$rs = $DB->Query("SELECT ID, ATTRIBUTES FROM b_xml_tree WHERE PARENT_ID = ".intval($xml_root_id)." AND NAME='".GetMessage('IBLOCK_XML2_USER_TAG_ABSENCE')."'");
			if($ar = $rs->Fetch())
			{
				if(strlen($ar["ATTRIBUTES"]) > 0)
				{
					$attrs = unserialize($ar["ATTRIBUTES"]);
					if(is_array($attrs))
					{
						if(array_key_exists(GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY'), $attrs))
						{
							$this->bUpdateOnly =
								($attrs[GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY')] == 'true') ||
								(intval($attrs[GetMessage('IBLOCK_XML2_USER_ATTR_UPDATE_ONLY')]) ? true : false);
							$this->next_step['bUpdateOnly'] = $this->bUpdateOnly;
						}
					}
				}

				$rs = $DB->Query("SELECT ID, ATTRIBUTES FROM b_xml_tree WHERE PARENT_ID = ".intval($ar['ID'])." AND NAME='".GetMessage('IBLOCK_XML2_USER_TAG_ABSENCE_ELEMENTS')."'");
				if ($ar = $rs->Fetch())
				{
					$this->next_step["XML_ABSENCE_PARENT"] = $ar['ID'];
				}
			}
		}

		if($this->next_step["XML_ABSENCE_PARENT"])
		{
			$rsParents = $DB->Query("SELECT ID, LEFT_MARGIN, RIGHT_MARGIN FROM b_xml_tree WHERE PARENT_ID = ".intval($this->next_step["XML_ABSENCE_PARENT"])." AND ID > ".intval($this->next_step["XML_LAST_ID"])." ORDER BY ID");

			while($arParent = $rsParents->Fetch())
			{
				$arXMLElement = $this->__ibxml->GetAllChildrenArray($arParent);

				$ID = $this->LoadAbsence($arXMLElement, $counter);

				$this->next_step["XML_LAST_ID"] = $arParent["ID"];

				if($interval > 0 && (time()-$start_time) > $interval)
					break;
			}
		}

		unset($this->__ib);

		//$this->CleanTempFiles();
		return $counter;
	}

	function GetAbsenceByXML_ID($XML_ID)
	{
		$dbRes = $this->__ib->GetList(array('SORT' => 'ASC'), array('IBLOCK_ID' => $this->ABSENCE_IBLOCK_ID, 'XML_ID' => $XML_ID));
		return $dbRes->Fetch();
	}

	function __GetAbsenceType($TYPE)
	{
		if (!is_array($this->arAbsenceTypes))
		{
			$this->arAbsenceTypes = array();
			$dbTypeRes = CIBlockPropertyEnum::GetList(array("SORT"=>"ASC", "VALUE"=>"ASC"), array('IBLOCK_ID' => $this->arParams['ABSENCE_IBLOCK_ID'], 'PROPERTY_ID' => 'ABSENCE_TYPE'));
			while ($arTypeValue = $dbTypeRes->GetNext())
			{
				$this->arAbsenceTypes[$arTypeValue['XML_ID']] = $arTypeValue['ID'];
			}
		}

		$TYPE = ToUpper($TYPE);

		if (false !== strpos($TYPE, GetMessage('INTR_IAC_VACATION')))
			return $this->arAbsenceTypes['VACATION'];
		elseif (false !== strpos($TYPE, GetMessage('INTR_IAC_ASSIGNMENT')))
			return $this->arAbsenceTypes['ASSIGNMENT'];
		else
			return $this->arAbsenceTypes['OTHER'];
	}

	function LoadAbsence($arXMLElement, &$counter)
	{
		$CURRENT_ENTRY = false;
		$XML_ID = $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_ID')];

		if ($arCurrentEntry = $this->GetAbsenceByXML_ID($XML_ID))
			$CURRENT_ENTRY = $arCurrentEntry['ID'];

		if (GetMessage('IBLOCK_XML2_USER_VALUE_DELETED') == $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_STATUS')])
		{
			// deleting
			if ($CURRENT_ENTRY)
			{
				$this->__ib->Delete($CURRENT_ENTRY);
			}

			$counter['DEL']++;

			return $CURRENT_ENTRY;
		}
		elseif ($arCurrentUser = $this->GetUserByXML_ID($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_USER')]))
		{
			$arFields = array(
				'XML_ID' => $XML_ID,
				'IBLOCK_SECTION' => false,
				'IBLOCK_ID' => $this->ABSENCE_IBLOCK_ID,
				'NAME' => $arCurrentUser['LAST_NAME'].' '.$arCurrentUser['NAME'].' - '.$arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_STATE')],
				'ACTIVE_FROM' => ConvertTimeStamp(MakeTimeStamp($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_DATE_FROM')], 'YYYY-MM-DD')),
				'ACTIVE_TO' => ConvertTimeStamp(MakeTimeStamp($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_DATE_TO')], 'YYYY-MM-DD')),
				'ACTIVE' => 'Y',
				'PREVIEW_TEXT' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_ABSENCE_CAUSE')],
				'PREVIEW_TEXT_TYPE' => 'text',
				'DETAIL_TEXT' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_DOCUMENT')],
				'DETAIL_TEXT_TYPE' => 'text',
				'PROPERTY_VALUES' => array(
					'USER' => $arCurrentUser['ID'],
					'STATE' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_STATE')],
					'FINISH_STATE' => $arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_FINISH_STATE')],
					'ABSENCE_TYPE' => $this->__GetAbsenceType($arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_STATE')].'|'.$arXMLElement[GetMessage('IBLOCK_XML2_USER_TAG_ABSENCE_CAUSE')]),
					'USER_ACTIVE' => $arCurrentUser['ACTIVE']
				),
			);

			if ($CURRENT_ENTRY)
			{
				if ($res = $this->__ib->Update($CURRENT_ENTRY, $arFields))
					$counter['UPD']++;
			}
			else
			{
				$CURRENT_ENTRY = $this->__ib->Add($arFields);
				if ($res = ($CURRENT_ENTRY > 0))
					$counter['ADD']++;
			}

			if (!$res)
			{
				$counter['ERR']++;
				return false;
			}

			return $CURRENT_ENTRY;
		}
		else
		{
			$counter['ERR']++;
			return false;
		}
	}
}
?>
