<?php
namespace PHPSTORM_META
{
	registerArgumentsSet('bitrix_transformer_serviceLocator_codes',
		'transformer.http.controllerResolver',
		'transformer.integration.analytics.registrar',
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('bitrix_transformer_serviceLocator_codes'));

	override(\Bitrix\Main\DI\ServiceLocator::get(0), map([
		'transformer.http.controllerResolver' => \Bitrix\Transformer\Http\ControllerResolver::class,
		'transformer.integration.analytics.registrar' => \Bitrix\Transformer\Integration\Analytics\Registrar::class,
	]));
}
