<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>

<style>
	#tasks-detail-card-container-over {display: none;}
</style>
<script type="text/javascript">

	app.pullDown({
		enable:   true,
		pulltext: '<?php echo GetMessageJS('MB_TASKS_TASK_SNMR_PULLDOWN_PULL'); ?>',
		downtext: '<?php echo GetMessageJS('MB_TASKS_TASK_SNMR_PULLDOWN_DOWN'); ?>',
		loadtext: '<?php echo GetMessageJS('MB_TASKS_TASK_SNMR_PULLDOWN_LOADING'); ?>',
		callback: function()
		{
			if (MBTasks.reloadPageAndCache && MBTasks.residentTaskId)
			{
				MBTasks.reloadPageAndCache(
					MBTasks.residentTaskId,
					{ showPopupLoader: false }
				);
				//app.pullDownLoadingStop();
			}
			else
				app.reload();
		}
	});

	if ( ! window.MBTasks )
		MBTasks = { lastTimeUIApplicationDidBecomeActiveNotification: 0 };

	if ( ! window.MBTasks.CPT )
		MBTasks.CPT = {};

	MBTasks.CPT.router = {
		UUIDs_processed: []
	};

	MBTasks.firstTime = true;
	//BXMobileApp.UI.Page.LoadingScreen.show();
	app.showPopupLoader();

	MBTasks.CPT.router.arParams = {
		DATE_TIME_FORMAT: '<?php
			echo CUtil::JSEscape(htmlspecialcharsbx($arParams['DATE_TIME_FORMAT']));
		?>',
		PATH_TEMPLATE_TO_USER_PROFILE: '<?php
			echo CUtil::JSEscape(htmlspecialcharsbx($arParams['PATH_TEMPLATE_TO_USER_PROFILE']));
		?>',
		PATH_TO_FORUM_SMILE: '<?php
			echo CUtil::JSEscape(htmlspecialcharsbx($arParams['PATH_TO_FORUM_SMILE']));
		?>',
		AVA_WIDTH: '<?php
			echo CUtil::JSEscape(htmlspecialcharsbx($arParams['AVATAR_SIZE']['width']));
		?>',
		AVA_HEIGHT: '<?php
			echo CUtil::JSEscape(htmlspecialcharsbx($arParams['AVATAR_SIZE']['height']));
		?>'
	};

	MBTasks.sessid = '<?php echo bitrix_sessid(); ?>';
	MBTasks.site = '<?php echo CUtil::JSEscape(SITE_ID); ?>';
	MBTasks.lang = '<?php echo CUtil::JSEscape(LANGUAGE_ID); ?>';
	MBTasks.userId = <?php echo (int) $arParams['USER_ID']; ?>;
	MBTasks.user_path_template = '<?php echo CUtil::JSEscape($arParams['PATH_TEMPLATE_TO_USER_PROFILE']); ?>';
	MBTasks.task_edit_path_template = '<?php
		echo CUtil::JSEscape(
			str_replace(
				array('#USER_ID#', '#user_id'),
				(int) $arParams['USER_ID'],
				$arParams['PATH_TO_USER_TASKS_EDIT']
			)
		);
	?>';
	MBTasks.snmRouterAjaxUrl = '<?=CUtil::JSEscape($arParams['PATH_TO_SNM_ROUTER_AJAX']); ?>';

	MBTasks.preloadedData = false;
	MBTasks.residentTaskId = false;	// ID of task currently located on page
	MBTasks.showedBaseDataHash = false;	// This is for check, that base data changed
	MBTasks.selectedMatrix = false;
	MBTasks.gear = 'L';

	MBTasks.cache = {};


</script>
<!-- <div id="tasks-router-view" style="display:none;"> -->
	<?php
if (CModule::IncludeModule('pull'))
	CPullWatch::Add($arParams['USER_ID'], 'TASKS_GENERAL_' . $arParams['USER_ID']);

		$arComParams = $arParams;
		$arComParams['JUST_SHOW_BULK_TEMPLATE'] = 'Y';

		$APPLICATION->IncludeComponent(
			'bitrix:mobile.tasks.detail', 
			'.default', 
			$arComParams, 
			false
		);
	?>
<!-- </div> -->
<div id="tasks-router-edit" style="display:none;">
	This is edit form placeholder
</div>
<div id="tasks-router-removed-task" style="display:none;">
	<?php echo GetMessage('MB_TASKS_TASK_SNMROUTER_TASK_WAS_REMOVED'); ?>
</div>

<script>
	ReadyDevice(function(){
		<?//__MBTasks__mobile_tasks_view_init() assumed to be initialized already?>

		window.I_3_MOBILE_TASKS_SNM_INIT_B = true;
		__MBTasks__mobile_tasks_snmrouter_init();
		window.I_4_MOBILE_TASKS_SNM_INIT_E = true;

		BX.addCustomEvent(
			'onOpenPageBefore', 
			function() { MBTasks.pageOpened() }
		);

		BX.addCustomEvent(
			'onHidePageAfter', 
			function() { 
				MBTasks.pageHided()
			}
		);

		app.onCustomEvent(
			'onPullExtendWatch',
			{
				id: 'TASKS_GENERAL_' + MBTasks.userId
			}
		);

		BX.addCustomEvent(
			'onPull',
			MBTasks.onPullHandler
		);

		if(!app.enableInVersion(13) && window.platform == 'ios')
		{
			setTimeout(function(){MBTasks.pageOpened()}, 3000);
		}
		else
		{
			MBTasks.pageOpened();
		}
	});
</script>

<?php
