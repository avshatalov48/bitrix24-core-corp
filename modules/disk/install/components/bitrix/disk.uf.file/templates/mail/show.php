<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

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
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
if (empty($arResult['FILES']))
	return;

$this->IncludeLangFile("show.php");

?>
<div>
<table cellspacing="0" cellpadding="0" border="0" align="left" style="border-collapse: collapse;mso-table-lspace: 0pt;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: 13px;">
<tr>
	<td valign="top" style="border-collapse: collapse;border-spacing: 0;color: #000000;padding: 5px 5px 0 0;vertical-align: top;"><?=GetMessage('MPL_FILES')?></td>
	<td valign="top" style="border-collapse: collapse;border-spacing: 0; padding: 5px 0 0 0;"><?
	foreach ($arResult['FILES'] as $file)
	{
		?><a href="<?=$file["VIEW_URL"]?>" style="color: #146cc5;"><?=$file["NAME"]?> (<?=$file["SIZE"]?>)</a><br /><?
	}
	?></td>
</tr>
</table>
</div>