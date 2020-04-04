<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<script>
	BX.message({
		TASKS_CONTEXT_PARTS_MODE : '<?php echo CUtil::JSEscape($arParams['MODE']); ?>',
		TASKS_DELETE_SUCCESS: '<?=GetMessage('TASKS_DELETE_SUCCESS')?>'
	});
</script>
<?php
foreach ($arResult['BLOCKS'] as $blockName)
	require_once($_SERVER["DOCUMENT_ROOT"] . $templateFolder . '/' . $blockName . '.php');
