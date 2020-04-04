<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!CModule::IncludeModule("voximplant"))
	return false;

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant/controller_hit.php");

