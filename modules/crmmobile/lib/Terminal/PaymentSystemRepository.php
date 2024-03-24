<?php

namespace Bitrix\CrmMobile\Terminal;

use Bitrix\Sale\Payment;
use Bitrix\Crm\Terminal\Config\TerminalPaysystemManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type;
use Bitrix\Sale\Internals\PaySystemRestHandlersTable;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Registry;
use Sale\Handlers\PaySystem\YandexCheckoutHandler;

class PaymentSystemRepository
{
	private const RU_ZONE = 'ru';

	public static function getByPayment(Payment $payment): array
	{
		return self::preparePaySystemList(
			self::getAvailablePaySystemList($payment)
		);
	}

	private static function getPaysystemManager(): TerminalPaysystemManager
	{
		return TerminalPaysystemManager::getInstance();
	}

	private static function preparePaySystemList(array $paySystemList): array
	{
		$result = [];

		$requiredPaySystemList = self::getRequiredPaySystemList();

		foreach ($paySystemList as $paySystemItem)
		{
			$result[] = [
				'handler' => (string)$paySystemItem['ACTION_FILE'],
				'type' => (string)$paySystemItem['PS_MODE'],
				'connected' => true,
				'id' => (int)$paySystemItem['ID'],
				'title' => $paySystemItem['NAME'],
				'sort' => (int)$paySystemItem['SORT'],
			];
		}

		foreach ($requiredPaySystemList as $requiredPaySystemItem)
		{
			$isFound = false;

			foreach ($result as $paySystem)
			{
				if (
					$requiredPaySystemItem['handler'] === $paySystem['handler']
					&& $requiredPaySystemItem['type'] === $paySystem['type']
				)
				{
					$isFound = true;
					break;
				}
			}

			if (!$isFound)
			{
				$result[] = $requiredPaySystemItem;
			}
		}

		$yandexCheckoutCode = self::getYandexCheckoutHandlerCode();
		foreach ($result as $index => $resultItem)
		{
			if ($resultItem['handler'] === $yandexCheckoutCode)
			{
				if ($resultItem['type'] === YandexCheckoutHandler::MODE_SBP)
				{
					$result[$index]['sort'] = -20;
				}
				elseif ($resultItem['type'] === YandexCheckoutHandler::MODE_SBERBANK_QR)
				{
					$result[$index]['sort'] = -10;
				}

				$result[$index]['sort'] = (int)$result[$index]['sort'];
			}
		}

		usort($result, static function ($item1, $item2) {
			return $item1['sort'] <=> $item2['sort'];
		});

		return $result;
	}

	private static function getAvailablePaySystemList(Payment $payment): array
	{
		$paySystemList = self::getRestPaySystemList($payment);
		if (self::isRuZone())
		{
			$localPaysystemList = self::getLocalPaySystemList();
			$localPaysytemIds = array_column($localPaysystemList, 'ID');
			$paySystemList = array_filter(
				$paySystemList,
				static fn ($paysystem) => !in_array($paysystem['ID'], $localPaysytemIds, true)
			);
			$config = TerminalPaysystemManager::getInstance()->getConfig();
			foreach ($localPaysystemList as $localPaysystemKey => $localPaysystem)
			{
				if ($localPaysystem['PS_MODE'] === YandexCheckoutHandler::MODE_SBP && !$config->isSbpEnabled())
				{
					unset($localPaysystemList[$localPaysystemKey]);
				}
				else if ($localPaysystem['PS_MODE'] === YandexCheckoutHandler::MODE_SBERBANK_QR && !$config->isSberQrEnabled())
				{
					unset($localPaysystemList[$localPaysystemKey]);
				}
			}
			$paySystemList = [...$localPaysystemList, ...$paySystemList];
		}

		$disabledPaysystems = self::getPaysystemManager()->getConfig()->getTerminalDisabledPaysystems();

		return array_filter(
			$paySystemList,
			static fn ($paySystem) => !in_array((int)$paySystem['ID'], $disabledPaysystems, true)
		);
	}

