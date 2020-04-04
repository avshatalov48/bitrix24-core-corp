<?

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Update\Stepper;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true){die();};

echo '<div id="disk-folder-list-place-for-stepper">' . \Bitrix\Disk\Ui\Stepper::getHtml() . '</div>';
