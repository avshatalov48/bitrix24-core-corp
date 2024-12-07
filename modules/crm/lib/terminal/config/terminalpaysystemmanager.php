<?php

namespace Bitrix\Crm\Terminal\Config;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Internals\PaySystemRestHandlersTable;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Registry;
use Sale\Handlers\PaySystem\YandexCheckoutHandler;
use Bitrix\Seo\Checkout\Service;
use Bitrix\SalesCenter\Integration\SaleManager;

final class TerminalPaysystemManager
{
	private static ?TerminalPaysystemManager $instance = null;

	public static function getInstance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function getConfig(): PaysystemConfig
	{
		return PaysystemConfig::getInstance();
	}

	public function getAvailablePaysystems(): array
	{
		$restHandlerCodes = [];
		$restHandlersIterator = PaySystemRestHandlersTable::getList([
			'select' => ['CODE', 'SETTINGS', 'NAME'],
		]);
		while ($restHandlerData = $restHandlersIterator->fetch())
		{
			$settings = $restHandlerData['SETTINGS'] ?? [];
			if (isset($settings['CHECKOUT_DATA']))
			{
				$restHandlerCodes[] = $restHandlerData['CODE'];
			}
		}

		if (!$restHandlerCodes)
		{
			return [];
		}

		$filter = [
			'@ACTION_FILE' => $restHandlerCodes,
		];
		$sbpPaysystemId = $this->getSbpPaySystem()['ID'] ?? null;
		if ($sbpPaysystemId)
		{
			$filter['!ID'][] = $sbpPaysystemId;
		}

		$sberQrPaysystemId = $this->getSberQrPaySystem()['ID'] ?? null;
		if ($sberQrPaysystemId)
		{
			$filter['!ID'][] = $sberQrPaysystemId;
		}

		$paysystems = self::getPaySystemList($filter);
		$result = [];

		foreach ($paysystems as $paysystem)
		{
			$result[] = [
				'id' => $paysystem['ID'],
				'title' => $paysystem['NAME'],
				'handler' => $paysystem['ACTION_FILE'],
				'path' => $this->getPaySystemPath([
					'ACTION_FILE' => $paysystem['ACTION_FILE'],
					'PS_MODE' => $paysystem['PS_MODE'],
					'ID' => $paysystem['ID'],
				]),
				'isConnected' => $this->isConnected($paysystem),
			];
		}

		return $result;
	}

	private function isConnected(array $paysystem): bool
	{
		if (!$paysystem['ACTION_FILE'] || $paysystem['ACTIVE'] !== 'Y')
		{
			return false;
		}

		[$className] = PaySystem\Manager::includeHandler($paysystem['ACTION_FILE']);

		$reflection = new \ReflectionClass($className);
		$className = $reflection->getName();

		if (
			$this->isOauth($paysystem['ID'])
			&& mb_strtolower($className) === mb_strtolower(\Sale\Handlers\PaySystem\YandexCheckoutHandler::class)
		)
		{
			return $this->hasAuth();
		}

		return true;
	}

	private function isOAuth($id): bool
	{
		$shopId = \Bitrix\Sale\BusinessValue::getMapping(
			'YANDEX_CHECKOUT_SHOP_ID',
			"PAYSYSTEM_".$id,
			null,
			[
				'MATCH' => \Bitrix\Sale\BusinessValue::MATCH_EXACT
			]
		);
		if (empty($shopId))
		{
			$shopId = \Bitrix\Sale\BusinessValue::getMapping(
				'YANDEX_CHECKOUT_SHOP_ID',
				"PAYSYSTEM_".$id,
				null,
				[
					'MATCH' => \Bitrix\Sale\BusinessValue::MATCH_COMMON
				]
			);
		}

		return !(isset($shopId['PROVIDER_VALUE']) && $shopId['PROVIDER_VALUE']);
	}

	private static function getPaySystemList(array $filter): array
	{
		$filter['=ENTITY_REGISTRY_TYPE'] = Registry::REGISTRY_TYPE_ORDER;

		return PaySystem\Manager::getList([
			'filter' => $filter,
			'select' => ['ID', 'NAME', 'ACTION_FILE', 'PS_MODE', 'SORT', 'ACTIVE'],
			'order' => ['ID' => 'DESC'],
		])->fetchAll();
	}

