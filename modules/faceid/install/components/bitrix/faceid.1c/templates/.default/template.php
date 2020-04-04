<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CMain $APPLICATION */

$jsMessagesCodes = array(
	'FACEID_TRACKER1C_CMP_JS_CAMERA_DEFAULT',
	'FACEID_TRACKER1C_CMP_JS_CAMERA_NOT_FOUND',
	'FACEID_TRACKER1C_CMP_JS_CAMERA_NO_SUPPORT',
	'FACEID_TRACKER1C_CMP_JS_CAMERA_ERROR',
	'FACEID_TRACKER1C_CMP_JS_AJAX_ERROR'
);

$jsMessages = array();

foreach ($jsMessagesCodes as $code)
{
	$jsMessages[$code] = Loc::getMessage($code);
}

$jsHtml = \CJSCore::GetHTML(array('ls', 'ajax'));



?>

<html>
<head>
    <title><?=Loc::getMessage("FACEID_1C_PUBLIC_TITLE")?></title>
</head>

<? if (empty($arResult['ERROR'])): ?>
    <?=$jsHtml?>

    <script type="text/javascript">

        BX.message(<?=\Bitrix\Main\Web\Json::encode($jsMessages)?>);

        BX.ready(function(){
            BXFaceIdStart({
                'AJAX_IDENTIFY_URL': '<?=$this->getComponent()->getPath()?>/ajax.php',
                'OAUTH_TOKEN': <?=\Bitrix\Main\Web\Json::encode($arResult['OAUTH_TOKEN'])?>
            });
        });
    </script>

    <script type="text/javascript" src="<?=$this->GetFolder()?>/script.js"></script>
<? endif ?>

<link href="/bitrix/templates/bitrix24/interface.css" type="text/css"  rel="stylesheet">
<link href="<?=$this->GetFolder()?>/style.css" type="text/css"  rel="stylesheet">

<div class="faceid-1c-wrap">
	<div class="faceid-tracker-sidebar-photo <?=!empty($arResult['ERROR'])?'faceid-block-warning-state':''?>" <?=!empty($arResult['ERROR'])?'':'style="display:none;"'?>>
		<div class="faceid-error-message" id="faceid-1c-ajax-error">
			<div class="faceid-error-message-text"></div>
		</div>

        <? if (!empty($arResult['ERROR'])): ?>
            <div id="faceid-camera-error" class="faceid-tracker-error faceid-tracker-error-top-center" style="display:block;">
                <?=$arResult['ERROR']?>
            </div>
        <? else: ?>
            <div id="faceid-camera-error" class="faceid-tracker-error faceid-tracker-error-top-center"></div>
        <? endif ?>

        <? if (empty($arResult['ERROR'])): ?>
            <video id="faceid-video" class="faceid-tracker-sidebar-video"><?=Loc::getMessage('FACEID_TRACKER1C_CMP_JS_CAMERA_ERROR')?></video>
            <img id="faceid-sent-snapshot">
            <canvas id="faceid-sent-snapshot-canvas"></canvas>

            <div class="faceid-tracker-sidebar-photo-settings faceid-1c-settings">
                <span class="faceid-tracker-sidebar-photo-settings-item" id="faceid-settings-button"></span>
                <div class="faceid-tracker-sidebar-photo-settings-inner" id="faceid-settings-container" style="display: none">
                    <div class="faceid-tracker-sidebar-photo-settings-inner-container">
                        <div class="faceid-tracker-sidebar-photo-settings-inner-title">
                            <span class="faceid-tracker-sidebar-photo-settings-inner-title-item"><?=Loc::getMessage('FACEID_TRACKER1C_CMP_CAMERA')?>:</span>
                        </div>
                        <div class="faceid-tracker-sidebar-photo-settings-inner-list" id="faceid-cameralist"></div>
                    </div><!--faceid-tracker-sidebar-photo-settings-inner-container-->
                </div>
            </div>
        <? endif; ?>

	</div><!--faceid-tracker-sidebar-photo-->

	<? if (empty($arResult['ERROR'])): ?>
        <div class="faceid-loader" id="faceid-1c-loader">
            <div class="faceid-loader-item">
                <div class="faceid-loader-inner">
                    <svg class="faceid-circular" viewBox="25 25 50 50">
                        <circle class="faceid-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="faceid-tracker-sidebar-photo-button">
            <span class="faceid-tracker-sidebar-photo-button-item" id="faceid-startbutton"></span>
            <div style="display: none;" class="webform-small-button-wait faceid-tracker-button-wait" id="faceid-startbutton-progress"></div>
        </div><!--faceid-tracker-sidebar-photo-button-->
	<? endif; ?>
</div>
<canvas id="faceid-canvas" style="display: none"></canvas>

</html>