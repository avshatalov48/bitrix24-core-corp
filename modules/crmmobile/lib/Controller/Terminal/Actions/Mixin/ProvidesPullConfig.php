<?php

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\Mixin;

use Bitrix\Crm\Terminal\PullManager;
use Bitrix\Sale\PaySystem;

trait ProvidesPullConfig
{
	public static function getPullConfig(): array
	{
		return [
			'pullConfig' => [
				'list' => [
					'command' => PullManager::COMMAND,
				],
				'payment' => [
					'moduleId' => PaySystem\PullManager::MODULE_ID,
					'command' => PaySystem\PullManager::PAYMENT_COMMAND,
					'events' => [
						'success' => PaySystem\PullManager::SUCCESSFUL_PAYMENT,
						'failure' => PaySystem\PullManager::FAILURE_PAYMENT,
					],
				],
			],
		];
	}
}
