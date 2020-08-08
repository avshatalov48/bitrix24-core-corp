<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var StatusUfComponent $component
 */

print $component->selectBoxFromArray(
	$arResult['additionalParameters']['NAME'],
	$arResult['additionalParameters']['VALUE']
);