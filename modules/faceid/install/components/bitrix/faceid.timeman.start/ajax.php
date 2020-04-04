<?php

/** @var $USER \CUser */
/** @var $APPLICATION \CMain */

define("PUBLIC_AJAX_MODE", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('faceid');

// set enabled flag
\Bitrix\Main\Config\Option::set('faceid', 'user_index_enabled', 1);

// start indexing
\Bitrix\Faceid\ProfilePhotoIndex::bindCustom(0);

// output indexing info
$stepperData = array('faceid' => array('Bitrix\Faceid\ProfilePhotoIndex'));
echo \Bitrix\Main\Update\Stepper::getHtml($stepperData, \Bitrix\Main\Localization\Loc::getMessage("FACEID_TMS_START_INDEX_PHOTOS"));

CMain::FinalActions();
