<?
IncludeModuleLangFile(__FILE__);

class CXDImport
{
	function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu)
	{
		global $USER;
		if(!$USER->IsAdmin())
			return;

		$aMenu = array(
			"parent_menu" => "global_menu_services",
			"section" => "xdimport",
			"sort" => 690,
			"text" => GetMessage("XDI_MENU"),
			"title" => GetMessage("XDI_TITLE"),
			"icon" => "xdimport_menu_icon",
			"page_icon" => "xdimport_page_icon",
			"items_id" => "menu_xdimport",
			"items" => array(
				array(
					"text" => GetMessage("XDI_MENU_LIVEFEED"),
					"url" => "xdi_lf_scheme_list.php?lang=".LANGUAGE_ID,
					"more_url" => array("xdi_lf_scheme_edit.php"),
					"title" => GetMessage("XDI_TITLE_LIVEFEED")
				)
			)
		);

		$aModuleMenu[] = $aMenu;
	}

	public static function ParseDaysOfMonth($strDaysOfMonth)
	{
		$arResult=array();
		if(strlen($strDaysOfMonth) > 0)
		{
			$arDoM = explode(",", $strDaysOfMonth);
			$arFound = array();
			foreach($arDoM as $strDoM)
			{
				if(preg_match("/^(\d{1,2})$/", trim($strDoM), $arFound))
				{
					if(intval($arFound[1]) < 1 || intval($arFound[1]) > 31)
						return false;
					else
						$arResult[]=intval($arFound[1]);
				}
				elseif(preg_match("/^(\d{1,2})-(\d{1,2})$/", trim($strDoM), $arFound))
				{
					if(intval($arFound[1]) < 1 || intval($arFound[1]) > 31 || intval($arFound[2]) < 1 || intval($arFound[2]) > 31 || intval($arFound[1]) >= intval($arFound[2]))
						return false;
					else
						for($i=intval($arFound[1]);$i<=intval($arFound[2]);$i++)
							$arResult[]=intval($i);
				}
				else
					return false;
			}
		}
		else
			return false;
		return $arResult;
	}

	public static function ParseDaysOfWeek($strDaysOfWeek)
	{
		if(strlen($strDaysOfWeek) <= 0)
			return false;

		$arResult = array();

		$arDoW = explode(",", $strDaysOfWeek);
		foreach($arDoW as $strDoW)
		{
			$arFound = array();
			if(
				preg_match("/^(\d)$/", trim($strDoW), $arFound)
				&& $arFound[1] >= 1
				&& $arFound[1] <= 7
			)
				$arResult[]=intval($arFound[1]);
			else
				return false;
		}

		return $arResult;
	}

	public static function ParseTimesOfDay($strTimesOfDay)
	{
		if(strlen($strTimesOfDay) <= 0)
			return false;

		$arResult = array();

		$arToD = explode(",", $strTimesOfDay);
		foreach($arToD as $strToD)
		{
			$arFound = array();
			if(
				preg_match("/^(\d{1,2}):(\d{1,2})$/", trim($strToD), $arFound)
				&& $arFound[1] <= 23
				&& $arFound[2] <= 59
			)
				$arResult[]=intval($arFound[1])*3600+intval($arFound[2])*60;
			else
				return false;
		}

		return $arResult;
	}

	static function WriteToLog($text, $code = "")
	{
		$filename = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/xdimport.log";
		$f = fopen($filename, "a");
		fwrite($f, date("Y-m-d H:i:s")." ".str_pad($code, 7)." ".htmlspecialcharsbx($text)."\n");
		fclose($f);
	}

	static function DetectUTF8($url)
	{
		$arBytes = array();
		for($i=0, $n=strlen($url); $i<$n; $i++)
			$arBytes[] = ord($url[$i]);

		$is_utf = 0;
		foreach($arBytes as $i => $byte)
		{
			if( ($byte & 0xC0) == 0x80 )
			{
				if( ($i > 0) && (($arBytes[$i-1] & 0xC0) == 0xC0) )
					$is_utf++;
				elseif( ($i > 0) && (($arBytes[$i-1] & 0x80) == 0x00) )
					$is_utf--;
			}
			elseif( ($i > 0) && (($arBytes[$i-1] & 0xC0) == 0xC0) )
				$is_utf--;
		}
		return $is_utf > 0;
	}
}
?>