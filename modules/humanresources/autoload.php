<?php

if (file_exists(__DIR__ . '/dev/Env/Autoload.php'))
{
	require_once __DIR__ . '/dev/Env/Autoload.php';
}

\CModule::AddAutoloadClasses('humanresources', [
	\Bitrix\HumanResources\Controller\HcmLink\Placement::class => 'lib/Controller/HcmLink/Placement.php',
]);
