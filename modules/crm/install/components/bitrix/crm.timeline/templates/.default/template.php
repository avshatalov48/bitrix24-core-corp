<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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

Bitrix\Main\UI\Extension::load(array('ui.buttons', 'ui.icons'));

//HACK: Preloading files for prevent trembling of player afer load.
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/timeline_player/timeline_player.css');

if(\Bitrix\Main\Loader::includeModule('disk'))
{
	Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/disk.uf.file/templates/.default/script.js');
	Bitrix\Main\UI\Extension::load([
		'ajax',
		'core',
		'disk_external_loader',
		'ui.tooltip',
		'ui.viewer',
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
$prefix = strtolower($guid);
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
$smsButtonID = "{$prefix}_sms_button";
$smsCancelButtonID = "{$prefix}_sms_cancel_button";

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
			<div class="crm-entity-stream-section-content crm-entity-stream-section-content-new">
				<div id="<?=htmlspecialcharsbx($menuBarContainerID)?>" class="crm-entity-stream-section-content-new-header">
					<a data-item-id="comment" class="crm-entity-stream-section-new-action" href="#">
						<?=GetMessage('CRM_TIMELINE_COMMENT')?>
					</a>
					<?if($arResult['ENABLE_WAIT'])
					{?>
					<a data-item-id="wait" data-item-title="<?=GetMessage('CRM_TIMELINE_WAIT')?>" class="crm-entity-stream-section-new-action" href="#">
							<?=GetMessage('CRM_TIMELINE_WAIT')?>
					</a>
					<?}?>
					<?if($arResult['ENABLE_CALL'])
					{?>
					<a data-item-id="call" data-item-title="<?=GetMessage('CRM_TIMELINE_CALL')?>" class="crm-entity-stream-section-new-action" href="#">
						<?=GetMessage('CRM_TIMELINE_CALL')?>
					</a>
					<?}?>
					<?if($arResult['ENABLE_SMS'])
					{?>
					<a data-item-id="sms" data-item-title="SMS" class="crm-entity-stream-section-new-action" href="#">
						SMS
					</a>
					<?}?>
					<?if($arResult['ENABLE_EMAIL'])
					{?>
					<a data-item-id="email" data-item-title="<?=GetMessage('CRM_TIMELINE_EMAIL')?>" class="crm-entity-stream-section-new-action" href="#">
						<?=GetMessage('CRM_TIMELINE_EMAIL')?>
					</a>
					<?}?>
					<?if($arResult['ENABLE_TASK'])
					{?>
					<a data-item-id="task" data-item-title="<?=GetMessage('CRM_TIMELINE_TASK')?>" class="crm-entity-stream-section-new-action" href="#">
						<?=GetMessage('CRM_TIMELINE_TASK')?>
					</a>
					<?}?>
					<?if($arResult['ENABLE_MEETING'])
					{?>
					<a data-item-id="meeting" data-item-title="<?=GetMessage('CRM_TIMELINE_MEETING')?>" class="crm-entity-stream-section-new-action" href="#">
						<?=GetMessage('CRM_TIMELINE_MEETING')?>
					</a>
					<?}?>
					<?if($arResult['ENABLE_VISIT'])
					{?>
					<a data-item-id="visit" data-item-title="<?=GetMessage('CRM_TIMELINE_VISIT')?>" class="crm-entity-stream-section-new-action" href="#">
						<?=GetMessage('CRM_TIMELINE_VISIT')?>
					</a>
					<?}?>
					<?if(count($arResult['ADDITIONAL_TABS']) > 0)
					{
						foreach($arResult['ADDITIONAL_TABS'] as $tab)
						{?>
							<a data-item-id="<?=$tab['id']?>" data-item-title="<?=\Bitrix\Main\Text\HtmlFilter::encode($tab['name'])?>" class="crm-entity-stream-section-new-action" href="#">
								<?=\Bitrix\Main\Text\HtmlFilter::encode($tab['name'])?>
							</a>
						<?}
					}?>

					<a class="crm-entity-stream-section-new-action-more" href="#">
						<?=GetMessage('CRM_TIMELINE_MORE')?>
					</a>
				</div>
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
							<strong><?=GetMessage("CRM_TIMELINE_SMS_MANAGE_TEXT_1")?></strong><br>
							<?=GetMessage("CRM_TIMELINE_SMS_MANAGE_TEXT_2")?><br>
							<?=GetMessage("CRM_TIMELINE_SMS_MANAGE_TEXT_3")?>
							<!--<span class="crm-entity-stream-content-sms-conditions-helper-icon"></span>-->
						</div>
					</div>
					<div class="crm-entity-stream-content-new-sms-btn-container">
						<a href="<?=htmlspecialcharsbx($arResult['SMS_MANAGE_URL'])?>" target="_top" class="crm-entity-stream-content-new-sms-connect-link"><?=GetMessage("CRM_TIMELINE_SMS_MANAGE_URL")?></a>
					</div>
					<?else:?>
					<div class="crm-entity-stream-content-sms-conditions-container">
						<div class="crm-entity-stream-content-sms-conditions">
							<?=GetMessage('CRM_TIMELINE_SMS_SENDER')?> <a href="#" data-role="sender-selector">sender</a><span data-role="from-container"><?=GetMessage('CRM_TIMELINE_SMS_FROM')?><?
							?> <a data-role="from-selector" href="#">from_number</a></span><?
							?><span data-role="client-container"> <?=GetMessage('CRM_TIMELINE_SMS_TO')?> <a data-role="client-selector" href="#">client_caption</a> <a data-role="to-selector" href="#">to_number</a></span>
							<!--<span class="crm-entity-stream-content-sms-conditions-helper-icon"></span>-->
						</div>
					</div>
					<textarea id="<?=htmlspecialcharsbx($smsInputID)?>" class="crm-entity-stream-content-new-sms-textarea" rows='1' placeholder="<?=GetMessage('CRM_TIMELINE_SMS_SEND_MESSAGE')?>"></textarea>
					<div class="crm-entity-stream-content-new-sms-btn-container">
						<button id="<?=htmlspecialcharsbx($smsButtonID)?>" class="ui-btn ui-btn-xs ui-btn-primary">
							<?=GetMessage('CRM_TIMELINE_SEND')?>
						</button>
						<a id="<?=htmlspecialcharsbx($smsCancelButtonID)?>" href="#" class="ui-btn ui-btn-xs ui-btn-link"><?=GetMessage('CRM_TIMELINE_CANCEL_BTN')?></a>
						<div class="crm-entity-stream-content-sms-symbol-counter"><?=GetMessage("CRM_TIMELINE_SMS_SYMBOLS")?><?
							?><span class="crm-entity-stream-content-sms-symbol-counter-number" data-role="message-length-counter" data-length-max="200">0</span><?
							?><?=GetMessage("CRM_TIMELINE_SMS_SYMBOLS_FROM")?><?
							?><span class="crm-entity-stream-content-sms-symbol-counter-number">200</span>
						</div>
					</div>
					<?endif;?>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
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

			BX.CrmHistoryItemMark.messages =
			{
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
				requestSuccessMark: "<?=GetMessageJS('CRM_TIMELINE_REQUEST_SUCCESSMARK')?>",
				requestRenewMark: "<?=GetMessageJS('CRM_TIMELINE_REQUEST_RENEWMARK')?>"
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
				activity: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_CREATION')?>"
			};

			BX.CrmHistoryItemLink.messages =
			{
				lead: "<?=GetMessageJS('CRM_TIMELINE_LEAD_LINK')?>",
				deal: "<?=GetMessageJS('CRM_TIMELINE_DEAL_LINK')?>"
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
				bizproc: "<?=GetMessageJS('CRM_TIMELINE_BIZPROC_TITLE')?>",
				activityRequest: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_REQUEST_TITLE')?>",
				restApplication: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_REST_APP_TITLE')?>",
				openLine: "<?=GetMessageJS('CRM_TIMELINE_ACTIVITY_OPEN_LINE')?>",
				expand: "<?=GetMessageJS('CRM_TIMELINE_EXPAND')?>",
				collapse: "<?=GetMessageJS('CRM_TIMELINE_COLLAPSE')?>",
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
				outgoingCallRemove: "<?=GetMessageJS('CRM_TIMELINE_OUTGOING_CALL_DELETION_CONFIRM')?>",
				incomingCallRemove: "<?=GetMessageJS('CRM_TIMELINE_INCOMING_CALL_DELETION_CONFIRM')?>",
				document: "<?=GetMessageJS('CRM_TIMELINE_DOCUMENT')?>",
				documentRemove: "<?=GetMessageJS('CRM_TIMELINE_DOCUMENT_DELETION_CONFIRM')?>"
			};

			BX.CrmTimelineWaitHelper.messages =
			{
				dayNominative: "<?=GetMessageJS('CRM_TIMELINE_WAIT_DAY_NOMINATIVE')?>",
				dayGenitiveSingular: "<?=GetMessageJS('CRM_TIMELINE_WAIT_DAY_GENITIVE_SINGULAR')?>",
				dayGenitivePlural: "<?=GetMessageJS('CRM_TIMELINE_WAIT_DAY_GENITIVE_PLURAL')?>",
				weekNominative: "<?=GetMessageJS('CRM_TIMELINE_WAIT_WEEK_NOMINATIVE')?>",
				weekGenitiveSingular: "<?=GetMessageJS('CRM_TIMELINE_WAIT_WEEK_GENITIVE_SINGULAR')?>",
				weekGenitivePlural: "<?=GetMessageJS('CRM_TIMELINE_WAIT_WEEK_GENITIVE_PLURAL')?>"
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

			BX.message({
				"CRM_TIMELINE_CALL_TRANSCRIPT": '<?=GetMessageJS("CRM_TIMELINE_CALL_TRANSCRIPT")?>',
				"CRM_TIMELINE_CALL_TRANSCRIPT_PENDING": '<?=GetMessageJS("CRM_TIMELINE_CALL_TRANSCRIPT_PENDING")?>',
				"CRM_TIMELINE_SMS_REST_MARKETPLACE": '<?=GetMessageJS("CRM_TIMELINE_SMS_REST_MARKETPLACE")?>',
				"CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS": '<?=GetMessageJS("CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS")?>',
				"CRM_TIMELINE_SMS_SENDER": '<?=GetMessageJS("CRM_TIMELINE_SMS_SENDER")?>',
				"CRM_TIMELINE_SMS_FROM": '<?=GetMessageJS("CRM_TIMELINE_SMS_FROM")?>',
				"CRM_TIMELINE_SMS_TO": '<?=GetMessageJS("CRM_TIMELINE_SMS_TO")?>',
				"CRM_TIMELINE_BIZPROC_CREATED": '<?=GetMessageJS("CRM_TIMELINE_BIZPROC_CREATED")?>',
				"CRM_TIMELINE_BIZPROC_COMPLETED": '<?=GetMessageJS("CRM_TIMELINE_BIZPROC_COMPLETED")?>',
				"CRM_TIMELINE_BIZPROC_TERMINATED": '<?=GetMessageJS("CRM_TIMELINE_BIZPROC_TERMINATED")?>',
				"CRM_TIMELINE_VISIT_AT": '<?=GetMessageJS("CRM_TIMELINE_VISIT_AT")?>',
				"CRM_TIMELINE_VISIT_WITH": '<?=GetMessageJS("CRM_TIMELINE_VISIT_WITH")?>',
				"CRM_TIMELINE_VISIT_VKONTAKTE_PROFILE": '<?=GetMessageJS("CRM_TIMELINE_VISIT_VKONTAKTE_PROFILE")?>',
				"CRM_TIMELINE_FASTEN_LIMIT_MESSAGE": '<?=GetMessageJS("CRM_TIMELINE_FASTEN_LIMIT_MESSAGE")?>',
				"CRM_TIMELINE_EMPTY_COMMENT_MESSAGE": '<?=GetMessageJS("CRM_TIMELINE_EMPTY_COMMENT_MESSAGE")?>',
				"CRM_TIMELINE_SPOTLIGHT_FASTEN_MESSAGE": '<?=GetMessageJS("CRM_TIMELINE_SPOTLIGHT_FASTEN_MESSAGE")?>'
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
					enableRest: <?=$arResult['ENABLE_REST'] ? 'true' : 'false'?>,
					restPlacement: '<?=$arResult['REST_PLACEMENT']?>',
					containerId: "<?=CUtil::JSEscape($listContainerID)?>",
					activityEditorId: "<?=CUtil::JSEscape($arResult['ACTIVITY_EDITOR_ID'])?>",
					chatData: <?=CUtil::PhpToJSObject($arResult['CHAT_DATA'])?>,
					scheduleData: <?=CUtil::PhpToJSObject($scheduleItems)?>,
					historyData: <?=CUtil::PhpToJSObject($historyItems)?>,
					historyNavigation: <?=CUtil::PhpToJSObject($arResult['HISTORY_NAVIGATION'])?>,
					fixedData: <?=CUtil::PhpToJSObject($fixedItems)?>,
					ajaxId: "<?=CUtil::JSEscape($arResult['AJAX_ID'])?>",
					currentUrl: "<?=CUtil::JSEscape($arResult['CURRENT_URL'])?>",
					serviceUrl: "/bitrix/components/bitrix/crm.timeline/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
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
					editorSmsButton: "<?=CUtil::JSEscape($smsButtonID)?>",
					editorSmsCancelButton: "<?=CUtil::JSEscape($smsCancelButtonID)?>",
					editorSmsConfig: <?=CUtil::PhpToJSObject($arResult['SMS_CONFIG'])?>,
					smsStatusDescriptions: <?=CUtil::PhpToJSObject($arResult['SMS_STATUS_DESCRIPTIONS'])?>,
					smsStatusSemantics: <?=CUtil::PhpToJSObject($arResult['SMS_STATUS_SEMANTICS'])?>,
					visitParameters: <?= \CUtil::PhpToJSObject($arResult['VISIT_PARAMETERS'])?>,
					spotlightFastenShowed: <?=$spotlightFastenShowed ? 'true' : 'false'?>
				}
			);
		}
	);
</script>
<?
