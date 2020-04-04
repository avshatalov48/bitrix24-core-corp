<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);
?>
<li class="feed-add-post-destination-block">
	<div class="feed-add-post-destination-title"><?=GetMessage("CRM_SL_MPF_DESTINATION_WHERE")?></div>
	<div class="feed-add-post-destination-wrap" id="feed-add-post-where-container">
		<span id="feed-add-post-where-item"></span>
		<span class="feed-add-destination-input-box" id="feed-add-post-where-input-box">
			<input type="text" value="" class="feed-add-destination-inp" id="feed-add-post-where-input" autocomplete="off">
		</span>
		<a href="#" class="feed-add-destination-link" id="bx-where-tag"></a>
	</div>
</li>
<script type="text/javascript">
	BX.ready(
		function()
		{
			setTimeout(function() {
				BX.message({
					CRM_SL_EVENT_EDIT_MPF_WHERE_1: '<?=GetMessageJS("CRM_SL_EVENT_EDIT_MPF_WHERE_1")?>',
					CRM_SL_EVENT_EDIT_MPF_WHERE_2: '<?=GetMessageJS("CRM_SL_EVENT_EDIT_MPF_WHERE_2")?>'
				});
				BX.CrmSonetEventEditor.destinationInit({
					userNameTemplate: '<?=CUtil::JSEscape($arParams['NAME_TEMPLATE'])?>',
					items : {
						contacts : <?=(empty($arResult['FEED_WHERE']['CONTACTS'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['CONTACTS'])); ?>,
						companies : <?=(empty($arResult['FEED_WHERE']['COMPANIES'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['COMPANIES'])); ?>,
						leads : <?=(empty($arResult['FEED_WHERE']['LEADS'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['LEADS'])); ?>,
						deals : <?=(empty($arResult['FEED_WHERE']['DEALS'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['DEALS'])); ?>
					},
					itemsLast : {
						contacts : <?=(empty($arResult['FEED_WHERE']['LAST']['CONTACTS'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['LAST']['CONTACTS'])); ?>,
						companies : <?=(empty($arResult['FEED_WHERE']['LAST']['COMPANIES'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['LAST']['COMPANIES'])); ?>,
						leads : <?=(empty($arResult['FEED_WHERE']['LAST']['LEADS'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['LAST']['LEADS'])); ?>,
						deals : <?=(empty($arResult['FEED_WHERE']['LAST']['DEALS'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['LAST']['DEALS'])); ?>,
						crm: <?=(empty($arResult['FEED_WHERE']['LAST']['CRM'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['LAST']['CRM'])); ?>
					},
					itemsSelected : <?=(empty($arResult['FEED_WHERE']['SELECTED'])? '{}': CUtil::PhpToJSObject($arResult['FEED_WHERE']['SELECTED']))?>
				});
			}, 100);
		}
	);
</script>


<?