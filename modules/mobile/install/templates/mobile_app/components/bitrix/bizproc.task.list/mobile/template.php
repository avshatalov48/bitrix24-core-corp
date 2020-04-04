<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="'
	.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/bizproc_mobile.js')
	.'"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'lenta-page');

if (strlen($arResult["FatalErrorMessage"]) > 0)
{
	?>
	<span class='errortext' style="color: red"><?= $arResult["FatalErrorMessage"] ?></span>
	<?
	return;
}

if (strlen($arResult["ErrorMessage"]) > 0)
{
	?>
	<span class='errortext' style="color: red"><?= $arResult["ErrorMessage"] ?></span><br /><br />
	<?
}
if (empty($arResult["RECORDS"])):?>
	<div class="bp-empty-search">
		<div class="bp-empty-search-box">
			<div class="bp-empty-search-text"><?=GetMessage("MB_BP_LIST_IS_EMPTY")?></div>
		</div>
	</div>
<?
else: foreach($arResult["RECORDS"] as $record):
	$task = $record['data'];
	if (empty($task['DOCUMENT_ICON']))
	{
		$moduleIcon = 'default';
		if (in_array($task['MODULE_ID'], array('crm', 'disk', 'iblock', 'lists', 'tasks')))
			$moduleIcon = $task['MODULE_ID'];

		$task['DOCUMENT_ICON'] = '/bitrix/templates/mobile_app/images/bizproc/document/bp-'.$moduleIcon.'-icon.png';
	}
	?>
	<div class="lenta-item bp-list-item">
		<div class="post-item-top-wrap">
			<div class="post-item-top">
			</div>
			<div class="post-item-post-block" onclick="return BX.BizProcMobile.openTaskPage(<?=(int)$task['ID']?>, event)">
				<span class="bp-title-desc-icon">
					<img src="<?=htmlspecialcharsbx($task['DOCUMENT_ICON'])?>" width="36" border="0" />
				</span>
				<div class="post-text-title"><?=$task["NAME"]?></div>
				<div class="post-item-text">
					<!-- content -->
					<div class="pb-popup-mobile">
						<div class="bp-post bp-lent">
							<?if ($task["DOCUMENT_NAME"]):?>
								<span class="bp-title-desc">
									<span class=""><?=$task["DOCUMENT_NAME"]?></span>
								</span>
							<?endif?>
							<div class="bp-short-process-inner">
								<?$APPLICATION->IncludeComponent(
									"bitrix:bizproc.workflow.faces",
									"",
									array(
										"WORKFLOW_ID" => $task["WORKFLOW_ID"],
										"TARGET_TASK_ID" => $task["ID"]
									),
									$component
								);
								?>
							</div>
							<?
							if ($task['USER_STATUS'] > CBPTaskUserStatus::Waiting):
								switch ($task['USER_STATUS'])
								{
									case CBPTaskUserStatus::Yes:
										echo '<span class="bp-status-ready"><span>'.GetMessage('BPATL_USER_STATUS_YES').'</span></span>';
										break;
									case CBPTaskUserStatus::No:
									case '4': //CBPTaskUserStatus::Cancel
										echo '<span class="bp-status-cancel"><span>'.GetMessage('BPATL_USER_STATUS_NO').'</span></span>';
										break;
									default:
										echo '<span class="bp-status-ready"><span>'.GetMessage('BPATL_USER_STATUS_OK').'</span></span>';
								}
							elseif ($task['IS_INLINE'] == 'Y'):?>
								<div class="bp-btn-panel">
									<div class="">
										<?
										$controls = CBPDocument::getTaskControls($task);
										foreach ($controls['BUTTONS'] as $control):
											$class = $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::Yes || $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::Ok ? 'accept' : 'decline';
											$props = CUtil::PhpToJSObject(array(
												'TASK_ID' => $task['ID'],
												$control['NAME'] => $control['VALUE'],
											));
											?>

											<a href="javascript:void(0)" onclick="return BX.BizProcMobile.doTask(<?=$props?>)" class="webform-small-button bp-small-button webform-small-button-<?=$class?>">
												<span class="bp-button-icon"></span>
												<span class="bp-button-text"><?=$control['TEXT']?></span>
											</a>
										<?
										endforeach;
										?>
									</div>
								</div>
							<?else:?>
								<div class="bp-btn-panel">
									<a href="javascript:void(0)" class="webform-small-button bp-small-button webform-small-button-blue">
										<span class="bp-button-text"><?=GetMessage("BPATL_BEGIN")?></span>
									</a>
								</div>
							<?endif?>

							<div class="bp-task-block">
								<span class="bp-task-block-title"><?=GetMessage("BPATL_TASK_TITLE")?>: </span>
								<?
								if (strlen($task["DESCRIPTION"]) > 0):
									echo nl2br($task["DESCRIPTION"]);
								else:
									echo $task["NAME"];
								endif;?>
							</div>
						</div>
					</div>
					<!-- /content -->
				</div>
				<div class="post-more-block" style="display: block;"></div>
			</div>
			<div class="post-item-inform-wrap" style="display: block;">
				<a class="post-item-more" onclick="return BX.BizProcMobile.openTaskPage(<?=(int)$task['ID']?>)" style="display: block;"><?=GetMessage('BPATL_TASK_LINK_TITLE')?></a>
			</div>
		</div>
	</div>
