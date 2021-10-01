<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die;

use Bitrix\Main\Localization\Loc;
\Bitrix\Main\UI\Extension::load("ui.buttons", "ui.fonts.opensans");

/** @var array $arResult */
\CJSCore::Init(array('update_stepper'));

$jsMessagesCodes = array(
	"FACEID_TMS_START_PLAN_UPGRADE_TITLE",
    "FACEID_TMS_START_PLAN_UPGRADE_TEXT",
	"FACEID_TMS_START_ENABLE_TM_TITLE",
	"FACEID_TMS_START_ENABLE_TM_TEXT",
	"FACEID_TMS_START_ENABLE_TM_CLOSE"
);

$jsMessages = array();

foreach ($jsMessagesCodes as $code)
{
	$jsMessages[$code] = Loc::getMessage($code);
}

?>

<script>
	BX.message(<?=\Bitrix\Main\Web\Json::encode($jsMessages)?>);
	BX.ready(function ()
	{
		FaceidTimeManStartPromo();
	});
</script>

<div class="b24-time-container">
	<div class="adm-promo-title adm-promo-main-title">
		<span class="adm-promo-title-item"><?= Loc::getMessage("FACEID_TMS_START_WELCOME")?></span>
	</div>
</div><!--b24-time-container-->

<div class="b24-time-container <?=\Bitrix\Main\Context::getCurrent()->getLanguage() != 'ru'? "b24-time-multilang" : '' ?>">
	<div class="b24-time-container-logo">
		<div class="b24-time-video-block">
			<div class="b24-time-video">
				<?$APPLICATION->IncludeComponent(
                    "bitrix:player",
                    ".default",
                    array(
                        "ADVANCED_MODE_SETTINGS" => "Y",
                        "AUTOSTART" => "Y",
                        "HEIGHT" => "337",
                        "MUTE" => "Y",
                        "PATH" => "https://www.youtube.com/embed/kGw9isDoKlo?rel=0",
                        "PLAYBACK_RATE" => "1",
                        "PLAYER_ID" => "",
                        "PLAYER_TYPE" => "videojs",
                        "SHOW_CONTROLS" => "Y",
                        "SIZE_TYPE" => "absolute",
                        "USE_PLAYLIST" => "N",
                        "VOLUME" => "90",
                        "WIDTH" => "532",
                        "COMPONENT_TEMPLATE" => ".default"
                    ),
                    false
                );?>
            </div>
		</div>
	</div>
</div>

<div class="b24-time-container">
	<div class="b24-time-desc"><?= Loc::getMessage("FACEID_TMS_START_DESCR_NEW")?>
	</div>
</div>

<div class="b24-time-container">
	<div class="adm-promo-title b24-list-title">
		<span class="adm-promo-title-item"><?= Loc::getMessage("FACEID_TMS_START_NAME_SERVICE")?></span>
	</div>
	<div class="b24-time-advantage">
		<span class="b24-time-advantage-item advantage-item-1"><?= Loc::getMessage("FACEID_TMS_START_ADV_1")?></span>
		<span class="b24-time-advantage-item advantage-item-2"><?= Loc::getMessage("FACEID_TMS_START_ADV_2")?></span>
		<span class="b24-time-advantage-item advantage-item-3"><?= Loc::getMessage("FACEID_TMS_START_ADV_3")?></span>
	</div><!--b24-time-advantage-->
	<!--		<div class="b24-time-border"></div>-->
</div><!--b24-time-container-->

<div class="b24-time-container">
	<div class="adm-promo-title b24-list-title">
		<span class="adm-promo-title-item"><?= Loc::getMessage("FACEID_TMS_START_HOW")?></span>
	</div>
	<ul class="b24-time-list">
		<li class="b24-time-list-item b24-time-count-block-1"><?= Loc::getMessage("FACEID_TMS_START_HOW_1")?></li>
		<li class="b24-time-list-item b24-time-count-block-2"><?= Loc::getMessage("FACEID_TMS_START_HOW_2")?></li>
		<li class="b24-time-list-item b24-time-count-block-3"><?= Loc::getMessage("FACEID_TMS_START_HOW_3")?></li>
		<li class="b24-time-list-item b24-time-count-block-4"><?= Loc::getMessage("FACEID_TMS_START_HOW_4")?></li>
	</ul>
	<div class="b24-time-border"></div>
</div><!--b24-time-container-->

<? if (\Bitrix\Main\Config\Option::get('faceid', 'user_index_processing', 0)): ?>
    <!-- indexing in progress -->
    <div id="faceid-tms-index" class="b24-time-container b24-time-centering-text-block">
        <div class="b24-time-progressbar-container" style="display: block">
            <div class="b24-time-progressbar"></div>
            <div class="b24-time-progressbar-desc"><?= Loc::getMessage("FACEID_TMS_START_INDEX_PROCESS_NEW")?></div>
        </div>
    </div>
    <div id="faceid-tms-stepper">
    <?
        $stepperData = array('faceid' => array('Bitrix\Faceid\ProfilePhotoIndex'));
        echo \Bitrix\Main\Update\Stepper::getHtml($stepperData, \Bitrix\Main\Localization\Loc::getMessage("FACEID_TMS_START_INDEX_PHOTOS"));
        return;
    ?>
    </div>
