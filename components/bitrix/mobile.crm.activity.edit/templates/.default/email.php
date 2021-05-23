<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
global $APPLICATION;
$APPLICATION->AddHeadString('<script type="text/javascript" src="' . CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . '/crm_mobile.js') . '"></script>', true, \Bitrix\Main\Page\AssetLocation::AFTER_JS_KERNEL);
$APPLICATION->SetPageProperty('BodyClass', 'crm-page');

CUtil::InitJSCore(array('ajax', 'date', 'mobile_crm'));

$UID = $arResult['UID'];
$mode = $arResult['MODE'];
$entityID = $arResult['ENTITY_ID'];
$isNew = $entityID <= 0;

$entity = $arResult['ENTITY'];
$dataItem = CCrmMobileHelper::PrepareActivityData($entity);
$communications = isset($dataItem['COMMUNICATIONS']) ? $dataItem['COMMUNICATIONS'] : array();

$prefix = htmlspecialcharsbx($UID);

$title = $isNew ? GetMessage('M_CRM_ACTIVITY_EDIT_NEW_EMAIL') : (isset($entity['SUBJECT']) ? $entity['SUBJECT'] : '');
$storageTypeID = $entity['STORAGE_TYPE_ID'];

if($storageTypeID === \Bitrix\Crm\Integration\StorageType::Disk)
	$storageElements = $entity['DISK_FILES'];
elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::WebDav)
	$storageElements = $entity['WEBDAV_ELEMENTS'];
elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::File)
	$storageElements = $entity['FILES'];
else
	$storageElements = array();

$ownerID = intval($arResult['OWNER_ID']);
$ownerTypeID = $arResult['OWNER_TYPE_ID'];
$ownerTypeName = $arResult['OWNER_TYPE_NAME'];
$ownerTitle = $arResult['OWNER_TITLE'];
$canChangeOwner = $arResult['CAN_CHANGE_OWNER'];
?>
<div class="crm_toppanel">
	<div class="crm_filter"><span class="crm_mail_icon"></span><?=htmlspecialcharsbx($title)?></div>
