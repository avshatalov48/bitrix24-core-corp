<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 */

$superGlobals = array(
	'_GET'=>1, '_SESSION'=>1, '_POST'=>1, '_COOKIE'=>1, '_REQUEST'=>1, '_FILES'=>1, '_SERVER'=>1, 'GLOBALS'=>1,
	'_ENV'=>1, 'DBType'=>1,  'DBDebug'=>1, 'DBDebugToFile'=>1, 'DBHost'=>1, 'DBName'=>1, 'DBLogin'=>1,
	'DBPassword'=>1, 'HTTP_ENV_VARS'=>1, 'HTTP_GET_VARS'=>1, 'HTTP_POST_VARS'=>1, 'HTTP_POST_FILES'=>1,
	'HTTP_COOKIE_VARS'=>1, 'HTTP_SERVER_VARS'=>1
);
foreach($superGlobals as $key => $value)
{
	unset($_REQUEST[$key]);
}
foreach($_REQUEST as $key => $value)
{
	$name = $key;
	if (!isset($$name))
	{
		$$name = $value;
	}
}

if ($arResult["IS_SIDE_PANEL"]): ?>
	<? LocalRedirect($arResult["REDIRECT_URL"])?>
<? else: ?>
	<? if ($arResult["INTERNAL_PAGE"]): ?>
		<?
			$titleClass = $APPLICATION->getPageProperty("TitleClass", false);
			$APPLICATION->setPageProperty("TitleClass", trim(sprintf("%s %s", $titleClass, "pagetitle-wrap-hide")));
		?>
		<iframe id="internal-page-iframe" src="<?=$arResult["FRAME_URL"]?>" frameborder="0" class="internal-page-iframe">
		</iframe>
	<? else: ?>
		<? define("INTERNAL_ADMIN_PAGE", "Y"); ?>
		<? $bodyClass = $APPLICATION->getPageProperty("BodyClass", false); ?>
		<? $APPLICATION->setPageProperty("BodyClass", trim(sprintf("%s %s", $bodyClass, "pagetitle-toolbar-field-view no-background"))); ?>
		<? require_once($_SERVER['DOCUMENT_ROOT'].$arResult["PAGE_PATH"]); ?>
	<? endif; ?>
<? endif; ?>

<script>
	BX.ready(function() {
		new BX.Main.AdminPageInclude({
			pagePath: '<?=CUtil::JSEscape($arResult["PAGE_PATH"])?>',
			pageParams: '<?=CUtil::JSEscape($arResult["PAGE_PARAMS"])?>'
		});

		<? if ($arResult["INTERNAL_PAGE"]): ?>
			var iframe = BX("internal-page-iframe");
			BX.bindOnce(iframe, "load", function() {
				iframe.style.height = iframe.contentDocument.body.scrollHeight + "px";
				BX.bind(iframe.contentDocument, "click", function() {
					if (BX.PopupMenu.getMenuById("main_buttons_popup_child_store"))
					{
						BX.PopupMenu.getMenuById("main_buttons_popup_child_store").close();
					}
				});
			});
		<? endif; ?>
	});
</script>