<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult['ENTRIES']) && count($arResult['ENTRIES']) > 0):
?>
<table width="100%" class="sonet-user-profile-groups data-table">
	<tr>
		<th><?= GetMessage("SONET_ABSENCE_USER_TITLE") ?></th>
	</tr>
	<tr>
		<td>
<div class="bx-user-absence-layout">
<?
	foreach ($arResult['ENTRIES'] as $arEntry):
		$ts_start = MakeTimeStamp($arEntry['DATE_ACTIVE_FROM']);
		$ts_finish = MakeTimeStamp($arEntry['DATE_ACTIVE_TO']);
		$ts_now = time();

		$bNow = $ts_now >= $ts_start && $ts_now <= $ts_finish;
?>
	<div class="bx-user-absence-entry<?echo $bNow ? ' bx-user-absence-now' : ''?>">
		<span class="bx-user-absence-entry-title"><?echo htmlspecialcharsbx($arEntry['TITLE'])?></span>
		<span class="bx-user-absence-entry-date"><?echo GetMessage('INTR_IAU_TPL'.($bNow ? '_TO' : '_FROM'))?> <?echo FormatDate($DB->DateFormatToPHP(FORMAT_DATETIME), MakeTimeStamp($arEntry['DATE_ACTIVE'.($bNow ? '_TO' : '_FROM')]))?></span>
	</div>
<?
	endforeach;
?>
</div>
		</td>
	</tr>
</table>
<?
endif;
?>