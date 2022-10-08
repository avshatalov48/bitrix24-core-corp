<?php
namespace PHPSTORM_META
{
	registerArgumentsSet('bitrix_documentgenerator_serviceLocator_codes',
		'documentgenerator.integration.intranet.binding.codeBuilder',
		'documentgenerator.service.actualizeQueue',
	);

	expectedArguments(\Bitrix\Main\DI\ServiceLocator::get(), 0, argumentsSet('bitrix_documentgenerator_serviceLocator_codes'));

	override(\Bitrix\Main\DI\ServiceLocator::get(0), map([
		'documentgenerator.integration.intranet.binding.codeBuilder' => \Bitrix\DocumentGenerator\Integration\Intranet\Binding\CodeBuilder::class,
		'documentgenerator.service.actualizeQueue' => \Bitrix\DocumentGenerator\Service\ActualizeQueue::class,
	]));
}
