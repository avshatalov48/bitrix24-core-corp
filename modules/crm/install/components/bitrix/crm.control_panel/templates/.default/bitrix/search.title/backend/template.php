<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$INPUT_ID = trim($arParams['~INPUT_ID']);
if($INPUT_ID === '')
	$INPUT_ID = 'title-search-input';

$CONTAINER_ID = trim($arParams['~CONTAINER_ID']);
if($CONTAINER_ID === '')
	$CONTAINER_ID = 'title-search';

?><script type="text/javascript">
if(typeof(BX.CrmSearchControl) === "undefined")
{
	BX.CrmSearchControl = { items: {} };
}
BX.CrmSearchControl.items["<?=CUtil::JSEscape($CONTAINER_ID)?>"] = new JCTitleSearch(
	{
		"AJAX_PAGE" : "<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>",
		"CONTAINER_ID": "<?=CUtil::JSEscape($CONTAINER_ID)?>",
		"INPUT_ID": "<?=CUtil::JSEscape($INPUT_ID)?>",
		"MIN_QUERY_LEN": 2
	}
);
</script>
