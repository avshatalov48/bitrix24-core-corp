<?php

namespace Bitrix\SalesCenter\Component;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class PaySystem
{
	private const DEFAULT_ITEM_SELECTED_COLOR = '#56C472';

	/**
	 * Return recommended pay system list
	 *
	 * @param array $paySystemList
	 * @param bool $isMainMode
	 * @param int|null $titleLengthLimit
	 * @param array|null $paySystemColor
	 * @return Result
	 */
	public function getRecommendedItems(
		array $paySystemList,
		bool $isMainMode,
		?int $titleLengthLimit = null,
		?array $paySystemColor = null,
		array $options = [],
	): Result
	{
		$result = new Result();
		if (!Loader::includeModule('sale'))
		{
			return $result->addError(new Main\Error("Module sale don't included"));
		}

		/** @var \Bitrix\Sale\PaySystem\Manager $paySystemManager */
		$paySystemManager = Main\DI\ServiceLocator::getInstance()->get('sale.paysystem.manager');

		$systemHandlerList = $this->getSystemPaySystemHandlersList();
		$paySystemPanel = $this->getPaySystemPanel($paySystemList, $isMainMode);
		$paySystemPath = $this->getPaySystemComponentPath();
		$paySystemIterator = $paySystemManager::getList([
			'select' => ['ID', 'ACTIVE', 'NAME', 'ACTION_FILE', 'PS_MODE'],
			'filter' => [
				'=ACTION_FILE' => array_keys($systemHandlerList),
				'=ENTITY_REGISTRY_TYPE' => 'ORDER',
			],
		]);

		$yandexHandler = \Sale\Handlers\PaySystem\YandexCheckoutHandler::class;
		$yandexHandler = $paySystemManager::getFolderFromClassName($yandexHandler);

		$paySystemActions = [];
		foreach ($paySystemIterator as $paySystem)
		{
			if (!$paySystem['PS_MODE'] && $paySystem['ACTION_FILE'] !== $yandexHandler)
			{
				$paySystem['PS_MODE'] = null;
			}

			$paySystemHandler = $paySystem['ACTION_FILE'];
			$inPanel = array_key_exists($paySystem['ACTION_FILE'], $paySystemPanel);
			$psMode = $paySystem['PS_MODE'];
			$isActive = $paySystem['ACTIVE'] === 'Y';
			if ($psMode !== null)
			{
				$inPanel = in_array($psMode, $paySystemPanel[$paySystem['ACTION_FILE']] ?? [], true);
			}

			if (!$isActive && !$inPanel)
			{
				continue;
			}

			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'ID' => $paySystem['ID'],
				'ACTION_FILE' => $paySystem['ACTION_FILE'],
			];

			if ($psMode !== null && isset($systemHandlerList[$paySystemHandler]['psMode']))
			{
				if (!isset($systemHandlerList[$paySystemHandler]['psMode'][$psMode]))
				{
					continue;
				}

				$queryParams['PS_MODE'] = $psMode;

				if (
					!isset($paySystemActions[$paySystemHandler]['ACTIVE'][$psMode])
					|| $paySystemActions[$paySystemHandler]['ACTIVE'][$psMode] === false
				)
				{
					$paySystemActions[$paySystemHandler]['ACTIVE'][$psMode] = $isActive;
				}
				$paySystemActions[$paySystemHandler]['PS_MODE'] = true;
				$paySystemActions[$paySystemHandler]['HANDLER_NAME'] = $systemHandlerList[$paySystemHandler]['name'];
				$paySystemActions[$paySystemHandler]['ITEMS'][$psMode]['ITEMS'][] = [
					'NAME' => Loc::getMessage('SPP_PAYSYSTEM_SETTINGS', [
						'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystem['NAME']),
					]),
					'LINK' => $paySystemPath->addParams($queryParams)->getLocator(),
				];
			}
			else
			{
				$paySystemPath->addParams($queryParams)->getLocator();

				if (
					!isset($paySystemActions[$paySystemHandler]['ACTIVE'])
					|| $paySystemActions[$paySystemHandler]['ACTIVE'] === false
				)
				{
					$paySystemActions[$paySystemHandler]['ACTIVE'] = $isActive;
				}
				$paySystemActions[$paySystemHandler]['PS_MODE'] = false;
				$paySystemActions[$paySystemHandler]['HANDLER_NAME'] = $systemHandlerList[$paySystemHandler]['name'];
				$paySystemActions[$paySystemHandler]['ITEMS'][] = [
					'NAME' => Loc::getMessage('SPP_PAYSYSTEM_SETTINGS', [
						'#PAYSYSTEM_NAME#' => htmlspecialcharsbx($paySystem['NAME']),
					]),
					'LINK' => $paySystemPath->addParams($queryParams)->getLocator(),
				];
			}
		}

