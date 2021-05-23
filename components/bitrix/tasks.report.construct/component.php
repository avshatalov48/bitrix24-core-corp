<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$requiredModules = array('tasks', 'intranet', 'socialnetwork');

foreach ($requiredModules as $requiredModule)
{
	if (!CModule::IncludeModule($requiredModule))
	{
		ShowError(GetMessage("F_NO_MODULE"));
		return 0;
	}
}

$this->IncludeComponentTemplate();


?>
