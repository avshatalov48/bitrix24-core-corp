<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
if (($templateParamsList ?? null) === null)
{
	$templateParamsList = $arResult['templateParamsList'];
}
$cellHtml = '';
foreach ((array)$templateParamsList as $templateParams)
{
	/** @var \Bitrix\Timeman\Component\WorktimeGrid\TemplateParams $templateParams */
	ob_start();
	?>
	<div data-day-cell-key="<?php echo htmlspecialcharsbx($templateParams->getDayCellId()) ; ?>" data-shift-block="true">
	<?
	require __DIR__ . '/cell.php';
	?>
	</div>
	<?
	$cellHtml .= ob_get_clean();
}
?>
<?php
if (empty($templateParamsList) && $arResult['PARTIAL_ITEM'] && $arResult['drawingDate'])
{
	?>
	<div data-day-cell-key="<?php echo htmlspecialcharsbx(\Bitrix\Timeman\Component\WorktimeGrid\TemplateParams::getDayCellIdByData($arResult['USER_ID'], $arResult['drawingDate'])); ?>"
			data-shift-block="true"
	></div>
	<?
}
echo $cellHtml;