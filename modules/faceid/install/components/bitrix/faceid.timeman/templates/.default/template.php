<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CMain $APPLICATION */

if (!isset($arResult['HAS_AGREEMENT']))
{
    echo Loc::getMessage('FACEID_TRACKERWD_CMP_AUTH_ONLY');
    return;
}

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/faceid/WebPhotoMaker/WebPhotoMaker.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/faceid/WebPhotoMaker/smoother.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/faceid/WebPhotoMaker/fpsmeter.min.js');

$jsMessagesCodes = array(
    'FACEID_TRACKERWD_CMP_JS_CAMERA_DEFAULT',
    'FACEID_TRACKERWD_CMP_JS_CAMERA_NOT_FOUND',
    'FACEID_TRACKERWD_CMP_JS_CAMERA_NO_SUPPORT',
    'FACEID_TRACKERWD_CMP_JS_CAMERA_ERROR',
    'FACEID_TRACKERWD_CMP_JS_FACE_NOT_FOUND',
    'FACEID_TRACKERWD_CMP_JS_FACE_ORIGINAL',
    'FACEID_TRACKERWD_CMP_JS_SAVE_CRM',
    'FACEID_TRACKERWD_CMP_JS_SAVE_CRM_DONE',
    'FACEID_TRACKERWD_CMP_JS_VK_LINK',
    'FACEID_TRACKERWD_CMP_JS_VK_LINK_ACTION',
    'FACEID_TRACKERWD_CMP_JS_VK_FOUND_PEOPLE',
    'FACEID_TRACKERWD_CMP_JS_VK_SELECT'
);

$jsMessages = array();

foreach ($jsMessagesCodes as $code)
{
    $jsMessages[$code] = Loc::getMessage($code);
}


?>

<script type="text/javascript">
	BX.message(<?=\Bitrix\Main\Web\Json::encode($jsMessages)?>);
	window.FACEID_AGREEMENT = <?=$arResult['HAS_AGREEMENT']?'true':'false'?>;

	if (window.FACEID_AGREEMENT)
    {
		BX.ready(function(){
			BXFaceIdStart();
		});
    }
</script>

