<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
		<p class="ios-guide-info"><?=\Bitrix\Main\Localization\Loc::getMessage('CAL_IOS_GUIDE_PREVIEW_INFO')?></p>
		<div class="ios-guide-wrapper">
			<div class="ios-guide-header">
			<h1 class="ios-guide-header-title"><?=\Bitrix\Main\Localization\Loc::getMessage('CAL_IOS_GUIDE_INSTALL_PROFILE')?></h1>
		</div>
		<div class="ios-guide-content">
			<div class="ios-guide-section ios-guide-section-inline">
				<div class="ios-guide-section-num">1</div>
				<div class="ios-guide-section-content">
					<p><?=\Bitrix\Main\Localization\Loc::getMessage('CAL_IOS_GUIDE_PROFILE_DOWNLOAD_BITRIX')?></p>
					<a href="<?=htmlspecialcharsbx($arResult['PROFILE_LINK'])?>" class="ios-guide-button"><?=\Bitrix\Main\Localization\Loc::getMessage('CAL_IOS_GUIDE_PROFILE_DOWNLOAD')?></a>
				</div>
			</div>
			<div class="ios-guide-section">
				<div class="ios-guide-section-num">2</div>
				<div class="ios-guide-section-content">
					<p><?=\Bitrix\Main\Localization\Loc::getMessage('CAL_IOS_GUIDE_INSTALL_PROFILE_IN_SETTINGS')?></p>
					<img src="/bitrix/components/bitrix/intranet.calendar.iosguide/templates/.default/images/<?=GetMessage('EC_CALENDAR_IOS_GUIDE_IMAGEFILE')?>" alt="" class="ios-guide-section-image">
				</div>
			</div>
			<div class="ios-guide-section ios-guide-section-final">
				<div class="ios-guide-section-num">3</div>
				<div class="ios-guide-section-content">
					<div class="ios-guide-section-title"><span><?=\Bitrix\Main\Localization\Loc::getMessage('CAL_IOS_GUIDE_IS_SUCCESS')?></span></div>
					<p><?=\Bitrix\Main\Localization\Loc::getMessage('CAL_IOS_GUIDE_IS_INSTALLED_ON_DEVICE')?></p>
					<p style="margin-top: 23px;"><a href="<?php echo \Bitrix\Ui\Util::getArticleUrlByCode(5686207);?>" class="ios-guide-link"><?=\Bitrix\Main\Localization\Loc::getMessage('CAL_IOS_GUIDE_READ_HOW_IT_WORK')?></a></p>
				</div>
			</div>
		</div>
	</div>
