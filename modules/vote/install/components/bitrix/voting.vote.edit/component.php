<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();

use Bitrix\Vote\VoteTable;
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @param array $arParams
 * @param array $arResult
 * @param string $componentName
 * @param CBitrixComponent $this
 */
if (!CModule::IncludeModule("vote"))
	return false;
$permission = intval($arParams["PERMISSION"] ? $arParams["PERMISSION"] : CVoteChannel::GetGroupPermission($arParams["CHANNEL_ID"]));
if ($permission < 4)
	return false;
/********************************************************************
				Input params
********************************************************************/
$arParams["CHANNEL_ID"] = intval($arParams["CHANNEL_ID"]);
$arParams["MULTIPLE"] = ($arParams["MULTIPLE"] == "Y" ? "Y" : "N");
if (preg_match("/[^a-z0-9_]+/i", $arParams["INPUT_NAME"]))
{
	showError(GetMessage("V_BAD_NAME_FORMAT"));
	return false;
}
$arParams["~INPUT_NAME"] = trim($arParams["INPUT_NAME"]);
$arParams["INPUT_NAME"] = $arParams["~INPUT_NAME"].($arParams["MULTIPLE"] == "Y" ? "[]" : "");
$arParams["INPUT_VALUE"] = (empty($arParams["INPUT_VALUE"]) ? array() :
	(is_array($arParams["INPUT_VALUE"]) ? $arParams["INPUT_VALUE"] : array($arParams["INPUT_VALUE"])));
$arParams["VOTE_UNIQUE"] = is_array($arParams["VOTE_UNIQUE"]) ? $arParams["VOTE_UNIQUE"] : array($arParams["VOTE_UNIQUE"]);
if (!isset($arParams["VOTE_UNIQUE_IP_DELAY"]) || !preg_match("/\d+ \w/is", $arParams["VOTE_UNIQUE_IP_DELAY"], $matches))
	$arParams["VOTE_UNIQUE_IP_DELAY"] = "10 D";
$arParams["CONTROL_ID"] = preg_match("/^[a-zA-Z0-9_]+$/", $arParams["CONTROL_ID"]) ? $arParams["CONTROL_ID"] : $this->randString(4);

$arParams["bVarsFromForm"] = $arParams["bVarsFromForm"] ? true:false;
/********************************************************************
				/Input params
********************************************************************/

/********************************************************************
				Data
********************************************************************/
$arResult["CONTROL_UID"] = md5($this->randString(15));
$arResult["VOTES"] = array();

if ($arParams["bVarsFromForm"])
{
	$arResult["VOTES"] = is_array($_POST[$arParams["~INPUT_NAME"]]) ?
		$_POST[$arParams["~INPUT_NAME"]."_DATA"] : array($_POST[$arParams["~INPUT_NAME"]."_DATA"]);
}
else if (!empty($arParams["INPUT_VALUE"]))
{
	$db_res = CVote::GetListEx(array("ID" => "ASC"),
		array("CHANNEL_ID" => $arParams["CHANNEL_ID"], "ACTIVE" => "Y", "@ID" => $arParams["INPUT_VALUE"]));
	while ($res = $db_res->Fetch())
	{
		$arResult["VOTES"][$res["ID"]] = $res + array("QUESTIONS" => array());
	}
	if (!empty($arResult["VOTES"]))
	{
		$dbRes = VoteTable::getList(array(
			"select" => array(
				"Q_" => "QUESTION.*",
				"A_" => "QUESTION.ANSWER",
			),
			"order" => array(
				"QUESTION.C_SORT" => "ASC",
				"QUESTION.ID" => "ASC",
				"QUESTION.ANSWER.C_SORT" => "ASC",
				"QUESTION.ANSWER.ID" => "ASC",
			),
			"filter" => array(
				"CHANNEL_ID" => $arParams["CHANNEL_ID"],
				"ACTIVE" => "Y",
				"ID" => array_keys($arResult["VOTES"])
			)
		));
		$question = ["ID" => null];
		while ($res = $dbRes->Fetch())
		{
			if ($res["Q_ID"] !== $question["ID"])
			{
				unset($question);
				foreach ($res as $key => $val)
				{
					if (strpos($key, "Q_") === 0)
						$question[substr($key, 2)] = $val;
				}
				$question += [
					"IMAGE" => null,
					"FIELD_NAME" => \Bitrix\Vote\Event::getFieldName($question["VOTE_ID"], $question["ID"]),
					"ANSWERS" => []
				];
			}
			if (!array_key_exists($question["VOTE_ID"], $arResult["VOTES"]))
			{
				$arResult["VOTES"][$question["VOTE_ID"]] = ["QUESTIONS" => []];
			}
			if (!array_key_exists($question["ID"], $arResult["VOTES"][$question["VOTE_ID"]]["QUESTIONS"]))
			{
				$arResult["VOTES"][$question["VOTE_ID"]]["QUESTIONS"][$question["ID"]] = &$question;
			}

			$answer = [];
			foreach ($res as $key => $val)
			{
				if (strpos($key, "A_") === 0)
					$answer[substr($key, 2)] = $val;
			}
			if (
				$question["FIELD_TYPE"] == \Bitrix\Vote\QuestionTypes::CHECKBOX ||
				$question["FIELD_TYPE"] == \Bitrix\Vote\QuestionTypes::MULTISELECT ||
				($question["FIELD_TYPE"] == \Bitrix\Vote\QuestionTypes::COMPATIBILITY &&
					($answer["FIELD_TYPE"] == \Bitrix\Vote\AnswerTypes::CHECKBOX || $answer["FIELD_TYPE"] == \Bitrix\Vote\AnswerTypes::MULTISELECT)
				)
			)
			{
				$question["MULTI"] = "Y";
			}
			$question["ANSWERS"][$answer["ID"]] = $answer;
		}
		unset($question);
	}
}
else // in case vote creating
{
	$arResult["VOTES"][] = [
		"DATE_END" => GetTime((time() + 30*86400)),
		"ANONYMITY" => \Bitrix\Vote\Vote\Anonymity::PUBLICLY,
		"OPTIONS" => \Bitrix\Vote\Vote\Option::ALLOW_REVOTE
	];
}
if (!empty($arResult["VOTES"]))
{
	if (!function_exists("htmlspecialcharsmix"))
	{
		function htmlspecialcharsmix(&$mixed)
		{
			if (is_array($mixed))
			{
				foreach($mixed as $key => $value)
				{
					if (is_string($value))
					{
						if (substr($key, 0, 1) != "~")
						{
							$mixed["~".$key] = $value;
							$mixed[$key] = htmlspecialcharsbx($value);
						}
					}
					else
					{
						$mixed[$key] = htmlspecialcharsmix($value);
					}
				}
			}
			elseif (is_string($mixed))
			{
				$mixed = htmlspecialcharsbx($mixed);
			}
			return $mixed;
		}
	}
	$arResult["VOTES"] = htmlspecialcharsmix($arResult["VOTES"]);
}
/********************************************************************
				/Data
********************************************************************/
$this->IncludeComponentTemplate();

return $arParams["CONTROL_ID"];
?>