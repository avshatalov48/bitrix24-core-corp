<?php
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */

switch(LANGUAGE_ID)
{
	case 'en':
	case 'ru':
	case 'ua':
	case 'de':
		$langId = LANGUAGE_ID;
		break;
	default:
		$langId = Loc::getDefaultLang(LANGUAGE_ID);
		break;
}
?>
<div class="bx-text-block bx-disk-text-block">
	<h3 class="bx-text-title"><?= Loc::getMessage('DISK_HELP_0') ?></h3>

	<p class="bx-text-paragraph"><?= Loc::getMessage('DISK_HELP_1') ?></p>
</div>
<div class="bx-text-block bx-disk-text-block">
	<a class="bx-text-link" onclick="BX.Disk.Help.onClickForScroll(event, BX('files_management'))" href="#"><?= Loc::getMessage('DISK_HELP_2') ?></a> <br/>
	<a class="bx-text-link" onclick="BX.Disk.Help.onClickForScroll(event, BX('create_docs'))" href="#"><?= Loc::getMessage('DISK_HELP_3') ?></a>
	<br/>
	<a class="bx-text-link" onclick="BX.Disk.Help.onClickForScroll(event, BX('what_is_it'))" href="#"><?= Loc::getMessage('DISK_HELP_4') ?></a> <br/>
	<a class="bx-text-link" onclick="BX.Disk.Help.onClickForScroll(event, BX('common_docs'))" href="#"><?= Loc::getMessage('DISK_HELP_5') ?></a> <br/>
	<a class="bx-text-link" onclick="BX.Disk.Help.onClickForScroll(event, BX('docs_events'))" href="#docs_events"><?= Loc::getMessage('DISK_HELP_6') ?></a> <br/>
</div>
<div class="bx-text-block bx-disk-text-block" id="files_management">
	<h3 class="bx-text-title"><?= Loc::getMessage('DISK_HELP_7') ?></h3>

	<p class="bx-text-paragraph"><?= Loc::getMessage('DISK_HELP_8') ?></p>
</div>
<img class="disk-info-page-img" src="/bitrix/components/bitrix/disk.help/templates/.default/images/disk-img-<?= $langId ?>.png" alt="img"/>

<div class="bx-text-block bx-disk-text-block" id="create_docs">
	<h3 class="bx-text-title"><?= Loc::getMessage('DISK_HELP_9') ?></h3>

	<p class="bx-text-paragraph"><?= Loc::getMessage('DISK_HELP_10') ?></p>
</div>
<img class="disk-info-page-img" src="/bitrix/components/bitrix/disk.help/templates/.default/images/create-file-img-<?= $langId ?>.png" alt="img"/>

<div class="bx-text-block bx-disk-text-block" id="what_is_it">
	<h3 class="bx-text-title"><?= Loc::getMessage('DISK_HELP_11') ?></h3>

	<p class="bx-text-paragraph"><?= Loc::getMessage('DISK_HELP_12') ?></p>
</div>
<img class="disk-info-page-img" src="/bitrix/components/bitrix/disk.help/templates/.default/images/desktop-img-<?= $langId ?>.png" alt="img"/>


<div class="bx-text-block bx-disk-text-block" id="common_docs">
	<h3 class="bx-text-title"><?= Loc::getMessage('DISK_HELP_13') ?></h3>

	<p class="bx-text-paragraph"><?= Loc::getMessage('DISK_HELP_14') ?></p>
</div>
<div class="bx-text-block bx-disk-text-block">
	<h3 class="bx-text-title"><?= Loc::getMessage('DISK_HELP_15') ?></h3>

	<p class="bx-text-paragraph"><?= Loc::getMessage('DISK_HELP_16') ?></p>
</div>
<img class="disk-info-page-img" src="/bitrix/components/bitrix/disk.help/templates/.default/images/file-add-popup-<?= $langId ?>.png" alt="img"/>

<div class="bx-text-block bx-disk-text-block" id="docs_events">
	<h3 class="bx-text-title"><?= Loc::getMessage('DISK_HELP_17') ?></h3>

	<p class="bx-text-paragraph"><?= Loc::getMessage('DISK_HELP_18') ?></p>
</div>
<img class="disk-info-page-img" src="/bitrix/components/bitrix/disk.help/templates/.default/images/file-page-<?= $langId ?>.png" alt="img"/>

<div class="bx-text-block bx-disk-text-block">
	<p class="bx-text-paragraph"><?= Loc::getMessage('DISK_HELP_19') ?></p>
</div>