</div>
<div class="crm_wrapper">
	<div class="crm_block_container aqua_style comments">
		<div class="crm_block_title fln"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_EDIT_EMAIL_FIELD_FROM'))?></div>
		<hr/>
		<div class="crm_card crm_arrow" style="padding-bottom: 0;">
			<div class="crm_card_image">
				<?if($arResult['USER_PHOTO_URL'] !== ''):?>
				<img src="<?=htmlspecialcharsbx($arResult['USER_PHOTO_URL'])?>" />
				<?endif;?>
			</div>
			<div id="<?=$prefix?>_addresser_name" class="crm_card_name_meeting"><?=htmlspecialcharsbx($arResult['USER_ACTUAL_NAME'])?></div>
			<div id="<?=$prefix?>_addresser_email" class="crm_card_description_meeting"><?=htmlspecialcharsbx($arResult['USER_ACTUAL_EMAIL'])?></div>
			<input type="hidden" id="<?=$prefix?>_addresser" value="<?=htmlspecialcharsbx($arResult['USER_ACTUAL_ADDRESSER'])?>" />
			<div class="clearboth"></div>
		</div>
	</div>

	<div class="crm_block_container">
		<div class="crm_block_title fln"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_EDIT_EMAIL_FIELD_COMM'))?></div>
		<hr />
		<div id="<?=$prefix?>_communication" class="crm_card" style="padding-bottom: 0;">
			<?foreach($communications as &$comm):?>
			<div class="task-form-participant-block">
				<div class="task-form-participant-row">
					<div class="task-form-participant-row-name">
						<a href="#" class="task-form-participant-row-link" onclick="return BX.eventReturnFalse();"><?=htmlspecialcharsbx($comm['TITLE'])?></a>
					</div>
					<div class="task-form-participant-row-post"><?=htmlspecialcharsbx($comm['VALUE'])?></div>
					<div class="task-form-participant-btn"><i></i></div>
				</div>
			</div>
			<?endforeach;?>
			<?unset($comm);?>
			<div class="tac" style="margin-top: 20px;"><a id="<?=$prefix?>_add_communication" class="crm_people_cont_aqua_two" href="#">+ <?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_EDIT_EMAIL_ADD_COMM'))?></a></div>
		</div>
	</div>
	<div class="crm_block_container">
		<div class="crm_block_title fln"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_EDIT_EMAIL_DETAIL_SECTION'))?></div>
		<hr>
		<div class="crm_meeting_info">
			<input id="<?=$prefix?>_subject" type="text" class="crm_input_text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_EDIT_EMAIL_FIELD_SUBJECT'))?>"/>
			<textarea id="<?=$prefix?>_description" class="crm_input_text" placeholder="<?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_EDIT_EMAIL_FIELD_DESCRIPTION'))?>"></textarea>
			<div class="tac" style="margin-top: 20px;">
			<!--<a href="" class="crm_buttons pics"><span></span></a>-->
			<!--<a href="" class="crm_buttons folder"><span></span></a>-->
			</div>
		</div>
	</div>

	<div class="crm_block_container aqua_style comments">
		<div<?=$canChangeOwner ? ' class="crm_arrow"' : ''?>>
			<div class="crm_block_title"><?=htmlspecialcharsbx(GetMessage('M_CRM_ACTIVITY_EDIT_FIELD_OWNER'))?>: <span id="<?=$prefix?>_owner_title"><?=$ownerTypeID === CCrmOwnerType::Deal ? htmlspecialcharsbx($ownerTitle) : GetMessage('M_CRM_ACTIVITY_EDIT_FIELD_OWNER_NOT_SPECIFIED')?></span></div>
			<input type="hidden"  id="<?=$prefix?>_owner_id" value="<?=$ownerID?>" />
			<input type="hidden"  id="<?=$prefix?>_owner_type" value="<?=htmlspecialcharsbx($ownerTypeName)?>" />
			<div class="clearboth"></div>
		</div>
	</div>

	<div class="crm_block_container"<?=empty($storageElements) ? ' style="display:none;"' : ''?>>
		<div class="crm_files">
			<input type="hidden"  id="<?=$prefix?>_storage_type" value="<?=htmlspecialcharsbx($storageTypeID)?>" />
			<ul id="<?=$prefix?>_files"><?
			if($storageTypeID === \Bitrix\Crm\Integration\StorageType::Disk):
				foreach($storageElements as &$info):
					?><li><a href="<?=htmlspecialcharsbx($info['VIEW_URL'])?>"><?=htmlspecialcharsbx($info['NAME'])?></a></li><?
				endforeach;
				unset($info);
			elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::WebDav):
				foreach($storageElements as &$info):
					?><li><a href="<?=htmlspecialcharsbx($info['VIEW_URL'])?>"><?=htmlspecialcharsbx($info['NAME'])?></a></li><?
				endforeach;
				unset($info);
			elseif($storageTypeID === \Bitrix\Crm\Integration\StorageType::File):
				foreach($storageElements as &$info):
					?><li><a href="<?=htmlspecialcharsbx($info['fileURL'])?>"><?=htmlspecialcharsbx($info['fileName'])?></a></li><?
				endforeach;
				unset($info);
			endif;
			?></ul>
		</div>
	</div>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			var context = BX.CrmMobileContext.getCurrent();
			context.enableReloadOnPullDown(
				{
					pullText: "<?=GetMessageJS('M_CRM_ACTIVITY_EDIT_PULL_TEXT')?>",
					downText: "<?=GetMessageJS('M_CRM_ACTIVITY_EDIT_DOWN_TEXT')?>",
					loadText: "<?=GetMessageJS('M_CRM_ACTIVITY_EDIT_LOAD_TEXT')?>"
				}
			);

			var dispatcher = BX.CrmEntityDispatcher.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					typeName: 'ACTIVITY',
					data: <?=CUtil::PhpToJSObject(array($dataItem))?>,
					serviceUrl: '<?=CUtil::JSEscape($arResult['SERVICE_URL'])?>',
					formatParams: <?=CUtil::PhpToJSObject(
						array(
							'ACTIVITY_EDIT_URL_TEMPLATE' => $arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
							'ACTIVITY_SHOW_URL_TEMPLATE' => $arParams['ACTIVITY_SHOW_URL_TEMPLATE'],
							'USER_PROFILE_URL_TEMPLATE' => $arParams['USER_PROFILE_URL_TEMPLATE'],
							'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
						)
					)?>
				}
			);

			BX.CrmEmailEditor.messages =
			{
				userSelectorOkButton: '<?=GetMessageJS('M_CRM_ACTIVITY_EDIT_USER_SELECTOR_OK_BTN')?>',
				userSelectorCancelButton: '<?=GetMessageJS('M_CRM_ACTIVITY_EDIT_USER_SELECTOR_CANCEL_BTN')?>'
			};

			<?
			$onDealSelectEventName = 'onCrmDealSelectForEmailActivity_'.$arResult['ENTITY_ID'];
			$dealSelectorUrl = CHTTP::urlAddParams($arResult['DEAL_SELECTOR_URL'], array(
				"event" => $onDealSelectEventName
			));
			?>
			var editor = BX.CrmEmailEditor.create(
				"<?=CUtil::JSEscape($UID)?>",
				{
					dispatcher: dispatcher,
					entityId: <?=CUtil::JSEscape($entityID)?>,
					title: '<?=CUtil::JSEscape($title)?>',
					prefix: "<?=CUtil::JSEscape($UID)?>",
					contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
					ownerId: "<?=CUtil::JSEscape($arResult['OWNER_ID'])?>",
					ownerType: "<?=CUtil::JSEscape($arResult['OWNER_TYPE_NAME'])?>",
					canChangeOwner: <?=$canChangeOwner ? 'true' : 'false'?>,
					communicationSelectorUrl: "<?=CUtil::JSEscape($arResult['COMMUNICATION_SELECTOR_URL'])?>",
					onDealSelectEventName: "<?=CUtil::JSEscape($onDealSelectEventName)?>",
					dealSelectorUrl: "<?=CUtil::JSEscape($dealSelectorUrl)?>",
					userEmailConfiguratorUrl: "<?=CUtil::JSEscape($arResult['USER_EMAIL_CONFIGURATOR_URL'])?>"
				}
			);
			editor.initializeFromExternalData();

			context.createButtons(
				{
					back:
					{
						type: 'right_text',
						style: 'custom',
						position: 'left',
						name: '<?=GetMessageJS('M_CRM_ACTIVITY_EDIT_EMAIL_CANCEL_BTN')?>',
						callback: context.createCloseHandler()
					},
					save:
					{
						type: 'right_text',
						style: 'custom',
						position: 'right',
						name: '<?=GetMessageJS("M_CRM_ACTIVITY_EDIT_EMAIL_{$mode}_BTN")?>',
						callback: editor.createSaveHandler()
					}
				}
			);
		}
	);
</script>
