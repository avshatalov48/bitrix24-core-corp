<?php

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\Mixin;

use Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider\ActionRepository;

trait ProvidesPsCreationActionProviders
{
	public static function getPsCreationActionProviders(): array
	{
		return [
			'psCreationActionProviders' => [
				'oauth' => ActionRepository::getInstance()->getOauthProviders(),
				'before' => ActionRepository::getInstance()->getBeforeProviders(),
			],
		];
	}
}
