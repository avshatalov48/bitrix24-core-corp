<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); 

if ($arResult['RESULT']['MORE_ROWS']):
?>
<script>
BXSPSync(<?=intval($arResult['RESULT']['COUNT'])?>);
</script>
<?
else:
?>
<script>
window.BXSPData.loaded += <?=intval($arResult['RESULT']['COUNT'])?>;
</script>
<?
	if ($arResult['QUEUE_EXISTS']):
?>
<script>
BXSPSyncAdditions('queue');
</script>
<?
	elseif ($arResult['LOG_EXISTS']):
?>
<script>
BXSPSyncAdditions('log');
</script>
<?
	else:
?>
<script>
alert(window.BXSPData.loaded + ' items loaded.');
if (window.BXSPData.loaded > 0)
	BX.reload();
</script>
<?
	endif;
endif;
?>