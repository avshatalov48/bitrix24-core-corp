<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var CrmOrderConnectorInstagramEdit $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFolder
 * @var string $componentPath
 */

if (!empty($arResult['URL_RELOAD']))
{
	?>
	<html>
	<body>
	<script>
		window.reloadAjaxConnector = function(urlReload)
		{
			parent.window.opener.location.href = urlReload; //parent.window.opener construction is used for both frame and page mode as universal
			parent.window.opener.addPreloader();
			window.close();
		};
		reloadAjaxConnector(<?=CUtil::PhpToJSObject($arResult['URL_RELOAD'])?>);
	</script>
	</body>
	</html>
	<?
}
