<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//echo '<pre>', print_r($arParams, true), print_r($arResult, true), '</pre>';
if (!is_array($arResult['ENTRIES']) || count($arResult['ENTRIES']) <= 0):
	echo GetMessage('INTR_ISHU_TPL_NULL');
else:
?>
<div class="bx-user-honour-layout">
<?
	foreach ($arResult['ENTRIES'] as $arEntry):
		$ts_start = MakeTimeStamp($arEntry['DATE_ACTIVE_FROM']);
		$ts_finish = MakeTimeStamp($arEntry['DATE_ACTIVE_TO']);
		$ts_now = time();
		
		$bNow = $ts_now >= $ts_start && $ts_now <= $ts_finish;

?>
	<div class="bx-user-honour-entry<?echo $bNow ? ' bx-user-honour-now' : ''?>">
		<span class="bx-user-honour-entry-date"><?echo $arEntry['DATE_ACTIVE_FROM']?></span>
		<span class="bx-user-honour-entry-title"><?echo htmlspecialcharsbx($arEntry['TITLE'])?></span>
	</div>
<?
	endforeach;
?>
</div>
<?
endif;
?>