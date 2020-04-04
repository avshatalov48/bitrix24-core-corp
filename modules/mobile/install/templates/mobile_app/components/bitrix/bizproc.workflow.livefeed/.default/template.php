<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$jsTasks = array();
foreach ($arResult['TASKS']['RUNNING'] as $task)
{
	$jsTask = array(
		'ID' => $task['ID'],
		'USERS' => array()
	);
	foreach ($task['USERS'] as $u)
	{
		$jsTask['USERS'][] = array(
			'USER_ID' => $u['USER_ID'],
			'STATUS' => $u['STATUS']
		);
	}
	$jsTasks[] = $jsTask;
}
?>

<?if (!$arResult['noWrap']):?>
<div class="bp-livefeed-wrapper">
<?endif;?>
<div class="pb-popup-mobile" data-role="mobile-log-bp-wf" data-rendered="" data-tasks="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($jsTasks))?>" data-workflow-id="<?=htmlspecialcharsbx($arParams["WORKFLOW_ID"])?>">
	<div class="bp-post bp-lent">
		<div class="bp-short-process-inner">
			<?$APPLICATION->IncludeComponent(
				"bitrix:bizproc.workflow.faces",
				"",
				array(
					"WORKFLOW_ID" => $arParams["WORKFLOW_ID"]
				),
				$component
			);
			?>
		</div>
		<span class="bp-status-ready user_status_yes" style="display: none">
			<span><?=GetMessage('BPATL_USER_STATUS_YES')?></span>
		</span>
		<span class="bp-status-cancel user_status_no" style="display: none">
			<span><?=GetMessage('BPATL_USER_STATUS_NO')?></span>
		</span>
		<span class="bp-status-ready user_status_ok" style="display: none">
			<span><?=GetMessage('BPATL_USER_STATUS_OK')?></span>
		</span>
		<span class="wf_status bp-status" style="display: none">
			<span class="bp-status-inner"><span><?=$arResult["WORKFLOW_STATE_INFO"]['STATE_TITLE']?></span></span>
		</span>
			<?foreach ($arResult['TASKS']['RUNNING'] as $task):?>
				<div class="bp-btn-panel task_buttons_<?=$task['ID']?>" style="display: none">
			<span class="bp-btn-panel-inner">
			<? if ($task['IS_INLINE'] == 'Y'):
				foreach ($task['BUTTONS'] as $control):
					$class = $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::Yes || $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::Ok ? 'accept' : 'decline';
					$props = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode(array(
						'TASK_ID' => $task['ID'],
						'WORKFLOW_ID' => $arParams["WORKFLOW_ID"],
						$control['NAME'] => $control['VALUE'],
					)));
					?>
					<a href="javascript:void(0)" data-task="<?=$props?>" onclick="return BX.BizProcMobile.doTask(JSON.parse(this.getAttribute('data-task')), BX.BizProcMobile.loadLogMessageCallback, true)" class="webform-small-button bp-small-button webform-small-button-<?=$class?>">
						<span class="bp-button-icon"></span>
						<span class="bp-button-text"><?=$control['TEXT']?></span>
					</a>
				<?endforeach;
			else:?>
				<a href="/mobile/bp/detail.php?task_id=<?=$task['ID']?>" onclick="return BX.BizProcMobile.openTaskPage(<?=(int)$task['ID']?>)" class="webform-small-button bp-small-button webform-small-button-blue">
					<span class="bp-button-text"><?=GetMessage("BPATL_BEGIN")?></span>
				</a>
			<?endif?>
			</span>
				</div>
			<?endforeach;?>

		<?foreach ($arResult['TASKS']['RUNNING'] as $task):?>
			<div class="bp-task-block task_block_<?=$task['ID']?>" style="display: none">
				<span class="bp-task-block-title"><?=GetMessage("BPATL_TASK_TITLE")?>: </span>
				<?=$task['NAME']?>
				<? if ($task['DESCRIPTION']):?>
					<br/>
						<?=nl2br($task['DESCRIPTION'])?>
				<?endif?>
			</div>
		<?endforeach;?>
<?if (!$arResult['noWrap']):?>
		<script>
			BX.ready(function() {
				BX.BizProcMobile.renderLogMessages(document);
				BXMobileApp.addCustomEvent('bpDoTaskComplete', function(params) {
					if (params.NEW_LOG_CONTENT && params.UPDATE_ID)
						BX.BizProcMobile.renderLogMessages(document, params.WORKFLOW_ID, params.NEW_LOG_CONTENT, params.UPDATE_ID);
				});
			});
		</script>
<?endif;?>
	</div>
</div>
<?if (!$arResult['noWrap']):?>
	</div>
<?endif;?>