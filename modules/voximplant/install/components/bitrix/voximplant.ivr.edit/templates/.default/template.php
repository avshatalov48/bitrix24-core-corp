<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @global array $arResult
 */

CJSCore::Init([
	'voximplant.common',
	'socnetlogdest',
	'phone_number',
	'sidepanel',
	'ui.alerts',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/components/bitrix/player/mediaplayer/jwplayer.js');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/voximplant.config.edit/templates/.default/style.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/font-awesome.css');

\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.hint");

$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
?>
<div id="vox-ivr-editor"></div>

<script>
	BX.message({
		'VOX_IVR_EDIT_MENU_TITLE': '<?= GetMessageJs('VOX_IVR_EDIT_MENU_TITLE')?>',
		'VOX_IVR_EDIT_MENU_LOAD_FILE': '<?= GetMessageJs('VOX_IVR_EDIT_MENU_LOAD_FILE')?>',
		'VOX_IVR_EDIT_MENU_SOURCE_FILE': '<?= GetMessageJs('VOX_IVR_EDIT_MENU_SOURCE_FILE')?>',
		'VOX_IVR_EDIT_MENU_SOURCE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_SOURCE')?>',
		'VOX_IVR_EDIT_MENU_SOURCE_MESSAGE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_SOURCE_MESSAGE')?>',
		'VOX_IVR_EDIT_MENU_DESCRIPTION_FILE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_DESCRIPTION_FILE')?>',
		'VOX_IVR_EDIT_MENU_TIMEOUT': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_TIMEOUT')?>',
		'VOX_IVR_EDIT_MENU_TIMEOUT_ACTION': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_TIMEOUT_ACTION')?>',
		'VOX_IVR_EDIT_MENU_ACTIONS': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTIONS')?>',
		'VOX_IVR_EDIT_MENU_SETUP': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_SETUP')?>',
		'VOX_IVR_EDIT_MENU_ACTION_REPEAT': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_REPEAT')?>',
		'VOX_IVR_EDIT_MENU_ACTION_QUEUE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_QUEUE')?>',
		'VOX_IVR_EDIT_MENU_ACTION_ITEM': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_ITEM')?>',
		'VOX_IVR_EDIT_MENU_ACTION_USER': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_USER')?>',
		'VOX_IVR_EDIT_MENU_ACTION_PHONE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_PHONE')?>',
		'VOX_IVR_EDIT_MENU_ACTION_DIRECTCODE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_DIRECTCODE')?>',
		'VOX_IVR_EDIT_MENU_ACTION_VOICEMAIL': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_VOICEMAIL')?>',
		'VOX_IVR_EDIT_MENU_ACTION_MESSAGE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_MESSAGE')?>',
		'VOX_IVR_EDIT_MENU_ACTION_RETURN': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_RETURN')?>',
		'VOX_IVR_EDIT_MENU_ACTION_EXIT': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_EXIT')?>',
		'VOX_IVR_EDIT_MENU_ACTION_NEW_ITEM': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_ACTION_NEW_ITEM')?>',
		'VOX_IVR_EDIT_SAVE': '<?=GetMessageJS('VOX_IVR_EDIT_SAVE')?>',
		'VOX_IVR_EDIT_CANCEL': '<?=GetMessageJS('VOX_IVR_EDIT_CANCEL')?>',
		'VOX_IVR_EDIT_RETURN': '<?=GetMessageJS('VOX_IVR_EDIT_RETURN')?>',
		'VOX_IVR_EDIT_SELECT': '<?=GetMessageJS('VOX_IVR_EDIT_SELECT')?>',
		'VOX_IVR_EDIT_CHANGE': '<?=GetMessageJS('VOX_IVR_EDIT_CHANGE')?>',
		'VOX_IVR_EDIT_ENCLOSED_MENU': '<?=GetMessageJS('VOX_IVR_EDIT_ENCLOSED_MENU')?>',
		'VOX_IVR_EDIT_FOLD': '<?=GetMessageJS('VOX_IVR_EDIT_FOLD')?>',
		'VOX_IVR_EDIT_UNFOLD': '<?=GetMessageJS('VOX_IVR_EDIT_UNFOLD')?>',
		'VOX_IVR_EDIT_MENU_DESCRIPTION_TEXT': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_DESCRIPTION_TEXT')?>',
		'VOX_IVR_EDIT_MENU_TEXT_TO_PRONOUNCE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_TEXT_TO_PRONOUNCE')?>',
		'VOX_IVR_EDIT_MENU_CHANGE_TTS_SETTINGS': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_CHANGE_TTS_SETTINGS')?>',
		'VOX_IVR_EDIT_MENU_CHANGE_TTS_VOICE': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_CHANGE_TTS_VOICE')?>',
		'VOX_IVR_EDIT_MENU_CHANGE_TTS_SPEED': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_CHANGE_TTS_SPEED')?>',
		'VOX_IVR_EDIT_MENU_CHANGE_TTS_VOLUME': '<?=GetMessageJS('VOX_IVR_EDIT_MENU_CHANGE_TTS_VOLUME')?>',
		'VOX_IVR_EDIT_VOICEMAIL_RECEIVER': '<?=GetMessageJS('VOX_IVR_EDIT_VOICEMAIL_RECEIVER')?>',
		'VOX_IVR_LIMIT_REACHED_TITLE': '<?= GetMessageJS('VOX_IVR_LIMIT_REACHED_TITLE')?>',
		'VOX_IVR_LIMIT_REACHED_TEXT': '<?= GetMessageJS('VOX_IVR_LIMIT_REACHED_TEXT')?>',
		'VOX_IVR_NOT_ALLOWED_TITLE': '<?= CUtil::JSEscape(\Bitrix\Voximplant\Ivr\Helper::getLicensePopupHeader())?>',
		'VOX_IVR_NOT_ALLOWED_TEXT': '<?= CUtil::JSEscape(\Bitrix\Voximplant\Ivr\Helper::getLicensePopupContent())?>',
		'VOX_IVR_EXIT_HINT': '<?= GetMessageJS('VOX_IVR_EXIT_HINT')?>',
		'VOX_IVR_GROUP_SETTINGS': '<?= GetMessageJS('VOX_IVR_GROUP_SETTINGS')?>',
		'VOX_IVR_HIDE_GROUP_CREATE': '<?= GetMessageJS('VOX_IVR_HIDE_GROUP_CREATE')?>',
		'VOX_IVR_HIDE_GROUP_SETTINGS': '<?= GetMessageJS('VOX_IVR_HIDE_GROUP_SETTINGS')?>'
	});
	BX.ready(function()
	{
		BX.IvrEditor.setDefaults({
			'VOICE_LIST': <?= CUtil::PhpToJSObject(\Bitrix\Voximplant\Tts\Language::getList())?>,
			'SPEED_LIST': <?= CUtil::PhpToJSObject(\Bitrix\Voximplant\Tts\Speed::getList())?>,
			'VOLUME_LIST': <?= CUtil::PhpToJSObject(\Bitrix\Voximplant\Tts\Volume::getList())?>,
			'DEFAULT_VOICE': '<?=\Bitrix\Voximplant\Tts\Language::getDefaultVoice(\Bitrix\Main\Application::getInstance()->getContext()->getLanguage())?>',
			'DEFAULT_SPEED': '<?=\Bitrix\Voximplant\Tts\Speed::getDefault()?>',
			'DEFAULT_VOLUME': '<?=\Bitrix\Voximplant\Tts\Volume::getDefault()?>',
			'IVR_LIST_URL': '<?=CVoxImplantMain::GetPublicFolder().'ivr.php'?>',
			'TELEPHONY_GROUPS': <?= CUtil::PhpToJSObject($arResult['TELEPHONY_GROUPS'])?>,
			'STRUCTURE': <?= CUtil::PhpToJSObject($arResult['STRUCTURE'])?>,
			'USERS': <?= CUtil::PhpToJSObject($arResult['USERS'])?>,
			'IS_ENABLED': <?= CUtil::PhpToJSObject(\Bitrix\Voximplant\Ivr\Ivr::isEnabled())?>,
			'MAX_DEPTH': <?= \Bitrix\Voximplant\Limits::getIvrDepth()?>,
			'MAX_GROUPS': <?= \Bitrix\Voximplant\Limits::getMaximumGroups()?>
		});

		ivrEditor = new BX.IvrEditor({
			node: BX('vox-ivr-editor'),
			isNew: <?= ($arResult['NEW'] ? 'true' : 'false')?>,
			ivrData: <?= CUtil::PhpToJSObject($arResult['IVR'])?>,
			ttsDisclaimer: '<?= CUtil::JSEscape($arResult['TTS_DISCLAIMER']) ?>',
		});

		ivrEditor.init();
        BX.UI.Hint.init(BX('ivrHint'));
	});
</script>