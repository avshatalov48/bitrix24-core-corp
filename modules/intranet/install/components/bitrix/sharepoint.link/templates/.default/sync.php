<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); 

if ($arResult['RESULT']['MORE_ROWS']):
?>
<script type="text/javascript">
BXSPSync(<?=intval($arResult['RESULT']['COUNT'])?>);
</script>
<?
else:
?>
<script type="text/javascript">
window.BXSPData.loaded += <?=intval($arResult['RESULT']['COUNT'])?>;
</script>
<?
	if ($arResult['QUEUE_EXISTS']):
?>
<script type="text/javascript">
BXSPSyncAdditions('queue');
</script>
<?
	elseif ($arResult['LOG_EXISTS']):
?>
<script type="text/javascript">
BXSPSyncAdditions('log');
</script>
<?
	else:
?>
<script type="text/javascript">
alert(window.BXSPData.loaded + ' items loaded.');
if (window.BXSPData.loaded > 0)
	BX.reload();
</script>
<?
	endif;
endif;
?>