<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

?><div class="lenta-notifier" id="lenta_notifier" onclick="__MSLRefresh(true); return false;"><?
	?><span class="lenta-notifier-arrow"></span><?
	?><span class="lenta-notifier-text"><?
		?><span id="lenta_notifier_cnt"></span>&nbsp;<span id="lenta_notifier_cnt_title"></span><?
	?></span><?
?></div><?
?><div class="lenta-notifier" id="lenta_notifier_2" onclick="app.exec('pullDownLoadingStart'); __MSLRefresh(true); return false;"><?
	?><span class="lenta-notifier-text"><?=Loc::getMessage("MOBILE_LOG_RELOAD_NEEDED")?></span><?
?></div><?
?><div class="lenta-notifier" id="lenta_refresh_error" onclick="__MSLRefreshError(false);"><?
	?><span class="lenta-notifier-text"><?=Loc::getMessage("MOBILE_LOG_RELOAD_ERROR")?></span><?
?></div>
