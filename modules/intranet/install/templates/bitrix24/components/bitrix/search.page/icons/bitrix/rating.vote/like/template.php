<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<span class="ilike-light">
	<span
		class="bx-ilike-button <?=($arResult['VOTE_AVAILABLE'] == 'Y'? '': 'bx-ilike-button-disable')?>"
		id="bx-ilike-button-<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>"
		data-vote-key-signed="<?= htmlspecialcharsbx($arResult['VOTE_KEY_SIGNED']) ?>"
	>
		<span class="bx-ilike-right-wrap <?=($arResult['USER_HAS_VOTED'] == 'N'? '': 'bx-you-like')?>"><span class="bx-ilike-right"><?=htmlspecialcharsEx($arResult['TOTAL_VOTES'])?></span></span>
		<span class="bx-ilike-left-wrap" <?=($arResult['VOTE_AVAILABLE'] == 'Y'? '': 'title="'.$arResult['ALLOW_VOTE']['ERROR_MSG'].'"')?>>
		<?if($arResult['VOTE_AVAILABLE'] == 'Y'):?><span class="bx-ilike-text"><?=($arResult['USER_HAS_VOTED'] == 'N'? GetMessage('RATING_LIKE_N'): GetMessage('RATING_LIKE_Y'))?></span><?endif;?></span>
	</span>
	<span class="bx-ilike-wrap-block" id="bx-ilike-popup-cont-<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>" style="display:none;">
		<span class="bx-ilike-popup">
			<span class="bx-ilike-wait"></span> 
		</span>
	</span>
</span>
<?$APPLICATION->AddHeadScript("/bitrix/js/main/rating_like.js");?>
<script type="text/javascript">
	RatingLike.Set(
		{
			likeId: '<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_ID']))?>',
			keySigned: '<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_KEY_SIGNED']))?>',
			entityTypeId: '<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['ENTITY_TYPE_ID']))?>',
			entityId: '<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['ENTITY_ID']))?>',
			available: '<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['VOTE_AVAILABLE']))?>',
			userId: '<?=$USER->GetId()?>',
			localize: {
				'LIKE_Y': '<?=GetMessage('RATING_LIKE_Y')?>',
				'LIKE_N': '<?=GetMessage('RATING_LIKE_N')?>',
				'LIKE_D': '<?=GetMessage('RATING_LIKE_D')?>'
			},
			template: 'light',
			pathToUserProfile: '<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['PATH_TO_USER_PROFILE']))?>'
		}
	);
</script>