<?endforeach; endif;?>

<script type="text/javascript">
	BX.ready(function(){
		app.menuCreate({
			items: [
				{
					name:"<?=GetMessageJs('BPATL_FILTER_STATUS_RUNNING')?>",
					<?=$arResult['currentUserStatus']==0?"image: '/bitrix/templates/mobile_app/images/bizproc/check.png',":''?>
					action: function()
					{
						app.loadPageBlank({
							url: "/mobile/bp/?USER_STATUS=0",
							bx24ModernStyle: true,
							unique: true
						})
					}
				},
				{
					name:"<?=GetMessageJs('BPATL_FILTER_STATUS_COMPLETE')?>",
					<?=$arResult['currentUserStatus']==1?"image: '/bitrix/templates/mobile_app/images/bizproc/check.png',":''?>
					action: function()
					{
						app.loadPageBlank({
							url:"/mobile/bp/?USER_STATUS=1",
							bx24ModernStyle: true,
							unique: true
						})
					}
				},
				{
					name:"<?=GetMessageJs('BPATL_FILTER_STATUS_ALL')?>",
					<?=$arResult['currentUserStatus']==2?"image: '/bitrix/templates/mobile_app/images/bizproc/check.png',":''?>
					action: function()
					{
						app.loadPageBlank({
							url:"/mobile/bp/?USER_STATUS=2",
							bx24ModernStyle: true,
							unique: true
						})
					}
				}
			]
		});
		<? $pageTitle = GetMessageJS("MB_BP_TITLE");
		if ($arResult['currentUserStatus'] > 0)
			$pageTitle = GetMessageJs($arResult['currentUserStatus'] == 2 ? 'BPATL_FILTER_STATUS_ALL' : 'BPATL_FILTER_STATUS_COMPLETE');
		?>
		BXMobileApp.UI.Page.TopBar.title.setText('<?=$pageTitle?>');
		BXMobileApp.UI.Page.TopBar.title.setCallback(function()
		{
			app.menuShow();
		});
		BXMobileApp.UI.Page.TopBar.title.show();

		var h = function() {
			app.reload();
		};
		BX.removeCustomEvent('bpDoTaskComplete', h);
		<?if ($arResult['currentUserStatus']==0):?>
		BXMobileApp.addCustomEvent('bpDoTaskComplete', h);
		<?endif?>
	});

	app.pullDown({
		enable:   true,
		pulltext: '<?php echo GetMessageJS('MB_BP_LIST_PULLDOWN_PULL'); ?>',
		downtext: '<?php echo GetMessageJS('MB_BP_LIST_PULLDOWN_DOWN'); ?>',
		loadtext: '<?php echo GetMessageJS('MB_BP_LIST_PULLDOWN_LOADING'); ?>',
		callback: function()
		{
			app.reload();
		}
	});
</script>