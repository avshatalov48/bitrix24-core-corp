<?php

use Bitrix\Main\ModuleManager;

return [
	'cloud'=> ModuleManager::isModuleInstalled("bitrix24") && COption::GetOptionString('bitrix24', 'network', 'N') == 'Y'
];