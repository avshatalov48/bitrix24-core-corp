<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

/**
 * usertype.php, Пользовательские свойства
 *
 * Содержит классы для поддержки пользовательских свойств.
 * @author Bitrix <support@bitrixsoft.com>
 * @version 1.0
 * @package usertype
 * @todo Добавить подсказку
 */

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Main\UserField\Types\BaseType;
use Bitrix\Main\UserField\Types\DateTimeType;

CModule::AddAutoloadClasses(
	"main",
	array(
		"CUserTypeString" => "classes/general/usertypestr.php",
		"CUserTypeInteger" => "classes/general/usertypeint.php",
		"CUserTypeDouble" => "classes/general/usertypedbl.php",
		"CUserTypeDateTime" => "classes/general/usertypetime.php",
		"CUserTypeDate" => "classes/general/usertypedate.php",
		"CUserTypeBoolean" => "classes/general/usertypebool.php",
		"CUserTypeFile" => "classes/general/usertypefile.php",
		"CUserTypeEnum" => "classes/general/usertypeenum.php",
		"CUserTypeIBlockSection" => "classes/general/usertypesection.php",
		"CUserTypeIBlockElement" => "classes/general/usertypeelement.php",
		"CUserTypeStringFormatted" => "classes/general/usertypestrfmt.php",
		"CUserTypeUrl" => "classes/general/usertypeurl.php",
	)
);

IncludeModuleLangFile(__FILE__);

/**
 * Данный класс используется для управления метаданными пользовательских свойств.
 *
 * <p>Выборки, Удаление Добавление и обновление метаданных таблицы b_user_field.</p>
 * create table b_user_field (
 * ID    int(11) not null auto_increment,
 * ENTITY_ID  varchar(50),
 * FIELD_NAME  varchar(50),
 * USER_TYPE_ID  varchar(50),
 * XML_ID    varchar(255),
 * SORT    int,
 * MULTIPLE  char(1) not null default 'N',
 * MANDATORY  char(1) not null default 'N',
 * SHOW_FILTER  char(1) not null default 'N',
 * SHOW_IN_LIST  char(1) not null default 'Y',
 * EDIT_IN_LIST  char(1) not null default 'Y',
 * IS_SEARCHABLE  char(1) not null default 'N',
 * SETTINGS  text,
 * PRIMARY KEY (ID),
 * UNIQUE ux_user_type_entity(ENTITY_ID, FIELD_NAME)
 * )
 * ------------------
 * ID
 * ENTITY_ID (example: IBLOCK_SECTION, USER ....)
 * FIELD_NAME (example: UF_EMAIL, UF_SOME_COUNTER ....)
 * SORT -- used to do check in the specified order
 * BASE_TYPE - String, Number, Integer, Enumeration, File, DateTime
 * USER_TYPE_ID
 * SETTINGS (blob) -- to store some settings which may be useful for an field instance
 * [some base settings comon to all types: mandatory or no, etc.]
 * <p>b_user_field</p>
 * <ul>
 * <li><b>ID</b> int(11) not null auto_increment
 * <li>ENTITY_ID varchar(50)
 * <li>FIELD_NAME varchar(20)
 * <li>USER_TYPE_ID varchar(50)
 * <li>XML_ID varchar(255)
 * <li>SORT int
 * <li>MULTIPLE char(1) not null default 'N'
 * <li>MANDATORY char(1) not null default 'N'
 * <li>SHOW_FILTER char(1) not null default 'N'
 * <li>SHOW_IN_LIST char(1) not null default 'Y'
 * <li>EDIT_IN_LIST char(1) not null default 'Y'
 * <li>IS_SEARCHABLE char(1) not null default 'N'
 * <li>SETTINGS text
 * <li>PRIMARY KEY (ID),
 * <li>UNIQUE ux_user_type_entity(ENTITY_ID, FIELD_NAME)
 * </ul>
 * create table b_user_field_lang (
 * USER_FIELD_ID int(11) REFERENCES b_user_field(ID),
 * LANGUAGE_ID char(2),
 * EDIT_FORM_LABEL varchar(255),
 * LIST_COLUMN_LABEL varchar(255),
 * LIST_FILTER_LABEL varchar(255),
 * ERROR_MESSAGE varchar(255),
 * HELP_MESSAGE varchar(255),
 * PRIMARY KEY (USER_FIELD_ID, LANGUAGE_ID)
 * )
 * <p>b_user_field_lang</p>
 * <ul>
 * <li><b>USER_FIELD_ID</b> int(11) REFERENCES b_user_field(ID)
 * <li><b>LANGUAGE_ID</b> char(2)
 * <li>EDIT_FORM_LABEL varchar(255)
 * <li>LIST_COLUMN_LABEL varchar(255)
 * <li>LIST_FILTER_LABEL varchar(255)
 * <li>ERROR_MESSAGE varchar(255)
 * <li>HELP_MESSAGE varchar(255)
 * <li>PRIMARY KEY (USER_FIELD_ID, LANGUAGE_ID)
 * </ul>
 * @package usertype
 * @subpackage classes
 */
class CAllUserTypeEntity extends CDBResult
{
	//must be extended
	function CreatePropertyTables($entity_id)
	{
		return true;
	}

	//must be extended
	function DropColumnSQL($strTable, $arColumns)
	{
		return array();
	}

