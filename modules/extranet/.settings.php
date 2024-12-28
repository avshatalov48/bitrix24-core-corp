<?php

return [
	'services' => [
		'value' => [
			'extranet.service.collaber' => [
				'className' => \Bitrix\Extranet\Service\CollaberService::class,
			],
			'extranet.service.user' => [
				'className' => \Bitrix\Extranet\Service\UserService::class,
			],
			'extranet.repository.user' => [
				'className' => \Bitrix\Extranet\Repository\ExtranetUserRepository::class,
			],
		],
	]
];