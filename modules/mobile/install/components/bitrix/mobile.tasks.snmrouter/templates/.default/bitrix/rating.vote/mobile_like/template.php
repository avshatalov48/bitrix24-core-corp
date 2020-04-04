<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadScript($templateFolder."/script_attached.js");
?><script>
BX.message({
	RVCPathToUserProfile: '<?=CUtil::JSEscape(htmlspecialcharsbx(str_replace("#", "(_)", $arResult['PATH_TO_USER_PROFILE'])))?>',
	RVCListBack: '<?=GetMessageJS("RATING_COMMENT_LIST_BACK")?>'
});
</script><?
?><div class="post-item-likes<?=($arResult['USER_HAS_VOTED'] == "N" ? "": "-liked")?><?
?><?=(intval($arResult["TOTAL_VOTES"]) > 1
		|| (
			intval($arResult["TOTAL_VOTES"]) == 1
			&& $arResult['USER_HAS_VOTED'] == "N"
		) ? " post-item-liked" : "")?>" id="bx-ilike-button-<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>"><?
	?><div class="post-item-likes-text"><?=GetMessage('RATING_COMMENT_LIKE')?></div><?
	?><div class="post-item-likes-counter" id="bx-ilike-count-<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>"><?
		?><?=htmlspecialcharsEx($arResult['TOTAL_VOTES'])?><?
	?></div><?
?></div><?
?><script type="text/javascript">
BX.ready(function() {
	var f = function() {
		new RatingLikeItems(
			'<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>',
			'<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['ENTITY_TYPE_ID']))?>',
			'<?=IntVal($arResult['ENTITY_ID'])?>',
			'<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_AVAILABLE']))?>'
		);
	};
	if (!RatingLikeItems)
	{
		window["RatingLikeItemsQueue"] = (window["RatingLikeItemsQueue"] || []);
		window["RatingLikeItemsQueue"].push(f);
	}
	else
	{
		f();
	}
});
</script>