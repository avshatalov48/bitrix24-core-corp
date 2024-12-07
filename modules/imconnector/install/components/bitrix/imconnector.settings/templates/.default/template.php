<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

Loc::loadMessages(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

?>
<?if(empty($arResult['RELOAD'])):?>
<div id="imconnector_settings">
	<div class="tel-set-cont-title"><?=Loc::getMessage("IMCONNECTOR_COMPONENT_SETTINGS_CONFIG_EDIT_CONNECTORS")?>
		<form class="imconnector-group-title-reload" action="" method="post" onsubmit="updateImconnectorSettings(<?=CUtil::PhpToJSObject($arResult['SETTINGS_RELOAD'])?>);return false;">
			<input type="hidden" name="settings_form" value="true">
			<input type="submit" class="imconnector-group-title-reload-input" value="<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_RELOAD')?>">
			<?=bitrix_sessid_post();?>
		</form>
	</div>
	<div class="tel-set-item tel-set-item-border">
	<?
	if (!empty($arResult['messages']))
	{
		echo '<div class="imconnector-settings-message imconnector-settings-message-success">';
		foreach ($arResult['messages'] as $value)
		{
			echo '<div>' . $value . '</div>';
		}
		echo '</div>';
	}
	if (!empty($arResult['error']))
	{
		echo '<div class="imconnector-settings-message imconnector-settings-message-error">';
		foreach ($arResult['error'] as $value)
		{
			echo '<div>' . $value . '</div>';
		}
		echo '</div>';
	}
	?>
	<?if(empty($arResult['CONNECTOR'])):?>
		<?=Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_NO_CONNECT_CONNECTOR')?>
	<?else:?>
		<?foreach ($arResult['CONNECTOR'] as $cell => $value):?>
		<?$APPLICATION->IncludeComponent(
			$value["component"],
			"",
			Array(
				"LINE" => $arParams['LINE'],
				"CONNECTOR" => $cell,
				"AJAX_MODE" => "Y",
				"AJAX_OPTION_ADDITIONAL" => "",
				"AJAX_OPTION_HISTORY" => "N",
				"AJAX_OPTION_JUMP" => "Y",
				"AJAX_OPTION_STYLE" => "Y",
			)
		);?>
		<?endforeach;?>
	<?endif;?>
	</div>
</div>
	<?=$arResult['LANG_JS_SETTING'];?>
	<script>
		BX.message({
			IMCONNECTOR_COMPONENT_SETTINGS_COLLAPSE: '<? echo GetMessageJS('IMCONNECTOR_COMPONENT_SETTINGS_COLLAPSE') ?>',
			IMCONNECTOR_COMPONENT_SETTINGS_DEPLOY: '<? echo GetMessageJS('IMCONNECTOR_COMPONENT_SETTINGS_DEPLOY') ?>'
		});
	</script>
<?else:?>
<html>
<body>
	<script>
		window.reloadAjaxImconnector = function(urlReload, idReload)
		{
			if(
				parent &&
				parent.window &&
				parent.window.opener &&
				parent.window.opener.BX &&
				parent.window.opener.BX.ajax
			)
			{
				parent.window.opener.BX.ajax.insertToNode(urlReload, idReload);
			}
			window.close();
		};
		reloadAjaxImconnector(<?=CUtil::PhpToJSObject($arResult['URL_RELOAD'])?>, <?=CUtil::PhpToJSObject('comp_' . $arResult['RELOAD'])?>);
	</script>
</body>
</html>
<?endif;?>

