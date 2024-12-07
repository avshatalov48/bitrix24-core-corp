<?php
return [
	'services' => [
		'value' => [
			'transformer.http.controllerResolver' => [
				'constructor' => static function () {
					$feature = \Bitrix\Transformer\Integration\Baas::getDedicatedControllerFeature();

					return new \Bitrix\Transformer\Http\ControllerResolver($feature);
				},
			],

			'transformer.integration.analytics.registrar' => [
				'constructor' => static function () {
					$feature = \Bitrix\Transformer\Integration\Baas::getDedicatedControllerFeature();

					$resolver = \Bitrix\Main\DI\ServiceLocator::getInstance()->get('transformer.http.controllerResolver');
					if ($resolver->getBaasDedicatedControllerUrl())
					{
						$dedicatedControllerUri = new \Bitrix\Main\Web\Uri($resolver->getBaasDedicatedControllerUrl());
					}
					else
					{
						$dedicatedControllerUri = null;
					}

					return new \Bitrix\Transformer\Integration\Analytics\Registrar(
						$feature,
						$dedicatedControllerUri,
					);
				},
			],
		],
	],
];
