<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/style.css");


$UID = $arResult['UID'];
$editorName = "{$UID}_lhe";
$prefix = htmlspecialcharsbx($UID).'_';
$submitHandlerName = "_{$UID}_submit";

$enableTitle = $arResult['ENABLE_TITLE'];
$event = $arResult['EVENT'];
$errorMessages = $arResult['ERROR_MESSAGES'];
$hasErrors = !empty($errorMessages);

$formParams = array(
	'FORM_ID' => $UID,
	'SHOW_MORE' => 'N',
	'PARSER' => array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'ForeColor', 'FontList', 'FontSizeList', 'RemoveFormat',
		'Quote', 'Code', 'InsertCut',
		'CreateLink', 'Image', 'Table', 'Justify',
		'InsertOrderedList', 'InsertUnorderedList',
		'SmileList', 'Source', 'UploadImage', 'InputVideo', 'MentionUser'
	),
	'BUTTONS' => array(
		'UploadFile',
		'CreateLink',
		'InputVideo',
		'Quote',
		'MentionUser'
	),
	'ADDITIONAL' => array(
		"<span id=\"{$prefix}message_title_toggle_btn\" title=\"".GetMessage('CRM_SL_EVENT_EDIT_BUTTON_TITLE_LEGEND')."\" ".
			"class=\"feed-add-post-form-title-btn".($enableTitle ? " feed-add-post-form-btn-active\" " : "\"")."></span>"
	),
	'TEXT' => array(
		'NAME' => 'MESSAGE',
		'VALUE' => $event['MESSAGE'],
		'HEIGHT' => '120px'
	),
	'UPLOAD_FILE' => false,
	'UPLOAD_WEBDAV_ELEMENT' => is_array($arResult['WEB_DAV_FILE_FIELD']) ? $arResult['WEB_DAV_FILE_FIELD'] : false,
	'UPLOAD_FILE_PARAMS' => array('width' => $arParams['IMAGE_MAX_WIDTH'], 'height' => $arParams['IMAGE_MAX_HEIGHT']),
	'DESTINATION' => array(
		"VALUE" => $arResult['FEED_DESTINATION'],
		'SHOW' => 'Y'
	),
	//'SMILES' => array('VALUE' => array()),
	'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
	'LHE' => array(
		'id' => $editorName,
		'documentCSS' => 'body {color:#434343;}',
		'ctrlEnterHandler' => $submitHandlerName,
		'jsObjName' => $editorName,
		'fontFamily' => "'Helvetica Neue', Helvetica, Arial, sans-serif",
		'fontSize' => '14px',
		'bInitByJS' => !$hasErrors
	),
	'MPF_DESTINATION_1' => GetMessage("CRM_SL_EVENT_EDIT_MPF_DESTINATION_1"),
	"USE_CLIENT_DATABASE" => "Y",
	"DEST_CONTEXT" => "CRM_POST",
	"PROPERTIES" => array(
		array_key_exists("UF_SONET_LOG_URL_PRV", $arResult["POST_PROPERTIES"]["DATA"]) ?
			array_merge(
				$arResult["POST_PROPERTIES"]["DATA"]["UF_SONET_LOG_URL_PRV"],
				array(
					'ELEMENT_ID' => 'url_preview_'.$editorName,
					'STYLE' => 'margin: 0 18px'
				)
			)
			:
			array()
	),
	"ALLOW_CRM_EMAILS" => "Y"
);

$postFormUri = isset($arResult['POST_FORM_URI']) ? $arResult['POST_FORM_URI'] : '';

if($hasErrors):
	?><div class="feed-notice-block" style="display: block; opacity: 1; height: auto;"><?
	foreach($errorMessages as &$errorMessage):
		?><div class="feed-add-error">
			<span class="feed-add-info-icon"></span>
			<span class="feed-add-info-text"><?=htmlspecialcharsbx($errorMessage);?></span>
		</div><?
	endforeach;
	unset($errorMessage);
	?></div><?