	/**
	 * Функция для выборки метаданных пользовательского свойства.
	 *
	 * <p>Возвращает ассоциативный массив метаданных который можно передать в Update.</p>
	 * @param integer $ID идентификатор свойства
	 * @return array Если свойство не найдено, то возвращается false
	 * @static
	 */
	public static function GetByID($ID)
	{
		global $DB;
		static $arLabels = array("EDIT_FORM_LABEL", "LIST_COLUMN_LABEL", "LIST_FILTER_LABEL", "ERROR_MESSAGE", "HELP_MESSAGE");
		static $cache = array();

		if(!array_key_exists($ID, $cache))
		{
			$rsUserField = CUserTypeEntity::GetList(array(), array("ID" => intval($ID)));
			if($arUserField = $rsUserField->Fetch())
			{
				$rs = $DB->Query("SELECT * FROM b_user_field_lang WHERE USER_FIELD_ID = " . intval($ID), false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
				while($ar = $rs->Fetch())
				{
					foreach($arLabels as $label)
						$arUserField[$label][$ar["LANGUAGE_ID"]] = $ar[$label];
				}
				$cache[$ID] = $arUserField;
			}
			else
				$cache[$ID] = false;
		}
		return $cache[$ID];
	}

	/**
	 * Функция для выборки метаданных пользовательских свойств.
	 *
	 * <p>Возвращает CDBResult - выборку в зависимости от фильтра и сортировки.</p>
	 * <p>Параметр aSort по умолчанию имеет вид array("SORT"=>"ASC", "ID"=>"ASC").</p>
	 * <p>Если в aFilter передается LANG, то дополнительно выбираются языковые сообщения.</p>
	 * @param array $aSort ассоциативный массив сортировки (ID, ENTITY_ID, FIELD_NAME, SORT, USER_TYPE_ID)
	 * @param array $aFilter ассоциативный массив фильтра со строгим сообветствием (<b>равно</b>) (ID, ENTITY_ID, FIELD_NAME, USER_TYPE_ID, SORT, MULTIPLE, MANDATORY, SHOW_FILTER)
	 * @return CDBResult
	 * @static
	 */
	public static function GetList($aSort = array(), $aFilter = array())
	{
		global $DB, $CACHE_MANAGER;

		if(CACHED_b_user_field !== false)
		{
			$cacheId = "b_user_type" . md5(serialize($aSort) . "." . serialize($aFilter));
			if($CACHE_MANAGER->Read(CACHED_b_user_field, $cacheId, "b_user_field"))
			{
				$arResult = $CACHE_MANAGER->Get($cacheId);
				$res = new CDBResult;
				$res->InitFromArray($arResult);
				$res = new CUserTypeEntity($res);
				return $res;
			}
		}

		$bLangJoin = false;
		$arFilter = array();
		foreach($aFilter as $key => $val)
		{
			if(is_array($val) || (string)$val == '')
				continue;

			$key = mb_strtoupper($key);
			$val = $DB->ForSql($val);

			switch($key)
			{
				case "ID":
				case "ENTITY_ID":
				case "FIELD_NAME":
				case "USER_TYPE_ID":
				case "XML_ID":
				case "SORT":
				case "MULTIPLE":
				case "MANDATORY":
				case "SHOW_FILTER":
				case "SHOW_IN_LIST":
				case "EDIT_IN_LIST":
				case "IS_SEARCHABLE":
					$arFilter[] = "UF." . $key . " = '" . $val . "'";
					break;
				case "LANG":
					$bLangJoin = $val;
					break;
			}
		}

		$arOrder = array();
		foreach($aSort as $key => $val)
		{
			$key = mb_strtoupper($key);
			$ord = (mb_strtoupper($val) <> "ASC" ? "DESC" : "ASC");
			switch($key)
			{
				case "ID":
				case "ENTITY_ID":
				case "FIELD_NAME":
				case "USER_TYPE_ID":
				case "XML_ID":
				case "SORT":
					$arOrder[] = "UF." . $key . " " . $ord;
					break;
			}
		}
		if(count($arOrder) == 0)
		{
			$arOrder[] = "UF.SORT asc";
			$arOrder[] = "UF.ID asc";
		}
		DelDuplicateSort($arOrder);
		$sOrder = "\nORDER BY " . implode(", ", $arOrder);

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE " . implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				UF.ID
				,UF.ENTITY_ID
				,UF.FIELD_NAME
				,UF.USER_TYPE_ID
				,UF.XML_ID
				,UF.SORT
				,UF.MULTIPLE
				,UF.MANDATORY
				,UF.SHOW_FILTER
				,UF.SHOW_IN_LIST
				,UF.EDIT_IN_LIST
				,UF.IS_SEARCHABLE
				,UF.SETTINGS
				" . ($bLangJoin ? "
					,UFL.EDIT_FORM_LABEL
					,UFL.LIST_COLUMN_LABEL
					,UFL.LIST_FILTER_LABEL
					,UFL.ERROR_MESSAGE
					,UFL.HELP_MESSAGE
				" : "") . "
			FROM
				b_user_field UF
				" . ($bLangJoin ? "LEFT JOIN b_user_field_lang UFL on UFL.LANGUAGE_ID = '" . $bLangJoin . "' AND UFL.USER_FIELD_ID = UF.ID" : "") . "
			" . $sFilter . $sOrder;

		if(CACHED_b_user_field === false)
		{
			$res = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br> LINE: " . __LINE__);
		}
		else
		{
			$arResult = array();
			$res = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br> LINE: " . __LINE__);
			while($ar = $res->Fetch())
				$arResult[] = $ar;

			/** @noinspection PhpUndefinedVariableInspection */
			$CACHE_MANAGER->Set($cacheId, $arResult);

			$res = new CDBResult;
			$res->InitFromArray($arResult);
		}

		return new CUserTypeEntity($res);
	}

	/**
	 * Функция проверки корректности значений метаданных пользовательских свойств.
	 *
	 * <p>Вызывается в методах Add и Update для проверки правильности введенных значений.</p>
	 * <p>Проверки:</p>
	 * <ul>
	 * <li>ENTITY_ID - обязательное
	 * <li>ENTITY_ID - не более 50-ти символов
	 * <li>ENTITY_ID - не должно содержать никаких символов кроме 0-9 A-Z и _
	 * <li>FIELD_NAME - обязательное
	 * <li>FIELD_NAME - не менее 4-х символов
	 * <li>FIELD_NAME - не более 50-ти символов
	 * <li>FIELD_NAME - не должно содержать никаких символов кроме 0-9 A-Z и _
	 * <li>FIELD_NAME - должно начинаться на UF_
	 * <li>USER_TYPE_ID - обязательное
	 * <li>USER_TYPE_ID - должен быть зарегистрирован
	 * </ul>
	 * <p>В случае ошибки ловите исключение приложения!</p>
	 * @param integer $ID - идентификатор свойства. 0 - для нового.
	 * @param array $arFields метаданные свойства
	 * @param bool $bCheckUserType
	 * @return boolean false - если хоть одна проверка не прошла.
	 */
	function CheckFields($ID, $arFields, $bCheckUserType = true)
	{
		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $APPLICATION, $USER_FIELD_MANAGER;
		$aMsg = array();
		$ID = intval($ID);

		if(($ID <= 0 || array_key_exists("ENTITY_ID", $arFields)) && $arFields["ENTITY_ID"] == '')
			$aMsg[] = array("id" => "ENTITY_ID", "text" => GetMessage("USER_TYPE_ENTITY_ID_MISSING"));
		if(array_key_exists("ENTITY_ID", $arFields))
		{
			if(mb_strlen($arFields["ENTITY_ID"]) > 50)
				$aMsg[] = array("id" => "ENTITY_ID", "text" => GetMessage("USER_TYPE_ENTITY_ID_TOO_LONG1"));
			if(!preg_match('/^[0-9A-Z_]+$/', $arFields["ENTITY_ID"]))
				$aMsg[] = array("id" => "ENTITY_ID", "text" => GetMessage("USER_TYPE_ENTITY_ID_INVALID"));
		}

		if(($ID <= 0 || array_key_exists("FIELD_NAME", $arFields)) && $arFields["FIELD_NAME"] == '')
			$aMsg[] = array("id" => "FIELD_NAME", "text" => GetMessage("USER_TYPE_FIELD_NAME_MISSING"));
		if(array_key_exists("FIELD_NAME", $arFields))
		{
			if(mb_strlen($arFields["FIELD_NAME"]) < 4)
				$aMsg[] = array("id" => "FIELD_NAME", "text" => GetMessage("USER_TYPE_FIELD_NAME_TOO_SHORT"));
			if(mb_strlen($arFields["FIELD_NAME"]) > 50)
				$aMsg[] = array("id" => "FIELD_NAME", "text" => GetMessage("USER_TYPE_FIELD_NAME_TOO_LONG1"));
			if(strncmp($arFields["FIELD_NAME"], "UF_", 3) !== 0)
				$aMsg[] = array("id" => "FIELD_NAME", "text" => GetMessage("USER_TYPE_FIELD_NAME_NOT_UF"));
			if(!preg_match('/^[0-9A-Z_]+$/', $arFields["FIELD_NAME"]))
				$aMsg[] = array("id" => "FIELD_NAME", "text" => GetMessage("USER_TYPE_FIELD_NAME_INVALID"));
		}

		if(($ID <= 0 || array_key_exists("USER_TYPE_ID", $arFields)) && $arFields["USER_TYPE_ID"] == '')
			$aMsg[] = array("id" => "USER_TYPE_ID", "text" => GetMessage("USER_TYPE_USER_TYPE_ID_MISSING"));
		if(
			$bCheckUserType
			&& array_key_exists("USER_TYPE_ID", $arFields)
			&& !$USER_FIELD_MANAGER->GetUserType($arFields["USER_TYPE_ID"])
		)
			$aMsg[] = array("id" => "USER_TYPE_ID", "text" => GetMessage("USER_TYPE_USER_TYPE_ID_INVALID"));

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	/**
	 * Функция добавляет пользовательское свойство.
	 *
	 * <p>Сначала вызывается метод экземпляра объекта CheckFields (т.е. $this->CheckFields($arFields) ).</p>
	 * <p>Если проверка прошла успешно, выполняется проверка на существование такого поля для данной сущности.</p>
	 * <p>Далее при необходимости создаются таблички вида <b>b_uts_[ENTITY_ID]</b> и <b>b_utm_[ENTITY_ID]</b>.</p>
	 * <p>После чего метаданные сохраняются в БД.</p>
	 * <p>И только после этого <b>изменяется стуктура таблицы b_uts_[ENTITY_ID]</b>.</p>
	 * <p>Массив arFields:</p>
	 * <ul>
	 * <li>ENTITY_ID - сущность
	 * <li>FIELD_NAME - фактически имя столбца в БД в котором будут храниться значения свойства.
	 * <li>USER_TYPE_ID - тип свойства
	 * <li>XML_ID - идентификатор для использования при импорте/экспорте
	 * <li>SORT - порядок сортировки (по умолчанию 100)
	 * <li>MULTIPLE - признак множественности Y/N (по умолчанию N)
	 * <li>MANDATORY - признак обязательности ввода значения Y/N (по умолчанию N)
	 * <li>SHOW_FILTER - показывать или нет в фильтре админ листа и какой тип использовать. см. ниже.
	 * <li>SHOW_IN_LIST - показывать или нет в админ листе (по умолчанию Y)
	 * <li>EDIT_IN_LIST - разрешать редактирование в формах, но не в API! (по умолчанию Y)
	 * <li>IS_SEARCHABLE - поле участвует в поиске (по умолчанию N)
	 * <li>SETTINGS - массив с настройками свойства зависимыми от типа свойства. Проходят "очистку" через обработчик типа PrepareSettings.
	 * <li>EDIT_FORM_LABEL - массив языковых сообщений вида array("ru"=>"привет", "en"=>"hello")
	 * <li>LIST_COLUMN_LABEL
	 * <li>LIST_FILTER_LABEL
	 * <li>ERROR_MESSAGE
	 * <li>HELP_MESSAGE
	 * </ul>
	 * <p>В случае ошибки ловите исключение приложения!</p>
	 * <p>Значения для SHOW_FILTER:</p>
	 * <ul>
	 * <li>N - не показывать
	 * <li>I - точное совпадение
	 * <li>E - маска
	 * <li>S - подстрока
	 * </ul>
	 * @param array $arFields метаданные нового свойства
	 * @param bool $bCheckUserType
	 * @return integer - иднтификатор добавленного свойства, false - если свойство не было добавлено.
	 */
	function Add($arFields, $bCheckUserType = true)
	{
		global $DB, $APPLICATION, $USER_FIELD_MANAGER, $CACHE_MANAGER;

		if(!$this->CheckFields(0, $arFields, $bCheckUserType))
			return false;

		$rs = CUserTypeEntity::GetList(array(), array(
			"ENTITY_ID" => $arFields["ENTITY_ID"],
			"FIELD_NAME" => $arFields["FIELD_NAME"],
		));

		if($rs->Fetch())
		{
			$aMsg = array();
			$aMsg[] = array(
				"id" => "FIELD_NAME",
				"text" => GetMessage("USER_TYPE_ADD_ALREADY_ERROR", array(
					"#FIELD_NAME#" => htmlspecialcharsbx($arFields["FIELD_NAME"]),
					"#ENTITY_ID#" => htmlspecialcharsbx($arFields["ENTITY_ID"]),
				)),
			);
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		unset($arFields["ID"]);
		if(intval($arFields["SORT"]) <= 0)
			$arFields["SORT"] = 100;
		if($arFields["MULTIPLE"] !== "Y")
			$arFields["MULTIPLE"] = "N";
		if($arFields["MANDATORY"] !== "Y")
			$arFields["MANDATORY"] = "N";
		$arFields["SHOW_FILTER"] = mb_substr($arFields["SHOW_FILTER"], 0, 1);
		if($arFields["SHOW_FILTER"] == '' || mb_strpos("NIES", $arFields["SHOW_FILTER"]) === false)
			$arFields["SHOW_FILTER"] = "N";
		if($arFields["SHOW_IN_LIST"] !== "N")
			$arFields["SHOW_IN_LIST"] = "Y";
		if($arFields["EDIT_IN_LIST"] !== "N")
			$arFields["EDIT_IN_LIST"] = "Y";
		if($arFields["IS_SEARCHABLE"] !== "Y")
			$arFields["IS_SEARCHABLE"] = "N";

		if(!array_key_exists("SETTINGS", $arFields))
			$arFields["SETTINGS"] = array();
		$arFields["SETTINGS"] = serialize($USER_FIELD_MANAGER->PrepareSettings(0, $arFields, $bCheckUserType));

		/**
		 * events
		 * PROVIDE_STORAGE - use own uf subsystem to store data (uts/utm tables)
		 */
		$commonEventResult = array('PROVIDE_STORAGE' => true);

		foreach(GetModuleEvents("main", "OnBeforeUserTypeAdd", true) as $arEvent)
		{
			$eventResult = ExecuteModuleEventEx($arEvent, array(&$arFields));

			if($eventResult === false)
			{
				if($e = $APPLICATION->GetException())
				{
					return false;
				}

				$aMsg = array();
				$aMsg[] = array(
					"id" => "FIELD_NAME",
					"text" => GetMessage("USER_TYPE_ADD_ERROR", array(
						"#FIELD_NAME#" => htmlspecialcharsbx($arFields["FIELD_NAME"]),
						"#ENTITY_ID#" => htmlspecialcharsbx($arFields["ENTITY_ID"]),
					))
				);

				$e = new CAdminException($aMsg);
				$APPLICATION->ThrowException($e);

				return false;
			}
			elseif(is_array($eventResult))
			{
				$commonEventResult = array_merge($commonEventResult, $eventResult);
			}
		}

		if(is_object($USER_FIELD_MANAGER))
			$USER_FIELD_MANAGER->CleanCache();

		if($commonEventResult['PROVIDE_STORAGE'])
		{
			if(!$this->CreatePropertyTables($arFields["ENTITY_ID"]))
				return false;

			$strType = $USER_FIELD_MANAGER->getUtsDBColumnType($arFields);

			if(!$strType)
			{
				$aMsg = array();
				$aMsg[] = array(
					"id" => "FIELD_NAME",
					"text" => GetMessage("USER_TYPE_ADD_ERROR", array(
						"#FIELD_NAME#" => htmlspecialcharsbx($arFields["FIELD_NAME"]),
						"#ENTITY_ID#" => htmlspecialcharsbx($arFields["ENTITY_ID"]),
					)),
				);
				$e = new CAdminException($aMsg);
				$APPLICATION->ThrowException($e);
				return false;
			}

			if(!$DB->Query("select ".$arFields["FIELD_NAME"]." from b_uts_".mb_strtolower($arFields["ENTITY_ID"]) . " where 1=0", true))
			{
				$ddl = "ALTER TABLE b_uts_".mb_strtolower($arFields["ENTITY_ID"]) . " ADD " . $arFields["FIELD_NAME"] . " " . $strType;
				if(!$DB->DDL($ddl, true, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__))
				{
					$aMsg = array();
					$aMsg[] = array(
						"id" => "FIELD_NAME",
						"text" => GetMessage("USER_TYPE_ADD_ERROR", array(
							"#FIELD_NAME#" => htmlspecialcharsbx($arFields["FIELD_NAME"]),
							"#ENTITY_ID#" => htmlspecialcharsbx($arFields["ENTITY_ID"]),
						))
					);
					$e = new CAdminException($aMsg);
					$APPLICATION->ThrowException($e);
					return false;
				}
			}
		}

		if($ID = $DB->Add("b_user_field", $arFields, array("SETTINGS")))
		{
			if(CACHED_b_user_field !== false)
				$CACHE_MANAGER->CleanDir("b_user_field");

			$arLabels = array("EDIT_FORM_LABEL", "LIST_COLUMN_LABEL", "LIST_FILTER_LABEL", "ERROR_MESSAGE", "HELP_MESSAGE");
			$arLangs = array();
			foreach($arLabels as $label)
			{
				if(isset($arFields[$label]) && is_array($arFields[$label]))
				{
					foreach($arFields[$label] as $lang => $value)
					{
						$arLangs[$lang][$label] = $value;
					}
				}
			}

			foreach($arLangs as $lang => $arLangFields)
			{
				$arLangFields["USER_FIELD_ID"] = $ID;
				$arLangFields["LANGUAGE_ID"] = $lang;
				$DB->Add("b_user_field_lang", $arLangFields);
			}
		}

		// post event
		$arFields['ID'] = $ID;

		foreach(GetModuleEvents("main", "OnAfterUserTypeAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($arFields));
		}

		return $ID;
	}

	/**
	 * Функция изменяет метаданные пользовательского свойства.
	 *
	 * <p>Надо сказать, что для скорейшего завершения разработки было решено пока не реализовывать
	 * такую же гибкость как в инфоблоках (обойдемся пока без alter'ов и прочего).</p>
	 * <p>Сначала вызывается метод экземпляра объекта CheckFields (т.е. $this->CheckFields($arFields) ).</p>
	 * <p>После чего метаданные сохраняются в БД.</p>
	 * <p>Массив arFields (только то что можно изменять):</p>
	 * <ul>
	 * <li>SORT - порядок сортировки
	 * <li>MANDATORY - признак обязательности ввода значения Y/N
	 * <li>SHOW_FILTER - признак показа в фильтре списка Y/N
	 * <li>SHOW_IN_LIST - признак показа в списке Y/N
	 * <li>EDIT_IN_LIST - разрешать редактирование поля в формах админки или нет Y/N
	 * <li>IS_SEARCHABLE - признак поиска Y/N
	 * <li>SETTINGS - массив с настройками свойства зависимыми от типа свойства. Проходят "очистку" через обработчик типа PrepareSettings.
	 * <li>EDIT_FORM_LABEL - массив языковых сообщений вида array("ru"=>"привет", "en"=>"hello")
	 * <li>LIST_COLUMN_LABEL
	 * <li>LIST_FILTER_LABEL
	 * <li>ERROR_MESSAGE
	 * <li>HELP_MESSAGE
	 * </ul>
	 * <p>В случае ошибки ловите исключение приложения!</p>
	 * @param integer $ID идентификатор свойства
	 * @param array $arFields новые метаданные свойства
	 * @return boolean - true в случае успешного обновления, false - в противном случае.
	 */
	function Update($ID, $arFields)
	{
		global $DB, $USER_FIELD_MANAGER, $CACHE_MANAGER, $APPLICATION;
		$ID = intval($ID);

		unset($arFields["ENTITY_ID"]);
		unset($arFields["FIELD_NAME"]);
		unset($arFields["USER_TYPE_ID"]);
		unset($arFields["MULTIPLE"]);

		if(!$this->CheckFields($ID, $arFields))
			return false;

		if(array_key_exists("SETTINGS", $arFields))
			$arFields["SETTINGS"] = serialize($USER_FIELD_MANAGER->PrepareSettings($ID, $arFields));
		if(array_key_exists("MANDATORY", $arFields) && $arFields["MANDATORY"] !== "Y")
			$arFields["MANDATORY"] = "N";
		if(array_key_exists("SHOW_FILTER", $arFields))
		{
			$arFields["SHOW_FILTER"] = mb_substr($arFields["SHOW_FILTER"], 0, 1);
			if(mb_strpos("NIES", $arFields["SHOW_FILTER"]) === false)
				$arFields["SHOW_FILTER"] = "N";
		}
		if(array_key_exists("SHOW_IN_LIST", $arFields) && $arFields["SHOW_IN_LIST"] !== "N")
			$arFields["SHOW_IN_LIST"] = "Y";
		if(array_key_exists("EDIT_IN_LIST", $arFields) && $arFields["EDIT_IN_LIST"] !== "N")
			$arFields["EDIT_IN_LIST"] = "Y";
		if(array_key_exists("IS_SEARCHABLE", $arFields) && $arFields["IS_SEARCHABLE"] !== "Y")
			$arFields["IS_SEARCHABLE"] = "N";

		// events
		foreach(GetModuleEvents("main", "OnBeforeUserTypeUpdate", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
			{
				if($e = $APPLICATION->GetException())
				{
					return false;
				}

				$aMsg = array();
				$aMsg[] = array(
					"id" => "FIELD_NAME",
					"text" => GetMessage("USER_TYPE_UPDATE_ERROR", array(
						"#FIELD_NAME#" => htmlspecialcharsbx($arFields["FIELD_NAME"]),
						"#ENTITY_ID#" => htmlspecialcharsbx($arFields["ENTITY_ID"]),
					))
				);

				$e = new CAdminException($aMsg);
				$APPLICATION->ThrowException($e);

				return false;
			}
		}

		if(is_object($USER_FIELD_MANAGER))
			$USER_FIELD_MANAGER->CleanCache();

		$strUpdate = $DB->PrepareUpdate("b_user_field", $arFields);

		static $arLabels = array("EDIT_FORM_LABEL", "LIST_COLUMN_LABEL", "LIST_FILTER_LABEL", "ERROR_MESSAGE", "HELP_MESSAGE");
		$arLangs = array();
		foreach($arLabels as $label)
		{
			if(is_array($arFields[$label]))
			{
				foreach($arFields[$label] as $lang => $value)
				{
					$arLangs[$lang][$label] = $value;
				}
			}
		}

		if($strUpdate <> "" || !empty($arLangs))
		{
			if(CACHED_b_user_field !== false)
			{
				$CACHE_MANAGER->CleanDir("b_user_field");
			}

			if($strUpdate <> "")
			{
				$strSql = "UPDATE b_user_field SET " . $strUpdate . " WHERE ID = " . $ID;
				if(array_key_exists("SETTINGS", $arFields))
					$arBinds = array("SETTINGS" => $arFields["SETTINGS"]);
				else
					$arBinds = array();
				$DB->QueryBind($strSql, $arBinds);
			}

			if(!empty($arLangs))
			{
				$DB->Query("DELETE FROM b_user_field_lang WHERE USER_FIELD_ID = " . $ID, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);

				foreach($arLangs as $lang => $arLangFields)
				{
					$arLangFields["USER_FIELD_ID"] = $ID;
					$arLangFields["LANGUAGE_ID"] = $lang;
					$DB->Add("b_user_field_lang", $arLangFields);
				}
			}

			foreach(GetModuleEvents("main", "OnAfterUserTypeUpdate", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arFields, $ID));
			}
		}

		return true;
	}

	/**
	 * Функция удаляет пользовательское свойство и все его значения.
	 *
	 * <p>Сначала удаляются метаданные свойства.</p>
	 * <p>Затем из таблички вида <b>b_utm_[ENTITY_ID]</b> удаляются все значения множественных свойств.</p>
	 * <p>После чего у таблички вида <b>b_uts_[ENTITY_ID]</b> дропается колонка.</p>
	 * <p>И если это было "последнее" свойство для сущности, то дропаются сами таблички хранившие значения.</p>
	 * @param integer $ID идентификатор свойства
	 * @return CDBResult - результат выполнения последнего запроса функции.
	 */
	function Delete($ID)
	{
		global $DB, $CACHE_MANAGER, $USER_FIELD_MANAGER, $APPLICATION;
		$ID = intval($ID);

		$rs = $this->GetList(array(), array("ID" => $ID));
		if($arField = $rs->Fetch())
		{
			/**
			 * events
			 * PROVIDE_STORAGE - use own uf subsystem to store data (uts/utm tables)
			 */
			$commonEventResult = array('PROVIDE_STORAGE' => true);

			foreach(GetModuleEvents("main", "OnBeforeUserTypeDelete", true) as $arEvent)
			{
				$eventResult = ExecuteModuleEventEx($arEvent, array(&$arField));

				if($eventResult === false)
				{
					if($e = $APPLICATION->GetException())
					{
						return false;
					}

					$aMsg = array();
					$aMsg[] = array(
						"id" => "FIELD_NAME",
						"text" => GetMessage("USER_TYPE_DELETE_ERROR", array(
							"#FIELD_NAME#" => htmlspecialcharsbx($arField["FIELD_NAME"]),
							"#ENTITY_ID#" => htmlspecialcharsbx($arField["ENTITY_ID"]),
						))
					);

					$e = new CAdminException($aMsg);
					$APPLICATION->ThrowException($e);

					return false;
				}
				elseif(is_array($eventResult))
				{
					$commonEventResult = array_merge($commonEventResult, $eventResult);
				}
			}

			if(is_object($USER_FIELD_MANAGER))
				$USER_FIELD_MANAGER->CleanCache();

			$arType = $USER_FIELD_MANAGER->GetUserType($arField["USER_TYPE_ID"]);
			//We need special handling of file type properties
			if($arType)
			{
				if($arType["BASE_TYPE"] == "file" && $commonEventResult['PROVIDE_STORAGE'])
				{
					// only if we store values
					if($arField["MULTIPLE"] == "Y")
						$strSql = "SELECT VALUE_INT VALUE FROM b_utm_".mb_strtolower($arField["ENTITY_ID"]) . " WHERE FIELD_ID=" . $arField["ID"];
					else
						$strSql = "SELECT ".$arField["FIELD_NAME"]." VALUE FROM b_uts_".mb_strtolower($arField["ENTITY_ID"]);
					$rsFile = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
					while($arFile = $rsFile->Fetch())
					{
						CFile::Delete($arFile["VALUE"]);
					}
				}
				elseif($arType["BASE_TYPE"] == "enum")
				{
					$obEnum = new CUserFieldEnum;
					$obEnum->DeleteFieldEnum($arField["ID"]);
				}
			}

			if(CACHED_b_user_field !== false) $CACHE_MANAGER->CleanDir("b_user_field");
			$rs = $DB->Query("DELETE FROM b_user_field_lang WHERE USER_FIELD_ID = " . $ID, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
			if($rs)
				$rs = $DB->Query("DELETE FROM b_user_field WHERE ID = " . $ID, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);

			if($rs && $commonEventResult['PROVIDE_STORAGE'])
			{
				// only if we store values
				$rs = $this->GetList(array(), array("ENTITY_ID" => $arField["ENTITY_ID"]));
				if($rs->Fetch()) // more than one
				{
					foreach($this->DropColumnSQL("b_uts_".mb_strtolower($arField["ENTITY_ID"]), array($arField["FIELD_NAME"])) as $strSql)
						$DB->Query($strSql, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
					$rs = $DB->Query("DELETE FROM b_utm_".mb_strtolower($arField["ENTITY_ID"]) . " WHERE FIELD_ID = '" . $ID . "'", false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
				}
				else
				{
					$DB->Query("DROP SEQUENCE SQ_B_UTM_" . $arField["ENTITY_ID"], true);
					$DB->Query("DROP TABLE b_uts_".mb_strtolower($arField["ENTITY_ID"]), false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
					$rs = $DB->Query("DROP TABLE b_utm_".mb_strtolower($arField["ENTITY_ID"]), false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
				}
			}

			foreach(GetModuleEvents("main", "OnAfterUserTypeDelete", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($arField, $ID));
			}
		}
		return $rs;
	}

	/**
	 * Функция удаляет ВСЕ пользовательские свойства сущности.
	 *
	 * <p>Сначала удаляются метаданные свойств.</p>
	 * <p>Можно вызвать при удалении инфоблока например.</p>
	 * <p>Затем таблички вида <b>b_utm_[ENTITY_ID]</b> и <b>b_uts_[ENTITY_ID]</b> дропаются.</p>
	 * @param string $entity_id идентификатор сущности
	 * @return CDBResult - результат выполнения последнего запроса функции.
	 */
	function DropEntity($entity_id)
	{
		global $DB, $CACHE_MANAGER, $USER_FIELD_MANAGER;
		$entity_id = preg_replace("/[^0-9A-Z_]+/", "", $entity_id);

		$rs = true;
		$rsFields = $this->GetList(array(), array("ENTITY_ID" => $entity_id));
		//We need special handling of file and enum type properties
		while($arField = $rsFields->Fetch())
		{
			$arType = $USER_FIELD_MANAGER->GetUserType($arField["USER_TYPE_ID"]);
			if($arType && ($arType["BASE_TYPE"] == "file" || $arType["BASE_TYPE"] == "enum"))
			{
				$this->Delete($arField["ID"]);
			}
		}

		$bDropTable = false;
		$rsFields = $this->GetList(array(), array("ENTITY_ID" => $entity_id));
		while($arField = $rsFields->Fetch())
		{
			$bDropTable = true;
			$DB->Query("DELETE FROM b_user_field_lang WHERE USER_FIELD_ID = " . $arField["ID"], false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
			$rs = $DB->Query("DELETE FROM b_user_field WHERE ID = " . $arField["ID"], false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
		}

		if($bDropTable)
		{
			$DB->Query("DROP SEQUENCE SQ_B_UTM_" . $entity_id, true);
			$DB->Query("DROP TABLE b_uts_".mb_strtolower($entity_id), true, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
			$rs = $DB->Query("DROP TABLE b_utm_".mb_strtolower($entity_id), true, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
		}

		if(CACHED_b_user_field !== false)
			$CACHE_MANAGER->CleanDir("b_user_field");

		if(is_object($USER_FIELD_MANAGER))
			$USER_FIELD_MANAGER->CleanCache();

		return $rs;
	}

	/**
	 * Функция Fetch.
	 *
	 * <p>Десериализует поле SETTINGS.</p>
	 * @return array возвращает false в случае последней записи выборки.
	 */
	function Fetch()
	{
		$res = parent::Fetch();
		if($res && $res["SETTINGS"] <> '')
		{
			$res["SETTINGS"] = unserialize($res["SETTINGS"], ['allowed_classes' => false]);
		}
		return $res;
	}
}

/**
 * Данный класс фактически является интерфейсной прослойкой между значениями
 * пользовательских свойств и сущностью к которой они привязаны.
 * @package usertype
 * @subpackage classes
 */
class CUserTypeManager
{
	const BASE_TYPE_INT = "int";
	const BASE_TYPE_FILE = "file";
	const BASE_TYPE_ENUM = "enum";
	const BASE_TYPE_DOUBLE = "double";
	const BASE_TYPE_DATETIME = "datetime";
	const BASE_TYPE_STRING = "string";

	/**
	 * Хранит все типы пользовательских свойств.
	 *
	 * <p>Инициализируется при первом вызове метода GetUserType.</p>
	 * @var array
	 */
	var $arUserTypes = false;
	var $arFieldsCache = array();
	var $arRightsCache = array();

	/**
	 * @var null|array Stores relations of usertype ENTITY_ID to ORM entities. Aggregated by event main:onUserTypeEntityOrmMap.
	 * @see CUserTypeManager::getEntityList()
	 */
	protected $entityList = null;

	function CleanCache()
	{
		$this->arFieldsCache = array();
		$this->arUserTypes = false;
	}

	/**
	 * Функция возвращает метаданные типа.
	 *
	 * <p>Если это первый вызов функции, то выполняется системное событие OnUserTypeBuildList (main).
	 * Зарегистрированные обработчики должны вернуть даные описания типа. В данном случае действует правило -
	 * кто последний тот и папа. (на случай если один тип зарегились обрабатывать "несколько" классов)</p>
	 * <p>Без параметров функция возвращает полный список типов.<p>
	 * <p>При заданном user_type_id - возвращает массив если такой тип зарегистрирован и false если нет.<p>
	 * @param string|bool $user_type_id необязательный. идентификатор типа свойства.
	 * @return array|boolean
	 */
	function GetUserType($user_type_id = false)
	{
		if(!is_array($this->arUserTypes))
		{
			$this->arUserTypes = array();
			foreach(GetModuleEvents("main", "OnUserTypeBuildList", true) as $arEvent)
			{
				$res = ExecuteModuleEventEx($arEvent);
				$this->arUserTypes[$res["USER_TYPE_ID"]] = $res;
			}
		}
		if($user_type_id !== false)
		{
			if(array_key_exists($user_type_id, $this->arUserTypes))
				return $this->arUserTypes[$user_type_id];
			else
				return false;
		}
		else
			return $this->arUserTypes;
	}

	function GetDBColumnType($arUserField)
	{
		if($arType = $this->GetUserType($arUserField["USER_TYPE_ID"]))
		{
			if(is_callable(array($arType["CLASS_NAME"], "getdbcolumntype")))
				return call_user_func_array(array($arType["CLASS_NAME"], "getdbcolumntype"), array($arUserField));
		}
		return "";
	}

	function getUtsDBColumnType($arUserField)
	{
		if($arUserField['MULTIPLE'] == 'Y')
		{
			$sqlHelper = \Bitrix\Main\Application::getConnection()->getSqlHelper();
			return $sqlHelper->getColumnTypeByField(new Entity\TextField('TMP'));
		}
		else
		{
			return $this->GetDBColumnType($arUserField);
		}
	}

	function getUtmDBColumnType($arUserField)
	{
		return $this->GetDBColumnType($arUserField);
	}

	function PrepareSettings($ID, $arUserField, $bCheckUserType = true)
	{
		$user_type_id = $arUserField["USER_TYPE_ID"];
		if($ID > 0)
		{
			$rsUserType = CUserTypeEntity::GetList(array(), array("ID" => $ID));
			$arUserType = $rsUserType->Fetch();
			if($arUserType)
			{
				$user_type_id = $arUserType["USER_TYPE_ID"];
			}
		}

		if(!$bCheckUserType)
		{
			if(!isset($arUserField["SETTINGS"]))
				return array();

			if(!is_array($arUserField["SETTINGS"]))
				return array();

			if(empty($arUserField["SETTINGS"]))
				return array();
		}

		if($arType = $this->GetUserType($user_type_id))
		{
			if(is_callable(array($arType["CLASS_NAME"], "preparesettings")))
				return call_user_func_array(array($arType["CLASS_NAME"], "preparesettings"), array($arUserField));
		}
		else
		{
			return array();
		}
		return null;
	}

	function OnEntityDelete($entity_id)
	{
		$obUserField = new CUserTypeEntity;
		return $obUserField->DropEntity($entity_id);
	}

	/**
	 * Функция возвращает метаданные полей определеных для сущности.
	 *
	 * <p>Важно! В $arUserField добалено поле ENTITY_VALUE_ID - это идентификатор экземпляра сущности
	 * позволяющий отделить новые записи от старых и соответсвенно использовать значения по умолчанию.</p>
	 */
	function GetUserFields($entity_id, $value_id = 0, $LANG = false, $user_id = false)
	{
		$entity_id = preg_replace("/[^0-9A-Z_]+/", "", $entity_id);
		$value_id = intval($value_id);
		$cacheId = $entity_id . "." . $LANG . '.' . (int)$user_id;

		global $DB;

		$result = array();
		if(!array_key_exists($cacheId, $this->arFieldsCache))
		{
			$arFilter = array("ENTITY_ID" => $entity_id);
			if($LANG)
				$arFilter["LANG"] = $LANG;
			$rs = CUserTypeEntity::GetList(array(), $arFilter);
			while($arUserField = $rs->Fetch())
			{
				if($arType = $this->GetUserType($arUserField["USER_TYPE_ID"]))
				{
					if($user_id !== 0	&& is_callable(array($arType["CLASS_NAME"], "checkpermission")))
					{
						if(!call_user_func_array(array($arType["CLASS_NAME"], "checkpermission"), [$arUserField, $user_id]))
							continue;
					}
					$arUserField["USER_TYPE"] = $arType;
					$arUserField["VALUE"] = false;
					if(!is_array($arUserField["SETTINGS"]) || empty($arUserField["SETTINGS"]))
						$arUserField["SETTINGS"] = $this->PrepareSettings(0, $arUserField);
					$result[$arUserField["FIELD_NAME"]] = $arUserField;
				}
			}
			$this->arFieldsCache[$cacheId] = $result;
		}
		else
		{
			$result = $this->arFieldsCache[$cacheId];
		}

		if (count($result) > 0 && $value_id > 0)
		{
			$valuesGottenByEvent = $this->getUserFieldValuesByEvent($result, $entity_id, $value_id);

			$select = "VALUE_ID";
			foreach($result as $fieldName => $arUserField)
			{
				$result[$fieldName]["ENTITY_VALUE_ID"] = $value_id;

				if (is_array($valuesGottenByEvent))
				{
					$result[$fieldName]["VALUE"] = array_key_exists($fieldName, $valuesGottenByEvent) ? $valuesGottenByEvent[$fieldName] : $result[$fieldName]["VALUE"];
				}
				else if ($arUserField["MULTIPLE"] == "N"
					&& is_array($arUserField["USER_TYPE"])
					&& array_key_exists("CLASS_NAME", $arUserField["USER_TYPE"])
					&& is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "FormatField")))
				{
					$select .= ", " . call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "FormatField"), array($arUserField, $fieldName)) . " " . $fieldName;
				}
				else
				{
					$select .= ", " . $fieldName;
				}
			}

			if (is_array($valuesGottenByEvent))
			{
				return $result;
			}

			$rs = $DB->Query("SELECT ".$select." FROM b_uts_".mb_strtolower($entity_id) . " WHERE VALUE_ID = " . $value_id, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
			if($ar = $rs->Fetch())
			{
				foreach($ar as $key => $value)
				{
					if(array_key_exists($key, $result))
					{
						if($result[$key]["MULTIPLE"] == "Y")
						{
							if(mb_substr($value, 0, 1) !== 'a' && $value > 0)
							{
								$value = $this->LoadMultipleValues($result[$key], $value);
							}
							else
							{
								$value = unserialize($value, ['allowed_classes' => false]);
							}
							$result[$key]["VALUE"] = $this->OnAfterFetch($result[$key], $value);
						}
						else
						{
							$result[$key]["VALUE"] = $this->OnAfterFetch($result[$key], $value);
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Replacement for getUserFields, if you are already have fetched old data
	 *
	 * @param      $entity_id
	 * @param      $readyData
	 * @param bool $LANG
	 * @param bool $user_id
	 * @param string $primaryIdName
	 *
	 * @return array
	 */
	function getUserFieldsWithReadyData($entity_id, $readyData, $LANG = false, $user_id = false, $primaryIdName = 'VALUE_ID')
	{
		if($readyData === null)
		{
			return $this->GetUserFields($entity_id, null, $LANG, $user_id);
		}

		$entity_id = preg_replace("/[^0-9A-Z_]+/", "", $entity_id);
		$cacheId = $entity_id . "." . $LANG . '.' . (int)$user_id;

		//global $DB;

		$result = array();
		if(!array_key_exists($cacheId, $this->arFieldsCache))
		{
			$arFilter = array("ENTITY_ID" => $entity_id);
			if($LANG)
				$arFilter["LANG"] = $LANG;

			$rs = call_user_func_array(array('CUserTypeEntity', 'GetList'), array(array(), $arFilter));
			while($arUserField = $rs->Fetch())
			{
				if($arType = $this->GetUserType($arUserField["USER_TYPE_ID"]))
				{
					if($user_id !== 0	&& is_callable(array($arType["CLASS_NAME"], "checkpermission")))
					{
						if(!call_user_func_array(array($arType["CLASS_NAME"], "checkpermission"), array($arUserField, $user_id)))
							continue;
					}
					$arUserField["USER_TYPE"] = $arType;
					$arUserField["VALUE"] = false;
					if(!is_array($arUserField["SETTINGS"]) || empty($arUserField["SETTINGS"]))
						$arUserField["SETTINGS"] = $this->PrepareSettings(0, $arUserField);
					$result[$arUserField["FIELD_NAME"]] = $arUserField;
				}
			}
			$this->arFieldsCache[$cacheId] = $result;
		}
		else
			$result = $this->arFieldsCache[$cacheId];

		foreach($readyData as $key => $value)
		{
			if(array_key_exists($key, $result))
			{
				if($result[$key]["MULTIPLE"] == "Y" && !is_array($value))
				{
					$value = unserialize($value, ['allowed_classes' => false]);
				}

				$result[$key]["VALUE"] = $this->OnAfterFetch($result[$key], $value);
				$result[$key]["ENTITY_VALUE_ID"] = $readyData[$primaryIdName];
			}
		}

		return $result;
	}

	function GetUserFieldValue($entity_id, $field_id, $value_id, $LANG = false)
	{
		global $DB;
		$entity_id = preg_replace("/[^0-9A-Z_]+/", "", $entity_id);
		$field_id = preg_replace("/[^0-9A-Z_]+/", "", $field_id);
		$value_id = intval($value_id);
		$strTableName = "b_uts_".mb_strtolower($entity_id);
		$result = false;

		$arFilter = array(
			"ENTITY_ID" => $entity_id,
			"FIELD_NAME" => $field_id,
		);
		if($LANG)
			$arFilter["LANG"] = $LANG;
		$rs = CUserTypeEntity::GetList(array(), $arFilter);
		if($arUserField = $rs->Fetch())
		{
			$values = $this->getUserFieldValuesByEvent([$arUserField['FIELD_NAME'] => $arUserField], $entity_id, $value_id);
			if(is_array($values))
			{
				return $values[$arUserField['FIELD_NAME']];
			}
			$arUserField["USER_TYPE"] = $this->GetUserType($arUserField["USER_TYPE_ID"]);
			$arTableFields = $DB->GetTableFields($strTableName);
			if(array_key_exists($field_id, $arTableFields))
			{
				$simpleFormat = true;
				$select = "";
				if($arUserField["MULTIPLE"] == "N")
				{
					if($arType = $arUserField["USER_TYPE"])
					{
						if(is_callable(array($arType["CLASS_NAME"], "FormatField")))
						{
							$select = call_user_func_array(array($arType["CLASS_NAME"], "FormatField"), array($arUserField, $field_id));
							$simpleFormat = false;
						}
					}
				}
				if($simpleFormat)
				{
					$select = $field_id;
				}

				$rs = $DB->Query("SELECT " . $select . " VALUE FROM " . $strTableName . " WHERE VALUE_ID = " . $value_id, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
				if($ar = $rs->Fetch())
				{
					if($arUserField["MULTIPLE"] == "Y")
						$result = $this->OnAfterFetch($arUserField, unserialize($ar["VALUE"], ['allowed_classes' => false]));
					else
						$result = $this->OnAfterFetch($arUserField, $ar["VALUE"]);
				}
			}
		}

		return $result;
	}

	/**
	 * Aggregates entity map by event.
	 * @return array [ENTITY_ID => 'SomeTable']
	 */
	function getEntityList()
	{
		if($this->entityList === null)
		{
			$event = new \Bitrix\Main\Event('main', 'onUserTypeEntityOrmMap');
			$event->send();

			foreach($event->getResults() as $eventResult)
			{
				if($eventResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
				{
					$result = $eventResult->getParameters(); // [ENTITY_ID => 'SomeTable']
					foreach($result as $entityId => $entityClass)
					{
						if(mb_substr($entityClass, 0, 1) !== '\\')
						{
							$entityClass = '\\' . $entityClass;
						}

						$this->entityList[$entityId] = $entityClass;
					}
				}
			}
		}

		return $this->entityList;
	}

	function OnAfterFetch($arUserField, $result)
	{
		if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onafterfetch")))
		{
			if($arUserField["MULTIPLE"] == "Y")
			{
				if(is_array($result))
				{
					$resultCopy = $result;
					$result = array();
					foreach($resultCopy as $key => $value)
					{
						$convertedValue = call_user_func_array(
							array($arUserField["USER_TYPE"]["CLASS_NAME"], "onafterfetch"),
							array(
								$arUserField,
								array(
									"VALUE" => $value,
								),
							)
						);
						if($convertedValue !== null)
						{
							$result[] = $convertedValue;
						}
					}
				}
			}
			else
			{
				$result = call_user_func_array(
					array($arUserField["USER_TYPE"]["CLASS_NAME"], "onafterfetch"),
					array(
						$arUserField,
						array(
							"VALUE" => $result,
						),
					)
				);
			}
		}
		return $result;
	}

	function LoadMultipleValues($arUserField, $valueId)
	{
		global $DB;
		$result = array();

		$rs = $DB->Query("
			SELECT *
			FROM b_utm_".mb_strtolower($arUserField["ENTITY_ID"]) . "
			WHERE VALUE_ID = " . intval($valueId) . "
			AND FIELD_ID = " . $arUserField["ID"] . "
		", false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
		while($ar = $rs->Fetch())
		{
			if($arUserField["USER_TYPE"]["USER_TYPE_ID"] == "date")
			{
				$result[] = mb_substr($ar["VALUE_DATE"], 0, 10);
			}
			else
			{
				switch($arUserField["USER_TYPE"]["BASE_TYPE"])
				{
					case "int":
					case "file":
					case "enum":
						$result[] = $ar["VALUE_INT"];
						break;
					case "double":
						$result[] = $ar["VALUE_DOUBLE"];
						break;
					case "datetime":
						$result[] = $ar["VALUE_DATE"];
						break;
					default:
						$result[] = $ar["VALUE"];
				}
			}
		}
		return $result;
	}

	function EditFormTab($entity_id)
	{
		return array(
			"DIV" => "user_fields_tab",
			"TAB" => GetMessage("USER_TYPE_EDIT_TAB"),
			"ICON" => "none",
			"TITLE" => GetMessage("USER_TYPE_EDIT_TAB_TITLE"),
		);
	}

	function EditFormShowTab($entity_id, $bVarsFromForm, $ID)
	{
		global $APPLICATION;

		if($this->GetRights($entity_id) >= "W")
		{
			echo "<tr colspan=\"2\"><td align=\"left\"><a href=\"/bitrix/admin/userfield_edit.php?lang=" . LANG . "&ENTITY_ID=" . urlencode($entity_id) . "&back_url=" . urlencode($APPLICATION->GetCurPageParam("", array("bxpublic")) . "&tabControl_active_tab=user_fields_tab") . "\">" . GetMessage("USER_TYPE_EDIT_TAB_HREF") . "</a></td></tr>";
		}

		$arUserFields = $this->GetUserFields($entity_id, $ID, LANGUAGE_ID);
		if(count($arUserFields) > 0)
		{
			foreach($arUserFields as $FIELD_NAME => $arUserField)
			{
				$arUserField["VALUE_ID"] = intval($ID);
				echo $this->GetEditFormHTML($bVarsFromForm, $GLOBALS[$FIELD_NAME], $arUserField);
			}
		}
	}

	function EditFormAddFields($entity_id, &$arFields, array $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		if(!is_array($arFields))
		{
			$arFields = array();
		}

		$files = isset($options['FILES']) ? $options['FILES'] : $_FILES;
		$form = isset($options['FORM']) && is_array($options['FORM']) ? $options['FORM'] : $GLOBALS;

		$arUserFields = $this->GetUserFields($entity_id);
		foreach($arUserFields as $arUserField)
		{
			if($arUserField["EDIT_IN_LIST"] == "Y")
			{
				if($arUserField["USER_TYPE"]["BASE_TYPE"] == "file")
				{
					if(isset($files[$arUserField["FIELD_NAME"]]))
					{
						if(is_array($files[$arUserField["FIELD_NAME"]]["name"]))
						{
							$arFields[$arUserField["FIELD_NAME"]] = array();
							foreach($files[$arUserField["FIELD_NAME"]]["name"] as $key => $value)
							{
								$old_id = $form[$arUserField["FIELD_NAME"] . "_old_id"][$key];
								$arFields[$arUserField["FIELD_NAME"]][$key] = array(
									"name" => $files[$arUserField["FIELD_NAME"]]["name"][$key],
									"type" => $files[$arUserField["FIELD_NAME"]]["type"][$key],
									"tmp_name" => $files[$arUserField["FIELD_NAME"]]["tmp_name"][$key],
									"error" => $files[$arUserField["FIELD_NAME"]]["error"][$key],
									"size" => $files[$arUserField["FIELD_NAME"]]["size"][$key],
									"del" => is_array($form[$arUserField["FIELD_NAME"] . "_del"]) &&
										(in_array($old_id, $form[$arUserField["FIELD_NAME"] . "_del"]) ||
											(
												array_key_exists($key, $form[$arUserField["FIELD_NAME"] . "_del"]) &&
												$form[$arUserField["FIELD_NAME"] . "_del"][$key] == "Y"
											)
										),
									"old_id" => $old_id
								);
							}
						}
						else
						{
							$arFields[$arUserField["FIELD_NAME"]] = $files[$arUserField["FIELD_NAME"]];
							$arFields[$arUserField["FIELD_NAME"]]["del"] = $form[$arUserField["FIELD_NAME"] . "_del"];
							$arFields[$arUserField["FIELD_NAME"]]["old_id"] = $form[$arUserField["FIELD_NAME"] . "_old_id"];
						}
					}
					else
					{
						if(isset($form[$arUserField["FIELD_NAME"]]))
						{
							if(!is_array($form[$arUserField["FIELD_NAME"]]))
							{
								if(intval($form[$arUserField["FIELD_NAME"]]) > 0)
								{
									$arFields[$arUserField["FIELD_NAME"]] = intval($form[$arUserField["FIELD_NAME"]]);
								}
							}
							else
							{
								$fields = array();
								foreach($form[$arUserField["FIELD_NAME"]] as $val)
								{
									if(intval($val) > 0)
									{
										$fields[] = intval($val);
									}
								}
								$arFields[$arUserField["FIELD_NAME"]] = $fields;
							}
						}
					}
				}
				else
				{
					if(isset($files[$arUserField["FIELD_NAME"]]))
					{
						$arFile = array();
						CFile::ConvertFilesToPost($files[$arUserField["FIELD_NAME"]], $arFile);

						if(isset($form[$arUserField["FIELD_NAME"]]))
						{
							if($arUserField["MULTIPLE"] == "Y")
							{
								foreach($form[$arUserField["FIELD_NAME"]] as $key => $value)
									$arFields[$arUserField["FIELD_NAME"]][$key] = array_merge($value, $arFile[$key]);
							}
							else
							{
								$arFields[$arUserField["FIELD_NAME"]] = array_merge($form[$arUserField["FIELD_NAME"]], $arFile);
							}
						}
						else
						{
							$arFields[$arUserField["FIELD_NAME"]] = $arFile;
						}
					}
					else
					{
						if(isset($form[$arUserField["FIELD_NAME"]]))
							$arFields[$arUserField["FIELD_NAME"]] = $form[$arUserField["FIELD_NAME"]];
					}
				}
			}
		}
	}

	/**
	 * Add field for filter.
	 * @param int $entityId Entity id.
	 * @param array $arFilterFields Array for fill.
	 */
	function AdminListAddFilterFields($entityId, &$arFilterFields)
	{
		$arUserFields = $this->GetUserFields($entityId);
		foreach($arUserFields as $fieldName => $arUserField)
		{
			if($arUserField['SHOW_FILTER'] != 'N' && $arUserField['USER_TYPE']['BASE_TYPE'] != 'file')
			{
				$arFilterFields[] = 'find_' . $fieldName;
				if($arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime')
				{
					$arFilterFields[] = 'find_' . $fieldName . '_from';
					$arFilterFields[] = 'find_' . $fieldName . '_to';
				}
			}
		}
	}

	function AdminListAddFilterFieldsV2($entityId, &$arFilterFields)
	{
		$arUserFields = $this->GetUserFields($entityId, 0, $GLOBALS["lang"]);
		foreach($arUserFields as $fieldName => $arUserField)
		{
			if($arUserField['SHOW_FILTER'] != 'N' && $arUserField['USER_TYPE']['BASE_TYPE'] != 'file')
			{
				if(is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetFilterData')))
				{
					$arFilterFields[] = call_user_func_array(
						array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetFilterData'),
						array(
							$arUserField,
							array(
								'ID' => $fieldName,
								'NAME' => $arUserField['LIST_FILTER_LABEL'] ?
									$arUserField['LIST_FILTER_LABEL'] : $arUserField['FIELD_NAME'],
							),
						)
					);
				}
			}
		}
	}

	function IsNotEmpty($value)
	{
		if(is_array($value))
		{
			foreach($value as $v)
			{
				if($v <> '')
					return true;
			}

			return false;
		}
		else
		{
			if($value <> '')
				return true;
			else
				return false;
		}
	}

	/**
	 * Add value for filter.
	 * @param int $entityId Entity id.
	 * @param array $arFilter Array for fill.
	 */
	function AdminListAddFilter($entityId, &$arFilter)
	{
		$arUserFields = $this->GetUserFields($entityId);
		foreach($arUserFields as $fieldName => $arUserField)
		{
			if(
				$arUserField['SHOW_FILTER'] != 'N' &&
				$arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime'
			)
			{
				$value1 = $GLOBALS['find_' . $fieldName . '_from'];
				$value2 = $GLOBALS['find_' . $fieldName . '_to'];
				if($this->IsNotEmpty($value1) && \Bitrix\Main\Type\Date::isCorrect($value1))
				{
					$date = new \Bitrix\Main\Type\Date($value1);
					$arFilter['>=' . $fieldName] = $date;
				}
				if($this->IsNotEmpty($value2) && \Bitrix\Main\Type\Date::isCorrect($value2))
				{
					$date = new \Bitrix\Main\Type\Date($value2);
					if($arUserField['USER_TYPE_ID'] != 'date')
					{
						$date->add('+1 day');
					}
					$arFilter['<=' . $fieldName] = $date;
				}
				continue;
			}
			else
			{
				$value = $GLOBALS['find_' . $fieldName];
			}
			if(
				$arUserField['SHOW_FILTER'] != 'N'
				&& $arUserField['USER_TYPE']['BASE_TYPE'] != 'file'
				&& $this->IsNotEmpty($value)
			)
			{
				if($arUserField['SHOW_FILTER'] == 'I')
				{
					$arFilter['=' . $fieldName] = $value;
				}
				elseif($arUserField['SHOW_FILTER'] == 'S')
				{
					$arFilter['%' . $fieldName] = $value;
				}
				else
				{
					$arFilter[$fieldName] = $value;
				}
			}
		}
	}

	function AdminListAddFilterV2($entityId, &$arFilter, $filterId, $filterFields)
	{
		$filterOption = new Bitrix\Main\UI\Filter\Options($filterId);
		$filterData = $filterOption->getFilter($filterFields);

		$arUserFields = $this->GetUserFields($entityId);
		foreach($arUserFields as $fieldName => $arUserField)
		{
			if($arUserField['SHOW_FILTER'] != 'N' && $arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime')
			{
				$value1 = $filterData[$fieldName . '_from'];
				$value2 = $filterData[$fieldName . '_to'];
				if($this->IsNotEmpty($value1) && \Bitrix\Main\Type\Date::isCorrect($value1))
				{
					$date = new \Bitrix\Main\Type\Date($value1);
					$arFilter['>=' . $fieldName] = $date;
				}
				if($this->IsNotEmpty($value2) && \Bitrix\Main\Type\Date::isCorrect($value2))
				{
					$date = new \Bitrix\Main\Type\Date($value2);
					if($arUserField['USER_TYPE_ID'] != 'date')
					{
						$date->add('+1 day');
					}
					$arFilter['<=' . $fieldName] = $date;
				}
				continue;
			}
			elseif($arUserField['SHOW_FILTER'] != 'N' && $arUserField['USER_TYPE']['BASE_TYPE'] == 'int')
			{
				switch($arUserField['USER_TYPE_ID'])
				{
					case 'boolean':
						if($filterData[$fieldName] === 'Y')
							$filterData[$fieldName] = 1;
						if($filterData[$fieldName] === 'N')
							$filterData[$fieldName] = 0;
						$value = $filterData[$fieldName];
						break;
					default:
						$value = $filterData[$fieldName];
				}
			}
			else
			{
				$value = $filterData[$fieldName];
			}
			if(
				$arUserField['SHOW_FILTER'] != 'N'
				&& $arUserField['USER_TYPE']['BASE_TYPE'] != 'file'
				&& $this->IsNotEmpty($value)
			)
			{
				if($arUserField['SHOW_FILTER'] == 'I')
				{
					unset($arFilter[$fieldName]);
					$arFilter['=' . $fieldName] = $value;
				}
				elseif($arUserField['SHOW_FILTER'] == 'S')
				{
					unset($arFilter[$fieldName]);
					$arFilter['%' . $fieldName] = $value;
				}
				else
				{
					$arFilter[$fieldName] = $value;
				}
			}
		}
	}

	function AdminListPrepareFields($entity_id, &$arFields)
	{
		$arUserFields = $this->GetUserFields($entity_id);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
			if($arUserField["EDIT_IN_LIST"] != "Y")
				unset($arFields[$FIELD_NAME]);
	}

	function AdminListAddHeaders($entity_id, &$arHeaders)
	{
		$arUserFields = $this->GetUserFields($entity_id, 0, $GLOBALS["lang"]);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if($arUserField["SHOW_IN_LIST"] == "Y")
			{
				$arHeaders[] = array(
					"id" => $FIELD_NAME,
					"content" => htmlspecialcharsbx($arUserField["LIST_COLUMN_LABEL"] ? $arUserField["LIST_COLUMN_LABEL"] : $arUserField["FIELD_NAME"]),
					"sort" => $arUserField["MULTIPLE"] == "N" ? $FIELD_NAME : false,
				);
			}
		}
	}

	function AddUserFields($entity_id, $arRes, &$row)
	{
		$arUserFields = $this->GetUserFields($entity_id);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
			if($arUserField["SHOW_IN_LIST"] == "Y" && array_key_exists($FIELD_NAME, $arRes))
				$this->AddUserField($arUserField, $arRes[$FIELD_NAME], $row);
	}

	function AddFindFields($entity_id, &$arFindFields)
	{
		$arUserFields = $this->GetUserFields($entity_id, 0, $GLOBALS["lang"]);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if($arUserField["SHOW_FILTER"] != "N" && $arUserField["USER_TYPE"]["BASE_TYPE"] != "file")
			{
				if($arUserField["USER_TYPE"] && is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "getfilterhtml")))
				{
					if($arUserField["LIST_FILTER_LABEL"])
					{
						$arFindFields[$FIELD_NAME] = htmlspecialcharsbx($arUserField["LIST_FILTER_LABEL"]);
					}
					else
					{
						$arFindFields[$FIELD_NAME] = $arUserField["FIELD_NAME"];
					}
				}
			}
		}
	}

	function AdminListShowFilter($entity_id)
	{
		$arUserFields = $this->GetUserFields($entity_id, 0, $GLOBALS["lang"]);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if($arUserField["SHOW_FILTER"] != "N" && $arUserField["USER_TYPE"]["BASE_TYPE"] != "file")
			{
				echo $this->GetFilterHTML($arUserField, "find_" . $FIELD_NAME, $GLOBALS["find_" . $FIELD_NAME]);
			}
		}
	}

	function ShowScript()
	{
		global $APPLICATION;

		$APPLICATION->AddHeadScript("/bitrix/js/main/usertype.js");

		return "";
	}

	function GetEditFormHTML($bVarsFromForm, $form_value, $arUserField)
	{
		global $APPLICATION;
		global $adminPage, $adminSidePanelHelper;

		if($arUserField["USER_TYPE"])
		{
			if($this->GetRights($arUserField["ENTITY_ID"]) >= "W")
			{
				$selfFolderUrl = $adminPage->getSelfFolderUrl();
				$userFieldUrl = $selfFolderUrl . "userfield_edit.php?lang=" . LANGUAGE_ID . "&ID=" . $arUserField["ID"];
				$userFieldUrl = $adminSidePanelHelper->editUrlToPublicPage($userFieldUrl);
				$edit_link = ($arUserField["HELP_MESSAGE"] ? htmlspecialcharsex($arUserField["HELP_MESSAGE"]) . '<br>' : '') . '<a href="' . htmlspecialcharsbx($userFieldUrl . '&back_url=' . urlencode($APPLICATION->GetCurPageParam("", array("bxpublic")) . '&tabControl_active_tab=user_fields_tab')) . '">' . htmlspecialcharsex(GetMessage("MAIN_EDIT")) . '</a>';
			}
			else
			{
				$edit_link = '';
			}

			$hintHTML = '<span id="hint_' . $arUserField["FIELD_NAME"] . '"></span><script>BX.hint_replace(BX(\'hint_' . $arUserField["FIELD_NAME"] . '\'), \'' . CUtil::JSEscape($edit_link) . '\');</script>&nbsp;';

			if($arUserField["MANDATORY"] == "Y")
				$strLabelHTML = $hintHTML . '<span class="adm-required-field">' . htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"] ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"]) . '</span>' . ':';
			else
				$strLabelHTML = $hintHTML . htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"] ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"]) . ':';

			if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "geteditformhtml")))
			{
				$js = $this->ShowScript();

				if(!$bVarsFromForm)
					$form_value = $arUserField["VALUE"];
				elseif($arUserField["USER_TYPE"]["BASE_TYPE"] == "file")
					$form_value = $GLOBALS[$arUserField["FIELD_NAME"] . "_old_id"];
				elseif($arUserField["EDIT_IN_LIST"] == "N")
					$form_value = $arUserField["VALUE"];

				if(
					$arUserField["MULTIPLE"] === "N"
					||
					!empty($arUserField['USER_TYPE']['USE_FIELD_COMPONENT'])
				)
				{
					$valign = "";
					$rowClass = "";
					$html = call_user_func_array(
						array($arUserField["USER_TYPE"]["CLASS_NAME"], "geteditformhtml"),
						array(
							$arUserField,
							array(
								"NAME" => $arUserField["FIELD_NAME"],
								"VALUE" => (is_array($form_value) ? $form_value : htmlspecialcharsbx($form_value)),
								"VALIGN" => &$valign,
								"ROWCLASS" => &$rowClass
							),
							$bVarsFromForm
						)
					);
					return '<tr' . ($rowClass != '' ? ' class="' . $rowClass . '"' : '') . '><td' . ($valign <> 'middle' ? ' class="adm-detail-valign-top"' : '') . ' width="40%">' . $strLabelHTML . '</td><td width="60%">' . $html . '</td></tr>' . $js;
				}
				elseif(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "geteditformhtmlmulty")))
				{
					if(!is_array($form_value))
					{
						$form_value = array();
					}
					foreach($form_value as $key => $value)
					{
						if(!is_array($value))
						{
							$form_value[$key] = htmlspecialcharsbx($value);
						}
					}

					$rowClass = "";
					$html = call_user_func_array(
						array($arUserField["USER_TYPE"]["CLASS_NAME"], "geteditformhtmlmulty"),
						array(
							$arUserField,
							array(
								"NAME" => $arUserField["FIELD_NAME"] . "[]",
								"VALUE" => $form_value,
								"ROWCLASS" => &$rowClass
							),
							$bVarsFromForm
						)
					);
					return '<tr' . ($rowClass != '' ? ' class="' . $rowClass . '"' : '') . '><td class="adm-detail-valign-top">' . $strLabelHTML . '</td><td>' . $html . '</td></tr>' . $js;
				}
				else
				{
					if(!is_array($form_value))
					{
						$form_value = array();
					}
					$html = "";
					$i = -1;
					foreach($form_value as $i => $value)
					{

						if(
							(is_array($value) && (implode("", $value) <> ''))
							|| ((!is_array($value)) && ($value <> ''))
						)
						{
							$html .= '<tr><td>' . call_user_func_array(
									array($arUserField["USER_TYPE"]["CLASS_NAME"], "geteditformhtml"),
									array(
										$arUserField,
										array(
											"NAME" => $arUserField["FIELD_NAME"] . "[" . $i . "]",
											"VALUE" => (is_array($value) ? $value : htmlspecialcharsbx($value)),
										),
										$bVarsFromForm
									)
								) . '</td></tr>';
						}
					}
					//Add multiple values support
					$rowClass = "";
					$FIELD_NAME_X = str_replace('_', 'x', $arUserField["FIELD_NAME"]);
					$fieldHtml = call_user_func_array(
						array($arUserField["USER_TYPE"]["CLASS_NAME"], "geteditformhtml"),
						array(
							$arUserField,
							array(
								"NAME" => $arUserField["FIELD_NAME"] . "[" . ($i + 1) . "]",
								"VALUE" => "",
								"ROWCLASS" => &$rowClass
							),
							$bVarsFromForm
						)
					);
					return '<tr' . ($rowClass != '' ? ' class="' . $rowClass . '"' : '') . '><td class="adm-detail-valign-top">' . $strLabelHTML . '</td><td>' .
						'<table id="table_' . $arUserField["FIELD_NAME"] . '">' . $html . '<tr><td>' . $fieldHtml . '</td></tr>' .
						'<tr><td style="padding-top: 6px;"><input type="button" value="' . GetMessage("USER_TYPE_PROP_ADD") . '" onClick="addNewRow(\'table_' . $arUserField["FIELD_NAME"] . '\', \'' . $FIELD_NAME_X . '|' . $arUserField["FIELD_NAME"] . '|' . $arUserField["FIELD_NAME"] . '_old_id\')"></td></tr>' .
						"<script type=\"text/javascript\">BX.addCustomEvent('onAutoSaveRestore', function(ob, data) {for (var i in data){if (i.substring(0," . (mb_strlen($arUserField['FIELD_NAME']) + 1) . ")=='" . CUtil::JSEscape($arUserField['FIELD_NAME']) . "['){" .
						'addNewRow(\'table_' . $arUserField["FIELD_NAME"] . '\', \'' . $FIELD_NAME_X . '|' . $arUserField["FIELD_NAME"] . '|' . $arUserField["FIELD_NAME"] . '_old_id\')' .
						"}}})</script>" .
						'</table>' .
						'</td></tr>' . $js;
				}
			}
		}
		return '';
	}

	function GetFilterHTML($arUserField, $filter_name, $filter_value)
	{
		if($arUserField["USER_TYPE"])
		{
			if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "getfilterhtml")))
			{
				$html = call_user_func_array(
						array($arUserField["USER_TYPE"]["CLASS_NAME"], "getfilterhtml"),
						array(
							$arUserField,
							array(
								"NAME" => $filter_name,
								"VALUE" => htmlspecialcharsex($filter_value),
							),
						)
					) . CAdminCalendar::ShowScript();
				return '<tr><td>' . htmlspecialcharsbx($arUserField["LIST_FILTER_LABEL"] ? $arUserField["LIST_FILTER_LABEL"] : $arUserField["FIELD_NAME"]) . ':</td><td>' . $html . '</td></tr>';
			}
		}
		return '';
	}

	/**
	 * @param $arUserField
	 * @param $value
	 * @param CAdminListRow $row
	 */
	function AddUserField($arUserField, $value, &$row)
	{
		if($arUserField["USER_TYPE"])
		{
			$js = $this->ShowScript();
			$useFieldComponent = !empty($arUserField['USER_TYPE']['USE_FIELD_COMPONENT']);

			if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtml")))
			{
				if($arUserField["MULTIPLE"] == "N")
				{
					$html = call_user_func_array(
						array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtml"),
						array(
							$arUserField,
							array(
								"NAME" => "FIELDS[" . $row->id . "][" . $arUserField["FIELD_NAME"] . "]",
								"VALUE" => ($useFieldComponent ? $value : htmlspecialcharsbx($value))
							),
						)
					);
					if($html === '')
						$html = '&nbsp;';
					$row->AddViewField($arUserField["FIELD_NAME"], $html . $js . CAdminCalendar::ShowScript());
				}
				elseif(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtmlmulty")))
				{
					if(is_array($value))
						$form_value = $value;
					else
						$form_value = unserialize($value, ['allowed_classes' => false]);

					if(!is_array($form_value))
						$form_value = array();

					foreach($form_value as $key => $val)
						$form_value[$key] = ($useFieldComponent ? $val : htmlspecialcharsbx($val));

					$html = call_user_func_array(
						array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtmlmulty"),
						array(
							$arUserField,
							array(
								"NAME" => "FIELDS[" . $row->id . "][" . $arUserField["FIELD_NAME"] . "]" . "[]",
								"VALUE" => $form_value,
							),
						)
					);
					if($html == '')
						$html = '&nbsp;';
					$row->AddViewField($arUserField["FIELD_NAME"], $html . $js . CAdminCalendar::ShowScript());
				}
				else
				{
					$html = "";

					if(is_array($value))
						$form_value = $value;
					else
						$form_value = $value <> '' ? unserialize($value, ['allowed_classes' => false]) : false;

					if(!is_array($form_value))
						$form_value = array();

					foreach($form_value as $i => $val)
					{
						if($html != "")
							$html .= " / ";
						$html .= call_user_func_array(
							array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtml"),
							array(
								$arUserField,
								array(
									"NAME" => "FIELDS[" . $row->id . "][" . $arUserField["FIELD_NAME"] . "]" . "[" . $i . "]",
									"VALUE" => ($useFieldComponent ? $val : htmlspecialcharsbx($val)),
								),
							)
						);
					}
					if($html == '')
						$html = '&nbsp;';
					$row->AddViewField($arUserField["FIELD_NAME"], $html . $js . CAdminCalendar::ShowScript());
				}
			}
			if($arUserField["EDIT_IN_LIST"] == "Y" && is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistedithtml")))
			{
				if(!$row->bEditMode)
				{
					// put dummy
					$row->AddEditField($arUserField["FIELD_NAME"], "&nbsp;");
				}
				elseif($arUserField["MULTIPLE"] == "N")
				{
					$html = call_user_func_array(
						array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistedithtml"),
						array(
							$arUserField,
							array(
								"NAME" => "FIELDS[" . $row->id . "][" . $arUserField["FIELD_NAME"] . "]",
								"VALUE" => ($useFieldComponent ? $value : htmlspecialcharsbx($value)),
							),
						)
					);
					if($html == '')
						$html = '&nbsp;';
					$row->AddEditField($arUserField["FIELD_NAME"], $html . $js . CAdminCalendar::ShowScript());
				}
				elseif(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistedithtmlmulty")))
				{
					if(is_array($value))
						$form_value = $value;
					else
						$form_value = $value <> '' ? unserialize($value, ['allowed_classes' => false]) : false;

					if(!is_array($form_value))
						$form_value = array();

					foreach($form_value as $key => $val)
						$form_value[$key] = ($useFieldComponent ? $val : htmlspecialcharsbx($val));

					$html = call_user_func_array(
						array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistedithtmlmulty"),
						array(
							$arUserField,
							array(
								"NAME" => "FIELDS[" . $row->id . "][" . $arUserField["FIELD_NAME"] . "][]",
								"VALUE" => $form_value,
							),
						)
					);
					if($html == '')
						$html = '&nbsp;';
					$row->AddEditField($arUserField["FIELD_NAME"], $html . $js . CAdminCalendar::ShowScript());
				}
				else
				{
					$html = "<table id=\"table_" . $arUserField["FIELD_NAME"] . "_" . $row->id . "\">";
					if(is_array($value))
						$form_value = $value;
					else
						$form_value = unserialize($value, ['allowed_classes' => false]);

					if(!is_array($form_value))
						$form_value = array();

					$i = -1;
					foreach($form_value as $i => $val)
					{
						$html .= '<tr><td>' . call_user_func_array(
								array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistedithtml"),
								array(
									$arUserField,
									array(
										"NAME" => "FIELDS[" . $row->id . "][" . $arUserField["FIELD_NAME"] . "]" . "[" . $i . "]",
										"VALUE" => ($useFieldComponent ? $val : htmlspecialcharsbx($val)),
									),
								)
							) . '</td></tr>';
					}
					$html .= '<tr><td>' . call_user_func_array(
							array($arUserField["USER_TYPE"]["CLASS_NAME"], "getadminlistedithtml"),
							array(
								$arUserField,
								array(
									"NAME" => "FIELDS[" . $row->id . "][" . $arUserField["FIELD_NAME"] . "]" . "[" . ($i + 1) . "]",
									"VALUE" => "",
								),
							)
						) . '</td></tr>';
					$html .= '<tr><td><input type="button" value="' . GetMessage("USER_TYPE_PROP_ADD") . '" onClick="addNewRow(\'table_' . $arUserField["FIELD_NAME"] . '_' . $row->id . '\', \'FIELDS\\\\[' . $row->id . '\\\\]\\\\[' . $arUserField["FIELD_NAME"] . '\\\\]\')"></td></tr>' .
						'</table>';
					$row->AddEditField($arUserField["FIELD_NAME"], $html . $js . CAdminCalendar::ShowScript());
				}
			}
		}
	}

	function getListView($userfield, $value)
	{
		$html = '';

		if(is_callable(array($userfield["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtml")))
		{
			if($userfield["MULTIPLE"] == "N")
			{
				$html = call_user_func_array(
					array($userfield["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtml"),
					array(
						$userfield,
						array(
							"VALUE" => htmlspecialcharsbx($value),
						)
					)
				);
			}
			elseif(is_callable(array($userfield["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtmlmulty")))
			{
				$form_value = is_array($value) ? $value : unserialize($value, ['allowed_classes' => false]);

				if(!is_array($form_value))
					$form_value = array();

				foreach($form_value as $key => $val)
					$form_value[$key] = htmlspecialcharsbx($val);

				$html = call_user_func_array(
					array($userfield["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtmlmulty"),
					array(
						$userfield,
						array(
							"VALUE" => $form_value,
						),
					)
				);
			}
			else
			{
				if(is_array($value))
					$form_value = $value;
				else
					$form_value = $value <> '' ? unserialize($value, ['allowed_classes' => false]) : false;

				if(!is_array($form_value))
					$form_value = array();

				foreach($form_value as $val)
				{
					if($html != "")
						$html .= " / ";

					$html .= call_user_func_array(
						array($userfield["USER_TYPE"]["CLASS_NAME"], "getadminlistviewhtml"),
						array(
							$userfield,
							array(
								"VALUE" => htmlspecialcharsbx($val),
							)
						)
					);
				}
			}
		}

		return $html <> ''? $html : '&nbsp;';
	}

	function CallUserTypeComponent($componentName, $componentTemplate, $arUserField, $arAdditionalParameters = array())
	{
		global $APPLICATION;
		$arParams = $arAdditionalParameters;
		$arParams['arUserField'] = $arUserField;
		ob_start();
		$APPLICATION->IncludeComponent(
			$componentName,
			$componentTemplate,
			$arParams,
			null,
			array("HIDE_ICONS" => "Y")
		);
		return ob_get_clean();
	}

	/**
	 * @param array $userField
	 * @param array|null $additionalParameters
	 * @return string|null
	 */
	public function renderField(array $userField, ?array $additionalParameters = array()): ?string
	{
		$userType = $this->getUserType($userField['USER_TYPE_ID']);
		if(!empty($userType['CLASS_NAME']) && is_callable([$userType['CLASS_NAME'], 'renderField']))
		{
			return call_user_func([$userType['CLASS_NAME'], 'renderField'], $userField, $additionalParameters);
		}
		return null;
	}

	function GetPublicView($arUserField, $arAdditionalParameters = array())
	{
		$event = new \Bitrix\Main\Event("main", "onBeforeGetPublicView", array(&$arUserField, &$arAdditionalParameters));
		$event->send();

		$arType = $this->GetUserType($arUserField["USER_TYPE_ID"]);

		$html = null;
		$event = new \Bitrix\Main\Event("main", "onGetPublicView", array($arUserField, $arAdditionalParameters));
		$event->send();
		foreach($event->getResults() as $evenResult)
		{
			if($evenResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
			{
				$html = $evenResult->getParameters();
				break;
			}
		}

		if($html !== null)
		{
			//All done
		}
		elseif($arUserField["VIEW_CALLBACK"] && is_callable($arUserField['VIEW_CALLBACK']))
		{
			$html = call_user_func_array($arUserField["VIEW_CALLBACK"], array(
				$arUserField,
				$arAdditionalParameters
			));
		}
		elseif($arType && $arType["VIEW_CALLBACK"] && is_callable($arType['VIEW_CALLBACK']))
		{
			$html = call_user_func_array($arType["VIEW_CALLBACK"], array(
				$arUserField,
				$arAdditionalParameters
			));
		}
		elseif($arUserField["VIEW_COMPONENT_NAME"])
		{
			$html = $this->CallUserTypeComponent(
				$arUserField["VIEW_COMPONENT_NAME"],
				$arUserField["VIEW_COMPONENT_TEMPLATE"],
				$arUserField,
				$arAdditionalParameters
			);
		}
		elseif($arType && $arType["VIEW_COMPONENT_NAME"])
		{
			$html = $this->CallUserTypeComponent(
				$arType["VIEW_COMPONENT_NAME"],
				$arType["VIEW_COMPONENT_TEMPLATE"],
				$arUserField,
				$arAdditionalParameters
			);
		}
		else
		{
			$html = $this->CallUserTypeComponent(
				"bitrix:system.field.view",
				$arUserField["USER_TYPE_ID"],
				$arUserField,
				$arAdditionalParameters
			);
		}

		$event = new \Bitrix\Main\Event("main", "onAfterGetPublicView", array($arUserField, $arAdditionalParameters, &$html));
		$event->send();

		return $html;
	}

	public function getPublicText($userField)
	{
		$userType = $this->getUserType($userField['USER_TYPE_ID']);
		if(!empty($userType['CLASS_NAME']) && is_callable(array($userType['CLASS_NAME'], 'getPublicText')))
			return call_user_func_array(array($userType['CLASS_NAME'], 'getPublicText'), array($userField));

		return join(', ', array_map(function($v)
		{
			return is_null($v) || is_scalar($v) ? (string)$v : '';
		}, (array)$userField['VALUE']));
	}

	function GetPublicEdit($arUserField, $arAdditionalParameters = array())
	{
		$event = new \Bitrix\Main\Event("main", "onBeforeGetPublicEdit", array(&$arUserField, &$arAdditionalParameters));
		$event->send();

		$arType = $this->GetUserType($arUserField["USER_TYPE_ID"]);

		$html = null;
		$event = new \Bitrix\Main\Event("main", "onGetPublicEdit", array($arUserField, $arAdditionalParameters));
		$event->send();
		foreach($event->getResults() as $evenResult)
		{
			if($evenResult->getType() == \Bitrix\Main\EventResult::SUCCESS)
			{
				$html = $evenResult->getParameters();
				break;
			}
		}

		if($html !== null)
		{
			//All done
		}
		elseif($arUserField["EDIT_CALLBACK"] && is_callable($arUserField['EDIT_CALLBACK']))
		{
			$html = call_user_func_array($arUserField["EDIT_CALLBACK"], array(
				$arUserField,
				$arAdditionalParameters
			));
		}
		elseif($arType && $arType["EDIT_CALLBACK"] && is_callable($arType['EDIT_CALLBACK']))
		{
			$html = call_user_func_array($arType["EDIT_CALLBACK"], array(
				$arUserField,
				$arAdditionalParameters
			));
		}
		elseif($arUserField["EDIT_COMPONENT_NAME"])
		{
			$html = $this->CallUserTypeComponent(
				$arUserField["EDIT_COMPONENT_NAME"],
				$arUserField["EDIT_COMPONENT_TEMPLATE"],
				$arUserField,
				$arAdditionalParameters
			);
		}
		elseif($arType && $arType["EDIT_COMPONENT_NAME"])
		{
			$html = $this->CallUserTypeComponent(
				$arType["EDIT_COMPONENT_NAME"],
				$arType["EDIT_COMPONENT_TEMPLATE"],
				$arUserField,
				$arAdditionalParameters
			);
		}
		else
		{
			$html = $this->CallUserTypeComponent(
				"bitrix:system.field.edit",
				$arUserField["USER_TYPE_ID"],
				$arUserField,
				$arAdditionalParameters
			);
		}

		$event = new \Bitrix\Main\Event("main", "onAfterGetPublicEdit", array($arUserField, $arAdditionalParameters, &$html));
		$event->send();

		return $html;
	}

	function GetSettingsHTML($arUserField, $bVarsFromForm = false)
	{
		if(!is_array($arUserField)) // New field
		{
			if($arType = $this->GetUserType($arUserField))
				if(is_callable(array($arType["CLASS_NAME"], "getsettingshtml")))
					return call_user_func_array(array($arType["CLASS_NAME"], "getsettingshtml"), array(false, array("NAME" => "SETTINGS"), $bVarsFromForm));
		}
		else
		{
			if(!is_array($arUserField["SETTINGS"]) || empty($arUserField["SETTINGS"]))
				$arUserField["SETTINGS"] = $this->PrepareSettings(0, $arUserField);

			if($arType = $this->GetUserType($arUserField["USER_TYPE_ID"]))
				if(is_callable(array($arType["CLASS_NAME"], "getsettingshtml")))
					return call_user_func_array(array($arType["CLASS_NAME"], "getsettingshtml"), array($arUserField, array("NAME" => "SETTINGS"), $bVarsFromForm));
		}
		return null;
	}

	/**
	 * @param       $entity_id
	 * @param       $ID
	 * @param       $arFields
	 * @param bool $user_id False means current user id.
	 * @param bool $checkRequired Whether to check required fields.
	 * @param array $requiredFields Conditionally required fields.
	 * @return bool
	 */
	function CheckFields($entity_id, $ID, $arFields, $user_id = false, $checkRequired = true, array $requiredFields = null)
	{
		global $APPLICATION;
		$requiredFieldMap = is_array($requiredFields) ? array_fill_keys($requiredFields, true) : null;
		$aMsg = array();
		//1 Get user typed fields list for entity
		$arUserFields = $this->GetUserFields($entity_id, $ID, LANGUAGE_ID);
		//2 For each field
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$enableRequiredFieldCheck = $arUserField["MANDATORY"] === "Y"
				? $checkRequired : ($requiredFieldMap && isset($requiredFieldMap[$FIELD_NAME]));

			//common Check for all fields
			$isSingleValue = ($arUserField["MULTIPLE"] === "N");
			if (
				$enableRequiredFieldCheck
				&& (
					(isset($ID) && $ID <= 0)
					|| array_key_exists($FIELD_NAME, $arFields)
				)
			)
			{
				$EDIT_FORM_LABEL = $arUserField["EDIT_FORM_LABEL"] <> '' ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"];

				if($arUserField["USER_TYPE"]["BASE_TYPE"] === "file")
				{
					$isNewFilePresent = false;
					$files = [];
					if(is_array($arUserField["VALUE"]))
					{
						$files = array_flip($arUserField["VALUE"]);
					}
					elseif($arUserField["VALUE"] > 0)
					{
						$files = [$arUserField["VALUE"] => 0];
					}
					elseif(is_numeric($arFields[$FIELD_NAME]))
					{
						$files = [$arFields[$FIELD_NAME] => 0];
					}

					if ($isSingleValue)
					{
						$value = $arFields[$FIELD_NAME];
						if(is_array($value) && array_key_exists("tmp_name", $value))
						{
							if(array_key_exists("del", $value) && $value["del"])
							{
								unset($files[$value["old_id"]]);
							}
							elseif(array_key_exists("size", $value) && $value["size"] > 0)
							{
								$isNewFilePresent = true;
							}
						}
						elseif($value > 0)
						{
							$isNewFilePresent = true;
							$files[$value] = $value;
						}
					}
					else
					{
						if(is_array($arFields[$FIELD_NAME]))
						{
							foreach($arFields[$FIELD_NAME] as $value)
							{
								if(is_array($value) && array_key_exists("tmp_name", $value))
								{
									if(array_key_exists("del", $value) && $value["del"])
									{
										unset($files[$value["old_id"]]);
									}
									elseif(array_key_exists("size", $value) && $value["size"] > 0)
									{
										$isNewFilePresent = true;
									}
								}
								elseif($value > 0)
								{
									$isNewFilePresent = true;
									$files[$value] = $value;
								}
							}
						}
					}

					if(!$isNewFilePresent && empty($files))
					{
						$aMsg[] = array("id" => $FIELD_NAME, "text" => str_replace("#FIELD_NAME#", $EDIT_FORM_LABEL, GetMessage("USER_TYPE_FIELD_VALUE_IS_MISSING")));
					}
				}
				elseif ($isSingleValue)
				{
					if((string)$arFields[$FIELD_NAME] === '')
					{
						$aMsg[] = array("id" => $FIELD_NAME, "text" => str_replace("#FIELD_NAME#", $EDIT_FORM_LABEL, GetMessage("USER_TYPE_FIELD_VALUE_IS_MISSING")));
					}
				}
				else
				{
					if(!is_array($arFields[$FIELD_NAME]))
					{
						$aMsg[] = array("id" => $FIELD_NAME, "text" => str_replace("#FIELD_NAME#", $EDIT_FORM_LABEL, GetMessage("USER_TYPE_FIELD_VALUE_IS_MISSING")));
					}
					else
					{
						$bFound = false;
						foreach($arFields[$FIELD_NAME] as $value)
						{
							if(
								(is_array($value) && (implode("", $value) <> ''))
								|| ((!is_array($value)) && ($value <> ''))
							)
							{
								$bFound = true;
								break;
							}
						}
						if(!$bFound)
						{
							$aMsg[] = array("id" => $FIELD_NAME, "text" => str_replace("#FIELD_NAME#", $EDIT_FORM_LABEL, GetMessage("USER_TYPE_FIELD_VALUE_IS_MISSING")));
						}
					}
				}
			}
			//identify user type
			if($arUserField["USER_TYPE"])
			{
				$CLASS_NAME = $arUserField["USER_TYPE"]["CLASS_NAME"];
				if(array_key_exists($FIELD_NAME, $arFields)	&& is_callable(array($CLASS_NAME, "checkfields")))
				{
					if($isSingleValue)
					{
						if (!($arFields[$FIELD_NAME] instanceof SqlExpression))
						{
							//apply appropriate check function
							$ar = call_user_func_array(
								array($CLASS_NAME, "checkfields"),
								array($arUserField, $arFields[$FIELD_NAME], $user_id)
							);
							$aMsg = array_merge($aMsg, $ar);
						}
					}
					elseif(is_array($arFields[$FIELD_NAME]))
					{
						foreach($arFields[$FIELD_NAME] as $value)
						{
							if(!empty($value))
							{
								if ($value instanceof SqlExpression)
								{
									$aMsg[] = [
										'id' => $FIELD_NAME,
										'text' => "Multiple field \"#FIELD_NAME#\" can't handle SqlExpression because of serialized uts cache"
									];
								}
								else
								{
									//apply appropriate check function
									$ar = call_user_func_array(
										array($CLASS_NAME, "checkfields"),
										array($arUserField, $value, $user_id)
									);
									$aMsg = array_merge($aMsg, $ar);
								}
							}
						}
					}
				}
			}
		}
		//3 Return succsess/fail flag
		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	/**
	 * Replacement for CheckFields, if you are already have fetched old data
	 *
	 * @param $entity_id
	 * @param $oldData
	 * @param $arFields
	 *
	 * @return bool
	 */
	function CheckFieldsWithOldData($entity_id, $oldData, $arFields)
	{
		global $APPLICATION;

		$aMsg = array();

		//1 Get user typed fields list for entity
		$arUserFields = $this->getUserFieldsWithReadyData($entity_id, $oldData, LANGUAGE_ID);

		//2 For each field
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			//identify user type
			if($arUserField["USER_TYPE"])
			{
				$CLASS_NAME = $arUserField["USER_TYPE"]["CLASS_NAME"];
				$EDIT_FORM_LABEL = $arUserField["EDIT_FORM_LABEL"] <> '' ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"];

				if(array_key_exists($FIELD_NAME, $arFields) && is_callable(array($CLASS_NAME, "checkfields")))
				{
					// check required values
					if($arUserField["MANDATORY"] === "Y")
					{
						if($arUserField["USER_TYPE"]["BASE_TYPE"] === "file")
						{
							$isNewFilePresent = false;
							$files = [];
							if(is_array($arUserField["VALUE"]))
							{
								$files = array_flip($arUserField["VALUE"]);
							}
							elseif($arUserField["VALUE"] > 0)
							{
								$files = array($arUserField["VALUE"] => 0);
							}
							elseif(is_numeric($arFields[$FIELD_NAME]))
							{
								$files = array($arFields[$FIELD_NAME] => 0);
							}

							if($arUserField["MULTIPLE"] === "N")
							{
								$value = $arFields[$FIELD_NAME];
								if(is_array($value) && array_key_exists("tmp_name", $value))
								{
									if(array_key_exists("del", $value) && $value["del"])
									{
										unset($files[$value["old_id"]]);
									}
									elseif(array_key_exists("size", $value) && $value["size"] > 0)
									{
										$isNewFilePresent = true;
									}
								}
								elseif ($value > 0)
								{
									$isNewFilePresent = true;
									$files[$value] = $value;
								}
							}
							else
							{
								if(is_array($arFields[$FIELD_NAME]))
								{
									foreach($arFields[$FIELD_NAME] as $value)
									{
										if(is_array($value) && array_key_exists("tmp_name", $value))
										{
											if(array_key_exists("del", $value) && $value["del"])
											{
												unset($files[$value["old_id"]]);
											}
											elseif(array_key_exists("size", $value) && $value["size"] > 0)
											{
												$isNewFilePresent = true;
											}
										}
										elseif ($value > 0)
										{
											$isNewFilePresent = true;
											$files[$value] = $value;
										}
									}
								}
							}

							if(!$isNewFilePresent && empty($files))
							{
								$aMsg[] = array("id" => $FIELD_NAME, "text" => str_replace("#FIELD_NAME#", $EDIT_FORM_LABEL, GetMessage("USER_TYPE_FIELD_VALUE_IS_MISSING")));
							}
						}
						elseif($arUserField["MULTIPLE"] == "N")
						{
							if($arFields[$FIELD_NAME] == '')
							{
								$aMsg[] = array("id" => $FIELD_NAME, "text" => str_replace("#FIELD_NAME#", $EDIT_FORM_LABEL, GetMessage("USER_TYPE_FIELD_VALUE_IS_MISSING")));
							}
						}
						else
						{
							if(!is_array($arFields[$FIELD_NAME]))
							{
								$aMsg[] = array("id" => $FIELD_NAME, "text" => str_replace("#FIELD_NAME#", $EDIT_FORM_LABEL, GetMessage("USER_TYPE_FIELD_VALUE_IS_MISSING")));
							}
							else
							{
								$bFound = false;
								foreach($arFields[$FIELD_NAME] as $value)
								{
									if(
										(is_array($value) && (implode("", $value) <> ''))
										|| ((!is_array($value)) && ($value <> ''))
									)
									{
										$bFound = true;
										break;
									}
								}
								if(!$bFound)
								{
									$aMsg[] = array("id" => $FIELD_NAME, "text" => str_replace("#FIELD_NAME#", $EDIT_FORM_LABEL, GetMessage("USER_TYPE_FIELD_VALUE_IS_MISSING")));
								}
							}
						}
					}

					// check regular values
					if($arUserField["MULTIPLE"] == "N")
					{
						//apply appropriate check function
						$ar = call_user_func_array(
							array($CLASS_NAME, "checkfields"),
							array($arUserField, $arFields[$FIELD_NAME])
						);
						$aMsg = array_merge($aMsg, $ar);
					}
					elseif(is_array($arFields[$FIELD_NAME]))
					{
						foreach($arFields[$FIELD_NAME] as $value)
						{
							if(!empty($value))
							{
								//apply appropriate check function
								$ar = call_user_func_array(
									array($CLASS_NAME, "checkfields"),
									array($arUserField, $value)
								);
								$aMsg = array_merge($aMsg, $ar);
							}
						}
					}
				}
			}
		}

		//3 Return succsess/fail flag
		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		return true;
	}

	function Update($entity_id, $ID, $arFields, $user_id = false)
	{
		global $DB;

		$entity_id = preg_replace("/[^0-9A-Z_]+/", "", $entity_id);

		$result = $this->updateUserFieldValuesByEvent($entity_id, (int)$ID, $arFields);
		if($result !== null)
		{
			return $result;
		}

		$result = false;

		$arUpdate = array();
		$arBinds = array();
		$arInsert = array();
		$arInsertType = array();
		$arDelete = array();
		$arUserFields = $this->GetUserFields($entity_id, $ID, false, $user_id);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if(array_key_exists($FIELD_NAME, $arFields))
			{
				$arUserField['VALUE_ID'] = $ID;
				if($arUserField["MULTIPLE"] == "N")
				{
					if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesave")))
						$arFields[$FIELD_NAME] = call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesave"), array($arUserField, $arFields[$FIELD_NAME], $user_id));

					if((string)$arFields[$FIELD_NAME] !== '')
						$arUpdate[$FIELD_NAME] = $arFields[$FIELD_NAME];
					else
						$arUpdate[$FIELD_NAME] = false;
				}
				elseif(is_array($arFields[$FIELD_NAME]))
				{
					$arInsert[$arUserField["ID"]] = array();
					$arInsertType[$arUserField["ID"]] = $arUserField["USER_TYPE"];
					$arInsertType[$arUserField['ID']]['FIELD_NAME'] = $arUserField['FIELD_NAME'];

					if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesaveall")))
					{
						$arInsert[$arUserField["ID"]] = call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesaveall"), array($arUserField, $arFields[$FIELD_NAME], $user_id));
					}
					else
					{
						foreach($arFields[$FIELD_NAME] as $value)
						{
							if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesave")))
								$value = call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesave"), array($arUserField, $value, $user_id));

							if($value <> '')
							{
								switch($arInsertType[$arUserField["ID"]]["BASE_TYPE"])
								{
									case "int":
									case "file":
									case "enum":
										$value = intval($value);
										break;
									case "double":
										$value = doubleval($value);
										if(!is_finite($value))
										{
											$value = 0;
										}
										break;
									case "string":
										$value = (string) $value;
										break;
								}
								$arInsert[$arUserField["ID"]][] = $value;
							}
						}
					}

					if($arUserField['USER_TYPE_ID'] == 'datetime')
					{
						$serialized = \Bitrix\Main\UserFieldTable::serializeMultipleDatetime($arInsert[$arUserField["ID"]]);
					}
					elseif($arUserField['USER_TYPE_ID'] == 'date')
					{
						$serialized = \Bitrix\Main\UserFieldTable::serializeMultipleDate($arInsert[$arUserField["ID"]]);
					}
					else
					{
						$serialized = serialize($arInsert[$arUserField["ID"]]);
					}

					$arBinds[$FIELD_NAME] = $arUpdate[$FIELD_NAME] = $serialized;

					$arDelete[$arUserField["ID"]] = true;
				}
			}
		}

		$lower_entity_id = mb_strtolower($entity_id);

		if(!empty($arUpdate))
			$strUpdate = $DB->PrepareUpdate("b_uts_" . $lower_entity_id, $arUpdate);
		else
			return $result;

		if($strUpdate <> '')
		{
			$result = true;
			$rs = $DB->QueryBind("UPDATE b_uts_" . $lower_entity_id . " SET " . $strUpdate . " WHERE VALUE_ID = " . intval($ID), $arBinds);
			$rows = $rs->AffectedRowsCount();
		}
		else
		{
			$rows = 0;
		}

		if(intval($rows) <= 0)
		{
			$rs = $DB->Query("SELECT 'x' FROM b_uts_" . $lower_entity_id . " WHERE VALUE_ID = " . intval($ID), false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
			if($rs->Fetch())
				$rows = 1;
		}

		if($rows <= 0)
		{
			$arUpdate["ID"] = $arUpdate["VALUE_ID"] = $ID;
			$DB->Add("b_uts_" . $lower_entity_id, $arUpdate, array_keys($arBinds));
		}
		else
		{
			foreach($arDelete as $key => $value)
			{
				$DB->Query("DELETE from b_utm_" . $lower_entity_id . " WHERE FIELD_ID = " . intval($key) . " AND VALUE_ID = " . intval($ID), false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
			}
		}

		foreach($arInsert as $FieldId => $arField)
		{
			switch($arInsertType[$FieldId]["BASE_TYPE"])
			{
				case "int":
				case "file":
				case "enum":
					$COLUMN = "VALUE_INT";
					break;
				case "double":
					$COLUMN = "VALUE_DOUBLE";
					break;
				case "datetime":
					$COLUMN = "VALUE_DATE";
					break;
				default:
					$COLUMN = "VALUE";
			}
			foreach($arField as $value)
			{
				if($value instanceof \Bitrix\Main\Type\Date)
				{
					// little hack to avoid timezone vs 00:00:00 ambiguity. for utm only
					$value = new \Bitrix\Main\Type\DateTime($value->format('Y-m-d H:i:s'), 'Y-m-d H:i:s');
				}

				switch($arInsertType[$FieldId]["BASE_TYPE"])
				{
					case "int":
					case "file":
					case "enum":
					case "double":
						break;
					case "datetime":
						$userFieldName = $arInsertType[$FieldId]['FIELD_NAME'];
						$value = DateTimeType::charToDate($arUserFields[$userFieldName], $value);
						break;
					default:
						$value = "'" . $DB->ForSql($value) . "'";
				}
				$DB->Query("INSERT INTO b_utm_" . $lower_entity_id . " (VALUE_ID, FIELD_ID, " . $COLUMN . ")
					VALUES (" . intval($ID) . ", '" . $FieldId . "', " . $value . ")", false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
			}
		}

		return $result;
	}

	public function copy($entity_id, $id, $copiedId, $entityObject, $userId = false, $ignoreList = [])
	{
		$userFields = $this->getUserFields($entity_id, $id);

		$fields = [];
		foreach($userFields as $fieldName => $userField)
		{
			if(!in_array($fieldName, $ignoreList))
			{
				if(is_callable([$userField["USER_TYPE"]["CLASS_NAME"], "onBeforeCopy"]))
				{
					$fields[$fieldName] = call_user_func_array(
						[$userField["USER_TYPE"]["CLASS_NAME"], "onBeforeCopy"],
						[$userField, $copiedId, $userField["VALUE"], $entityObject, $userId]
					);
				}
				else
				{
					$fields[$fieldName] = $userField["VALUE"];
				}
			}
		}

		$this->update($entity_id, $copiedId, $fields, $userId);

		foreach($userFields as $fieldName => $userField)
		{
			if(!in_array($fieldName, $ignoreList))
			{
				if(is_callable([$userField["USER_TYPE"]["CLASS_NAME"], "onAfterCopy"]))
				{
					$fields[$fieldName] = call_user_func_array(
						[$userField["USER_TYPE"]["CLASS_NAME"], "onAfterCopy"],
						[$userField, $copiedId, $fields[$fieldName], $entityObject, $userId]
					);
				}
			}
		}
	}

	function Delete($entity_id, $ID)
	{
		global $DB;

		$result = $this->deleteUserFieldValuesByEvent($entity_id, $ID);
		if($result !== null)
		{
			return;
		}

		if($arUserFields = $this->GetUserFields($entity_id, $ID, false, 0))
		{
			foreach($arUserFields as $arUserField)
			{
				if(is_array($arUserField["VALUE"]))
				{
					foreach($arUserField["VALUE"] as $value)
					{
						if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "ondelete")))
							call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "ondelete"), array($arUserField, $value));

						if($arUserField["USER_TYPE"]["BASE_TYPE"] == "file")
							CFile::Delete($value);
					}
				}
				else
				{
					if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "ondelete")))
						call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "ondelete"), array($arUserField, $arUserField["VALUE"]));

					if($arUserField["USER_TYPE"]["BASE_TYPE"] == "file")
						CFile::Delete($arUserField["VALUE"]);
				}
			}
			$DB->Query("DELETE FROM b_utm_".mb_strtolower($entity_id) . " WHERE VALUE_ID = " . intval($ID), false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
			$DB->Query("DELETE FROM b_uts_".mb_strtolower($entity_id) . " WHERE VALUE_ID = " . intval($ID), false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
		}
	}

	function OnSearchIndex($entity_id, $ID)
	{
		$result = "";
		if($arUserFields = $this->GetUserFields($entity_id, $ID, false, 0))
		{
			foreach($arUserFields as $arUserField)
			{
				if($arUserField["IS_SEARCHABLE"] == "Y")
				{
					if($arUserField["USER_TYPE"])
						if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onsearchindex")))
							$result .= "\r\n" . call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onsearchindex"), array($arUserField));
				}
			}
		}
		return $result;
	}

	function GetRights($ENTITY_ID = false, $ID = false)
	{
		if(($ID !== false) && array_key_exists("ID:" . $ID, $this->arRightsCache))
		{
			return $this->arRightsCache["ID:" . $ID];
		}
		if(($ENTITY_ID !== false) && array_key_exists("ENTITY_ID:" . $ENTITY_ID, $this->arRightsCache))
		{
			return $this->arRightsCache["ENTITY_ID:" . $ENTITY_ID];
		}

		global $USER;
		if(is_object($USER) && $USER->CanDoOperation('edit_other_settings'))
		{
			$RIGHTS = "X";
		}
		else
		{
			$RIGHTS = "D";
			if($ID !== false)
			{
				$ar = CUserTypeEntity::GetByID($ID);
				if($ar)
					$ENTITY_ID = $ar["ENTITY_ID"];
			}

			foreach(GetModuleEvents("main", "OnUserTypeRightsCheck", true) as $arEvent)
			{
				$res = ExecuteModuleEventEx($arEvent, array($ENTITY_ID));
				if($res > $RIGHTS)
					$RIGHTS = $res;
			}
		}

		if($ID !== false)
		{
			$this->arRightsCache["ID:" . $ID] = $RIGHTS;
		}
		if($ENTITY_ID !== false)
		{
			$this->arRightsCache["ENTITY_ID:" . $ENTITY_ID] = $RIGHTS;
		}

		return $RIGHTS;
	}

	/**
	 * @param             $arUserField
	 * @param null|string $fieldName
	 * @param array $fieldParameters
	 *
	 * @return Entity\DatetimeField|Entity\FloatField|Entity\IntegerField|Entity\StringField|mixed
	 * @throws Bitrix\Main\ArgumentException
	 */
	public function getEntityField($arUserField, $fieldName = null, $fieldParameters = array())
	{
		if(empty($fieldName))
		{
			$fieldName = $arUserField['FIELD_NAME'];
		}

		if(is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'getEntityField')))
		{
			$field = call_user_func(array($arUserField['USER_TYPE']['CLASS_NAME'], 'getEntityField'), $fieldName, $fieldParameters);
		}
		elseif($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'date')
		{
			$field = new Entity\DateField($fieldName, $fieldParameters);
		}
		else
		{
			switch($arUserField['USER_TYPE']['BASE_TYPE'])
			{
				case 'int':
				case 'enum':
				case 'file':
					$field = new Entity\IntegerField($fieldName, $fieldParameters);
					break;
				case 'double':
					$field = new Entity\FloatField($fieldName, $fieldParameters);
					break;
				case 'string':
					$field = new Entity\StringField($fieldName, $fieldParameters);
					break;
				case 'datetime':
					$field = new Entity\DatetimeField($fieldName, $fieldParameters);
					break;
				default:
					throw new \Bitrix\Main\ArgumentException(sprintf(
						'Unknown userfield base type `%s`', $arUserField["USER_TYPE"]['BASE_TYPE']
					));
			}
		}

		$ufHandlerClass = $arUserField['USER_TYPE']['CLASS_NAME'];

		if (is_subclass_of($ufHandlerClass, BaseType::class))
		{
			$defaultValue = $ufHandlerClass::getDefaultValue($arUserField);
			$field->configureDefaultValue($defaultValue);
		}

		return $field;
	}

	/**
	 * @param                    $arUserField
	 * @param Entity\ScalarField $entityField
	 *
	 * @return Entity\ReferenceField[]
	 */
	public function getEntityReferences($arUserField, Entity\ScalarField $entityField)
	{
		if(is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'getEntityReferences')))
		{
			return call_user_func(array($arUserField['USER_TYPE']['CLASS_NAME'], 'getEntityReferences'), $arUserField, $entityField);
		}

		return array();
	}

	protected function getUserFieldValuesByEvent(array $userFields, string $entityId, int $value): ?array
	{
		$result = [];
		if($value === 0)
		{
			return null;
		}
		$isGotByEvent = false;
		$event = new \Bitrix\Main\Event('main', 'onGetUserFieldValues', ['userFields' => $userFields, 'entityId' => $entityId, 'value' => $value]);
		$event->send();
		foreach($event->getResults() as $eventResult)
		{
			if($eventResult->getType() === \Bitrix\Main\EventResult::SUCCESS)
			{
				$parameters = $eventResult->getParameters();
				if(isset($parameters['values']) && is_array($parameters['values']))
				{
					$isGotByEvent = true;
					foreach($userFields as $fieldName => $userField)
					{
						if(isset($parameters['values'][$fieldName]))
						{
							$result[$fieldName] = $parameters['values'][$fieldName];
						}
					}
				}
			}
		}
		if($isGotByEvent)
		{
			return $result;
		}

		return null;
	}

	protected function updateUserFieldValuesByEvent(string $entityId, int $id, array $fields): ?bool
	{
		$result = null;

		$event = new \Bitrix\Main\Event('main', 'onUpdateUserFieldValues', ['entityId' => $entityId, 'id' => $id, 'fields' => $fields]);
		$event->send();
		foreach($event->getResults() as $eventResult)
		{
			if($eventResult->getType() === \Bitrix\Main\EventResult::SUCCESS)
			{
				$result = true;
			}
			elseif($eventResult->getType() === \Bitrix\Main\EventResult::ERROR)
			{
				$result = false;
			}
		}

		return $result;
	}

	protected function deleteUserFieldValuesByEvent(string $entityId, int $id): ?bool
	{
		$result = null;

		$event = new \Bitrix\Main\Event('main', 'onDeleteUserFieldValues', ['entityId' => $entityId, 'id' => $id]);
		$event->send();
		foreach($event->getResults() as $eventResult)
		{
			if($eventResult->getType() === \Bitrix\Main\EventResult::SUCCESS)
			{
				$result = true;
			}
			elseif($eventResult->getType() === \Bitrix\Main\EventResult::ERROR)
			{
				$result = false;
			}
		}

		return $result;
	}

}

class CUserTypeSQL
{
	var $table_alias = "BUF";
	var $entity_id = false;
	var $user_fields = array();

	var $select = array();
	var $filter = array();
	var $order = array();

	/** @var CSQLWhere */
	var $obWhere = false;

	function SetEntity($entity_id, $ID)
	{
		global $USER_FIELD_MANAGER;

		$this->user_fields = $USER_FIELD_MANAGER->GetUserFields($entity_id);
		$this->entity_id = mb_strtolower(preg_replace("/[^0-9A-Z_]+/", "", $entity_id));
		$this->select = array();
		$this->filter = array();
		$this->order = array();

		$this->obWhere = new CSQLWhere;
		$num = 0;
		$arFields = array();
		foreach($this->user_fields as $FIELD_NAME => $arField)
		{
			if($arField["MULTIPLE"] == "Y")
				$num++;
			$table_alias = $arField["MULTIPLE"] == "N" ? $this->table_alias : $this->table_alias . $num;
			$arType = $this->user_fields[$FIELD_NAME]["USER_TYPE"];

			if($arField["MULTIPLE"] == "N")
				$TABLE_FIELD_NAME = $table_alias . "." . $FIELD_NAME;
			elseif($arType["BASE_TYPE"] == "int")
				$TABLE_FIELD_NAME = $table_alias . ".VALUE_INT";
			elseif($arType["BASE_TYPE"] == "file")
				$TABLE_FIELD_NAME = $table_alias . ".VALUE_INT";
			elseif($arType["BASE_TYPE"] == "enum")
				$TABLE_FIELD_NAME = $table_alias . ".VALUE_INT";
			elseif($arType["BASE_TYPE"] == "double")
				$TABLE_FIELD_NAME = $table_alias . ".VALUE_DOUBLE";
			elseif($arType["BASE_TYPE"] == "datetime")
				$TABLE_FIELD_NAME = $table_alias . ".VALUE_DATE";
			else
				$TABLE_FIELD_NAME = $table_alias . ".VALUE";

			$arFields[$FIELD_NAME] = array(
				"TABLE_ALIAS" => $table_alias,
				"FIELD_NAME" => $TABLE_FIELD_NAME,
				"FIELD_TYPE" => $arType["BASE_TYPE"],
				"USER_TYPE_ID" => $arType["USER_TYPE_ID"],
				"MULTIPLE" => $arField["MULTIPLE"],
				"JOIN" => $arField["MULTIPLE"] == "N" ?
					"INNER JOIN b_uts_" . $this->entity_id . " " . $table_alias . " ON " . $table_alias . ".VALUE_ID = " . $ID :
					"INNER JOIN b_utm_" . $this->entity_id . " " . $table_alias . " ON " . $table_alias . ".FIELD_ID = " . $arField["ID"] . " AND " . $table_alias . ".VALUE_ID = " . $ID,
				"LEFT_JOIN" => $arField["MULTIPLE"] == "N" ?
					"LEFT JOIN b_uts_" . $this->entity_id . " " . $table_alias . " ON " . $table_alias . ".VALUE_ID = " . $ID :
					"LEFT JOIN b_utm_" . $this->entity_id . " " . $table_alias . " ON " . $table_alias . ".FIELD_ID = " . $arField["ID"] . " AND " . $table_alias . ".VALUE_ID = " . $ID,
			);

			if($arType["BASE_TYPE"] == "enum")
			{
				$arFields[$FIELD_NAME . "_VALUE"] = array(
					"TABLE_ALIAS" => $table_alias . "EN",
					"FIELD_NAME" => $table_alias . "EN.VALUE",
					"FIELD_TYPE" => "string",
					"MULTIPLE" => $arField["MULTIPLE"],
					"JOIN" => $arField["MULTIPLE"] == "N" ?
						"INNER JOIN b_uts_" . $this->entity_id . " " . $table_alias . "E ON " . $table_alias . "E.VALUE_ID = " . $ID . "
						INNER JOIN b_user_field_enum " . $table_alias . "EN ON " . $table_alias . "EN.ID = " . $table_alias . "E." . $FIELD_NAME :
						"INNER JOIN b_utm_" . $this->entity_id . " " . $table_alias . "E ON " . $table_alias . "E.FIELD_ID = " . $arField["ID"] . " AND " . $table_alias . "E.VALUE_ID = " . $ID . "
						INNER JOIN b_user_field_enum " . $table_alias . "EN ON " . $table_alias . "EN.ID = " . $table_alias . "E.VALUE_INT",
					"LEFT_JOIN" => $arField["MULTIPLE"] == "N" ?
						"LEFT JOIN b_uts_" . $this->entity_id . " " . $table_alias . "E ON " . $table_alias . "E.VALUE_ID = " . $ID . "
						LEFT JOIN b_user_field_enum " . $table_alias . "EN ON " . $table_alias . "EN.ID = " . $table_alias . "E." . $FIELD_NAME :
						"LEFT JOIN b_utm_" . $this->entity_id . " " . $table_alias . "E ON " . $table_alias . "E.FIELD_ID = " . $arField["ID"] . " AND " . $table_alias . "E.VALUE_ID = " . $ID . "
						LEFT JOIN b_user_field_enum " . $table_alias . "EN ON " . $table_alias . "EN.ID = " . $table_alias . "E.VALUE_INT",
				);
			}
		}
		$this->obWhere->SetFields($arFields);
	}

	function SetSelect($arSelect)
	{
		$this->obWhere->bDistinctReqired = false;
		$this->select = array();
		if(is_array($arSelect))
		{
			if(in_array("UF_*", $arSelect))
			{
				foreach($this->user_fields as $FIELD_NAME => $arField)
				{
					$this->select[$FIELD_NAME] = true;
				}
			}
			else
			{
				foreach($arSelect as $field)
				{
					if(array_key_exists($field, $this->user_fields))
					{
						$this->select[$field] = true;
					}
				}
			}
		}
	}

	function GetDistinct()
	{
		return $this->obWhere->bDistinctReqired;
	}

	function GetSelect()
	{
		$result = "";
		foreach($this->select as $key => $value)
		{
			$simpleFormat = true;
			if($this->user_fields[$key]["MULTIPLE"] == "N")
			{
				if($arType = $this->user_fields[$key]["USER_TYPE"])
				{
					if(is_callable(array($arType["CLASS_NAME"], "FormatField")))
					{
						$result .= ", " . call_user_func_array(array($arType["CLASS_NAME"], "FormatField"), array($this->user_fields[$key], $this->table_alias . "." . $key)) . " " . $key;
						$simpleFormat = false;
					}
				}
			}
			if($simpleFormat)
			{
				$result .= ", " . $this->table_alias . "." . $key;
			}
		}
		return $result;
	}

	function GetJoin($ID)
	{
		$result = $this->obWhere->GetJoins();
		$table = " b_uts_" . $this->entity_id . " " . $this->table_alias . " ";
		if((count($this->select) > 0 || count($this->order) > 0) && mb_strpos($result, $table) === false)
			$result .= "\nLEFT JOIN" . $table . "ON " . $this->table_alias . ".VALUE_ID = " . $ID;
		return $result;
	}

	function SetOrder($arOrder)
	{
		if(is_array($arOrder))
		{
			$this->order = array();
			foreach($arOrder as $field => $order)
			{
				if(array_key_exists($field, $this->user_fields))
					$this->order[$field] = $order != "ASC" ? "DESC" : "ASC";
			}
		}
	}

	function GetOrder($field)
	{
		$field = mb_strtoupper($field);
		if(isset($this->order[$field]))
			$result = $this->table_alias . "." . $field;
		else
			$result = "";
		return $result;
	}

	function SetFilter($arFilter)
	{
		if(is_array($arFilter))
			$this->filter = $arFilter;
	}

	function GetFilter()
	{
		return $this->obWhere->GetQuery($this->filter);
	}
}

class CUserFieldEnum
{
	function SetEnumValues($FIELD_ID, $values)
	{
		global $DB, $CACHE_MANAGER, $APPLICATION;
		$aMsg = array();
		$originalValues = $values;

		foreach($values as $i => $row)
		{
			foreach($row as $key => $val)
			{
				if(strncmp($key, "~", 1) === 0)
				{
					unset($values[$i][$key]);
				}
			}
		}

		/*check unique XML_ID*/
		$arAdded = array();
		$salt = RandString(8);
		foreach($values as $key => $value)
		{
			if(strncmp($key, "n", 1) === 0 && $value["DEL"] != "Y" && $value["VALUE"] <> '')
			{
				if($value["XML_ID"] == '')
				{
					$values[$key]["XML_ID"] = $value["XML_ID"] = md5($salt . $value["VALUE"]);
				}

				if(array_key_exists($value["XML_ID"], $arAdded))
				{
					$aMsg[] = array("text" => GetMessage("USER_TYPE_XML_ID_UNIQ", array("#XML_ID#" => $value["XML_ID"])));
				}
				else
				{
					$rsEnum = $this->GetList(array(), array("USER_FIELD_ID" => $FIELD_ID, "XML_ID" => $value["XML_ID"]));
					if($arEnum = $rsEnum->Fetch())
					{
						$aMsg[] = array("text" => GetMessage("USER_TYPE_XML_ID_UNIQ", array("#XML_ID#" => $value["XML_ID"])));
					}
					else
					{
						$arAdded[$value["XML_ID"]]++;
					}
				}
			}
		}

		$previousValues = array();

		$rsEnum = $this->GetList(array(), array("USER_FIELD_ID" => $FIELD_ID));
		while($arEnum = $rsEnum->Fetch())
		{
			$previousValues[$arEnum["ID"]] = $arEnum;

			if(array_key_exists($arEnum["ID"], $values))
			{
				$value = $values[$arEnum["ID"]];
				if($value["VALUE"] == '' || $value["DEL"] == "Y")
				{
				}
				elseif(
					$arEnum["VALUE"] != $value["VALUE"] ||
					$arEnum["DEF"] != $value["DEF"] ||
					$arEnum["SORT"] != $value["SORT"] ||
					$arEnum["XML_ID"] != $value["XML_ID"]
				)
				{
					if($value["XML_ID"] == '')
						$value["XML_ID"] = md5($value["VALUE"]);

					$bUnique = true;
					if($arEnum["XML_ID"] != $value["XML_ID"])
					{
						if(array_key_exists($value["XML_ID"], $arAdded))
						{
							$aMsg[] = array("text" => GetMessage("USER_TYPE_XML_ID_UNIQ", array("#XML_ID#" => $value["XML_ID"])));
							$bUnique = false;
						}
						else
						{
							$rsEnumXmlId = $this->GetList(array(), array("USER_FIELD_ID" => $FIELD_ID, "XML_ID" => $value["XML_ID"]));
							if($arEnumXmlId = $rsEnumXmlId->Fetch())
							{
								$aMsg[] = array("text" => GetMessage("USER_TYPE_XML_ID_UNIQ", array("#XML_ID#" => $value["XML_ID"])));
								$bUnique = false;
							}
						}
					}
					if($bUnique)
					{
						$arAdded[$value["XML_ID"]]++;
					}
				}
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		if(CACHED_b_user_field_enum !== false)
			$CACHE_MANAGER->CleanDir("b_user_field_enum");

		foreach($values as $key => $value)
		{
			if(strncmp($key, "n", 1) === 0 && $value["DEL"] != "Y" && $value["VALUE"] <> '')
			{
				if($value["XML_ID"] == '')
					$value["XML_ID"] = md5($value["VALUE"]);

				if($value["DEF"] != "Y")
					$value["DEF"] = "N";

				$value["USER_FIELD_ID"] = $FIELD_ID;
				$id = $DB->Add("b_user_field_enum", $value);

				$originalValues[$id] = $originalValues[$key];
				unset($originalValues[$key], $values[$key]);
			}
		}
		$rsEnum = $this->GetList(array(), array("USER_FIELD_ID" => $FIELD_ID));
		while($arEnum = $rsEnum->Fetch())
		{
			if(array_key_exists($arEnum["ID"], $values))
			{
				$value = $values[$arEnum["ID"]];
				if($value["VALUE"] == '' || $value["DEL"] == "Y")
				{
					$DB->Query("DELETE FROM b_user_field_enum WHERE ID = " . $arEnum["ID"], false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
				}
				elseif($arEnum["VALUE"] != $value["VALUE"] ||
					$arEnum["DEF"] != $value["DEF"] ||
					$arEnum["SORT"] != $value["SORT"] ||
					$arEnum["XML_ID"] != $value["XML_ID"])
				{
					if($value["XML_ID"] == '')
						$value["XML_ID"] = md5($value["VALUE"]);

					unset($value["ID"]);
					$strUpdate = $DB->PrepareUpdate("b_user_field_enum", $value);
					if($strUpdate <> '')
						$DB->Query("UPDATE b_user_field_enum SET " . $strUpdate . " WHERE ID = " . $arEnum["ID"], false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
				}
			}
		}
		if(CACHED_b_user_field_enum !== false)
			$CACHE_MANAGER->CleanDir("b_user_field_enum");

		$event = new \Bitrix\Main\Event('main', 'onAfterSetEnumValues', [$FIELD_ID, $originalValues, $previousValues]);
		$event->send();

		return true;
	}

	public static function GetList($aSort = array(), $aFilter = array())
	{
		global $DB, $CACHE_MANAGER;

		if(CACHED_b_user_field_enum !== false)
		{
			$cacheId = "b_user_field_enum" . md5(serialize($aSort) . "." . serialize($aFilter));
			if($CACHE_MANAGER->Read(CACHED_b_user_field_enum, $cacheId, "b_user_field_enum"))
			{
				$arResult = $CACHE_MANAGER->Get($cacheId);
				$res = new CDBResult;
				$res->InitFromArray($arResult);
				return $res;
			}
		}
		else
		{
			$cacheId = '';
		}

		$bJoinUFTable = false;
		$arFilter = array();
		foreach($aFilter as $key => $val)
		{
			if(is_array($val))
			{
				if(count($val) <= 0)
					continue;
				$val = array_map(array($DB, "ForSQL"), $val);
				$val = "('" . implode("', '", $val) . "')";
			}
			else
			{
				if((string)$val == '')
					continue;
				$val = "('" . $DB->ForSql($val) . "')";
			}

			$key = mb_strtoupper($key);
			switch($key)
			{
				case "ID":
				case "USER_FIELD_ID":
				case "VALUE":
				case "DEF":
				case "SORT":
				case "XML_ID":
					$arFilter[] = "UFE." . $key . " in " . $val;
					break;
				case "USER_FIELD_NAME":
					$bJoinUFTable = true;
					$arFilter[] = "UF.FIELD_NAME in " . $val;
					break;
			}
		}

		$arOrder = array();
		foreach($aSort as $key => $val)
		{
			$key = mb_strtoupper($key);
			$ord = (mb_strtoupper($val) <> "ASC" ? "DESC" : "ASC");
			switch($key)
			{
				case "ID":
				case "USER_FIELD_ID":
				case "VALUE":
				case "DEF":
				case "SORT":
				case "XML_ID":
					$arOrder[] = "UFE." . $key . " " . $ord;
					break;
			}
		}
		if(count($arOrder) == 0)
		{
			$arOrder[] = "UFE.SORT asc";
			$arOrder[] = "UFE.ID asc";
		}
		DelDuplicateSort($arOrder);
		$sOrder = "\nORDER BY " . implode(", ", $arOrder);

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE " . implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				UFE.ID
				,UFE.USER_FIELD_ID
				,UFE.VALUE
				,UFE.DEF
				,UFE.SORT
				,UFE.XML_ID
			FROM
				b_user_field_enum UFE
				" . ($bJoinUFTable ? "INNER JOIN b_user_field UF ON UF.ID = UFE.USER_FIELD_ID" : "") . "
			" . $sFilter . $sOrder;

		if($cacheId == '')
		{
			$res = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br> LINE: " . __LINE__);
		}
		else
		{
			$arResult = array();
			$res = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br> LINE: " . __LINE__);
			while($ar = $res->Fetch())
				$arResult[] = $ar;

			$CACHE_MANAGER->Set($cacheId, $arResult);

			$res = new CDBResult;
			$res->InitFromArray($arResult);
		}

		return $res;
	}

	function DeleteFieldEnum($FIELD_ID)
	{
		global $DB, $CACHE_MANAGER;
		$DB->Query("DELETE FROM b_user_field_enum WHERE USER_FIELD_ID = " . intval($FIELD_ID), false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);
		if(CACHED_b_user_field_enum !== false) $CACHE_MANAGER->CleanDir("b_user_field_enum");
	}
}
