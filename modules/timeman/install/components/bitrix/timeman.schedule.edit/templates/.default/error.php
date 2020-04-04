<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

foreach ($arResult['errorMessages'] as  $error)
{
	ShowError($error);
}