		foreach ($paySystemPanel as $handler => $psModeList)
		{
			$isPaySystemAvailableResult = $this->isPaySystemAvailable($handler);
			$isPaySystemAvailable = $isPaySystemAvailableResult->isSuccess()
				&& $isPaySystemAvailableResult->getData()[0]
			;

			if (!$isPaySystemAvailable)
			{
				continue;
			}

			if ($psModeList)
			{
				foreach ($psModeList as $psMode)
				{
					$isPaySystemAvailableResult = $this->isPaySystemAvailable($handler, $psMode);
					$isPaySystemAvailable = $isPaySystemAvailableResult->isSuccess()
						&& $isPaySystemAvailableResult->getData()[0]
					;

					if (!$isPaySystemAvailable)
					{
						continue;
					}

					if (empty($paySystemActions[$handler]['ITEMS'][$psMode]))
					{
						$paySystemActions[$handler]['PS_MODE'] = true;
						$paySystemActions[$handler]['ACTIVE'][$psMode] = false;
						$paySystemActions[$handler]['ITEMS'][$psMode] = [];
					}
				}
			}
			elseif (empty($paySystemActions[$handler]))
			{
				$paySystemActions[$handler] = [
					'ACTIVE' => false,
					'PS_MODE' => false,
				];
			}
		}

		if ($paySystemActions)
		{
			$paySystemActions = $this->getPaySystemMenu($paySystemActions);
		}

		$paySystemItems = [];
		foreach ($paySystemActions as $handler => $paySystem)
		{
			if ($handler === 'cash' && isset($options['hideCash']) && $options['hideCash'] === 'Y')
			{
				continue;
			}

			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'CREATE' => 'Y',
			];

			$isActive = false;
			$title = $paySystemManager::getHandlerName($handler);
			if (!$title)
			{
				$title = $paySystem['HANDLER_NAME'];
			}

			$handlerTitle = $title;

			$image = $this->getImagePath() . 'marketplace_default.svg';
			$itemSelectedImage = $this->getImagePath() . 'marketplace_default_s.svg';

			$imagePath = $this->getImagePath() . $handler . '.svg';
			$itemSelectedImagePath = $this->getImagePath() . $handler . '_s.svg';
			if (Main\IO\File::isFileExists(Main\Application::getDocumentRoot() . $imagePath))
			{
				$image = $imagePath;
				$itemSelectedImage = $itemSelectedImagePath;
			}

