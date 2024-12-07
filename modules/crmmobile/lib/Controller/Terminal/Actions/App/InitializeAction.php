<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Terminal\Actions\App;

use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Mixin\ProvidesPsCreationActionProviders;
use Bitrix\CrmMobile\Controller\Terminal\Actions\Mixin\ProvidesPullConfig;
use Bitrix\CrmMobile\Integration\Sale\Payment\EntityEditorFieldsProvider;
use Bitrix\Main\Loader;
use Bitrix\Main\PhoneNumber\Parser;
use Bitrix\Sale\PaySystem;
use Bitrix\SalesCenter\Integration\LandingManager;

class InitializeAction extends Action
{
	use ProvidesPsCreationActionProviders;
	use ProvidesPullConfig;

	final public function run(): array
	{
		if (Loader::includeModule('pull'))
		{
			PaySystem\PullManager::subscribeOnPayment((int)$this->getCurrentUser()->getId());
		}

		$fieldsProvider = new EntityEditorFieldsProvider();

		if (Loader::includeModule('salescenter'))
		{
			$connectedSiteId = LandingManager::getInstance()->getConnectedSiteId();
			$isPhoneConfirmed = LandingManager::getInstance()->isPhoneConfirmed();
		}

		return array_merge(
			[
				'defaultCountry' => Parser::getDefaultCountry(),
				'currencyId' => \CCrmCurrency::GetBaseCurrencyID(),
				'createPaymentFields' => [
					$fieldsProvider->getSumField(),
					$fieldsProvider->getPhoneField(),
					$fieldsProvider->getClientName(),
				],
			],
			[
				'isPhoneConfirmed' => $isPhoneConfirmed ?? true,
				'connectedSiteId' => $connectedSiteId ?? 0,
			],
			self::getPsCreationActionProviders(),
			self::getPullConfig(),
		);
	}
}
