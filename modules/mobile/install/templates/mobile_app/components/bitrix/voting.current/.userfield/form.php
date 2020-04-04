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
$this->IncludeLangFile("form.php");
?><div class="bx-vote-question-block"><?
	$this->__component->params = $APPLICATION->IncludeComponent(
		"bitrix:voting.form",
		".default",
		Array(
			"VOTE_ID" => $arResult["VOTE_ID"],
			"VOTE_ASK_CAPTCHA" => $arParams["VOTE_ASK_CAPTCHA"],
			"PERMISSION" => $arParams["PERMISSION"],
			"VOTE_RESULT_TEMPLATE" => $arResult["VOTE_RESULT_TEMPLATE"],
			"ADDITIONAL_CACHE_ID" => $arResult["ADDITIONAL_CACHE_ID"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"UID" => $arParams["UID"]
		),
		($this->__component->__parent ?: $component)
	);
?></div><?
?><a href="javascript:void(0);" class="bx-vote-button bx-vote-button-vote" id="vote-do-<?=$arParams["UID"]?>"><?=GetMessage("VOTE_SUBMIT_BUTTON")?></a><?
?><a class="bx-vote-button bx-vote-button-result" href="<?=(strlen($arParams["ACTION_PAGE"]) > 0 ? $arParams["ACTION_PAGE"] : $APPLICATION->GetCurPageParam("view_result=Y",
	array("VOTE_ID","VOTING_OK","VOTE_SUCCESSFULL", "view_result", "view_form", "sessid", "AJAX_RESULT", "AJAX_POST", "VOTE_ID")))?>" id="vote-view-<?=$arParams["UID"]?>"><?=GetMessage("VOTE_SUBMIT_RESULTS")?></a>
