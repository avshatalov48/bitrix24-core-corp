<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.notification");

CJSCore::Init(['documentpreview', 'sidepanel']);

if($arResult['TOP_VIEW_TARGET_ID'])
{
	$this->SetViewTarget($arResult['TOP_VIEW_TARGET_ID']);
}
?>
				<? $APPLICATION->IncludeComponent(
					"bitrix:main.ui.filter",
					"",
					$arResult['FILTER']
				); ?>
<?
if($arResult['TOP_VIEW_TARGET_ID'])
{
	$this->EndViewTarget();
}
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