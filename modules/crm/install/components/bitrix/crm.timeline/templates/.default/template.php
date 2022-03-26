<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Integration;

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/message.js');

Bitrix\Main\UI\Extension::load(
	[
		'ui.buttons',
		'ui.icons',
		'ui.selector',
		'crm.zoom',
		'ui.timeline',
		'ui.forms',
		'crm.delivery.taxi',
		'crm.timeline',
		'sidepanel',
		'crm.restriction.bitrix24',
		'ui.hint',
	]
);

//HACK: Preloading files for prevent trembling of player afer load.
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/timeline_player/timeline_player.css');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/calendar/planner.css');

if(\Bitrix\Main\Loader::includeModule('disk'))
{
	Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');
	Bitrix\Main\UI\Extension::load([
		'ajax',
		'core',
		'disk_external_loader',
		'ui.tooltip',
		'ui.viewer',
		'ui.hint',
		'disk.document',
		'disk.viewer.document-item',
		'disk.viewer.actions',
	]);
}

$jsLibraries = array('crm_visit_tracker', 'ui.viewer', 'player');

if(\Bitrix\Main\Loader::includeModule('voximplant'))
{
	$jsLibraries[] = 'voximplant_transcript';
}
$spotlightFastenShowed = true;
if (!$arResult['READ_ONLY'])
{
	$spotlight = new \Bitrix\Main\UI\Spotlight("CRM_TIMELINE_FASTEN_SWITCHER");
	if(!$spotlight->isViewed($USER->GetID()))
	{
		$jsLibraries[] = 'spotlight';
		$spotlightFastenShowed = false;
	}
}

CJSCore::Init($jsLibraries);

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$listContainerID = "{$prefix}_list";
$editorContainerID = "{$prefix}_editor";
$menuBarContainerID = "{$prefix}_menu_bar";

$commentContainerID = "{$prefix}_comment_container";
$commentInputID = "{$prefix}_comment";
$commentButtonID = "{$prefix}_comment_button";
$commentCancelButtonID = "{$prefix}_comment_cancel_button";

$waitContainerID = "{$prefix}_wait_container";
$waitConfigContainerID = "{$prefix}_wait_interval";
$waitInputID = "{$prefix}_wait";
$waitButtonID = "{$prefix}_wait_button";
$waitCancelButtonID = "{$prefix}_wait_cancel_button";

$smsContainerID = "{$prefix}_sms_container";
$smsInputID = "{$prefix}_sms";
$smsTemplatesContainerId = "{$prefix}_sms_templates_container";
$smsButtonID = "{$prefix}_sms_button";
$smsCancelButtonID = "{$prefix}_sms_cancel_button";
$fileUploaderZoneId = "diskuf-selectdialog-{$prefix}";
$fileInputName = "{$prefix}-sms-files";
$fileUploaderInputName = "{$prefix}-sms-files-uploader";

$zoomContainerID = "{$prefix}_zoom_container";

$activityEditorID = "{$prefix}_editor";
$scheduleItems = $arResult['SCHEDULE_ITEMS'];
$historyItems = $arResult['HISTORY_ITEMS'];
$fixedItems = $arResult['FIXED_ITEMS'];

if(!empty($arResult['ERRORS']))
{
	foreach($arResult['ERRORS'] as $error)
	{
		ShowError($error);
	}
	return;
}

