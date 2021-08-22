<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

?><div class="lenta-notifier" id="lenta_notifier"><?php
	?><span class="lenta-notifier-arrow"></span><?php
	?><span class="lenta-notifier-text"><?php
		?><span id="lenta_notifier_cnt"></span>&nbsp;<span id="lenta_notifier_cnt_title"></span><?php
	?></span><?php
?></div><?php
?><div class="lenta-notifier" id="lenta_notifier_2"><?php
	?><span class="lenta-notifier-text"><?= Loc::getMessage('MOBILE_LOG_RELOAD_NEEDED2') ?></span><?php
?></div><?php
?><div class="lenta-notifier" id="lenta_refresh_error"><?php
	?><span class="lenta-notifier-text"><?= Loc::getMessage('MOBILE_LOG_RELOAD_ERROR') ?></span><?php
?></div><?php
?><div class="lenta-notifier" id="lenta_nextpage_error"><?php
	?><span class="lenta-notifier-text"><?= Loc::getMessage('MOBILE_LOG_NEXTPAGE_ERROR') ?></span><?php
?></div><?php

