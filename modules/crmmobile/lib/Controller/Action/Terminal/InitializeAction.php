<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Action\Terminal;

use Bitrix\Crm\Terminal\PullManager;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider\ActionRepository;
use Bitrix\CrmMobile\Terminal\EntityEditorFieldsProvider;
use Bitrix\Main\Loader;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Sale\PaySystem;

class InitializeAction extends Action
{
	public function run()
	{
		if (Loader::includeModule('pull'))
		{
			PaySystem\PullManager::subscribeOnPayment((int)$this->getCurrentUser()->getId());
		}

		$fieldsProvider = new EntityEditorFieldsProvider();

		return [
			'defaultCountry' => Parser::getDefaultCountry(),
			'currencyId' => \CCrmCurrency::GetBaseCurrencyID(),
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
			'psCreationActionProviders' => [
				'oauth' => ActionRepository::getInstance()->getOauthProviders(),
				'before' => ActionRepository::getInstance()->getBeforeProviders(),
			],
			'createPaymentFields' => [
				$fieldsProvider->getSumField(),
				$fieldsProvider->getPhoneField(),
				$fieldsProvider->getClientName(),
			],
		];
	}
}
