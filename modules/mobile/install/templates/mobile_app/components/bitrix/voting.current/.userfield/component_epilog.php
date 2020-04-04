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
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 */
$script = "";
if ($GLOBALS["USER"]->IsAuthorized() && CModule::IncludeModule("pull"))
{
	CPullWatch::Add($GLOBALS["USER"]->GetID(), 'VOTE_'.$arResult["VOTE_ID"]);
}
if ($_SERVER["REQUEST_METHOD"] == "POST" &&
	array_key_exists("VOTING.RESULT", $arResult) &&
	array_key_exists("arResult", $arResult["VOTING.RESULT"]) &&
	($questions = $arResult["VOTING.RESULT"]["arResult"]["QUESTIONS"]) &&
	!empty($questions) &&
	array_key_exists("PUBLIC_VOTE_ID", $_REQUEST) && $_REQUEST["PUBLIC_VOTE_ID"] == $arResult["VOTE_ID"] &&
	array_key_exists("vote", $_REQUEST) && strlen($_REQUEST["vote"])>0 &&
	($GLOBALS["VOTING_ID"] == $arResult["VOTE_ID"] && is_array($_SESSION["VOTE_ARRAY"]) && in_array($arResult["VOTE_ID"], $_SESSION["VOTE_ARRAY"])) &&
	CModule::IncludeModule("pull"))
{
	$result = array();
	foreach ($questions as $question)
	{
		$result[$question["ID"]] = array();
		foreach ($question["ANSWERS"] as $arAnswer)
		{
			$result[$question["ID"]][$arAnswer["ID"]] = array(
				'PERCENT' => $arAnswer["PERCENT"],
				'USERS' => $arAnswer["USERS"],
				'COUNTER' => $arAnswer["COUNTER"]
			);
		}
	}
	if (!empty($result))
	{
		CPullWatch::AddToStack('VOTE_'.$arResult["VOTE_ID"],
			Array(
				'module_id' => 'vote',
				'command' => 'voting',
				'params' => Array(
					"VOTE_ID" => $arResult["VOTE_ID"],
					"AUTHOR_ID" => $GLOBALS["USER"]->GetId(),
					"QUESTIONS" => $result
				)
			)
		);
	}
}
?>
<script>
	BX.ready(
		function(){
			BX.Mobile.Vote.init({
				id : '<?=$arParams["UID"]?>',
				voteId : <?=$arParams["VOTE_ID"]?>,
				url : '<?=CUtil::JSEscape($arParams["ACTION_PAGE"] ?: POST_FORM_ACTION_URI)?>',
				startCheck : <?=intval($this->params["lastVote"])?>
			});
		}
	);
</script>
<?
$res = ob_get_clean();

if ($_REQUEST["VOTE_ID"] == $arParams["VOTE_ID"] && $_REQUEST["AJAX_POST"] == "Y" && check_bitrix_sessid()):
	$APPLICATION->RestartBuffer();
	while(ob_get_clean());
	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo $res;
	CMain::FinalActions();
	die();
endif;

$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/rating.vote/templates/like/popup.css');
CJSCore::Init(array('ajax'));
?><div class="bx-vote-block bx-vote-block-<?=($this->getTemplate()->__page)?>" id="vote-block-<?=$arParams["UID"]?>"><?=$script?><?=$res?></div>