<?php
namespace PHPSTORM_META
{
	registerArgumentsSet('bitrix_transformer_serviceLocator_codes',
		'transformer.service.http.controllerResolver',
		'transformer.service.command.locker',
		'transformer.service.integration.analytics.registrar',
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('bitrix_transformer_serviceLocator_codes'));

	override(\Bitrix\Main\DI\ServiceLocator::get(0), map([
		'transformer.service.http.controllerResolver' => \Bitrix\Transformer\Service\Http\ControllerResolver::class,
		'transformer.service.command.locker' => \Bitrix\Transformer\Service\Command\Locker::class,
		'transformer.service.integration.analytics.registrar' => \Bitrix\Transformer\Service\Integration\Analytics\Registrar::class,
	]));
}
