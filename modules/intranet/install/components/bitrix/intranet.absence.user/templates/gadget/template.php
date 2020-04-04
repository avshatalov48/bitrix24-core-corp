<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult['ENTRIES']) && count($arResult['ENTRIES']) > 0):
	?><div class="bx-user-absence-layout"><?
	$bFirst = true;
	foreach ($arResult['ENTRIES'] as $arEntry):
		$ts_start = MakeTimeStamp($arEntry['DATE_ACTIVE_FROM']);
		$ts_finish = MakeTimeStamp($arEntry['DATE_ACTIVE_TO']);
		$ts_now = time();

		$bNow = $ts_now >= $ts_start && $ts_now <= $ts_finish;
		if (!$bFirst):
			?><br /><?
		endif;
		?>
		<div class="bx-user-absence-entry<?echo $bNow ? ' bx-user-absence-now' : ''?>">
		<span class="bx-user-absence-entry-date"><?echo GetMessage('INTR_IAU_TPL'.($bNow ? '_TO' : '_FROM'))?> <?echo FormatDate($DB->DateFormatToPHP(FORMAT_DATETIME), MakeTimeStamp($arEntry['DATE_ACTIVE'.($bNow ? '_TO' : '_FROM')]))?></span><br>
		<span class="bx-user-absence-entry-title"><?echo htmlspecialcharsbx($arEntry['TITLE'])?></span>
		</div><?
		$bFirst = false;
	endforeach;
	?></div><?
else:
	echo GetMessage('INTR_IAU_TPL_NOT_FOUND');
endif;
?>