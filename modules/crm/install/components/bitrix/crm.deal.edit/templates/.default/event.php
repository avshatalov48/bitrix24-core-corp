<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @var array $arResult
 */

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

$event = isset($arResult['EXTERNAL_EVENT']) && is_array($arResult['EXTERNAL_EVENT'])
	? $arResult['EXTERNAL_EVENT'] : array();

$isCanceled = isset($event['IS_CANCELED']) ? $event['IS_CANCELED'] : false;

if($isCanceled):
?><div class="crm-view-message"><?=GetMessage('CRM_DEAL_EDIT_EVENT_CANCELED')?></div><?
else:
	$info = isset($arResult['INFO']) && is_array($arResult['INFO']) ? $arResult['INFO'] : array();
?><div class="crm-view-message">
	<?=GetMessage(
		'CRM_DEAL_EDIT_EVENT_SUCCESSFULLY_CREATED',
		array(
			'#URL#' => isset($info['url']) ? htmlspecialcharsbx($info['url']) : '',
			'#TITLE#' => isset($info['title']) ? htmlspecialcharsbx($info['title']) : ''
		)
	)?>
</div><?
endif;
?><script type="text/javascript">
	BX.ready(
		function()
		{
			if(window.opener)
			{
				window.opener.focus();
			}

			BX.localStorage.set(
				"<?=CUtil::JSEscape($event['NAME'])?>",
				<?=CUtil::PhpToJSObject($event['PARAMS'])?>,
				10
			);
		}
	);
</script>