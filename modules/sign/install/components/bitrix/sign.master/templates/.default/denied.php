<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var SignMasterComponent $component */

$steps = include 'steps_map.php';

$steIdFromRequest = $component->getRequest($arParams['VAR_STEP_ID']);
$stepId = $steIdFromRequest ?: 'loadFile';
$currentStep =& $steps[$stepId];

if ($currentStep['code'] === 'final')
{
	include 'steps/' . $currentStep['content'];
}
else
{
	$component->includeError('ui.error');
}