<? elseif (\Bitrix\Main\Config\Option::get('faceid', 'user_indexed', 0)): ?>
    <!-- index is ready -->
    <div class="b24-time-container" id="faceid-tms-done">
        <div class="b24-time-bottom-title"><?= Loc::getMessage("FACEID_TMS_START_DONE")?></div>
        <div class="b24-time-bottom-subtitle"><?= Loc::getMessage("FACEID_TMS_START_SMILE")?></div>
    </div>
	<? return; ?>
<? else: ?>
    <!-- no index yet -->
    <div class="b24-time-container b24-time-centering-text-block">
        <div class="b24-time-button b24-time-button-blue" id="faceid-tms-howto-open"><?= Loc::getMessage("FACEID_TMS_START_DO_START")?></div>
    </div>
<? endif ?>
<!-- next step -->

<div class="b24-time-container" id="faceid-tms-howto" style="display: none">
	<div class="b24-time-desc-block b24-time-desc-block-blue b24-time-count-block-1">
		<div class="b24-time-desc-text"><?= Loc::getMessage("FACEID_TMS_START_DO_1")?></div>
	</div>
	<div class="b24-time-desc-block b24-time-desc-block-blue b24-time-count-block-2">
		<div class="b24-time-desc-text-list">
            <?= Loc::getMessage("FACEID_TMS_START_DO_2")?>
		</div>
		<ul class="b24-time-desc-list b24-time-desc-text-list-item">
			<li class="b24-time-desc-list-item b24-time-desc-list-item-count-1">
				<div class="b24-time-desc-list-item-element-title"><?= Loc::getMessage("FACEID_TMS_START_DO_3")?></div>
				<div class="b24-time-desc-list-item-element"><?= Loc::getMessage("FACEID_TMS_START_DO_31")?></div>
				<div class="b24-time-desc-list-item-element"><?= Loc::getMessage("FACEID_TMS_START_DO_32")?></div>
			</li>
			<li class="b24-time-desc-list-item b24-time-desc-list-item-count-2">
				<div class="b24-time-desc-list-item-element-title"><?= Loc::getMessage("FACEID_TMS_START_DO_4")?></div>
				<div class="b24-time-desc-list-item-element"><?= Loc::getMessage("FACEID_TMS_START_DO_41")?></div>
			</li>
		</ul>
	</div>
	<div class="b24-time-desc-block b24-time-desc-block-blue b24-time-count-block-3">
		<div class="b24-time-desc-text">
            <? if ($arResult['TIMEMAN_AVAILABLE'] || !$arResult['IS_B24']): ?>
                <?= Loc::getMessage('FACEID_TMS_START_DO_5_NEW')?>
            <? else: ?>
                <?= Loc::getMessage("FACEID_TMS_START_DO_5_NO_TIMEMAN")?>
            <? endif ?>
		</div>
	</div>
</div>
<div id="faceid-tms-index" class="b24-time-container b24-time-centering-text-block" style="display: none">

	<? if ($arResult['TIMEMAN_AVAILABLE'] && $arResult['TIMEMAN_ENABLED']): ?>
		<div class="b24-time-button b24-time-button-blue" id="faceid-tms-index-button"><?= Loc::getMessage("FACEID_TMS_START_DO_TRY")?></div>
	<? elseif ($arResult['IS_B24'] && !$arResult['TIMEMAN_AVAILABLE']) : ?>
		<div class="b24-time-button b24-time-button-blue" id="faceid-tms-plan-upgrade-button"><?= Loc::getMessage("FACEID_TMS_START_DO_TRY_NO_TIMEMAN")?></div>
	<? elseif ($arResult['IS_B24'] && $arResult['TIMEMAN_AVAILABLE'] && !$arResult['TIMEMAN_ENABLED']): ?>
		<div class="b24-time-button b24-time-button-blue" id="faceid-tms-enable-timeman-button"><?= Loc::getMessage("FACEID_TMS_START_DO_TRY")?></div>
	<? endif ?>

	<div class="b24-time-progressbar-container">
		<div class="b24-time-progressbar"></div>
		<div class="b24-time-progressbar-desc"><?= Loc::getMessage("FACEID_TMS_START_INDEX_PROCESS_NEW")?></div>
	</div>

	<div class="b24-time-container" id="faceid-tms-index-done">
		<div class="b24-time-bottom-title"><?= Loc::getMessage("FACEID_TMS_START_DONE")?></div>
		<div class="b24-time-bottom-subtitle"><?= Loc::getMessage("FACEID_TMS_START_SMILE")?></div>
	</div>

    <? if (\Bitrix\Main\Loader::includeModule('bitrix24')): ?>
        <div class="b24-time-desc-block b24-time-desc-text-small b24-time-desc-block-red b24-time-desc-warning">
            <div><?= Loc::getMessage("FACEID_TMS_START_WARN_NEW", array('T_URL' => '/settings/license_all.php'))?></div>
        </div>
    <? endif ?>
</div>

<div id="faceid-tms-stepper"></div>