<div class="faceid-tracker-wrapper">

	<div class="faceid-tracker-header-description" style="visibility: hidden; display: none">
		<div class="faceid-tracker-header-description-title">
			<span class="faceid-tracker-header-description-title-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_TITLE')?></span>
		</div>
		<div class="faceid-tracker-header-description-visual">
			<div class="faceid-tracker-header-description-visual-item"></div>
		</div>
		<div class="faceid-tracker-header-description-inner">
			<div class="faceid-tracker-header-description-list">
				<div class="faceid-tracker-header-description-list-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_DESCR_P1_NEW')?></div>
				<div class="faceid-tracker-header-description-list-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_DESCR_P2_NEW')?></div>
			</div>
		</div>
		<div class="faceid-tracker-header-description-close" id="faceid-tracker-header-description-close">
			<span class="faceid-tracker-header-description-close-item"></span>
		</div>
	</div><!--faceid-tracker-header-description-->

	<div class="faceid-tracker-sidebar">
		<div class="faceid-tracker-sidebar-photo">

            <div class="faceid-tracker-workday">
                <div class="faceid-tracker-workday-started" id="faceid-tracker-workday-started"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATUS_STARTED')?></div>
                <div class="faceid-tracker-workday-ended" id="faceid-tracker-workday-ended"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATUS_ENDED')?></div>
                <div class="faceid-tracker-workday-opened" id="faceid-tracker-workday-opened">
                    <div class="faceid-tracker-workday-action-pause">puase</div>
                    <div class="faceid-tracker-workday-action faceid-tracker-workday-action-end" id="faceid-tracker-workday-action-end"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_ACTION_END')?></div>
                </div>
                <div class="faceid-tracker-workday-paused" id="faceid-tracker-workday-paused">
                    <div class="faceid-tracker-workday-action-resume">resume</div>
                    <div class="faceid-tracker-workday-action-end">end</div>
                </div>
            </div>

			<div id="faceid-camera-error" class="faceid-tracker-error faceid-tracker-error-top-center"></div>

			<video id="faceid-video" class="faceid-tracker-sidebar-video"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_JS_CAMERA_ERROR')?></video>
            <canvas id="faceid-overlay-face-border" class="faceid-tracker-border-overlay"></canvas>

			<div id="faceid-fullscreen-button" class="faceid-tracker-sidebar-photo-mode faceid-tracker-sidebar-photo-full-mode">
				<div class="faceid-tracker-sidebar-photo-mode-icon"></div>
			</div>

            <div class="faceid-tracker-sidebar-photo-settings">
				<span class="faceid-tracker-sidebar-photo-settings-item" id="faceid-settings-button"></span>
				<div class="faceid-tracker-sidebar-photo-settings-inner" id="faceid-settings-container" style="display: none">
					<div class="faceid-tracker-sidebar-photo-settings-inner-container">
						<div class="faceid-tracker-sidebar-photo-settings-inner-title">
							<span class="faceid-tracker-sidebar-photo-settings-inner-title-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_CAMERA')?>:</span>
						</div>
						<div class="faceid-tracker-sidebar-photo-settings-inner-list" id="faceid-cameralist"></div>
					</div><!--faceid-tracker-sidebar-photo-settings-inner-container-->
				</div>
			</div>
		</div><!--faceid-tracker-sidebar-photo-->
		<div class="faceid-tracker-sidebar-settings">
			<div class="faceid-tracker-sidebar-photo-button">
				<span class="faceid-tracker-sidebar-photo-button-item" id="faceid-startbutton"></span>
			</div><!--faceid-tracker-sidebar-photo-button-->
			<div class="faceid-tracker-sidebar-settings-title">
				<span class="faceid-tracker-sidebar-title-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_AUTO')?></span>
				<span class="faceid-tracker-border"></span>
			</div>
			<div class="faceid-tracker-sidebar-settings-control">
				<div class="faceid-tracker-sidebar-settings-control-item">
						<span class="faceid-tracker-sidebar-settings-control-inner">
							<input type="checkbox" id="faceid-auto-identify" class="faceid-tracker-sidebar-settings-control-checkbox" checked>
							<label for="faceid-auto-identify" class="faceid-tracker-sidebar-settings-control-label"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_AUTO_PHOTO')?></label>
							<span class="faceid-tracker-control-info"></span>
						</span>
				</div>
				<div class="faceid-tracker-sidebar-settings-control-item">
						<span class="faceid-tracker-sidebar-settings-control-inner">
							<input type="checkbox" id="2" class="faceid-tracker-sidebar-settings-control-checkbox" disabled="">
							<label for="2" class="faceid-tracker-sidebar-settings-control-label" style="color: #ccc"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_AUTO_LEAD')?></label>
							<span class="faceid-tracker-control-info"></span>
						</span>
				</div>
				<div class="faceid-tracker-sidebar-settings-status-user" style="visibility: hidden">
					<div class="faceid-tracker-sidebar-settings-status-user-title">
						<span class="faceid-tracker-sidebar-title-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATS')?></span>
					</div>
					<div class="faceid-tracker-sidebar-settings-status-container">
						<div class="faceid-tracker-sidebar-settings-status-inner-block">
							<div class="faceid-tracker-sidebar-settings-status-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATS_NEW')?></div>
							<div class="faceid-tracker-sidebar-settings-status-number" id="faceid-stats-new-count"><?=$arResult['STATS']['NEW_VISITORS']?></div>
						</div>
						<div class="faceid-tracker-sidebar-settings-status-inner-block">
							<div class="faceid-tracker-sidebar-settings-status-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATS_OLD')?></div>
							<div class="faceid-tracker-sidebar-settings-status-number" id="faceid-stats-old-count"><?=$arResult['STATS']['OLD_VISITORS']?></div>
						</div>
						<div class="faceid-tracker-sidebar-settings-status-inner-container faceid-tracker-sidebar-total">
							<div class="faceid-tracker-sidebar-settings-status-inner-block">
								<div class="faceid-tracker-sidebar-settings-status-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATS_ALL')?></div>
								<div class="faceid-tracker-sidebar-settings-status-number" id="faceid-stats-total-count"><?=$arResult['STATS']['TOTAL_VISITORS']?></div>
							</div>
						</div>
					</div><!--faceid-tracker-sidebar-settings-status-container-->
				</div><!--faceid-tracker-sidebar-settings-status-user-->
				<div class="faceid-tracker-sidebar-settings-status-crm" style="visibility: hidden">
					<div class="faceid-tracker-sidebar-settings-status-crm-title">
						<span class="faceid-tracker-sidebar-title-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATS_CRM')?></span>
					</div>
					<div class="faceid-tracker-sidebar-settings-status-inner-container faceid-tracker-sidebar-total">
						<div class="faceid-tracker-sidebar-settings-status-inner-block">
							<div class="faceid-tracker-sidebar-settings-status-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATS_CRM_SAVE')?>:</div>
							<div class="faceid-tracker-sidebar-settings-status-number" id="faceid-stats-crm-count"><?=$arResult['STATS']['CRM_VISITORS']?></div>
						</div>
					</div>
				</div><!--faceid-tracker-sidebar-settings-status-crm-->
				<div class="faceid-tracker-sidebar-status-link">
					<a href="#" class="faceid-tracker-sidebar-status-link-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_STATS_DETAILED')?></a>
				</div>
			</div><!--faceid-tracker-sidebar-settings-->

		</div>

	</div><!--faceid-tracker-sidebar-->

	<div class="faceid-tracker-main" style="visibility: hidden">

		<div class="faceid-tracker-main-header">
			<div class="faceid-tracker-main-header-user">
				<span class="faceid-tracker-main-header-user-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_HEAD_VISITORS')?></span>
				<span class="faceid-tracker-main-header-user-number" id="faceid-stats-current-count">0</span>
			</div>
			<div class="faceid-tracker-main-header-recognition">
				<span class="faceid-tracker-main-header-recognition-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_HEAD_CREDITS')?> <span class="faceid-tracker-main-header-recognition-total" id="faceid-credits-balance"><?=$arResult['BALANCE']?></span></span>
				<a href="<?=$arResult['BUY_MORE_URL']?>" class="faceid-tracker-main-header-recognition-add"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_HEAD_CREDITS_ADD')?></a>
			</div><!--faceid-tracker-main-header-recognition-->
		</div><!--faceid-tracker-main-header-->

		<div class="faceid-tracker-main-user-container" id="faceid-tracker-main-user-container">

			<div class="faceid-tracker-main-user-start-block faceid-tracker-animate" id="faceid-tracker-main-user-start-block">
				<div class="faceid-tracker-main-user-start-desc">
					<div class="faceid-tracker-main-user-start-desc-item">
						<?=Loc::getMessage('FACEID_TRACKERWD_CMP_FIRST_TIME_VISIT_NEW')?>
					</div>
				</div>
				<div class="faceid-tracker-main-user-start-icon">
					<div class="faceid-tracker-main-user-start-icon-item"></div>
				</div>
			</div><!--faceid-tracker-main-user-start-block-->

			<canvas id="faceid-canvas" style="display: none"></canvas>

			<div class="faceid-tracker-main-user-more" id="faceid-tracker-main-user-more">
				<span class="faceid-tracker-main-user-more-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_LIST_MORE')?></span>
				<span class="faceid-tracker-main-user-more-triangle"></span>
			</div>

		</div><!--faceid-tracker-main-user-container-->

	</div><!--faceid-tracker-main-->

