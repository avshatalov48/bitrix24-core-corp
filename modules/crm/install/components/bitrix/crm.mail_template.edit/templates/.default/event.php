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

if($isCanceled)
{
	?><div class="crm-view-message"><?=GetMessage('CRM_TEMPLATE_EDIT_EVENT_CANCELED')?></div><?
}
else
{
	?>

	<div class="crm-view-message">
	<?=GetMessage(
		'CRM_TEMPLATE_EDIT_EVENT_SUCCESSFULLY_CREATED',
		array(
			'#URL#' => (int)$event['PARAMS']['templateId'] ? rtrim(SITE_DIR, '/').'/crm/configs/mailtemplate/edit/'.(int)$event['PARAMS']['templateId'] : $arParams['PATH_TO_MAIL_TEMPLATE_LIST'],
			'#TITLE#' => isset($event['PARAMS']['templateTitle']) ? htmlspecialcharsbx($event['PARAMS']['templateTitle']) : ''
		)
	)?>
	</div>
	<?
}

?>

<script>
	BX.ready(
		function()
		{
			BX.localStorage.set(
				"<?=CUtil::JSEscape($event['NAME'])?>",
				<?=CUtil::PhpToJSObject($event['PARAMS'])?>,
				10
			);

			var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
			if (slider && slider.close)
			{
				slider.close();
				return;
			}

			if(window.opener)
			{
				window.opener.focus();
			}
		}
	);
</script>

<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
die;
