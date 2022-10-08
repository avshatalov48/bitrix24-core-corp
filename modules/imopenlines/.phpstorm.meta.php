<?php
namespace PHPSTORM_META
{
	registerArgumentsSet('bitrix_imopenlines_serviceLocator',
		'ImOpenLines.Config',
		'ImOpenLines.Services.Message',
		'ImOpenLines.Services.ChatDispatcher',
		'ImOpenLines.Services.SessionManager',
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('bitrix_imopenlines_serviceLocator'));

	override(\Bitrix\Main\DI\ServiceLocator::get(0), map([
		'ImOpenLines.Config' => \Bitrix\ImOpenLines\Config::class,
		'ImOpenLines.Services.Message' => \Bitrix\ImOpenLines\Services\Message::class,
		'ImOpenLines.Services.ChatDispatcher' => \Bitrix\ImOpenLines\Services\ChatDispatcher::class,
		'ImOpenLines.Services.SessionManager' => \Bitrix\ImOpenLines\Services\SessionManager::class,
    ]));
}