?>
<div class="crm-entity-stream-container-content">
	<div id="<?=htmlspecialcharsbx($listContainerID)?>" class="crm-entity-stream-container-list">
		<div id="<?=htmlspecialcharsbx($editorContainerID)?>" class="crm-entity-stream-section crm-entity-stream-section-new">
			<div class="crm-entity-stream-section-icon crm-entity-stream-section-icon-new"></div>
			<div class="crm-entity-stream-section-content">
				<?
				$baseMenuItem = [
					"TEXT" => "Some item",
					"URL" => "javascript:void(0);",
					"ID" => "some",
					"ON_CLICK" => "BX.onCustomEvent('".$prefix."_menu', ['#ID#']);"
				];
				$menuItems = [([
					"ID" => "comment",
					"TITLE" => GetMessage("CRM_TIMELINE_COMMENT"),
					"TEXT" => GetMessage("CRM_TIMELINE_COMMENT"),
				] + $baseMenuItem)];

				if ($arResult["ENABLE_TASK"])
				{
					$menuItems[] = [
							"ID" => "task",
							"TEXT" => GetMessage('CRM_TIMELINE_TASK'),
							"TITLE" => GetMessage('CRM_TIMELINE_TASK'),
						] + $baseMenuItem;
				}

				if ($arResult["ENABLE_SMS"])
				{
					$menuItems[] = [
							"ID" => "sms",
							"TEXT" =>
								(\Bitrix\Main\Loader::includeModule('messageservice') && \Bitrix\MessageService\Sender\Sms\Ednaru::isSupported())
									? GetMessage("CRM_TIMELINE_SMS_TITLE2")
									: GetMessage("CRM_TIMELINE_SMS_TITLE")
							,
							"TITLE" => GetMessage("CRM_TIMELINE_SMS") ,
						] + $baseMenuItem;
				}

				if ($arResult["ENABLE_EMAIL"])
				{
					$menuItems[] = [
							"ID" => "email",
							"TEXT" => GetMessage('CRM_TIMELINE_EMAIL'),
							"TITLE" => GetMessage('CRM_TIMELINE_EMAIL'),
						] + $baseMenuItem;
				}

				if ($arResult["ENABLE_DELIVERY"])
				{
					$menuItems[] = [
							"ID" => "delivery",
							"TEXT" => GetMessage("CRM_TIMELINE_DELIVERY"),
							"TITLE" => GetMessage("CRM_TIMELINE_DELIVERY"),
						] + $baseMenuItem;
				}

				if ($arResult["ENABLE_WAIT"])
				{
					$menuItems[] = [
						"ID" => "wait",
						"TEXT" => GetMessage("CRM_TIMELINE_WAIT"),
						"TITLE" => GetMessage("CRM_TIMELINE_WAIT"),
					] + $baseMenuItem;
				}

				if ($arResult["ENABLE_ZOOM"])
				{
					$menuItems[] = [
						"ID" => "zoom",
						"TEXT" => GetMessage("CRM_TIMELINE_ZOOM"),
						"TITLE" => GetMessage("CRM_TIMELINE_ZOOM"),
						"ON_CLICK" => ($arResult['STATUS_ZOOM'] ? "BX.onCustomEvent('".$prefix."_menu', ['#ID#']);" : "BX.Crm.Zoom.onNotConnectedHandler({$arResult["USER_ID"]})")
					] + $baseMenuItem;
				}
				elseif (
					Bitrix\Main\Loader::includeModule("bitrix24")
					&& (!isset($arParams['~ENABLE_ZOOM']) || $arParams['~ENABLE_ZOOM'])
				)
				{
					$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", []);
					$menuItems[] = [
						"ID" => "zoom",
						"TEXT" => GetMessage("CRM_TIMELINE_ZOOM"),
						"TITLE" => GetMessage("CRM_TIMELINE_ZOOM"),
						"IS_LOCKED" => true,
						"ON_CLICK" => "BX.Crm.Zoom.onNotAvailableHandler()",
					] + $baseMenuItem;
				}

				if ($arResult["ENABLE_MEETING"])
				{
					$menuItems[] = [
							"ID" => "meeting",
							"TEXT" => GetMessage('CRM_TIMELINE_MEETING'),
							"TITLE" => GetMessage('CRM_TIMELINE_MEETING'),
						] + $baseMenuItem;
				}

				if ($arResult["ENABLE_CALL"])
				{
					$menuItems[] = [
						"ID" => "call",
						"TEXT" => GetMessage("CRM_TIMELINE_CALL"),
						"TITLE" => GetMessage("CRM_TIMELINE_CALL"),
					] + $baseMenuItem;
				}

				if ($arResult['ENABLE_VISIT'])
				{
					$menuItems[] = [
							'ID' => 'visit',
							'TEXT' => GetMessage('CRM_TIMELINE_VISIT'),
							'TITLE' => GetMessage('CRM_TIMELINE_VISIT'),
						] + ($arResult['IS_VISIT_RESTRICTED'] ? [
							'IS_LOCKED' => true,
						] : [] ) + $baseMenuItem;
				}

				foreach($arResult['ADDITIONAL_TABS'] as $tab)
				{
					$menuItems[] = [
						"ID" => $tab['id'],
						"TEXT" => \Bitrix\Main\Text\HtmlFilter::encode($tab['name']),
						"TITLE" => \Bitrix\Main\Text\HtmlFilter::encode($tab['name']),
					] + $baseMenuItem;
				}

				$menuBarObjectId = mb_strtolower($arResult['ENTITY_TYPE_NAME']);
				if (isset($arResult['EXTRAS']['CATEGORY_ID']) && $arResult['EXTRAS']['CATEGORY_ID'] > 0)
				{
					$menuBarObjectId .= '_' . $arResult['EXTRAS']['CATEGORY_ID'];
				}
				$menuBarObjectId .= '_menu';
				$mode = true;

				if (array_key_exists('ENTITY_CONFIG_SCOPE', $arParams)
					&& array_key_exists('USER_SCOPE_ID', $arParams)
					&& $arParams['ENTITY_CONFIG_SCOPE'] !== Bitrix\Crm\Entity\EntityEditorConfigScope::PERSONAL)
				{
					$menuBarObjectIdParts = [
						'crm_scope_timeline',
						$arParams['ENTITY_CONFIG_SCOPE'],
						$arResult['ENTITY_TYPE_NAME'],
					];
					if (isset($arResult['EXTRAS']['CATEGORY_ID']) && $arResult['EXTRAS']['CATEGORY_ID'] > 0)
					{
						$menuBarObjectIdParts[] = $arResult['EXTRAS']['CATEGORY_ID'];
					}
					$menuBarObjectIdParts[] = $arParams['USER_SCOPE_ID'];

					$menuBarObjectId = mb_strtolower(implode('_', $menuBarObjectIdParts));
					$mode = CCrmAuthorizationHelper::CheckConfigurationUpdatePermission() ? 'common' : false;
				}
				?><div class="crm-entity-stream-section-menu"><?$APPLICATION->IncludeComponent(
					"bitrix:main.interface.buttons",
					"",
					array(
						"ID" => $menuBarObjectId,
						"ITEMS" => array_map(function ($item) {
							$item["ON_CLICK"] = str_replace("#ID#", $item["ID"], $item["ON_CLICK"]);
							return $item;
						}, $menuItems),
						"EDIT_MODE" => $mode,
						"THEME" => "compact",
					)
				);?></div>
				<div id="<?=htmlspecialcharsbx($commentContainerID)?>" class="crm-entity-stream-content-new-detail">
					<textarea id="<?=htmlspecialcharsbx($commentInputID)?>" rows="1" class="crm-entity-stream-content-new-comment-textarea" placeholder="<?=GetMessage('CRM_TIMELINE_COMMENT_PLACEHOLDER')?>"></textarea>
					<div class="crm-entity-stream-content-new-comment-btn-container">
						<button id="<?=htmlspecialcharsbx($commentButtonID)?>" class="ui-btn ui-btn-xs ui-btn-primary">
							<?=GetMessage('CRM_TIMELINE_SEND')?>
						</button>
						<span id="<?=htmlspecialcharsbx($commentCancelButtonID)?>" class="ui-btn ui-btn-xs ui-btn-link"><?=GetMessage('CRM_TIMELINE_CANCEL_BTN')?></span>
					</div>
				</div>
				<div id="<?=htmlspecialcharsbx($waitContainerID)?>" class="crm-entity-stream-content-wait-detail focus" style="display: none;">
					<div class="crm-entity-stream-content-wait-conditions-container">
						<div class="crm-entity-stream-content-wait-conditions" id="<?=htmlspecialcharsbx($waitConfigContainerID)?>">
						</div>
					</div>
					<textarea id="<?=htmlspecialcharsbx($waitInputID)?>" rows="1" class="crm-entity-stream-content-wait-comment-textarea" placeholder="<?=GetMessage('CRM_TIMELINE_WAIT_PLACEHOLDER')?>"></textarea>
					<div class="crm-entity-stream-content-wait-comment-btn-container">
						<button id="<?=htmlspecialcharsbx($waitButtonID)?>" class="ui-btn ui-btn-xs ui-btn-primary">
							<?=GetMessage('CRM_TIMELINE_CREATE_WAITING')?>
						</button>
						<span id="<?=htmlspecialcharsbx($waitCancelButtonID)?>" class="ui-btn ui-btn-xs ui-btn-link"><?=GetMessage('CRM_TIMELINE_CANCEL_BTN')?></span>
					</div>
				</div>
				<div id="<?=htmlspecialcharsbx($smsContainerID)?>" class="crm-entity-stream-content-new-detail focus" style="display: none;">
					<?if (!$arResult['SMS_CAN_SEND_MESSAGE']):?>
					<div class="crm-entity-stream-content-sms-conditions-container">
						<div class="crm-entity-stream-content-sms-conditions">
							<div class="crm-entity-stream-content-sms-conditions-text">
								<strong><?=GetMessage("CRM_TIMELINE_SMS_MANAGE_TEXT_1")?></strong><br>
								<?=GetMessage("CRM_TIMELINE_SMS_MANAGE_TEXT_2")?><br>
								<?=GetMessage("CRM_TIMELINE_SMS_MANAGE_TEXT_3")?>
								<!--<span class="crm-entity-stream-content-sms-conditions-helper-icon"></span>-->
							</div>
						</div>
					</div>
					<div class="crm-entity-stream-content-new-sms-btn-container">
						<a href="#" data-role="sender-selector" target="_top" class="crm-entity-stream-content-new-sms-connect-link"><?=GetMessage("CRM_TIMELINE_SMS_MANAGE_URL")?></a>
						<?php if($arResult['ENABLE_SALESCENTER'])
						{?>
							<div class="crm-entity-stream-content-sms-salescenter-container-absolute" data-role="salescenter-starter">
								<div class="crm-entity-stream-content-sms-salescenter-icon"></div>
								<div class="crm-entity-stream-content-sms-button-text"><?=GetMessage('CRM_TIMELINE_SMS_SALESCENTER_STARTER')?></div>
							</div>
							<?php
						}
						?>
					</div>
					<?else:?>
					<div class="crm-entity-stream-content-sms-buttons-container">
						<?php if($arResult['ENABLE_SALESCENTER'])
						{?>
						<div class="crm-entity-stream-content-sms-button" data-role="salescenter-starter">
							<div class="crm-entity-stream-content-sms-salescenter-icon"></div>
							<div class="crm-entity-stream-content-sms-button-text"><?=GetMessage('CRM_TIMELINE_SMS_SALESCENTER_STARTER')?></div>
						</div>
						<?php
						}
						if($arResult['ENABLE_FILES'])
						{
						?>
						<div class="crm-entity-stream-content-sms-button" data-role="sms-file-selector">
							<div class="crm-entity-stream-content-sms-file-icon"></div>
							<div class="crm-entity-stream-content-sms-button-text"><?=GetMessage('CRM_TIMELINE_SMS_SEND_FILE')?></div>
						</div>
						<?php
						}
						if($arResult['ENABLE_DOCUMENTS'])
						{?>
						<div class="crm-entity-stream-content-sms-button" data-role="sms-document-selector">
							<div class="crm-entity-stream-content-sms-document-icon"></div>
							<div class="crm-entity-stream-content-sms-button-text"><?=GetMessage('CRM_TIMELINE_SMS_SEND_DOCUMENT');?></div>
						</div>
						<?}?>
						<div class="crm-entity-stream-content-sms-detail-toggle" data-role="sms-detail-switcher">
							<?=GetMessage('CRM_TIMELINE_DETAILS');?>
						</div>
					</div>
					<div class="crm-entity-stream-content-sms-conditions-container hidden" data-role="sms-detail">
						<div class="crm-entity-stream-content-sms-conditions">
							<div class="crm-entity-stream-content-sms-conditions-text">
								<?=GetMessage('CRM_TIMELINE_SMS_SENDER')?> <a href="#" data-role="sender-selector">sender</a><span data-role="from-container"><?=GetMessage('CRM_TIMELINE_SMS_FROM')?><?
									?> <a data-role="from-selector" href="#">from_number</a></span><?
								?><span data-role="client-container"> <?=GetMessage('CRM_TIMELINE_SMS_TO')?> <a data-role="client-selector" href="#">client_caption</a> <a data-role="to-selector" href="#">to_number</a></span>
							</div>
							<!--<span class="crm-entity-stream-content-sms-conditions-helper-icon"></span>-->
						</div>
					</div>
					<textarea id="<?=htmlspecialcharsbx($smsInputID)?>" class="crm-entity-stream-content-new-sms-textarea" rows='1' placeholder="<?=GetMessage('CRM_TIMELINE_SMS_ENTER_MESSAGE')?>"></textarea>
					<div class="crm-entity-stream-content-new-sms-templates" id="<?=$smsTemplatesContainerId?>">
						<div class="ui-ctl-label-text">
							<?=\Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_SMS_TEMPLATE_LIST_TITLE')?><span class="ui-hint" data-role="hint"><span class="ui-hint-icon"></span></span>
						</div>
						<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100" data-role="template-selector">
							<div class="ui-ctl-element" data-role="template-title"></div>
							<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						</div>
						<div class="crm-entity-stream-content-new-sms-preview" data-role="preview"></div>
					</div>
					<?php
					if($arResult['ENABLE_FILES_EXTERNAL_LINK'])
					{
					?>
					<div class="crm-entity-stream-content-sms-file-uploader-zone" data-role="sms-file-upload-zone" data-node-id="<?=htmlspecialcharsbx($prefix);?>">
						<div id="<?=htmlspecialcharsbx($fileUploaderZoneId);?>" class="diskuf-files-entity diskuf-selectdialog bx-disk">
							<div class="diskuf-files-block checklist-loader-files">
								<div class="diskuf-placeholder">
									<table class="files-list">
										<tbody class="diskuf-placeholder-tbody"></tbody>
									</table>
								</div>
							</div>
							<div class="diskuf-extended">
								<input type="hidden" name="<?=htmlspecialcharsbx($fileInputName);?>[]" value="" />
							</div>
							<div class="diskuf-extended-item">
								<label for="<?=htmlspecialcharsbx($fileUploaderInputName);?>" data-role="sms-file-upload-label"></label>
								<input class="diskuf-fileUploader" id="<?=htmlspecialcharsbx($fileUploaderInputName);?>" type="file" data-role="sms-file-upload-input" />
							</div>
							<div class="diskuf-extended-item">
								<span class="diskuf-selector-link" data-role="sms-file-selector-bitrix">
								</span>
							</div>
						</div>
					</div>
					<?php
					}
					elseif($arResult['SHOW_FILES_FEATURE'])
					{
					?>
					<div class="crm-entity-stream-content-sms-file-external-link-popup" data-role="sms-file-external-link-disabled">
						<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-container">
							<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-inner">
								<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-desc">
									<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-img">
										<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-img-lock"></div>
									</div>
									<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-desc-text">
										<?=GetMessage('CRM_TIMELINE_SMS_FILE_EXTERNAL_LINK_FEATURE');?>
									</div>
								</div>
								<div class="crm-entity-stream-content-sms-file-external-link-popup-limit-buttons">
									<?php
									\CBitrix24::showTariffRestrictionButtons('disk_manual_external_link');
									?>
								</div>
							</div>
						</div>
					</div>
					<?php
					}
					?>
					<div class="crm-entity-stream-content-new-sms-btn-container">
						<button id="<?=htmlspecialcharsbx($smsButtonID)?>" class="ui-btn ui-btn-xs ui-btn-primary">
							<?=GetMessage('CRM_TIMELINE_SEND')?>
						</button>
						<a id="<?=htmlspecialcharsbx($smsCancelButtonID)?>" href="#" class="ui-btn ui-btn-xs ui-btn-link"><?=GetMessage('CRM_TIMELINE_CANCEL_BTN')?></a>
						<div class="crm-entity-stream-content-sms-symbol-counter" data-role="message-length-counter-wrap"><?=GetMessage("CRM_TIMELINE_SMS_SYMBOLS")?><?
							?><span class="crm-entity-stream-content-sms-symbol-counter-number" data-role="message-length-counter" data-length-max="200">0</span><?
							?><?=GetMessage("CRM_TIMELINE_SMS_SYMBOLS_FROM")?><?
							?><span class="crm-entity-stream-content-sms-symbol-counter-number">200</span>
						</div>
					</div>
					<?endif;?>
				</div>
				<div id="<?=htmlspecialcharsbx($zoomContainerID)?>" class="crm-entity-stream-content-new-detail focus" style="display: none;"></div>
			</div>
		</div>
	</div>