endif;
?><div class="feed-wrap" style="position:relative;"><div class="feed-add-post-block">
<div class="feed-add-post-micro" id="<?=CUtil::JSEscape($UID)?>_micro" style="display: <?=(($hasErrors)? 'none' : 'block')?>"><?
	?><div id="<?=CUtil::JSEscape($UID)?>_micro_inner"><?
		?><span class="feed-add-post-micro-title"><?=GetMessage('CRM_SL_EVENT_EDIT_LINK_SHOW_NEW')?></span><?
		?><span class="feed-add-post-micro-dnd"><?=GetMessage('CRM_SL_EVENT_EDIT_DRAG_ATTACHMENTS2')?></span><?
	?></div><?
?></div>
<div id="microblog-form">
<form action="<?=htmlspecialcharsbx($postFormUri !== '' ? $postFormUri : POST_FORM_ACTION_URI)?>" id="<?=htmlspecialcharsbx($UID)?>" name="<?=htmlspecialcharsbx($UID)?>" method="POST" enctype="multipart/form-data" target="_self">
	<?=bitrix_sessid_post();?>
	<div id="feed-add-post-content-message">
		<div class="feed-add-post-title" id="<?=$prefix?>message_title_wrapper"<?=$enableTitle ? '' : ' style="display: none;"'?>>
			<input id="<?=$prefix?>message_title" type="text" name="POST_TITLE" class="feed-add-post-inp feed-add-post-inp-active"
				value="<?=htmlspecialcharsbx($event['TITLE'])?>" placeholder="<?=GetMessage("CRM_SL_EVENT_EDIT_BUTTON_TITLE_LEGEND")?>" />
			<input id="<?=$prefix?>enable_message_title" type="hidden" name="ENABLE_POST_TITLE" value="<?=$enableTitle ? 'Y' : 'N'?>" />
			<div class="feed-add-close-icon" id="<?=$prefix?>message_title_close_btn"></div>
		</div><?
		if(!$hasErrors):
			?><span class="crm-feed-form-title-arrow"></span><?
		endif;

		$this->SetViewTarget('mpl_input_additional', 100);
		include($_SERVER["DOCUMENT_ROOT"].$this->getFolder().'/mpf.php');
		$this->EndViewTarget('mpl_input_additional');

		$APPLICATION->IncludeComponent(
			'bitrix:main.post.form',
			'',
			$formParams,
			$component,
			array('HIDE_ICONS' => 'Y')
		);

	?></div>
	<div class="feed-buttons-block" id="<?=$prefix?>button_block">
		<a href="#" id="<?=$prefix?>button_save" class="feed-add-button" onmousedown="BX.addClass(this, 'feed-add-button-press')" onmouseup="BX.removeClass(this,'feed-add-button-press')"><span class="feed-add-button-left"></span><span class="feed-add-button-text" onclick="MPFbuttonShowWait(this);"><?=htmlspecialcharsbx(GetMessage('CRM_SL_EVENT_EDIT_BUTTON_SAVE'))?></span><span class="feed-add-button-right"></span></a>
		<a href="#" id="<?=$prefix?>button_cancel" class="feed-cancel-com"><?=htmlspecialcharsbx(GetMessage('CRM_SL_EVENT_EDIT_BUTTON_CANCEL'))?></a>
	</div>
</form>
</div></div></div>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var uid = "<?=CUtil::JSEscape($UID)?>";
			var editor = BX.CrmSonetEventEditor.create(
				uid,
				{
					formId: "<?=CUtil::JSEscape($UID)?>",
					prefix:"<?=CUtil::JSEscape(htmlspecialcharsback($prefix))?>",
					editorName:"<?=CUtil::JSEscape($editorName)?>"
				}
			);

			//CTRL+ENTER form submit handler
			window["<?=CUtil::JSEscape($submitHandlerName)?>"] = editor.createSubmitHandler();
		}
	);
</script>
