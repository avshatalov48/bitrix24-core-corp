<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\CrmMobile\Integration\Sale\PaymentSystem\Creation\ActionProvider\ActionRepository;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Sale\PaySystem\Manager;

Loader::includeModule('sale');

class PaySystemMode extends Base
{
	public function initializeOauthParamsAction(): array
	{
		return [
			'oauthData' => ActionRepository::getInstance()->getOauthProviders(),
			'beforeData' => ActionRepository::getInstance()->getBeforeProviders(),
		];
	}

	public function getPaySystemModeListAction(string $handlerName): array
	{
		[$className] = Manager::includeHandler($handlerName);

		if (!class_exists($className))
		{
			return [];
		}

		$modeList = $className::getHandlerModeList();

		/** @var \Bitrix\Sale\PaySystem\Manager $paySystemManager */
		$paySystemManager = ServiceLocator::getInstance()->get('sale.paysystem.manager');
		$configuredModes = $paySystemManager::getList([
			'select' => ['ID', 'PS_MODE', 'ACTIVE'],
			'filter' => [
				'=ACTION_FILE' => $handlerName,
				'=ENTITY_REGISTRY_TYPE' => 'ORDER',
			],
		])->fetchAll();

		$enabledModes = array_filter($configuredModes, static function($mode) {
			return $mode['ACTIVE'] === 'Y';
		});

		// reindex by the 'PS_MODE' column for easier access
		$enabledModes = array_column($enabledModes, null, 'PS_MODE');
		$configuredModes = array_column($configuredModes, null, 'PS_MODE');

		$result = [];
		foreach ($modeList as $modeId => $modeTitle)
		{
			$id = $enabledModes[$modeId]['ID'] ?? 0;
			if (!$id)
			{
				$id = $configuredModes[$modeId]['ID'] ?? 0;
			}

			$result[$modeId] = [
				'modeId' => $modeId,
				'id' => (int)$id,
				'title' => $modeTitle,
				'enabled' => isset($enabledModes[$modeId]),
			];
		}

		return $this->sortModes($handlerName, $result);
	}

	private function sortModes(string $handlerName, array $modes): array
	{
		$sortedModes = $modes;

		if ($handlerName === 'yandexcheckout')
		{
			$order = [
				'sbp',
				'bank_card',
				'sberbank',
				'sberbank_sms',
				'sberbank_qr',
				'yoo_money',
				'alfabank',
				'tinkoff_bank',
				'',
				'qiwi',
				'webmoney',
				'embedded',
				'installments',
				'cash',
			];

			$sortedModes = array_merge(array_flip($order), $sortedModes);
		}

		return array_values($sortedModes);
	}
}