	private static function getLocalPaySystemList(): array
	{
		$yandexCheckoutHandler = self::getYandexCheckoutHandlerCode();
		$psModes = [
			YandexCheckoutHandler::MODE_SBP,
			YandexCheckoutHandler::MODE_SBERBANK_QR,
		];

		$paySystemList = [];
		foreach ($psModes as $psMode)
		{
			$paySystem = PaySystem\Manager::getList([
				'filter' => [
					'=ACTION_FILE' => $yandexCheckoutHandler,
					'@PS_MODE' => $psMode,
					'=ENTITY_REGISTRY_TYPE' => Registry::REGISTRY_TYPE_ORDER,
				],
				'select' => ['ID', 'NAME', 'ACTION_FILE', 'PS_MODE', 'SORT'],
				'order' => ['ID' => 'ASC'],
				'cache' => ['ttl' => 86400],
				'limit' => 1,
			])->fetch();
			if ($paySystem)
			{
				$paySystemList[] = $paySystem;
			}
		}

		Type\Collection::sortByColumn($paySystemList, ['ID' => SORT_DESC]);

		$result = [];
		// use only uniq payment systems
		foreach ($paySystemList as $paySystemData)
		{
			$key = $paySystemData['ACTION_FILE'] . $paySystemData['PS_MODE'];
			if (!isset($result[$key]))
			{
				$result[$key] = $paySystemData;
			}
		}

		return array_values($result);
	}

	private static function getRestPaySystemList(Payment $payment): array
	{
		$restCheckoutHandlerCodes = [];

		$paySystemRestHandlersIterator = PaySystemRestHandlersTable::getList([
			'select' => ['CODE', 'SETTINGS'],
		]);
		while ($paySystemRestHandlersData = $paySystemRestHandlersIterator->fetch())
		{
			$settings = $paySystemRestHandlersData['SETTINGS'] ?? [];
			if (isset($settings['CHECKOUT_DATA']))
			{
				$restCheckoutHandlerCodes[] = $paySystemRestHandlersData['CODE'];
			}
		}

		if (!$restCheckoutHandlerCodes)
		{
			return [];
		}

		$result = [];

		$filter = [
			'@ACTION_FILE' => $restCheckoutHandlerCodes,
		];

		$paySystemList = self::getPaySystemList($filter);
		foreach ($paySystemList as $paySystemData)
		{
			$service = new PaySystem\Service($paySystemData);
			$paySystem = new PaySystem\RestHandler('', $service);
			if ($paySystem->canCheckout($payment))
			{
				$result[] = $paySystemData;
			}
		}

		return $result;
	}

	private static function getPaySystemList(array $filter): array
	{
		$filter['=ENTITY_REGISTRY_TYPE'] = Registry::REGISTRY_TYPE_ORDER;

		return PaySystem\Manager::getList([
			'filter' => $filter,
			'select' => ['ID', 'NAME', 'ACTION_FILE', 'PS_MODE', 'SORT'],
			'order' => ['ID' => 'DESC'],
			'cache' => ['ttl' => 86400],
		])->fetchAll();
	}

	private static function isRuZone(): bool
	{
		$result = Option::get('crm', 'terminal_is_ru_zone', null);
		if ($result === 'Y')
		{
			return true;
		}
		elseif ($result === 'N')
		{
			return false;
		}

		$zone = null;
		if (ModuleManager::isModuleInstalled('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
		}
		elseif (Loader::includeModule('intranet'))
		{
			$zone = \CIntranetUtils::getPortalZone();
		}

		return $zone === self::RU_ZONE;
	}

	private static function getRequiredPaySystemList(): array
	{
		$result = [];
		if (self::isRuZone())
		{
			$config = TerminalPaysystemManager::getInstance()->getConfig();
			if ($config->isSbpEnabled())
			{
				$result[] = [
					'handler' => self::getYandexCheckoutHandlerCode(),
					'type' => YandexCheckoutHandler::MODE_SBP,
					'connected' => false,
					'id' => 0,
					'title' => null,
				];
			}

			if ($config->isSberQrEnabled())
			{
				$result[] = [
					'handler' => self::getYandexCheckoutHandlerCode(),
					'type' => YandexCheckoutHandler::MODE_SBERBANK_QR,
					'connected' => false,
					'id' => 0,
					'title' => null,
				];
			}
		}

		return $result;
	}

	private static function getYandexCheckoutHandlerCode(): string
	{
		static $result = null;
		if (!is_null($result))
		{
			return $result;
		}

		$result = (string)PaySystem\Manager::getFolderFromClassName(YandexCheckoutHandler::class);
		PaySystem\Manager::includeHandler($result);

		return $result;
	}
}
