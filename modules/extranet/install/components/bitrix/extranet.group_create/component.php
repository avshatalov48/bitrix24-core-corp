<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("SONET_MODULE_NOT_INSTALL"));
	return;
}

$arDefaultUrlTemplates404 = array(
	"index" => "index.php",
	"invite" => "#group_id#/invite/"
);

$arDefaultUrlTemplatesN404 = array(
	"invite" => "page=invite&group_id=#group_id#",
	);

$arDefaultVariableAliases404 = array();
$arDefaultVariableAliases = array();
$componentPage = "";
$arComponentVariables = array("group_id", "user_id", "page");

if ($_REQUEST["auth"]=="Y" && $USER->IsAuthorized())
	LocalRedirect($APPLICATION->GetCurPageParam("", array("login", "logout", "register", "forgot_password", "change_password", "backurl", "auth")));

if (!array_key_exists("PATH_TO_GROUP", $arParams) || strlen($arParams["PATH_TO_GROUP"]) <= 0)
	$arParams["PATH_TO_GROUP"] = SITE_DIR."workgroups/group/#group_id#/";

if ($arParams["SEF_MODE"] == "Y")
{
	$arVariables = array();

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams["SEF_URL_TEMPLATES"]);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams["VARIABLE_ALIASES"]);

	$componentPage = CComponentEngine::ParseComponentPath($arParams["SEF_FOLDER"], $arUrlTemplates, $arVariables);

	if (array_key_exists($arVariables["page"], $arDefaultUrlTemplates404))
		$componentPage = $arVariables["page"];

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
	{
		//if (strlen($componentPage) <= 0)
		$componentPage = "index";
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
		$arResult["PATH_TO_".strToUpper($url)] = $arParams["SEF_FOLDER"].$value;

	if ($_REQUEST["auth"] == "Y")
		$componentPage = "auth";

}
else
{
	$arVariables = array();

	if (is_array($arParams["VARIABLE_ALIASES"]))
	{
		foreach ($arParams["VARIABLE_ALIASES"] as $key => $val)
			$arParams["VARIABLE_ALIASES"][$key] = (!empty($val) ? $val : $key);

		
		$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams["VARIABLE_ALIASES"]);
		CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);
		if (!empty($arDefaultUrlTemplatesN404) && !empty($arParams["VARIABLE_ALIASES"]))
		{
			foreach ($arDefaultUrlTemplatesN404 as $url => $value)
			{
				$pattern = array();
				$replace = array();
				foreach ($arParams["VARIABLE_ALIASES"] as $key => $res)
				{
					if ($key != $res && !empty($res))
					{
						$pattern[] = preg_quote("/(^|([&?]+))".$key."\=/is");
						$replace[] = "$1".$res."=";
					}
				}
				if (!empty($pattern))
				{
					$value = preg_replace($pattern, $replace, $value);
					$arDefaultUrlTemplatesN404[$url] = $value;
				}
			}
		}

		foreach ($arDefaultUrlTemplatesN404 as $url => $value)
		{
			$arParamsKill = array("page", "path", 
					"section_id", "element_id", "action", "user_id", "group_id", "action", "use_light_view", "AJAX_CALL",
					"edit_section", "sessid", "post_id", "category", "topic_id", "result", "MESSAGE_TYPE");
			$arParamsKill = array_merge($arParamsKill, $arParams["VARIABLE_ALIASES"]);
			$arResult["PATH_TO_".strToUpper($url)] = $GLOBALS["APPLICATION"]->GetCurPageParam($value, $arParamsKill);
		}
		if (array_key_exists($arVariables["page"], $arDefaultUrlTemplates404))
			$componentPage = $arVariables["page"];
	}

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
	{
		//if (strlen($componentPage) <= 0)
		$componentPage = "index";
	}
	if ($_REQUEST["auth"] == "Y")
		$componentPage = "auth";
}

if ($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["skip"]) > 0)
{
	if (strpos($arParams["PATH_TO_GROUP"], "#group_id#") !== false && intval($arVariables["group_id"]) > 0)
	{
		$redirect_path = str_replace("#group_id#", intval($arVariables["group_id"]), $arParams["PATH_TO_GROUP"]);
		LocalRedirect($redirect_path);
	}
	
}

$arResult = array_merge(
	array(
		"SEF_MODE" => $arParams["SEF_MODE"],
		"SEF_FOLDER" => $arParams["SEF_FOLDER"],
		"VARIABLES" => $arVariables,
		"ALIASES" => $arParams["SEF_MODE"] == "Y"? array(): $arVariableAliases,
	),
	$arResult
);

$arParams["ERROR_MESSAGE"] = "";
$arParams["NOTE_MESSAGE"] = "";

$this->IncludeComponentTemplate($componentPage);
?>