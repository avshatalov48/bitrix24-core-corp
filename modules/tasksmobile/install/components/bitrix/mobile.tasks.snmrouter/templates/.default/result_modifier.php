<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @var array $arParams
 * @var CUser $USER
 */
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/tasks/logic.js');
$APPLICATION->AddHeadString('<link href="'.CUtil::GetAdditionalFileURL($this->getFolder().'/style.css').'" type="text/css" rel="stylesheet" />');
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
?>
<script>
BX.debugEnable(true);
BX.message({
	PAGE_TITLE : '<?=GetMessageJS('MB_TASKS_GENERAL_TITLE')?>',
	MB_TASKS_ROLES_TASK_ADD : '<?=GetMessageJS('MB_TASKS_ROLES_TASK_ADD')?>',
	MB_TASKS_TASK_ERROR_TITLE : '<?=GetMessageJS('MB_TASKS_TASK_ERROR_TITLE')?>',
	MB_TASKS_PULLDOWN_PULL : '<?=GetMessageJS('MB_TASKS_TASKS_LIST_PULLDOWN_PULL')?>',
	MB_TASKS_PULLDOWN_DOWN : '<?=GetMessageJS('MB_TASKS_TASKS_LIST_PULLDOWN_DOWN')?>',
	MB_TASKS_PULLDOWN_LOADING : '<?=GetMessageJS('MB_TASKS_TASKS_LIST_PULLDOWN_LOADING')?>',
	MB_TASKS_TASKS_LIST_MENU_GOTO_FILTER : '<?=GetMessageJS('MB_TASKS_TASKS_LIST_MENU_GOTO_FILTER')?>',
	PATH_TO_USER_TASKS_LIST_SORT : '<?=CUtil::JSEscape($arParams['PATH_TO_USER_TASKS_LIST_SORT'])?>',
	MB_TASKS_TASKS_LIST_MENU_CREATE_NEW_TASK : '<?=GetMessageJS('MB_TASKS_TASKS_LIST_MENU_CREATE_NEW_TASK')?>',
	TASK_DATE_TIME_FORMAT: '<?=CUtil::JSEscape($arParams['DATE_TIME_FORMAT'])?>',
	TASK_PATH_TO_USER_PROFILE: '<?=CUtil::JSEscape($arParams['PATH_TEMPLATE_TO_USER_PROFILE'])?>',
	TASK_PATH_TO_SNM_ROUTER : '<?=CUtil::JSEscape($arParams['PATH_TO_SNM_ROUTER']); ?>',
	TASK_PATH_TO_AJAX : '<?=CUtil::JSEscape($arParams['PATH_TO_SNM_ROUTER_AJAX']); ?>',
	TASK_PATH_TO_FILTER : '<?=CUtil::JSEscape($arParams['PATH_TO_USER_TASKS_FILTER']) ?>',
	TASK_PATH_TO_READ : '<?=CUtil::JSEscape($arParams['PATH_TO_USER_TASKS_TASK'])?>',
	TASK_PATH_TO_EDIT : ('<?=CUtil::JSEscape($arParams['PATH_TO_USER_TASKS_EDIT'])?>' + (!window.app.enableInVersion(15) ? '&s=#SALT#' : '')),
	TASK_PATH_TO_CREATE : ('<?=CUtil::JSEscape(str_replace(array("#TASK_ID#", "#task_id#"), 0, $arParams['PATH_TO_USER_TASKS_EDIT']))?>' + (!window.app.enableInVersion(15) ? '&s=#SALT#' : '')),
	TASK_PATH_TO_SELECTOR : '<?=CUtil::JSEscape($arParams['PATH_TO_USER_TASKS_SELECTOR'])?>'
});
BX.ready(function(){
	BX.namespace("BX.Mobile.Tasks");
	BX.Mobile.Tasks.statusMap = <?=CUtil::PhpToJSObject(CTaskItem::getStatusMap())?>;
	BX.Mobile.Tasks.createWindow = function(uid, parent_id) {
		uid = (uid || BX("USER_ID"));
		parent_id = (parent_id||0);
		var url = BX.message('TASK_PATH_TO_CREATE').
			replace(/#USER_ID#/gi, uid).
			replace(/#SALT#/gi, new Date().getTime()),
			f = function(taskId, wId, task){
			BX.removeCustomEvent("onTaskWasCreated", f);
			task = (!task && BX.type.isArray(taskId) ? taskId[2] : task);
			if (task && task["ID"]) {
				window.BXMobileApp.PageManager.loadPageUnique({
				url: BX.message('TASK_PATH_TO_READ').replace(/#TASK_ID#/gi, task["ID"]).replace(/#USER_ID#/gi, BX.message('USER_ID')),
				bx24ModernStyle : true
			}, 100);
			}
		};
		if (parent_id > 0)
			url = BX.util.add_url_param(url, {PARENT_ID : parent_id});

		var reg = /GROUP_ID=(\d+)/gi,
			h = window.location.href,
			r;
		while (h.indexOf("LIST_MODE=TASKS_FROM_GROUP") >= 0 && (r = reg.exec(h)) !== null)
		{
			url += ("&" + r[0]);
		}
		BXMobileApp.addCustomEvent("onTaskWasCreated", f);
		window.BXMobileApp.PageManager.loadPageModal({
			url: url,
			bx24ModernStyle : true,
			cache : !window.app.enableInVersion(15)
		});
	};
});
<?
	if (CModule::IncludeModule('pull') && $USER->IsAuthorized())
	{
		\CPullWatch::Add($USER->getId(), 'TASKS_GENERAL_'.$arParams['USER_ID']);
		?>BXMobileApp.onCustomEvent('onPullExtendWatch', { id: 'TASKS_GENERAL_<?=$arParams['USER_ID']?>'}, true);<?
	}
?>
</script>