</div><?
$filterClassName = $arResult['IS_HISTORY_FILTER_APPLIED']
	? 'crm-entity-stream-section-filter-show' : 'crm-entity-stream-section-filter-hide';

?><div id="timeline-filter" class="crm-entity-stream-section crm-entity-stream-section-filter <?=$filterClassName?>">
	<div class="crm-entity-stream-section-content">
		<div>
			<div class="crm-entity-stream-filter-container">
				<?
				$APPLICATION->includeComponent(
					'bitrix:main.ui.filter',
					'',
					array(
						'FILTER_ID' => $arResult['HISTORY_FILTER_ID'],
						'COMMON_PRESETS_ID' => $arResult['HISTORY_FILTER_PRESET_ID'],
						'THEME' => 'ROUNDED',
						'FILTER' => $arResult['HISTORY_FILTER'],
						'FILTER_PRESETS' => $arResult['HISTORY_FILTER_PRESETS'],
						'DISABLE_SEARCH' => false,
						'ENABLE_LIVE_SEARCH' => false,
						'ENABLE_LABEL' => true,
						'RESET_TO_DEFAULT_MODE' => false,
						'CONFIG' => array('AUTOFOCUS' => false),
						'LAZY_LOAD' => array(
							'GET_LIST' => '/bitrix/components/bitrix/crm.timeline/filter.ajax.php?action=list&filter_id='.urlencode($arResult['HISTORY_FILTER_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
							'GET_FIELD' => '/bitrix/components/bitrix/crm.timeline/filter.ajax.php?action=field&filter_id='.urlencode($arResult['HISTORY_FILTER_ID']).'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
						)
					)
				);
				?>
				<span class="crm-entity-stream-filter-close"></span>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	<?
	if (\Bitrix\Main\Loader::includeModule('intranet'))
	{
		$menuExtensions = \Bitrix\Intranet\Binding\Menu::getMenuItems(
			Integration\Intranet\BindingMenu\SectionCode::TIMELINE,
			Integration\Intranet\BindingMenu\CodeBuilder::getMenuCode((int)($arResult['ENTITY_TYPE_ID'] ?? null)),
			[
				'inline' => true,
				'context' => [
					'ENTITY_ID' => $arResult['ENTITY_ID']
				]
			]
		);
		if ($menuExtensions)
		{
			echo 'var IntranetExtensions = ' . \CUtil::phpToJSObject($menuExtensions) . ";\n\n";
		}
	}
	$finalSummaryPhraseCodes = [
		'summary' => 'CRM_TIMELINE_FINAL_SUMMARY_TITLE',
		'documents' => 'CRM_TIMELINE_FINAL_SUMMARY_DOCUMENTS_TITLE',
	];
	if ($arResult['ENTITY_TYPE_ID'] === \CCrmOwnerType::SmartInvoice)
	{
		$finalSummaryPhraseCodes = [
			'summary' => 'CRM_TIMELINE_FINAL_SUMMARY_INVOICE_TITLE',
			'documents' => 'CRM_TIMELINE_FINAL_SUMMARY_DOCUMENTS_INVOICE_TITLE',
		];
	}

	?>
	BX.ready(
		function()
		{
			BX.CrmSchedule.messages =
			{
				planned: "<?=GetMessageJS('CRM_TIMELINE_SCHEDULE_PLANNED')?>",
				stub: "<?=GetMessageJS('CRM_TIMELINE_COMMON_SCHEDULE_STUB')?>",
				leadStub: "<?=GetMessageJS('CRM_TIMELINE_LEAD_SCHEDULE_STUB')?>",
				dealStub: "<?=GetMessageJS('CRM_TIMELINE_DEAL_SCHEDULE_STUB')?>"
			};

			BX.CrmHistory.messages =
			{
				filterButtonCaption: "<?=GetMessageJS('CRM_TIMELINE_FILTER_BUTTON_CAPTION')?>",
				filterEmptyResultStub: "<?=GetMessageJS('CRM_TIMELINE_FILTER_EMPTY_RESULT_STUB')?>"
			};

			BX.CrmHistoryItemMark.messages =
			{
				entitySuccessMark: "<?= GetMessageJS('CRM_TIMELINE_MARK_ENTITY_SUCCESS_MARK') ?>",
				entityFailedMark: "<?= GetMessageJS('CRM_TIMELINE_MARK_ENTITY_FAILED_MARK') ?>",
				entityContentTemplate: "<?= GetMessageJS('CRM_TIMELINE_MARK_ENTITY_CONTENT_TEMPLATE') ?>",
				dealSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_DEAL_SUCCESS_MARK')?>",
				dealFailedMark: "<?=GetMessageJS('CRM_TIMELINE_DEAL_FAILED_MARK')?>",
				orderSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_ORDER_SUCCESS_MARK')?>",
				orderFailedMark: "<?=GetMessageJS('CRM_TIMELINE_ORDER_FAILED_MARK')?>",
				incomingEmailSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_INCOMING_EMAIL_SUCCESSMARK')?>",
				outgoingEmailSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_OUTGOING_EMAIL_SUCCESSMARK')?>",
				incomingEmailRenewMark: "<?=GetMessageJS('CRM_TIMELINE_INCOMING_EMAIL_RENEWMARK')?>",
				outgoingEmailRenewMark: "<?=GetMessageJS('CRM_TIMELINE_OUTGOING_EMAIL_RENEWMARK')?>",
				incomingCallSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_INCOMING_CALL_SUCCESSMARK')?>",
				outgoingCallSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_OUTGOING_CALL_SUCCESSMARK')?>",
				incomingCallRenewMark: "<?=GetMessageJS('CRM_TIMELINE_INCOMING_CALL_RENEWMARK')?>",
				outgoingCallRenewMark: "<?=GetMessageJS('CRM_TIMELINE_OUTGOING_CALL_RENEWMARK')?>",
				meetingSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_MEETING_SUCCESSMARK')?>",
				meetingRenewMark: "<?=GetMessageJS('CRM_TIMELINE_MEETING_RENEWMARK')?>",
				taskSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_TASK_SUCCESSMARK_2')?>",
				taskRenewMark: "<?=GetMessageJS('CRM_TIMELINE_TASK_RENEWMARK')?>",
				webformSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_WEBFORM_SUCCESSMARK')?>",
				webformRenewMark: "<?=GetMessageJS('CRM_TIMELINE_WEBFORM_RENEWMARK')?>",
				requestSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_REQUEST_SUCCESSMARK_1')?>",
				requestRenewMark: "<?=GetMessageJS('CRM_TIMELINE_REQUEST_RENEWMARK_1')?>"
			};

			BX.CrmHistoryItemCreation.messages =
			{
				lead: "<?=GetMessageJS('CRM_TIMELINE_LEAD_CREATION')?>",
				deal: "<?=GetMessageJS('CRM_TIMELINE_DEAL_CREATION')?>",
				deal_recurring: "<?=GetMessageJS('CRM_TIMELINE_RECURRING_DEAL_CREATION')?>",
				order: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CREATION')?>",
				order_payment: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_CREATION')?>",
				order_shipment: "<?=GetMessageJS('CRM_TIMELINE_ORDER_SHIPMENT_CREATION')?>",
				contact: "<?=GetMessageJS('CRM_TIMELINE_CONTACT_CREATION')?>",
				company: "<?=GetMessageJS('CRM_TIMELINE_COMPANY_CREATION')?>",
				quote: "<?=GetMessageJS('CRM_TIMELINE_QUOTE_CREATION')?>",
				invoice: "<?=GetMessageJS('CRM_TIMELINE_INVOICE_CREATION')?>",
				task: "<?=GetMessageJS('CRM_TIMELINE_TASK_CREATION')?>",
				activity: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_CREATION')?>",
				dealOrderTitle: "<?=GetMessageJS('CRM_TIMELINE_DEAL_ORDER_TITLE')?>",
			};

			BX.CrmHistoryItemLink.messages =
			{
				title: "<?= GetMessageJS('CRM_TIMELINE_LINK_TITLE') ?>",
				contentTemplate: "<?= GetMessageJS('CRM_TIMELINE_LINK_CONTENT_TEMPLATE') ?>"
			};

			BX.CrmHistoryItemUnlink.messages =
			{
				title: "<?= GetMessageJS('CRM_TIMELINE_UNLINK_TITLE') ?>",
				contentTemplate: "<?= GetMessageJS('CRM_TIMELINE_LINK_CONTENT_TEMPLATE') ?>"
			};

			BX.CrmTimelineCallAction.messages =
			{
				telephonyNotSupported: "<?=GetMessageJS('CRM_TIMELINE_TELEPHONY_NOT_SUPPORTED')?>"
			};

			BX.CrmSchedulePostponeAction.messages =
			{
				postpone: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE')?>",
				forOneHour: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_1H')?>",
				forTwoHours: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_2H')?>",
				forThreeHours: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_3H')?>",
				forOneDay: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_1D')?>",
				forTwoDays: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_2D')?>",
				forThreeDays: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_3D')?>"
			};

			BX.CrmSchedulePostponeController.messages =
			{
				title: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE')?>",
				forOneHour: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_1H')?>",
				forTwoHours: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_2H')?>",
				forThreeHours: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_3H')?>",
				forOneDay: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_1D')?>",
				forTwoDays: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_2D')?>",
				forThreeDays: "<?=GetMessageJS('CRM_TIMELINE_POSTPONE_3D')?>"
			};

			BX.CrmTimelineItem.messages =
			{
				from: "<?=GetMessageJS('CRM_TIMELINE_FROM')?>",
				to: "<?=GetMessageJS('CRM_TIMELINE_TO')?>",
				reciprocal: "<?=GetMessageJS('CRM_TIMELINE_RECIPROCAL')?>",
				details: "<?=GetMessageJS('CRM_TIMELINE_DETAILS')?>",
				termless: "<?=GetMessageJS('CRM_TIMELINE_TERMLESS')?>",
				comment: "<?=GetMessageJS('CRM_TIMELINE_COMMENT')?>",
				incomingEmail: "<?=GetMessageJS('CRM_TIMELINE_INCOMING_EMAIL_TITLE')?>",
				outgoingEmail: "<?=GetMessageJS('CRM_TIMELINE_OUTGOING_EMAIL_TITLE')?>",
				emailSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_EMAIL_SUCCESSMARK')?>",
				emailRenewMark: "<?=GetMessageJS('CRM_TIMELINE_EMAIL_RENEWMARK')?>",
				incomingCall: "<?=GetMessageJS('CRM_TIMELINE_INCOMING_CALL_TITLE')?>",
				outgoingCall: "<?=GetMessageJS('CRM_TIMELINE_OUTGOING_CALL_TITLE')?>",
				callSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_CALL_SUCCESSMARK')?>",
				callRenewMark: "<?=GetMessageJS('CRM_TIMELINE_CALL_RENEWMARK')?>",
				meeting: "<?=GetMessageJS('CRM_TIMELINE_MEETING_TITLE')?>",
				meetingSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_MEETING_SUCCESSMARK')?>",
				meetingRenewMark: "<?=GetMessageJS('CRM_TIMELINE_MEETING_RENEWMARK')?>",
				task: "<?=GetMessageJS('CRM_TIMELINE_TASK_TITLE')?>",
				taskSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_TASK_SUCCESSMARK_2')?>",
				taskRenewMark: "<?=GetMessageJS('CRM_TIMELINE_TASK_RENEWMARK')?>",
				webform: "<?=GetMessageJS('CRM_TIMELINE_WEBFORM_TITLE')?>",
				wait: "<?=GetMessageJS('CRM_TIMELINE_WAIT_TITLE')?>",
				sms: "<?=GetMessageJS('CRM_TIMELINE_SMS')?>",
				visit: "<?=GetMessageJS('CRM_TIMELINE_VISIT')?>",
				zoom: "<?=GetMessageJS('CRM_TIMELINE_ZOOM')?>",
				bizproc: "<?=GetMessageJS('CRM_TIMELINE_BIZPROC_TITLE')?>",
				activityRequest: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_REQUEST_TITLE_1')?>",
				restApplication: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_REST_APP_TITLE')?>",
				openLine: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_OPEN_LINE')?>",
				expand: "<?=GetMessageJS('CRM_TIMELINE_EXPAND_SM')?>",
				collapse: "<?=GetMessageJS('CRM_TIMELINE_COLLAPSE_SM')?>",
				menuEdit: "<?=GetMessageJS('CRM_TIMELINE_MENU_EDIT')?>",
				menuView: "<?=GetMessageJS('CRM_TIMELINE_MENU_VIEW')?>",
				menuCancel: "<?=GetMessageJS('CRM_TIMELINE_MENU_CANCEL')?>",
				menuDelete: "<?=GetMessageJS('CRM_TIMELINE_MENU_DELETE')?>",
				menuFasten: "<?=GetMessageJS('CRM_TIMELINE_MENU_FASTEN')?>",
				menuUnfasten: "<?=GetMessageJS('CRM_TIMELINE_MENU_UNFASTEN')?>",
				send: "<?=GetMessageJS('CRM_TIMELINE_SEND')?>",
				cancel: "<?=GetMessageJS('CRM_TIMELINE_CANCEL_BTN')?>",
				removeConfirmTitle: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_DELETION_TITLE_CONFIRM')?>",
				removeConfirm: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_DELETION_CONFIRM')?>",
				meetingRemove: "<?=GetMessageJS('CRM_TIMELINE_MEETING_DELETION_CONFIRM')?>",
				taskRemove: "<?=GetMessageJS('CRM_TIMELINE_TASK_DELETION_CONFIRM')?>",
				emailRemove: "<?=GetMessageJS('CRM_TIMELINE_EMAIL_DELETION_CONFIRM')?>",
				commentRemove: "<?=GetMessageJS('CRM_TIMELINE_COMMENT_DELETION_CONFIRM')?>",
				deliveryRemove: "<?=GetMessageJS('CRM_TIMELINE_DELIVERY_ACTIVITY_DELETION_CONFIRM')?>",
				outgoingCallRemove: "<?=GetMessageJS('CRM_TIMELINE_OUTGOING_CALL_DELETION_CONFIRM')?>",
				incomingCallRemove: "<?=GetMessageJS('CRM_TIMELINE_INCOMING_CALL_DELETION_CONFIRM')?>",
				document: "<?=GetMessageJS('CRM_TIMELINE_DOCUMENT')?>",
				documentRemove: "<?=GetMessageJS('CRM_TIMELINE_DOCUMENT_DELETION_CONFIRM')?>",
				zoomCreatedMessage: '<?=GetMessageJS("CRM_TIMELINE_ZOOM_CREATED_CONFERENCE_MESSAGE")?>',
				zoomCreatedCopyInviteLink: '<?=GetMessageJS("CRM_TIMELINE_ZOOM_CREATED_COPY_INVITE_LINK")?>',
				zoomCreatedStartConference: '<?=GetMessageJS("CRM_TIMELINE_ZOOM_CREATED_START_CONFERENCE")?>',
				storeDocument: "<?=GetMessageJS('CRM_TIMELINE_STORE_DOCUMENT_TITLE')?>",
				storeDocumentDescription: "<?=GetMessageJS('CRM_TIMELINE_STORE_DOCUMENT_DESCRIPTION')?>",
			};

			BX.CrmTimelineWaitEditor.messages =
			{
				completionTypeAfter: "<?=GetMessageJS('CRM_TIMELINE_WAIT_COMPLETION_TYPE_AFTER')?>",
				completionTypeBefore: "<?=GetMessageJS('CRM_TIMELINE_WAIT_COMPLETION_TYPE_BEFORE')?>",
				//---
				oneDay: "<?=GetMessageJS('CRM_TIMELINE_WAIT_1D')?>",
				twoDays: "<?=GetMessageJS('CRM_TIMELINE_WAIT_2D')?>",
				threeDays: "<?=GetMessageJS('CRM_TIMELINE_WAIT_3D')?>",
				oneWeek: "<?=GetMessageJS('CRM_TIMELINE_WAIT_1W')?>",
				twoWeek: "<?=GetMessageJS('CRM_TIMELINE_WAIT_2W')?>",
				threeWeeks: "<?=GetMessageJS('CRM_TIMELINE_WAIT_3W')?>",
				custom: "<?=GetMessageJS('CRM_TIMELINE_WAIT_CUSTOM')?>",
				afterDays: "<?=GetMessageJS('CRM_TIMELINE_WAIT_AFTER_CUSTOM_DAYS')?>",
				beforeDate: "<?=GetMessageJS('CRM_TIMELINE_WAIT_DEFORE_CUSTOM_DATE')?>"
			};

			BX.CrmHistoryItemSender.messages =
			{
				title: "<?=GetMessageJS('CRM_TIMELINE_SENDER_TITLE')?>",
				read: "<?=GetMessageJS('CRM_TIMELINE_SENDER_READ')?>",
				click: "<?=GetMessageJS('CRM_TIMELINE_SENDER_CLICK')?>",
				unsub: "<?=GetMessageJS('CRM_TIMELINE_SENDER_UNSUB')?>",
				error: "<?=GetMessageJS('CRM_TIMELINE_SENDER_ERROR')?>",
				removed: "<?=GetMessageJS('CRM_TIMELINE_SENDER_NAME_REMOVED')?>"
			};

			BX.CrmTimelineWaitConfigurationDialog.messages =
			{
				title: "<?=GetMessageJS('CRM_TIMELINE_WAIT_CONFIG_TITLE')?>",
				prefixTypeAfter: "<?=GetMessageJS('CRM_TIMELINE_WAIT_CONFIG_PREFIX_TYPE_AFTER')?>",
				prefixTypeBefore: "<?=GetMessageJS('CRM_TIMELINE_WAIT_CONFIG_PREFIX_TYPE_BEFORE')?>",
				targetPrefixTypeBefore: "<?=GetMessageJS('CRM_TIMELINE_WAIT_TARGET_PREFIX_TYPE_BEFORE')?>",
				select: "<?=GetMessageJS('CRM_TIMELINE_CHOOSE')?>"
			};

			BX.CrmEntityChat.messages =
				{
					invite: "<?=GetMessageJS('CRM_TIMELINE_CHAT_INVITE')?>"
				};

			BX.CrmHistoryItemOrderCreation.messages =
				{
					order: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CREATION')?>",
					unpaid: "<?=GetMessageJS('CRM_TIMELINE_ORDER_UNPAID')?>",
					paid: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAID')?>",
					done: "<?=GetMessageJS('CRM_TIMELINE_ORDER_DONE')?>",
					canceled: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CANCELED')?>",
					urlOrderLink: "<?=GetMessageJS('CRM_TIMELINE_ORDER_ORDER_LINK')?>",
				};

			BX.CrmHistoryItemOrderModification.messages =
				{
					order: "<?=GetMessageJS('CRM_TIMELINE_ORDER_TITLE')?>",
					unpaid: "<?=GetMessageJS('CRM_TIMELINE_ORDER_UNPAID')?>",
					done: "<?=GetMessageJS('CRM_TIMELINE_ORDER_DONE')?>",
					canceled: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CANCELED')?>",
					paid: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAID')?>",
					deducted: "<?=GetMessageJS('CRM_TIMELINE_ORDER_DEDUCTED')?>",
					unshipped: "<?=GetMessageJS('CRM_TIMELINE_ORDER_UNSHIPPED')?>",
					viewed: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_VIEWED')?>",
					sent: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_SENT')?>",
					allowedDelivery: "<?=GetMessageJS('CRM_TIMELINE_ORDER_ALLOWED_DELIVERY')?>",
					disallowedDelivery: "<?=GetMessageJS('CRM_TIMELINE_ORDER_DISALLOWED_DELIVERY')?>",
					orderPayment: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_TITLE')?>",
					orderPaymentLegendPaid: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_LEGEND_PAID')?>",
					orderPaymentLegendUnpaid: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_LEGEND_UNPAID')?>",
					orderPaymentSuccessTitle: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_SUCCESS')?>",
					orderPaySystemTitle: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYSYSTEM_TITLE')?>",
					orderShipment: "<?=GetMessageJS('CRM_TIMELINE_ORDER_SHIPMENT_TITLE')?>",
					orderShipmentLegendDeducted: "<?=GetMessageJS('CRM_TIMELINE_ORDER_SHIPMENT_LEGEND_DEDUCTED')?>",
					orderShipmentLegendUnshipped: "<?=GetMessageJS('CRM_TIMELINE_ORDER_SHIPMENT_LEGEND_UNSHIPPED')?>",
					orderPaymentError: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_ERROR')?>",
					orderPaymentProcess: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_PROCESS')?>",
					orderPaymentStatusErrorReason: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYMENT_STATUS_ERROR_REASON_TITLE')?>",
					orderPaymentPaySystemClick: "<?=GetMessageJS('CRM_TIMELINE_ORDER_PAYSYSTEM_CLICK')?>",
					orderManualContinuePay: "<?=GetMessageJS('CRM_TIMELINE_ORDER_MANUAL_CONTINUE_PAY')?>",
					orderManualAddCheck: "<?=GetMessageJS('CRM_TIMELINE_PAYMENT_NEED_MANUAL_ADD_CHECK')?>",
					orderManualAddCheckHelpLink: "<?=GetMessageJS('CRM_TIMELINE_PAYMENT_NEED_MANUAL_ADD_CHECK_HELP_LINK')?>",
				};

			BX.CrmHistoryItemFinalSummary.messages =
				{
					title: "<?=GetMessageJS($finalSummaryPhraseCodes['summary'])?>",
					orderPaid: "<?=GetMessageJS('CRM_TIMELINE_FINAL_SUMMARY_ORDER_PAID_2')?>",
					basketBasePrice: "<?=GetMessageJS('CRM_TIMELINE_FINAL_SUMMARY_BASKET_BASE_PRICE')?>",
					basketPrice: "<?=GetMessageJS('CRM_TIMELINE_FINAL_SUMMARY_BASKET_PRICE')?>",
					paymentStatusY: "<?=GetMessageJS('CRM_TIMELINE_FINAL_SUMMARY_PAYMENT_STATUS_Y')?>",
					sumForPay: "<?=GetMessageJS('CRM_TIMELINE_FINAL_SUMMARY_SUM_FOR_PAY')?>",
				};

			BX.CrmHistoryItemFinalSummaryDocuments.messages =
				{
					title: "<?=GetMessageJS($finalSummaryPhraseCodes['documents'])?>",
				};

			BX.CrmHistoryItemOrcderCheck.messages =
				{
					orderCheck: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CHECK_TITLE')?>",
					printed: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CHECK_PRINTED')?>",
					unprinted: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CHECK_UNPRINTED')?>",
					sended: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CHECK_SENT')?>",
					urlLink: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CHECK_LINK')?>",
					sendedTitle: "<?=GetMessageJS('CRM_TIMELINE_ORDER_CHECK_SENT_TITLE')?>",
				};

			BX.CrmHistoryItemStoreDocumentCreation.messages =
				{
					arrivalDocument: "<?=GetMessageJS('CRM_TIMELINE_ARRIVAL_DOCUMENT_CREATION')?>",
					storeAdjustmentDocument: "<?=GetMessageJS('CRM_TIMELINE_STORE_ADJUSTMENT_DOCUMENT_CREATION')?>",
					movingDocument: "<?=GetMessageJS('CRM_TIMELINE_MOVING_DOCUMENT_CREATION')?>",
					deductDocument: "<?=GetMessageJS('CRM_TIMELINE_DEDUCT_DOCUMENT_CREATION')?>",
					shipmentDocument: "<?=GetMessageJS('CRM_TIMELINE_SHIPMENT_DOCUMENT_CREATION')?>",
				};

			BX.CrmHistoryItemStoreDocumentModification.messages =
				{
					arrivalDocument: "<?=GetMessageJS('CRM_TIMELINE_ARRIVAL_DOCUMENT')?>",
					storeAdjustmentDocument: "<?=GetMessageJS('CRM_TIMELINE_STORE_ADJUSTMENT_DOCUMENT')?>",
					movingDocument: "<?=GetMessageJS('CRM_TIMELINE_MOVING_DOCUMENT')?>",
					shipmentDocument: "<?=GetMessageJS('CRM_TIMELINE_SHIPMENT_DOCUMENT')?>",
					deductDocument: "<?=GetMessageJS('CRM_TIMELINE_DEDUCT_DOCUMENT')?>",
					arrivalModification: "<?=GetMessageJS('CRM_TIMELINE_ARRIVAL_DOCUMENT_MODIFICATION')?>",
					storeAdjustmentModification: "<?=GetMessageJS('CRM_TIMELINE_STORE_ADJUSTMENT_DOCUMENT_MODIFICATION')?>",
					movingModification: "<?=GetMessageJS('CRM_TIMELINE_MOVING_DOCUMENT_MODIFICATION')?>",
					deductModification: "<?=GetMessageJS('CRM_TIMELINE_DEDUCT_DOCUMENT_MODIFICATION')?>",
					shipmentModification: "<?=GetMessageJS('CRM_TIMELINE_SHIPMENT_DOCUMENT_MODIFICATION')?>",
					conductError: "<?=GetMessageJS('CRM_TIMELINE_CONDUCT_ERROR')?>",
				};

			BX.message({
				"CRM_TIMELINE_WAIT_DAY_PLURAL_0": '<?=GetMessageJS("CRM_TIMELINE_WAIT_DAY_PLURAL_0")?>',
				"CRM_TIMELINE_WAIT_DAY_PLURAL_1": '<?=GetMessageJS("CRM_TIMELINE_WAIT_DAY_PLURAL_1")?>',
				"CRM_TIMELINE_WAIT_DAY_PLURAL_2": '<?=GetMessageJS("CRM_TIMELINE_WAIT_DAY_PLURAL_2")?>',
				"CRM_TIMELINE_WAIT_WEEK_PLURAL_0": '<?=GetMessageJS("CRM_TIMELINE_WAIT_WEEK_PLURAL_0")?>',
				"CRM_TIMELINE_WAIT_WEEK_PLURAL_1": '<?=GetMessageJS("CRM_TIMELINE_WAIT_WEEK_PLURAL_1")?>',
				"CRM_TIMELINE_WAIT_WEEK_PLURAL_2": '<?=GetMessageJS("CRM_TIMELINE_WAIT_WEEK_PLURAL_2")?>',
				"CRM_TIMELINE_CALL_TRANSCRIPT": '<?=GetMessageJS("CRM_TIMELINE_CALL_TRANSCRIPT")?>',
				"CRM_TIMELINE_CALL_TRANSCRIPT_PENDING": '<?=GetMessageJS("CRM_TIMELINE_CALL_TRANSCRIPT_PENDING")?>',
				"CRM_TIMELINE_SMS_REST_MARKETPLACE": '<?=GetMessageJS("CRM_TIMELINE_SMS_REST_MARKETPLACE")?>',
				"CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS": '<?=GetMessageJS("CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS")?>',
				"CRM_TIMELINE_SMS_SENDER": '<?=GetMessageJS("CRM_TIMELINE_SMS_SENDER")?>',
				"CRM_TIMELINE_SMS_FROM": '<?=GetMessageJS("CRM_TIMELINE_SMS_FROM")?>',
				"CRM_TIMELINE_SMS_TO": '<?=GetMessageJS("CRM_TIMELINE_SMS_TO")?>',
				"CRM_TIMELINE_SMS_TEMPLATES_NOT_FOUND": '<?=GetMessageJS("CRM_TIMELINE_SMS_TEMPLATES_NOT_FOUND")?>',
				"CRM_TIMELINE_BIZPROC_CREATED": '<?=GetMessageJS("CRM_TIMELINE_BIZPROC_CREATED")?>',
				"CRM_TIMELINE_BIZPROC_COMPLETED": '<?=GetMessageJS("CRM_TIMELINE_BIZPROC_COMPLETED")?>',
				"CRM_TIMELINE_BIZPROC_TERMINATED": '<?=GetMessageJS("CRM_TIMELINE_BIZPROC_TERMINATED")?>',
				"CRM_TIMELINE_VISIT_AT": '<?=GetMessageJS("CRM_TIMELINE_VISIT_AT")?>',
				"CRM_TIMELINE_VISIT_WITH": '<?=GetMessageJS("CRM_TIMELINE_VISIT_WITH")?>',
				"CRM_TIMELINE_VISIT_VKONTAKTE_PROFILE": '<?=GetMessageJS("CRM_TIMELINE_VISIT_VKONTAKTE_PROFILE")?>',
				"CRM_TIMELINE_FASTEN_LIMIT_MESSAGE": '<?=GetMessageJS("CRM_TIMELINE_FASTEN_LIMIT_MESSAGE")?>',
				"CRM_TIMELINE_EMPTY_COMMENT_MESSAGE": '<?=GetMessageJS("CRM_TIMELINE_EMPTY_COMMENT_MESSAGE")?>',
				"CRM_TIMELINE_SPOTLIGHT_FASTEN_MESSAGE": '<?=GetMessageJS("CRM_TIMELINE_SPOTLIGHT_FASTEN_MESSAGE")?>',
				"CRM_TIMELINE_SCORING_TITLE_2": '<?=GetMessageJS("CRM_TIMELINE_SCORING_TITLE_2")?>',
				"CRM_TIMELINE_DETAILS": '<?=GetMessageJS("CRM_TIMELINE_DETAILS")?>',
				"CRM_TIMELINE_COLLAPSE": '<?=GetMessageJS("CRM_TIMELINE_COLLAPSE")?>',
				"CRM_TIMELINE_SMS_UPLOAD_FILE": '<?=GetMessageJS("CRM_TIMELINE_SMS_UPLOAD_FILE")?>',
				"CRM_TIMELINE_SMS_FIND_FILE": '<?=GetMessageJS("CRM_TIMELINE_SMS_FIND_FILE")?>',
				"CRM_TIMELINE_ZOOM_SUCCESSFUL_ACTIVITY": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_SUCCESSFUL_ACTIVITY")?>',
				"CRM_TIMELINE_ZOOM_CONFERENCE_END": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_CONFERENCE_END")?>',
				"CRM_TIMELINE_ZOOM_JOINED_CONFERENCE": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_JOINED_CONFERENCE")?>',
				"CRM_TIMELINE_ZOOM_MEETING_RECORD": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_MEETING_RECORD")?>',
				"CRM_TIMELINE_ZOOM_MEETING_RECORD_PART": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_MEETING_RECORD_PART")?>',
				"CRM_TIMELINE_ZOOM_MEETING_RECORD_IN_PROCESS": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_MEETING_RECORD_IN_PROCESS")?>',
				"CRM_TIMELINE_ZOOM_DOWNLOAD_VIDEO": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_DOWNLOAD_VIDEO")?>',
				"CRM_TIMELINE_ZOOM_DOWNLOAD_AUDIO": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_DOWNLOAD_AUDIO")?>',
				"CRM_TIMELINE_ZOOM_CLICK_TO_WATCH": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_CLICK_TO_WATCH")?>',
				"CRM_TIMELINE_ZOOM_LOGIN_REQUIRED": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_LOGIN_REQUIRED")?>',
				"CRM_TIMELINE_ZOOM_PLAY_LINK_VIDEO": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_PLAY_LINK_VIDEO")?>',
				"CRM_TIMELINE_ZOOM_COPY_PASSWORD": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_COPY_PASSWORD")?>',
				"CRM_TIMELINE_DOCUMENT_VIEWED": '<?=GetMessageJS("CRM_TIMELINE_DOCUMENT_VIEWED")?>',
				"CRM_TIMELINE_DOCUMENT_VIEWED_STATUS": '<?=GetMessageJS("CRM_TIMELINE_DOCUMENT_VIEWED_STATUS")?>',
				"CRM_TIMELINE_DOCUMENT_CREATED_STATUS": '<?=GetMessageJS("CRM_TIMELINE_DOCUMENT_CREATED_STATUS")?>',
				"CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_1": '<?=GetMessageJS("CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_1")?>',
				"CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_1.5": '<?=GetMessageJS("CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_1.5")?>',
				"CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_2": '<?=GetMessageJS("CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_2")?>',
				"CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_3": '<?=GetMessageJS("CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_3")?>',
				"CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_TEXT": '<?=GetMessageJS("CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_TEXT")?>',
				"DISK_TMPLT_THUMB": '',
				"DISK_TMPLT_THUMB2": '',
			});

			BX.CrmTimelineManager.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					ownerTypeId: <?=$arResult['ENTITY_TYPE_ID']?>,
					ownerId: <?=$arResult['ENTITY_ID']?>,
					ownerInfo: <?=CUtil::PhpToJSObject($arResult['ENTITY_INFO'])?>,
					userId: <?=$arResult['USER_ID']?>,
					readOnly: <?=$arResult['READ_ONLY'] ? 'true' : 'false'?>,
					pullTagName: "<?=CUtil::JSEscape($arResult['PULL_TAG_NAME'])?>",
					progressSemantics: "<?=CUtil::JSEscape($arResult['PROGRESS_SEMANTICS'])?>",
					enableWait: <?=$arResult['ENABLE_WAIT'] ? 'true' : 'false'?>,
					enableSms: <?=$arResult['ENABLE_SMS'] ? 'true' : 'false'?>,
					enableZoom: <?=$arResult['ENABLE_ZOOM'] ? 'true' : 'false'?>,
					statusZoom: <?=$arResult['STATUS_ZOOM'] ? 'true' : 'false'?>,
					enableRest: <?=$arResult['ENABLE_REST'] ? 'true' : 'false'?>,
					restPlacement: '<?=$arResult['REST_PLACEMENT']?>',
					containerId: "<?=CUtil::JSEscape($listContainerID)?>",
					activityEditorId: "<?=CUtil::JSEscape($arResult['ACTIVITY_EDITOR_ID'])?>",
					chatData: <?=CUtil::PhpToJSObject($arResult['CHAT_DATA'])?>,
					scheduleData: <?=CUtil::PhpToJSObject($scheduleItems)?>,
					historyData: <?=CUtil::PhpToJSObject($historyItems)?>,
					historyNavigation: <?=CUtil::PhpToJSObject($arResult['HISTORY_NAVIGATION'])?>,
					historyFilterId: "<?=CUtil::JSEscape($arResult['HISTORY_FILTER_ID'])?>",
					isHistoryFilterApplied: <?=$arResult['IS_HISTORY_FILTER_APPLIED'] ? 'true' : 'false'?>,
					fixedData: <?=CUtil::PhpToJSObject($fixedItems)?>,
					ajaxId: "<?=CUtil::JSEscape($arResult['AJAX_ID'])?>",
					currentUrl: "<?=CUtil::JSEscape($arResult['CURRENT_URL'])?>",
					serviceUrl: "/bitrix/components/bitrix/crm.timeline/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
					menuBarObjectId: "<?=CUtil::JSEscape($menuBarObjectId)?>",
					menuBarContainer: "<?=CUtil::JSEscape($menuBarContainerID)?>",
					editorContainer: "<?=CUtil::JSEscape($editorContainerID)?>",
					editorCommentContainer: "<?=CUtil::JSEscape($commentContainerID)?>",
					editorCommentInput: "<?=CUtil::JSEscape($commentInputID)?>",
					editorCommentEditorName: "<?=CUtil::JSEscape($editorName)?>",
					editorCommentButton: "<?=CUtil::JSEscape($commentButtonID)?>",
					editorCommentCancelButton: "<?=CUtil::JSEscape($commentCancelButtonID)?>",
					editorWaitContainer: "<?=CUtil::JSEscape($waitContainerID)?>",
					editorWaitConfigContainer: "<?=CUtil::JSEscape($waitConfigContainerID)?>",
					editorWaitInput: "<?=CUtil::JSEscape($waitInputID)?>",
					editorWaitButton: "<?=CUtil::JSEscape($waitButtonID)?>",
					editorWaitCancelButton: "<?=CUtil::JSEscape($waitCancelButtonID)?>",
					editorWaitTargetDates: <?=CUtil::PhpToJSObject($arResult['WAIT_TARGET_DATES'])?>,
					editorWaitConfig: <?=CUtil::PhpToJSObject($arResult['WAIT_CONFIG'])?>,
					editorSmsContainer: "<?=CUtil::JSEscape($smsContainerID)?>",
					editorSmsInput: "<?=CUtil::JSEscape($smsInputID)?>",
					editorSmsTemplatesContainer: "<?=CUtil::JSEscape($smsTemplatesContainerId)?>",
					editorSmsButton: "<?=CUtil::JSEscape($smsButtonID)?>",
					editorSmsCancelButton: "<?=CUtil::JSEscape($smsCancelButtonID)?>",
					editorSmsConfig: <?=CUtil::PhpToJSObject($arResult['SMS_CONFIG'])?>,
					smsStatusDescriptions: <?=CUtil::PhpToJSObject($arResult['SMS_STATUS_DESCRIPTIONS'])?>,
					smsStatusSemantics: <?=CUtil::PhpToJSObject($arResult['SMS_STATUS_SEMANTICS'])?>,
					editorZoomContainer: "<?=CUtil::JSEscape($zoomContainerID)?>",
					visitParameters: <?= \CUtil::PhpToJSObject($arResult['VISIT_PARAMETERS'])?>,
					spotlightFastenShowed: <?=$spotlightFastenShowed ? 'true' : 'false'?>,
					audioPlaybackRate: <?= (float) $arResult['AUDIO_PLAYBACK_RATE'] ?>
				}
			);
		}
	);
</script>
<?