			if (!empty($paySystem['ITEMS']))
			{
				if ($paySystem['PS_MODE'])
				{
					foreach ($paySystem['ITEMS'] as $psMode => $paySystemItem)
					{
						$psModeImage = '';
						$psModeSelectedImage = '';
						$type = $psMode;
						$isActive = $paySystemActions[$handler]['ACTIVE'][$psMode];
						if (
							!$isActive
							&& (
								isset($paySystemPanel[$handler])
								&& !in_array($psMode, $paySystemPanel[$handler], true)
							)
						)
						{
							continue;
						}

						if (
							empty($paySystemItem)
							&& (
								isset($paySystemPanel[$handler])
								&& !in_array($psMode, $paySystemPanel[$handler], true)
							)
						)
						{
							continue;
						}

						$title = $paySystemManager::getHandlerName($handler, $psMode);

						if (!$title)
						{
							$title = $handlerTitle;
						}

						$queryParams['ACTION_FILE'] = $handler;
						$queryParams['PS_MODE'] = $psMode;
						$paySystemPath = $this->getPaySystemComponentPath();
						$paySystemPath->addParams($queryParams);

						$imagePath = $this->getImagePath() . $handler . '_' . $psMode . '.svg';
						$itemSelectedImagePath = $this->getImagePath() . $handler . '_' . $psMode . '_s.svg';
						if (Main\IO\File::isFileExists(Main\Application::getDocumentRoot() . $imagePath))
						{
							$psModeImage = $imagePath;
							$psModeSelectedImage = $itemSelectedImagePath;
						}

						if (is_null($paySystemColor))
						{
							$itemSelectedColor = null;
						}
						else
						{
							$itemSelectedColor = $this->getItemSelectColor($paySystemColor, $handler, $psMode)
								?? self::DEFAULT_ITEM_SELECTED_COLOR
							;
						}

						$paySystemItems[] = [
							'id' => $handler . '_' . $psMode,
							'sort' => $this->getPaySystemSort($paySystemList, $handler, $psMode),
							'title' => is_null($titleLengthLimit)
								? $title
								: $this->getFormattedTitle($title, $titleLengthLimit),
							'image' => !empty($psModeImage) ? $psModeImage : $image,
							'itemSelectedColor' => $itemSelectedColor,
							'itemSelected' => $isActive,
							'itemSelectedImage' => !empty($psModeSelectedImage) ? $psModeSelectedImage : $itemSelectedImage,
							'data' => [
								'type' => 'paysystem',
								'connectPath' => $paySystemPath->getLocator(),
								'menuItems' => $paySystemItem['ITEMS'] ?? $paySystemItem,
								'showMenu' => !empty($paySystemItem),
								'paySystemType' => $type,
								'recommendation' => $this->isPaySystemRecommendation($paySystemList, $handler, $psMode),
							],
						];
					}
				}
				else
				{
					$isActive = $paySystemActions[$handler]['ACTIVE'];

					if (!$isActive && (!array_key_exists($handler, $paySystemPanel)))
					{
						continue;
					}
					$type = $handler;

					$queryParams['ACTION_FILE'] = $handler;
					$paySystemPath = $this->getPaySystemComponentPath();
					$paySystemPath->addParams($queryParams);

					if (is_null($paySystemColor))
					{
						$itemSelectedColor = null;
					}
					else
					{
						$itemSelectedColor = $this->getItemSelectColor($paySystemColor, $handler)
							?? self::DEFAULT_ITEM_SELECTED_COLOR
						;
					}

					$paySystemItems[] = [
						'id' => $handler,
						'sort' => $this->getPaySystemSort($paySystemList, $handler),
						'title' => is_null($titleLengthLimit)
							? $title
							: $this->getFormattedTitle($title, $titleLengthLimit),
						'image' => $image,
						'itemSelectedColor' => $itemSelectedColor,
						'itemSelected' => $isActive,
						'itemSelectedImage' => $itemSelectedImage,
						'data' => [
							'type' => 'paysystem',
							'connectPath' => $paySystemPath->getLocator(),
							'menuItems' => $paySystem['ITEMS'],
							'showMenu' => !empty($paySystem['ITEMS']),
							'paySystemType' => $type,
							'recommendation' => $this->isPaySystemRecommendation($paySystemList, $handler),
						],
					];
				}
			}
			else
			{
				$type = $handler;
				$queryParams['ACTION_FILE'] = $handler;
				$paySystemPath = $this->getPaySystemComponentPath();
				$paySystemPath->addParams($queryParams);

				$paySystemItems[] = [
					'id' => $handler,
					'sort' => $this->getPaySystemSort($paySystemList, $handler),
					'title' => is_null($titleLengthLimit)
						? $title
						: $this->getFormattedTitle($title, $titleLengthLimit),
					'image' => $image,
					'itemSelectedColor' => $this->paySystemColor[$handler] ?? '#56C472',
					'itemSelected' => $isActive,
					'itemSelectedImage' => $itemSelectedImage,
					'data' => [
						'type' => 'paysystem',
						'connectPath' => $paySystemPath->getLocator(),
						'menuItems' => [],
						'showMenu' => false,
						'paySystemType' => $type,
						'recommendation' => $this->isPaySystemRecommendation($paySystemList, $handler),
					],
				];
			}
		}

		Main\Type\Collection::sortByColumn($paySystemItems, ['sort' => SORT_ASC]);

		return $result->setData($paySystemItems);
	}

	private function getPaySystemComponentPath(): Main\Web\Uri
	{
		$paySystemPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem');
		$paySystemPath = getLocalPath('components' . $paySystemPath . '/slider.php');

		return new Main\Web\Uri($paySystemPath);
	}

	private function getPaySystemMenu(array $paySystemActions): array
	{
		$name = Loc::getMessage('SPP_PAYSYSTEM_ADD');

		foreach ($paySystemActions as $handler => $paySystems)
		{
			if (!$paySystems || empty($paySystems['ITEMS']))
			{
				continue;
			}

			$queryParams = [
				'lang' => LANGUAGE_ID,
				'publicSidePanel' => 'Y',
				'CREATE' => 'Y',
				'ACTION_FILE' => mb_strtolower($handler),
			];

			if ($paySystems['PS_MODE'])
			{
				foreach ($paySystems['ITEMS'] as $psMode => $paySystem)
				{
					if (!$paySystem)
					{
						continue;
					}

					$queryParams['PS_MODE'] = $psMode;
					$paySystemPath = $this->getPaySystemComponentPath();
					$paySystemPath->addParams($queryParams);

					$items = $paySystemActions[$handler]['ITEMS'][$psMode]['ITEMS']
						?? $paySystemActions[$handler]['ITEMS'][$psMode]
					;
					array_unshift($items,
						[
							'NAME' => $name,
							'LINK' => $paySystemPath->getLocator(),
						],
						[
							'DELIMITER' => true,
						]
					);
					if (isset($paySystemActions[$handler]['ITEMS'][$psMode]['ITEMS']))
					{
						$paySystemActions[$handler]['ITEMS'][$psMode]['ITEMS'] = $items;
					}
					else
					{
						$paySystemActions[$handler]['ITEMS'][$psMode] = $items;
					}
				}
			}
			else
			{
				$paySystemPath = $this->getPaySystemComponentPath();
				$paySystemPath->addParams($queryParams);

				array_unshift($paySystemActions[$handler]['ITEMS'],
					[
						'NAME' => $name,
						'LINK' => $paySystemPath->getLocator(),
					],
					[
						'DELIMITER' => true,
					]
				);
			}
		}

		return $paySystemActions;
	}

	private function isPaySystemAvailable($handler, $psMode = null): Result
	{
		$result = new Result();
		if (!Loader::includeModule('sale'))
		{
			return $result->addError(new Main\Error('Module sale don\'t included'));
		}
		$paySystemManager = Main\DI\ServiceLocator::getInstance()->get('sale.paysystem.manager');

		$description = $paySystemManager::getHandlerDescription($handler);
		$isAvailable = $description && !(isset($description['IS_AVAILABLE']) && !$description['IS_AVAILABLE']);
		if (!$psMode)
		{
			return $result->setData([$isAvailable]);
		}

		$psModeList = [];
		/** @var \Bitrix\Sale\PaySystem\BaseServiceHandler $handlerClass */
		[$handlerClass] = $paySystemManager::includeHandler($handler);
		if (class_exists($handlerClass))
		{
			$psModeList = $handlerClass::getHandlerModeList();
		}

		return $result->setData([isset($psModeList[$psMode])]);
	}

	private function isPaySystemRecommendation(array $originalPaySystemList, $handler, $psMode = false)
	{
		$isRecommendation = false;
		if ($psMode)
		{
			return $originalPaySystemList[$handler]['psMode'][$psMode]['recommendation'] ?? $isRecommendation;
		}

		return $originalPaySystemList[$handler]['recommendation'] ?? $isRecommendation;
	}

	private function getItemSelectColor(array $paySystemColor, string $handler, ?string $psMode = null): ?string
	{
		if (isset($paySystemColor[$handler]) && is_array($paySystemColor[$handler]))
		{
			if (is_null($psMode))
			{
				return null;
			}

			$itemSelectedColor = $paySystemColor[$handler][$psMode] ?? null;
		}
		else
		{
			$itemSelectedColor = $paySystemColor[$handler] ?? null;
		}

		return $itemSelectedColor;
	}

	/**
	 * @param string $title
	 * @return string
	 */
	private function getFormattedTitle(string $title, int $titleLengthLimit): string
	{
		if (mb_strlen($title) > $titleLengthLimit)
		{
			$title = mb_substr($title, 0, $titleLengthLimit - 3) . '...';
		}

		return $title;
	}

	private function getPaySystemSort(array $paySystemList, $handler, $psMode = false)
	{
		$defaultSort = 10000;
		if ($psMode)
		{
			return $paySystemList[$handler]['psMode'][$psMode]['sort'] ?? $defaultSort;
		}

		return $paySystemList[$handler]['sort'] ?? $defaultSort;
	}

	private function getImagePath(): string
	{
		static $imagePath = '';
		if ($imagePath)
		{
			return $imagePath;
		}

		$componentPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.paysystem.panel');
		$componentPath = getLocalPath('components' . $componentPath);

		$imagePath = $componentPath . '/templates/.default/images/';
		return $imagePath;
	}

	private function getPaySystemPanel(array $paySystemList, bool $isMainMode): array
	{
		$zone = $this->getZone();
		$paySystemPanel = [];
		if ($isMainMode)
		{
			foreach ($paySystemList as $handler => $handlerItem)
			{
				if (!empty($handlerItem['psMode']))
				{
					foreach ($handlerItem['psMode'] as $psMode => $psModeItem)
					{
						if ($psModeItem['main'])
						{
							$paySystemPanel[$handler][] = $psMode;
						}
					}
				}
				elseif (
					$handlerItem['main']
					|| (in_array($zone, ['ru', 'by']) === false && $handler === 'paypal')
				)
				{
					$paySystemPanel[$handler] = [];
				}
			}
		}
		else
		{
			foreach ($paySystemList as $handler => $handlerItem)
			{
				if (!empty($handlerItem['psMode']))
				{
					foreach ($handlerItem['psMode'] as $psMode => $psModeItem)
					{
						$paySystemPanel[$handler][] = $psMode;
					}
				}
				else
				{
					$paySystemPanel[$handler] = [];
				}
			}
		}

		return $paySystemPanel;
	}

	/**
	 * Returns portal's zone
	 *
	 * @return string
	 */
	public function getZone(): string
	{
		static $zone = null;
		if ($zone !== null)
		{
			return $zone;
		}

		if (Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			$zone = \CBitrix24::getPortalZone();
			return $zone;
		}

		$iterator = Main\Localization\LanguageTable::getList([
			'select' => ['ID'],
			'filter' => ['=DEF' => 'Y', '=ACTIVE' => 'Y'],
		]);
		$row = $iterator->fetch();
		$zone = $row['ID'];
		if ($zone !== null)
		{
			return $zone;
		}

		if (defined('LANGUAGE_ID'))
		{
			$row = Main\Localization\LanguageTable::getList([
				'select' => ['ID'],
				'filter' => ['=LID' => LANGUAGE_ID],
			])->fetch();
			$zone = $row['ID'];
		}

		return $zone;
	}

	private function getSystemPaySystemHandlersList(): array
	{
		$paySystemManager = Main\DI\ServiceLocator::getInstance()->get('sale.paysystem.manager');
		$systemHandlerList = [];

		$handlerList = $paySystemManager::getHandlerList();
		if (isset($handlerList['SYSTEM']))
		{
			$systemHandlers = array_keys($handlerList['SYSTEM']);
			foreach ($systemHandlers as $key => $systemHandler)
			{
				if ($systemHandler === 'inner')
				{
					continue;
				}

				$handlerDescription = $paySystemManager::getHandlerDescription($systemHandler);
				if (empty($handlerDescription))
				{
					continue;
				}

				$systemHandlerList[$systemHandler] = [
					'name' => $handlerDescription['NAME'] ?? $handlerList['SYSTEM'][$systemHandler],
				];

				/** @var \Bitrix\Sale\PaySystem\BaseServiceHandler $handlerClass */
				$handlerClass = $paySystemManager::getClassNameFromPath($systemHandler);
				if (!class_exists($handlerClass))
				{
					$documentRoot = Main\Application::getDocumentRoot();
					$path = $paySystemManager::getPathToHandlerFolder($systemHandler);
					$fullPath = $documentRoot . $path . '/handler.php';
					if ($path && Main\IO\File::isFileExists($fullPath))
					{
						require_once $fullPath;
					}
				}

				if (class_exists($handlerClass) && ($psMode = $handlerClass::getHandlerModeList()))
				{
					$systemHandlerList[$systemHandler]['psMode'] = $psMode;
				}
			}
		}

		return $systemHandlerList;
	}
}
