<?php
return [
	'services' => [
		'value' => [
			'transformer.service.http.controllerResolver' => [
				'constructor' => static function () {
					$feature = \Bitrix\Transformer\Integration\Baas::getDedicatedControllerFeature();

					return new \Bitrix\Transformer\Service\Http\ControllerResolver($feature);
				},
			],

			'transformer.service.command.locker' => [
				'constructor' => static function () {
					return new \Bitrix\Transformer\Service\Command\Locker();
				},
			],

			'transformer.service.integration.analytics.registrar' => [
				'constructor' => static function () {
					$feature = \Bitrix\Transformer\Integration\Baas::getDedicatedControllerFeature();

					$resolver = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('transformer.service.http.controllerResolver');
					if ($resolver->getBaasDedicatedControllerUrl())
					{
						$dedicatedControllerUri = new \Bitrix\Main\Web\Uri($resolver->getBaasDedicatedControllerUrl());
					}
					else
					{
						$dedicatedControllerUri = null;
					}

					return new \Bitrix\Transformer\Service\Integration\Analytics\Registrar(
						$feature,
						$dedicatedControllerUri,
					);
				},
			],
		],
	],
	'controllers' => [
		'value' => [
			'defaultNamespace' => '\\Bitrix\\Transformer\\Controller',
		],
		'readonly' => true,
	],
];