</div><!--<!--faceid-tracker-wrapper-->

<div id="faceid-tracker-profile-search-example" class="faceid-tracker-profile-search" style="display: none">
	<div class="faceid-tracker-profile-search-header">
		<div class="faceid-tracker-profile-search-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_VK_TITLE')?></div>
		<div class="faceid-tracker-profile-search-close">
			<span class="faceid-tracker-header-description-close-item"></span>
		</div>
	</div><!--faceid-tracker-profile-search-header-->
	<div class="faceid-tracker-profile-search-main">

		<div class="faceid-tracker-profile-search-loading faceid-tracker-animate-visible">
			<div class="faceid-tracker-profile-search-loading-block">
				<div class="faceid-tracker-user-loader-item">
					<div class="faceid-tracker-error"></div>
					<div class="faceid-tracker-loader">
						<svg class="faceid-tracker-circular" viewBox="25 25 50 50">
							<circle class="faceid-tracker-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>
						</svg>
					</div>
				</div>
				<div class="faceid-tracker-profile-search-loading-desc"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_VK_PROGRESS')?></div>
			</div>
		</div><!--faceid-tracker-profile-search-loading-->

		<div class="faceid-tracker-profile-search-found-more">
			<div class="faceid-tracker-profile-search-found-more-header">
				<div class="faceid-tracker-profile-search-found-more-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_VK_FOUND_COUNT')?></div>
				<div class="faceid-tracker-profile-search-found-more-count"></div>
			</div>
			<div class="faceid-tracker-profile-search-found-container">

			</div><!--faceid-tracker-profile-search-found-container-->

		</div><!--faceid-tracker-profile-search-found-->

	</div><!--faceid-tracker-profile-search-main-->
</div><!--faceid-tracker-profile-search-->


<? if (!$arResult['HAS_AGREEMENT']): ?>
<div class="tracker-agreement-shadow">
	<div class="tracker-agreement-popup">
		<div class="tracker-agreement-popup-title">
			<div class="tracker-agreement-popup-title-item"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_AGR_TITLE')?></div>
			<div class="tracker-agreement-popup-close">
				<a class="tracker-agreement-popup-close-item" href="/"></a>
			</div>
		</div>
		<?=Loc::getMessage('FACEID_TRACKERWD_CMP_AGR_TEXT')?>
		<div class="tracker-agreement-popup-button">
			<button id="faceid-agreement-accept" class="ui-btn ui-btn-md ui-btn-success"><?=Loc::getMessage('FACEID_TRACKERWD_CMP_AGR_BUTTON')?></button>
		</div>
	</div>
</div>
<form method="post" id="faceid-agreement-accept-form">
    <input type="hidden" name="sign" value="<?=htmlspecialcharsbx($arResult['AGREEMENT_SIGN'])?>">
    <input type="hidden" name="accept" value="1">
</form>

<script type="text/javascript">
	BX.ready(function(){
		if (BX('faceid-agreement-accept'))
		{
			BX.bind(BX('faceid-agreement-accept'), 'click', function(){
				BX.submit(BX('faceid-agreement-accept-form'));
			});
		}
	});
</script>
<? endif ?>