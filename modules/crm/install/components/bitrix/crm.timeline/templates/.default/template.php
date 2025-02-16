<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true){
	die();
}

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\MenuIdResolver;
use Bitrix\Crm\Entity\EntityEditorConfigScope;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Operation\Autostart\FillFieldsSettings;
use Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;

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

Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.buttons',
	'ui.icons',
	'ui.selector',
	'ui.notification',
	'crm.zoom',
	'ui.timeline',
	'ui.forms',
	'crm.timeline',
	'sidepanel',
	'crm.restriction.bitrix24',
	'ui.hint',
	'ui.viewer',
	'applayout',
	'im.public',
]);

//HACK: Preloading files for prevent trembling of player afer load.
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/timeline_player/timeline_player.css');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/calendar/planner.css');

if (\Bitrix\Main\Loader::includeModule('disk'))
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
		'disk.viewer.board-item',
		'disk.viewer.actions',
	]);
}

$jsLibraries = [
	'ui.viewer',
	'player',
	//used in CRM_DOCUMENT activity item in inline edit to select new create date
	'date',
];

if (\Bitrix\Main\Loader::includeModule('voximplant'))
{
	$jsLibraries[] = 'voximplant_transcript';
}

$spotlightFastenShowed = true;
if (!$arResult['READ_ONLY'])
{
	$spotlight = new \Bitrix\Main\UI\Spotlight("CRM_TIMELINE_FASTEN_SWITCHER");
	if (!$spotlight->isViewed($USER->GetID()))
	{
		$jsLibraries[] = 'spotlight';
		$spotlightFastenShowed = false;
	}
}

CJSCore::Init($jsLibraries);

$categoryId =  (isset($arResult['EXTRAS']['CATEGORY_ID']) && (int)$arResult['EXTRAS']['CATEGORY_ID'] >= 0)
	? (int)$arResult['EXTRAS']['CATEGORY_ID']
	: null
;

if (
	AIManager::isAiCallProcessingEnabled()
	&& in_array((int)($arResult['ENTITY_TYPE_ID'] ?? 0), AIManager::SUPPORTED_ENTITY_TYPE_IDS, true)
)
{
	echo (\Bitrix\Crm\Tour\CopilotInCall::getInstance())
		->setEntityTypeId($arResult['ENTITY_TYPE_ID'])
		->build()
	;

	echo (\Bitrix\Crm\Tour\CopilotRunAutomatically::getInstance())
		->setEntityTypeId($arResult['ENTITY_TYPE_ID'])
		->build()
	;

	echo (\Bitrix\Crm\Tour\CopilotRunManually::getInstance())
		->setEntityTypeId($arResult['ENTITY_TYPE_ID'])
		->build()
	;

	$autostartSettings = FillFieldsSettings::get($arResult['ENTITY_TYPE_ID'], $categoryId);
	if (
		$autostartSettings->isAutostartTranscriptionOnlyOnFirstCallWithRecording()
		|| !$autostartSettings->shouldAutostart(TranscribeCallRecording::TYPE_ID, CCrmActivityDirection::Incoming)
		|| !$autostartSettings->shouldAutostart(TranscribeCallRecording::TYPE_ID, CCrmActivityDirection::Outgoing)
	)
	{
		if (AIManager::isBaasServiceHasPackage())
		{
			echo (\Bitrix\Crm\Tour\CopilotInCallAutomatically::getInstance())
				->setEntityTypeId($arResult['ENTITY_TYPE_ID'])
				->build()
			;
		}
		else
		{
			echo (\Bitrix\Crm\Tour\CopilotInCallBuyingBoost::getInstance())
				->setEntityTypeId($arResult['ENTITY_TYPE_ID'])
				->build()
			;
		}
	}
}

if (ServiceLocator::getInstance()->get('crm.integration.sign')::isEnabled())
{
	echo (\Bitrix\Crm\Tour\Sign\SignB2eDocumentProcess::getInstance())
		->setEntityTypeId((int)($arResult['ENTITY_TYPE_ID'] ?? 0))
		->setEntityId((int)($arResult['ENTITY_ID'] ?? 0))
		->build();
}

