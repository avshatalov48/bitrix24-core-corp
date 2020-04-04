<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2016 Bitrix
 *
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CBitrixComponent $component
 */

$this->IncludeLangFile("result.php");
?><div class="bx-vote-question-block"><?
$this->__component->params = $APPLICATION->IncludeComponent(
	"bitrix:voting.result",
	".default",
	Array(
		"VOTE_ID" => $arResult["VOTE_ID"],
		"PERMISSION" => $arParams["PERMISSION"],
		"SHOW_VOTED_USERS" => "Y",
		"VOTE_ALL_RESULTS" => "N",
		"NEED_SORT" => "N",
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"ADDITIONAL_CACHE_ID" => $arResult["ADDITIONAL_CACHE_ID"],
		"NAME_TEMPLATE" => $arParams["~NAME_TEMPLATE"],
		"PATH_TO_USER" => $arParams["~PATH_TO_USER"],
		"UID" => $arParams["UID"]
		),
	($this->__component->__parent ?: $component),
	array("HIDE_ICONS" => "Y")
);
?></div><?
if ($arParams["CAN_REVOTE"] == "Y" || $arParams["CAN_VOTE"] == "Y") {
	?><a href="<?=(strlen($arParams["ACTION_PAGE"]) > 0 ? $arParams["ACTION_PAGE"] : $APPLICATION->GetCurPageParam("", array("VOTE_ID","VOTING_OK","VOTE_SUCCESSFULL", "view_form", "view_result")))?>" <?
		?>id="vote-revote-<?=$arParams["UID"]?>" class="bx-vote-button bx-vote-button-vote" <?
		?>><?=($arParams["CAN_REVOTE"] == "Y" ? GetMessage("VOTE_RESUBMIT_BUTTON") : GetMessage("VOTE_SUBMIT_BUTTON"))?></a><?
}
?>