	public function isSbpPaysystemConnected(): bool
	{
		$sbpPaySystem = $this->getSbpPaySystem();

		return
			isset($sbpPaySystem['ID'])
			&& $sbpPaySystem['ACTIVE'] === 'Y'
			&& (
				!$this->isOAuth($sbpPaySystem['ID'])
				|| $this->hasAuth()
			)
		;
	}

	public function isSberQrPaysystemConnected(): bool
	{
		$sberQrPaySystem = $this->getSberQrPaySystem();

		return
			isset($sberQrPaySystem['ID'])
			&& $sberQrPaySystem['ACTIVE'] === 'Y'
			&& (
				!$this->isOAuth($sberQrPaySystem['ID'])
				|| $this->hasAuth()
			)
		;
	}

	private function hasAuth(): bool
	{
		$yandexAdapter = Service::getAuthAdapter(Service::TYPE_YANDEX);
		$yookassaAdapter = Service::getAuthAdapter(Service::TYPE_YOOKASSA);

		return $yandexAdapter->hasAuth() || $yookassaAdapter->hasAuth();
	}

	public function getSbpPaysystemPath(): string
	{
		return $this->getPaySystemPath($this->getSbpPaySystem());
	}

	public function getSberQrPaysystemPath(): string
	{
		return $this->getPaySystemPath($this->getSberQrPaySystem());
	}

	public function getPaysystemPanelPath(): string
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem.panel');
		$paySystemPath = getLocalPath('components'.$paySystemPath.'/slider.php');
		$paySystemPath = new \Bitrix\Main\Web\Uri($paySystemPath);
		$paySystemPath->addParams([
			'type' => 'main',
			'mode' => 'main',
			'hideCash' => 'Y',
		]);

		return $paySystemPath->getLocator();
	}

	public function isAnyPaysystemActive(): bool
	{
		$params = [
			'select' => ['ID'],
			'filter' => [
				'=ACTIVE' => 'Y',
				'!=ACTION_FILE' => [
					'inner',
					'cash',
				],
			]
		];

		return (bool)Sale\Internals\PaySystemActionTable::getRow($params);
	}

	private function getSbpPaySystem(): array
	{
		$actionFile = $this->getYandexCheckoutHandlerCode();

		$paySystem = $this->getPaySystem($actionFile, YandexCheckoutHandler::MODE_SBP);
		if (!$paySystem)
		{
			$paySystem = [
				'ACTION_FILE' => $actionFile,
				'PS_MODE' => YandexCheckoutHandler::MODE_SBP,
			];
		}

		return $paySystem;
	}

	private function getSberQrPaySystem(): array
	{
		$actionFile = $this->getYandexCheckoutHandlerCode();

		$paySystem = $this->getPaySystem($actionFile, YandexCheckoutHandler::MODE_SBERBANK_QR);
		if (!$paySystem)
		{
			$paySystem = [
				'ACTION_FILE' => $actionFile,
				'PS_MODE' => YandexCheckoutHandler::MODE_SBERBANK_QR,
			];
		}

		return $paySystem;
	}

	private function getPaySystemPath(array $queryParams): string
	{
		$paySystemPath = $this->getPaySystemComponentPath();
		$paySystemPath->addParams($queryParams);

		return $paySystemPath->getLocator();
	}

	private function getPaySystemComponentPath(): Main\Web\Uri
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components' . $paySystemPath . '/slider.php');

		return new Main\Web\Uri($paySystemPath);
	}

	private function getPaySystem(string $actionFile, string $psMode): ?array
	{
		$paySystem = Sale\PaySystem\Manager::getList([
			'filter' => [
				'=ACTION_FILE' => $actionFile,
				'=PS_MODE' => $psMode,
				'=ENTITY_REGISTRY_TYPE' => Sale\Registry::REGISTRY_TYPE_ORDER,
			],
			'select' => ['ID','ACTION_FILE', 'PS_MODE', 'ACTIVE'],
			'order' => ['ID' => 'ASC'],
			'limit' => 1,
			'cache' => ['ttl' => 86400],
		])->fetch();

		return $paySystem ?: null;
	}

	private function getYandexCheckoutHandlerCode(): string
	{
		static $result = null;
		if (!is_null($result))
		{
			return $result;
		}

		$result = (string)Sale\PaySystem\Manager::getFolderFromClassName(
			YandexCheckoutHandler::class
		);
		Sale\PaySystem\Manager::includeHandler($result);

		return $result;
	}
}
