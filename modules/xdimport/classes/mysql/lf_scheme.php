<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport/classes/general/lf_scheme.php");

class CXDILFScheme extends CAllXDILFScheme
{
	function Add($arFields)
	{
		global $DB;

		if(!$this->CheckFields("add", $arFields))
		{
			return false;
		}

		$ID = $DB->Add("b_xdi_lf_scheme", $arFields);

		if (
			$ID > 0
			&& $arFields["ACTIVE"] == "Y"
			&& $arFields["AUTO"] == "Y"
		)
		{
			CAgent::AddAgent("CXDILFScheme::CheckRequest();", "xdimport", "N", COption::GetOptionString("xdimport", "xdi_lf_checkrequest_interval", 300));
		}

		return $ID;
	}

	function Update($ID, $arFields)
	{
		global $DB;
		$ID = intval($ID);

		if(!$this->CheckFields("update", $arFields))
		{
			return false;
		}

		unset($arFields["ID"]);
		$strUpdate = $DB->PrepareUpdate("b_xdi_lf_scheme", $arFields);
		if($strUpdate!="")
		{
			$strSql = "UPDATE b_xdi_lf_scheme SET ".$strUpdate." WHERE ID=".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("XDI_SCHEME_".$ID);
			}

			if (
				$ID > 0 
				&& $arFields["ACTIVE"] == "Y" 
				&& $arFields["AUTO"] == "Y"
			)
			{
				CAgent::AddAgent("CXDILFScheme::CheckRequest();", "xdimport", "N", COption::GetOptionString("xdimport", "xdi_lf_checkrequest_interval", 300));
			}
		}

		return $ID;
	}

	function GetList($aSort=array(), $aFilter=array())
	{
		global $DB;

		$arFilter = array();
		foreach($aFilter as $key=>$val)
		{
			$val = $DB->ForSql($val);
			if(strlen($val)<=0)
				continue;
			switch(strtoupper($key))
			{
				case "ACTIVE":
					$arFilter[] = "S.ACTIVE='".$val."'";
					break;
				case "AUTO":
					$arFilter[] = "S.AUTO='".$val."'";
					break;
				case "LID":
					$arFilter[] = "S.LID='".$val."'";
					break;
				case "ID":
					$arFilter[] = "S.ID='".$val."'";
					break;
				case "TYPE":
					$arFilter[] = "S.TYPE='".$val."'";
					break;
				case "HASH":
					$arFilter[] = "S.HASH='".$val."'";
					break;
				case "ENABLE_COMMENTS":
					$arFilter[] = "S.ENABLE_COMMENTS='".$val."'";
					break;
			}
		}

		$arOrder = array();
		foreach($aSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"?"DESC":"ASC");
			switch(strtoupper($key))
			{
				case "TYPE":
					$arOrder[] = "S.TYPE ".$ord;
					break;
				case "LID":
					$arOrder[] = "S.LID ".$ord;
					break;
				case "ACTIVE":
					$arOrder[] = "S.ACTIVE ".$ord;
					break;
				case "AUTO":
					$arOrder[] = "S.AUTO ".$ord;
					break;
				case "SORT":
					$arOrder[] = "S.SORT ".$ord;
					break;
				case "ID":
					$arOrder[] = "S.ID ".$ord;
					break;
				case "LAST_EXECUTED":
					$arOrder[] = "S.LAST_EXECUTED ".$ord;
					break;
				case "NAME":
					$arOrder[] = "S.NAME ".$ord;
					break;
			}
		}
		if(count($arOrder) == 0)
			$arOrder[] = "S.ID DESC";
		$sOrder = "\nORDER BY ".implode(", ",$arOrder);

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE ".implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				S.ID
				,S.TYPE
				,S.NAME
				,S.SORT
				,S.LID
				,S.ACTIVE
				,S.AUTO
				,".$DB->DateToCharFunction("S.LAST_EXECUTED", "FULL")." AS LAST_EXECUTED
				,S.DAYS_OF_MONTH
				,S.DAYS_OF_WEEK
				,S.TIMES_OF_DAY
				,S.HOST
				,S.PORT
				,S.PAGE
				,S.LOGIN
				,S.PASSWORD
				,S.METHOD
				,S.IS_HTML
				,S.PARAMS
				,S.ENTITY_TYPE
				,S.ENTITY_ID
				,S.EVENT_ID
				,S.HASH
				,S.ENABLE_COMMENTS
				,S.URI
			FROM
				b_xdi_lf_scheme S
			".$sFilter.$sOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

}
?>