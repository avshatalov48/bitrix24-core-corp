<?php

use Bitrix\Intranet\Integration\Socialnetwork\Url\GroupUrl;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Web\Json;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load('ui.icon-set.outline');

$this->setFrameMode(true);

$inputId = trim($arParams["~INPUT_ID"]);
if (empty($inputId))
{
	$inputId = 'title-search-input';
}

$inputId = CUtil::JSEscape($inputId);

$containerId = trim($arParams["~CONTAINER_ID"]);
if ($containerId == '')
{
	$containerId = 'title-search';
}
$containerId = CUtil::JSEscape($containerId);

?>

<div class="air-header__search ui-icon-set__scope" id="air-header-search">
	<div class="air-header__search-container">
		<form class="air-header__search-form" method="get" name="search-form" onsubmit="return false;" id="<?=$containerId?>">
			<input
				type="text"
				class="air-header__search-input"
				autocomplete="off"
				id="<?=$inputId?>"
				placeholder = "<?=GetMessage("CT_BST_SEARCH_HINT")?>"
			>
			<div class="air-header__search-icon" id="air-header-search-icon" tabindex="-1"></div>
		</form>
	</div>
</div>
<? $frame = $this->createFrame()->begin(''); ?>
<script>
	BX.message({
		GLOBAL_SEARCH : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH")?>",
		SEARCH_MORE : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_MORE")?>",
		SEARCH_NO_RESULT : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_NO_RESULT")?>",
		SEARCH_CRM_LEAD : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_LEAD")?>",
		SEARCH_CRM_DEAL : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_DEAL")?>",
		SEARCH_CRM_INVOICE : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_INVOICE")?>",
		SEARCH_CRM_CONTACT : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_CONTACT")?>",
		SEARCH_CRM_COMPANY : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_COMPANY")?>",
		SEARCH_CRM_QUOTE : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_QUOTE_MSGVER_1")?>",
		SEARCH_CRM_DYNAMIC : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_CRM_DYNAMIC")?>",
		SEARCH_TASKS : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_TASKS")?>",
		SEARCH_DISK : "<?=GetMessageJS("CT_BST_GLOBAL_SEARCH_DISK_MSGVER_1")?>"
	});

	new BX.Intranet.Bitrix24.SearchTitle({
		containerId: 'air-header-search',
		buttonId: 'air-header-search-icon',
		inputId: '<?=$inputId?>',
		searchOptions: {
			AJAX_PAGE: '<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>',
			CONTAINER_ID: '<?=$containerId?>',
			INPUT_ID: '<?=$inputId?>',
			MIN_QUERY_LEN: 3,
			FORMAT: 'json',
			CATEGORIES_ALL: <?=Json::encode($arResult['CATEGORIES_ALL'])?>,
			USER_URL: '<?=CUtil::JSEscape(
				Option::get('socialnetwork', 'user_page', SITE_DIR . 'company/personal/', SITE_ID)
				. 'user/#user_id#/'
			)?>',
			GROUP_URL: '<?=CUtil::JSEscape(
				Option::get('socialnetwork', 'workgroups_page', SITE_DIR . 'workgroups/', SITE_ID)
				. 'group/#group_id#/'
			)?>',
			COLLAB_URL: '<?= CUtil::JSEscape(GroupUrl::getCollabTemplate()) ?>',
			WAITER_TEXT: '<?=GetMessageJS('CT_BST_WAITER_TEXT')?>',
			CURRENT_TS: <?=time()?>,
			GLOBAL_SEARCH_CATEGORIES: <?=Json::encode($arResult["GLOBAL_SEARCH_CATEGORIES"])?>,
			MORE_USERS_URL: '<?=SITE_DIR . "company/?apply_filter=Y&with_preset=Y&FIND="?>',
			MORE_GROUPS_URL: '<?=SITE_DIR . "workgroups/?apply_filter=Y&with_preset=Y&FIND="?>',
			IS_CRM_INSTALLED: '<?=ModuleManager::isModuleInstalled("crm") ? "Y" : "N"?>',
		}
	});
</script>
<?
$frame->end();
