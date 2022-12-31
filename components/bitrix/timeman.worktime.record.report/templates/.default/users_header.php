<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

?>
<div class="timeman-report-users">
	<div class="timeman-report-user">
		<span class="timeman-report-user-label"><?= htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_FROM')); ?></span>
		<a href="<?= $arResult['USER_PROFILE_PATH']; ?>" class="timeman-report-user-avatar" <?= $arResult['USER_PHOTO_PATH'] ?
			' style="background: url(\'' . Uri::urnEncode($arResult['USER_PHOTO_PATH']) . '\') ' .
			'no-repeat scroll center center transparent; background-size: cover;"' : '' ?>
		></a>
		<div class="timeman-report-user-info">
			<a href="<?= $arResult['USER_PROFILE_PATH']; ?>" class="timeman-report-user-name"><?=
				htmlspecialcharsbx($arResult['USER_FORMATTED_NAME'])
				?></a>
			<span class="timeman-report-user-position"><?=
				htmlspecialcharsbx($arResult['USER_WORK_POSITION'])
				?></span>
		</div>
	</div>
	<div class="timeman-report-user">
		<span class="timeman-report-user-label"><?= htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_TO')); ?></span>
		<a href="<?= $arResult['MANAGER_PROFILE_PATH']; ?>" class="timeman-report-user-avatar" <?= $arResult['MANAGER_PHOTO_PATH'] ?
			' style="background: url(\'' . Uri::urnEncode($arResult['MANAGER_PHOTO_PATH']) . '\') ' .
			'no-repeat scroll center center transparent; background-size: cover;"' : '' ?>></a>
		<div class="timeman-report-user-info">
			<a href="<?= $arResult['MANAGER_PROFILE_PATH']; ?>" class="timeman-report-user-name"><?=
				htmlspecialcharsbx($arResult['MANAGER_FORMATTED_NAME'])
				?></a>
			<span class="timeman-report-user-position"><?=
				htmlspecialcharsbx($arResult['MANAGER_WORK_POSITION'])
				?></span>
		</div>
	</div>
</div>