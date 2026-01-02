<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

{ //TODO has a dependency on bizproc, delete after update
	$mobileConstant = \CBPViewHelper::class . '::MOBILE_CONTEXT';
	$hasBizprocConstant = defined($mobileConstant);
}

return [
	'hasBizprocConstant' => $hasBizprocConstant
];