if ($arResult['BIZPROC_AVAILABLE'])
{
	echo \Bitrix\Crm\Tour\Bizproc\WorkflowStarted::getInstance()->build();
	echo \Bitrix\Crm\Tour\Bizproc\WorkflowTaskAddInTimeline::getInstance()->build();
	echo \Bitrix\Crm\Tour\Bizproc\WorkflowTaskCompletedInTimeline::getInstance()->build();
}

$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$listContainerID = "{$prefix}_list";
$editorContainerID = "{$prefix}_editor";

$activityEditorID = "{$prefix}_editor";
$scheduleItems = $arResult['SCHEDULE_ITEMS'];
$historyItems = $arResult['HISTORY_ITEMS'];
$fixedItems = $arResult['FIXED_ITEMS'];

foreach ($scheduleItems as $scheduleItem)
{
	if ($scheduleItem instanceof \Bitrix\Crm\Service\Timeline\Item\Activity\ToDo)
	{
		echo (\Bitrix\Crm\Tour\TodoOverlapEvents::getInstance())->setTodo($scheduleItem)->build();
	}
}

if (!empty($arResult['ERRORS']))
{
	foreach ($arResult['ERRORS'] as $error)
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
				<?php
				$mode = true;
				if ($arParams['ENTITY_CONFIG_SCOPE'] !== EntityEditorConfigScope::PERSONAL)
				{
					$mode = Container::getInstance()->getUserPermissions()->isAdminForEntity($arResult['ENTITY_TYPE_ID']);
				}

				$menuId = MenuIdResolver::getMenuId($arResult['ENTITY_TYPE_ID'], $arResult['USER_ID'], $categoryId);
				$APPLICATION->IncludeComponent(
					'bitrix:crm.timeline.menubar',
					'',
					[
						'GUID' => $arResult['GUID'],
						'MENU_ID' => $menuId,
						'ALLOW_MOVE_ITEMS' => $mode,
						'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $arResult['ENTITY_ID'],
						'ENTITY_CATEGORY_ID' => $categoryId,
						'ENTITY_CONFIG_SCOPE' => $arParams['ENTITY_CONFIG_SCOPE'] ?? EntityEditorConfigScope::UNDEFINED,
						'READ_ONLY' => $arResult['READ_ONLY'] ?? false,
						'EXTRAS' => [
							'IS_MY_COMPANY' => $arResult['EXTRAS']['IS_MY_COMPANY'] ?? 'N',
							'ANALYTICS' => $arResult['EXTRAS']['ANALYTICS'] ?? [],
						],
					]
				);
?>
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
							'GET_LIST' => '/bitrix/components/bitrix/crm.timeline/filter.ajax.php?action=list&filter_id='.urlencode($arResult['HISTORY_FILTER_ID']).'&entity_type_id='.$arResult['ENTITY_TYPE_ID'].'&entity_id='.$arResult['ENTITY_ID'].'&siteID='.SITE_ID.'&'.bitrix_sessid_get(),
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

<script>
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
			if (BX.Currency)
			{
				BX.Currency.setCurrencies(<?=CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true)?>);
			}

			BX.CrmSchedule.messages =
			{
				planned: "<?=GetMessageJS('CRM_TIMELINE_SCHEDULE_PLANNED_NEW')?>",
				stubTitle: "<?=GetMessageJS('CRM_TIMELINE_COMMON_SCHEDULE_STUB_TITLE')?>",
				stub: "<?=GetMessageJS('CRM_TIMELINE_SCHEDULE_STUB_DEFAULT')?>",
				leadStub: "<?=GetMessageJS('CRM_TIMELINE_SCHEDULE_STUB_LEAD')?>",
				dealStub: "<?=GetMessageJS('CRM_TIMELINE_SCHEDULE_STUB_DEAL')?>"
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
				quote: "<?=GetMessageJS('CRM_TIMELINE_QUOTE_CREATION_MSGVER_1')?>",
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
				storeDocumentProduct: "<?=GetMessageJS('CRM_TIMELINE_STORE_DOCUMENT_TITLE_2')?>",
				storeDocumentProductDescription: "<?=GetMessageJS('CRM_TIMELINE_STORE_DOCUMENT_DESCRIPTION_3')?>",
				storeDocumentService: "<?=GetMessageJS('CRM_TIMELINE_STORE_DOCUMENT_SERVICE_TITLE')?>",
				storeDocumentServiceDescription: "<?=GetMessageJS('CRM_TIMELINE_STORE_DOCUMENT_SERVICE_DESCRIPTION')?>",
				automationDebugger: "<?=GetMessageJS('CRM_TIMELINE_AUTOMATION_DEBUGGER_TITLE')?>",
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

			BX.CrmEntityChat.messages =
				{
					invite: "<?=GetMessageJS('CRM_TIMELINE_CHAT_INVITE_MSGVER_1')?>"
				};

			BX.message({
				"CRM_TIMELINE_CALL_TRANSCRIPT": '<?=GetMessageJS("CRM_TIMELINE_CALL_TRANSCRIPT")?>',
				"CRM_TIMELINE_CALL_TRANSCRIPT_PENDING": '<?=GetMessageJS("CRM_TIMELINE_CALL_TRANSCRIPT_PENDING")?>',
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
				"CRM_TIMELINE_ZOOM_SUCCESSFUL_ACTIVITY": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_SUCCESSFUL_ACTIVITY")?>',
				"CRM_TIMELINE_ZOOM_CONFERENCE_END": '<?=GetMessageJS("CRM_TIMELINE_ZOOM_CONFERENCE_END_2")?>',
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
				"CRM_TIMELINE_WORKFLOW_EFFICIENCY_AVERAGE_PROCESS_TIME": '<?=GetMessageJS("CRM_TIMELINE_WORKFLOW_EFFICIENCY_AVERAGE_PROCESS_TIME")?>',
				"CRM_TIMELINE_WORKFLOW_EFFICIENCY_PERFORMED_QUICKLY": '<?=GetMessageJS("CRM_TIMELINE_WORKFLOW_EFFICIENCY_PERFORMED_QUICKLY")?>',
				"CRM_TIMELINE_WORKFLOW_EFFICIENCY_PERFORMED_SLOWLY": '<?=GetMessageJS("CRM_TIMELINE_WORKFLOW_EFFICIENCY_PERFORMED_SLOWLY")?>',
				"CRM_TIMELINE_WORKFLOW_EFFICIENCY_PERFORMED_NO_PROGRESS": '<?=GetMessageJS("CRM_TIMELINE_WORKFLOW_EFFICIENCY_PERFORMED_NO_PROGRESS")?>',
				"CRM_TIMELINE_WORKFLOW_RESULT_NO_RIGHTS_VIEW": '<?=GetMessageJS("CRM_TIMELINE_WORKFLOW_RESULT_NO_RIGHTS_VIEW")?>',
				"CRM_TIMELINE_WORKFLOW_RESULT_NO_RIGHTS_TOOLTIP": '<?=GetMessageJS("CRM_TIMELINE_WORKFLOW_RESULT_NO_RIGHTS_TOOLTIP")?>',
				"CRM_TIMELINE_WORKFLOW_NO_RESULT": '<?=GetMessageJS("CRM_TIMELINE_WORKFLOW_NO_RESULT")?>',
				"CRM_TIMELINE_WORKFLOW_RESULT_TITLE": '<?=GetMessageJS("CRM_TIMELINE_WORKFLOW_RESULT_TITLE")?>',
			});

			var timeline = BX.CrmTimelineManager.create(
				"<?=CUtil::JSEscape($guid)?>",
				{
					ownerTypeId: <?=$arResult['ENTITY_TYPE_ID']?>,
					ownerId: <?=$arResult['ENTITY_ID']?>,
					ownerInfo: <?=CUtil::PhpToJSObject($arResult['ENTITY_INFO'])?>,
					userId: <?=$arResult['USER_ID']?>,
					readOnly: <?=$arResult['READ_ONLY'] ? 'true' : 'false'?>,
					currentUser: <?=\Bitrix\Main\Web\Json::encode($arResult['LAYOUT_CURRENT_USER'])?>,
					pingSettings: <?=\Bitrix\Main\Web\Json::encode($arResult['PING_SETTINGS'])?>,
					calendarSettings: <?=\Bitrix\Main\Web\Json::encode($arResult['CALENDAR_SETTINGS'] ?? [])?>,
					colorSettings: <?=\Bitrix\Main\Web\Json::encode($arResult['COLOR_SETTINGS'] ?? [])?>,
					pullTagName: "<?=CUtil::JSEscape($arResult['PULL_TAG_NAME'])?>",
					progressSemantics: "<?=CUtil::JSEscape($arResult['PROGRESS_SEMANTICS'])?>",
					containerId: "<?=CUtil::JSEscape($listContainerID)?>",
					activityEditorId: "<?=CUtil::JSEscape($arResult['ACTIVITY_EDITOR_ID'])?>",
					chatData: <?=CUtil::PhpToJSObject($arResult['CHAT_DATA'])?>,
					scheduleData: <?=\Bitrix\Main\Web\Json::encode($scheduleItems)?>,
					historyData: <?=\Bitrix\Main\Web\Json::encode($historyItems)?>,
					historyNavigation: <?=CUtil::PhpToJSObject($arResult['HISTORY_NAVIGATION'])?>,
					historyFilterId: "<?=CUtil::JSEscape($arResult['HISTORY_FILTER_ID'])?>",
					isHistoryFilterApplied: <?=$arResult['IS_HISTORY_FILTER_APPLIED'] ? 'true' : 'false'?>,
					fixedData: <?=\Bitrix\Main\Web\Json::encode($fixedItems)?>,
					ajaxId: "<?=CUtil::JSEscape($arResult['AJAX_ID'])?>",
					currentUrl: "<?=CUtil::JSEscape($arResult['CURRENT_URL'])?>",
					serviceUrl: "/bitrix/components/bitrix/crm.timeline/ajax.php?&site=<?=SITE_ID?>&<?=bitrix_sessid_get()?>",
					editorContainer: "<?=CUtil::JSEscape($editorContainerID)?>",
					spotlightFastenShowed: <?=$spotlightFastenShowed ? 'true' : 'false'?>,
					audioPlaybackRate: <?= (float) $arResult['AUDIO_PLAYBACK_RATE'] ?>
				}
			);
			BX.CrmTimelineManager.setDefault(timeline);
		}
	);
</script>
<?php
if ($arResult['BIZPROC_AVAILABLE']): ?>
	<script>
		BX.ready(
			function ()
			{
				new BX.CrmTimelineWorkflowEventManager({
					hasRunningWorkflow: <?= ($arResult['DOCUMENT_HAS_RUNNING_WORKFLOW'] ?? false) ? 'true' : 'false' ?>,
					hasWaitingWorkflowTask: <?= ($arResult['DOCUMENT_HAS_WAITING_WORKFLOW_TASK'] ?? false) ? 'true' : 'false' ?>,
					workflowTaskActivityId: <?= (int)($arResult['WORKFLOW_TASK_ACTIVITY_ID'] ?? 0) ?>,
					workflowFirstTourClosed: <?= ($arResult['WORKFLOW_FIRST_TOUR_WAS_CLOSED'] ?? false) ? 'true' : 'false' ?>,
					workflowTaskStatusTitle: '<?=GetMessageJS('CRM_TIMELINE_AUTOMATION_WORKFLOW_TASK_STATUS')?>',
				})
			}
		);
	</script>
<?php endif;
