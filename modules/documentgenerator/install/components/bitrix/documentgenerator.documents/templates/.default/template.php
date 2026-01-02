<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\UI\Extension::load("ui.notification");

CJSCore::Init(['documentpreview', 'sidepanel']);

?>
	<div class="docs-template-grid">
		<?$APPLICATION->IncludeComponent(
			"bitrix:main.ui.grid",
			"",
			$arResult['GRID']
		);?>
	</div>
	<script>
		BX.ready(function()
		{
			BX.DocumentGenerator.DocumentList.init('<?=CUtil::JSEscape($arResult['GRID']['GRID_ID']);?>');
			<?='BX.message('.\CUtil::PhpToJSObject(\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__)).');'?>
		});
	</script>
