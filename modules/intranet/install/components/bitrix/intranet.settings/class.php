<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\Landing\RequisitesLanding;
use Bitrix\Intranet\Settings\CommunicationSettings;
use Bitrix\Intranet\Settings\EmployeeSettings;
use Bitrix\Intranet\Settings\MainPageSettings;
use Bitrix\Intranet\Settings\RequisiteSettings;
use Bitrix\Intranet\Settings\ScheduleSettings;
use Bitrix\Intranet\Settings\SecuritySettings;
use Bitrix\Intranet\Settings\SettingsFactory;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Intranet\Settings\ToolsSettings;
use Bitrix\Intranet\Settings\ConfigurationSettings;
use Bitrix\Intranet\Settings\PortalSettings;
use Bitrix\Intranet\Settings\GdprSettings;
use Bitrix\Intranet\Settings\SettingsPageProviderInterface;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main;
use Bitrix\Intranet;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Intranet\User;
use Bitrix\Intranet\Settings\SettingsPermission;

class SettingsComponent extends CBitrixComponent implements Controllerable, Errorable
{
	const EXTERNAL_LINKS = 'EXTERNAL_LINKS';
	protected ErrorCollection $errorCollection;

	public function configureActions(): array
	{
		return [];
	}

	private function getProviders(): array
	{
		$providerClasses = [
			ToolsSettings::class,
			PortalSettings::class,
			// todo: remove after open Vibe for all
			// MainPageSettings::class,
			CommunicationSettings::class,
			EmployeeSettings::class,
			RequisiteSettings::class,
			ScheduleSettings::class,
			GdprSettings::class,
			SecuritySettings::class,
			ConfigurationSettings::class,
		];

		// todo: remove after open Vibe for all
		if (
			Loader::includeModule('landing')
			&& \Bitrix\Landing\Mainpage\Manager::isAvailable()
		)
		{
			array_splice($providerClasses, 2, 0, MainPageSettings::class);
		}

		$providers = [];
		$sort = 0;
		foreach ($providerClasses as $settingsClass)
		{
			/** @var \Bitrix\Intranet\Settings\AbstractSettings $settingsClass */
			if ($settingsClass::isAvailable())
			{
				$sort += 10;
				$providers[$settingsClass::TYPE] =
					Intranet\Settings\SettingsInternalPageProvider::createFromType(
						$settingsClass::TYPE,
						Loc::getMessage('MENU_ITEM_NAME_' . mb_strtoupper($settingsClass::TYPE)),
						$sort
					)
				;
			}
		}

		/*
		Main\EventManager::getInstance()
			->addEventHandler('intranet', 'onSettingsProvidersCollect', function(Main\Event $event) {
				$event->addResult(new Main\EventResult(Main\EventResult::SUCCESS, ['providers' => ['ai' = new \Bitrix\AI\Settings\IntranetProvider()]]]));
			})
		;*/

		$event = new Main\Event('intranet', 'onSettingsProvidersCollect', ['providers' => $providers]);
		$event->send();

		$sortedProviders = [];
		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() === Main\EventResult::SUCCESS)
			{
				$eventParams = $eventResult->getParameters();

				if (is_array($eventParams) && isset($eventParams['providers']) && is_array($eventParams['providers']))
				{
					foreach ($eventParams['providers'] as $provider)
					{
						if ($provider instanceof SettingsPageProviderInterface && !isset($sortedProviders[$provider->getType()]))
						{
							$sortedProviders[$provider->getType()] = $provider;
						}
					}
				}
			}
		}

		$providers = array_merge($sortedProviders, $providers);
		uasort(
			$providers,
			fn(SettingsPageProviderInterface $providerA, SettingsPageProviderInterface $providerB) => $providerA->getSort() > $providerB->getSort()
		);

		return $providers;
	}

	public function onPrepareComponentParams($arParams): array
	{
		$this->errorCollection = new ErrorCollection();

		$arParams['IS_GDPR_AVAILABLE'] = GdprSettings::isGdprAvailable() ? 'Y' : 'N';

		return $arParams;
	}

	public function getStartPage(array $codesOfPages): string
	{
		return isset($this->arParams['START_PAGE'])
		&& in_array($this->arParams['START_PAGE'], $codesOfPages, true)
			? $this->arParams['START_PAGE']
			: current($codesOfPages);
	}

	public static function hasAccess(): bool
	{
		$permission = static::createPermission();
		return $permission->canEdit() || $permission->canRead();
	}

	public static function createPermission(): SettingsPermission
	{
		return SettingsPermission::initByUser(CurrentUser::get());
	}

	public function executeComponent(): void
	{
		if (!static::hasAccess())
		{
			ShowError(Loc::getMessage('SETTINGS_ACCESS_DENIED'));
			return;
		}
		if ($this->getTemplateName() !== '.main')
		{
			$this->includeComponentTemplate();
			return;
		}

		$providers = $this->getProviders();
		//region collect subPages
		$this->arResult['SUBPAGES'] = [];
		$providers = array_filter(
			$providers,
			function($provider)
			{
				if ($provider instanceof Intranet\Settings\SettingsSubPageProviderInterface)
				{
					if (empty($this->arResult['SUBPAGES'][$provider->getParentType()]))
					{
						$this->arResult['SUBPAGES'][$provider->getParentType()] = [];
					}
					array_push($this->arResult['SUBPAGES'][$provider->getParentType()], ...$provider->getJsExtensions());

					return false;
				}

				return true;
			}
		);
		//endregion

		$codesOfPages = array_keys($providers);
		$this->arResult['START_PAGE'] = $this->getStartPage($codesOfPages);
		$this->arResult['ANALYTIC_CONTEXT'] = htmlspecialcharsbx($_REQUEST['analyticContext'] ?? '');
		$this->arResult['OPTION_TO_MOVE'] = htmlspecialcharsbx($_REQUEST['option'] ?? '');
		$this->arResult['MENU_ITEMS'] = [];
		$this->arResult['PERMISSION'] = static::createPermission()->getPermission();
		$this->arResult['PAGES_PERMISSION'] = [];

		$settingsFactory = $this->createFactory();
		foreach ($providers as $type => $provider)
		{
			$pageSettingsInstance = $settingsFactory->build($type);

			$this->arResult['MENU_ITEMS'][$provider->getType() . '_item'] = [
					'NAME' => $provider->getTitle(),
					'ACTIVE' => $this->arResult['START_PAGE'] === $type,
					'ATTRIBUTES' => [
						'title' => $provider->getTitle(),
						'DATA' => [
							'type' => $type,
						],
					],
					'DISABLED' => !$pageSettingsInstance->getPermission()->canRead(),
					'OPERATIVE' => $pageSettingsInstance->getPermission()->canRead()
				] + ($provider instanceof Intranet\Settings\SettingsExternalPageProviderInterface ?
					[
						'EXTERNAL' => 'Y',
						'EXTENSIONS' => $provider->getJsExtensions()
					] : []
				)
			;

			if ($type === 'tools' && $pageSettingsInstance->getPermission()->canEdit())
			{
				$toolList = ToolsManager::getInstance()->getToolList();
				$this->arResult['MENU_ITEMS'][$type . '_item']['ATTRIBUTES']['bx-disable-expand-by-link'] = 'Y';

				foreach ($toolList as $tool)
				{
					if ($tool->isEnabled() && ($tool->getSettingsPath() || $tool->getLeftMenuPath()))
					{
						$this->arResult['MENU_ITEMS'][$type . '_item']['CHILDREN'][$tool->getId() . '_item'] = [
							'NAME' => $tool->getName(),
							'OPERATIVE' => false,
							'ATTRIBUTES' => [
								'href' => str_replace("#USER_ID#", CurrentUser::get()->GetID(), $tool->getSettingsPath() ?? $tool->getLeftMenuPath()),
								'target' => '_blank',
								'title' => $tool->getName(),
								'DATA' => [
									'id' => $tool->getId(),
								],
							],
						];
					}
				}
			}

			$this->arResult['PAGES_PERMISSION'][$type] = $pageSettingsInstance->getPermission()->getPermission();
		}

		$this->includeComponentTemplate();
	}

	public function createFactory(): SettingsFactory
	{
		$factory = new SettingsFactory();
		$providers = $this->getProviders();

		foreach ($providers as $type => $provider)
		{
			$factory->register($type, $provider);
		}

		return $factory;
	}

	/**
	 * @throws ArgumentException
	 */
	public function setAction(): Result
	{
		$request = Application::getInstance()
			->getContext()
			->getRequest();
		$result = new Result();

		if (!static::createPermission()->canEdit())
		{
			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('SETTINGS_ACCESS_DENIED')));
			return new Result();
		}

		$settingsFactory = $this->createFactory();
		$settingsData = $request->getPostList()->getValues();

		foreach ($settingsData as $type => $data)
		{
			foreach($settingsFactory->buildAll($type) as $settings)
			{
				$errorCollection = $settings->set($data)->validate();
				$this->errorCollection->add($errorCollection->getValues());
			}
		}

		if ($this->errorCollection->count() > 0)
		{
			return $result;
		}

		foreach ($settingsData as $type => $data)
		{
			foreach($settingsFactory->buildAll($type) as $settings)
			{
				$saveResult = $settings->set($data)->save();
				if (!$saveResult->isSuccess())
				{
					$this->errorCollection->add($saveResult->getErrors());
				}
			}
		}

		return $result;
	}

	/**
	 * @throws ArgumentException
	 */
	public function getAction(string $type): array
	{
		$pageSettingsInstance = $this->createFactory();
		$provider = $pageSettingsInstance->build($type);
		if (!$provider->getPermission()->canRead())
		{
//			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('SETTINGS_ACCESS_DENIED')));
			return [];
		}

		return $provider->get()->toArray();
	}

	/**
	 * @throws Main\LoaderException
	 * @throws ArgumentException
	 */
	public function getLandingAction(int $companyId, int $requisiteId, int $bankRequisiteId): array
	{
		$factory = $this->createFactory();
		$pageSettingsInstance = $factory->build(RequisiteSettings::TYPE);
		if (!$pageSettingsInstance->getPermission()->canRead())
		{
//			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('SETTINGS_ACCESS_DENIED')));
			return [];
		}

		Loader::includeModule('crm');

		$requisites = new RequisitesLanding($companyId, $requisiteId, $bankRequisiteId);
		if (!$requisites->isLandingConnected())
		{
			$requisites->connectLanding();
		}
		if ($requisites->isLandingConnected())
		{
			return [
				'is_connected' => $requisites->isLandingConnected(),
				'is_public' => $requisites->isLandingPublic(),
				'public_url' => $requisites->getLandingPublicUrl(),
				'edit_url' => $requisites->getLandingEditUrl(),
			];
		}

		$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('SETTINGS_ERROR_CREATE_LANDING')));

		return [];
	}

	public function analyticAction(array $data): void
	{
		foreach ($data as $item)
		{
			$event = new AnalyticsEvent($item['event'], $item['tool'], $item['category']);
			if (isset($item['type']))
			{
				$event->setType($item['type']);
			}
			if (isset($item['c_element']))
			{
				$event->setElement($item['c_element']);
			}
			if (isset($item['c_section']))
			{
				$event->setSection($item['c_section']);
			}
			if (isset($item['c_sub_section']))
			{
				$event->setSubSection($item['c_sub_section']);
			}
			if (isset($item['p1']))
			{
				$event->setP1($item['p1']);
			}
			if (isset($item['p2']))
			{
				$event->setP2($item['p2']);
			}
			if (isset($item['p3']))
			{
				$event->setP3($item['p3']);
			}

			$event->send();
		}
	}

	/**
	 * @throws ArgumentException
	 */
	public function searchAction(string $query): array
	{
		$result = [];
		$settingsFactory = $this->createFactory();
		foreach ($this->getProviders() as $provider)
		{
			$pageSettingsInstance = $settingsFactory->build($provider->getType());
			if (!$pageSettingsInstance->getPermission()->canRead())
			{
				continue;
			}
			$found = $pageSettingsInstance->find($query);
			if (count($found) > 0)
			{
				$result[] = [
					'title' => $provider->getTitle(),
					'page' => $provider->getType(),
					'options' => $found
				];
			}
		}

		/*Main\EventManager::getInstance()
			->addEventHandler(
				'intranet',
				'onSettingsSearchOutLinks',
				function(Main\Event $event) {
				$event->addResult(new Main\EventResult(
					Main\EventResult::SUCCESS,
					[
						'title' => 'Terminal',
						'options' => [
							[
								'title' => 'Terminal 1',
								'url' => '/'
							],
							[
								'title' => 'Terminal 2',
								'url' => '/company/'
							],
						],
					]
					)
				);
			});*/

		$event = new Main\Event('intranet', 'onSettingsSearchOutLinks', ['query' => $query]);
		$event->send();
		foreach ($event->getResults() as $eventResult)
		{
			$eventParams = $eventResult->getParameters();
			if (
				!isset($eventParams['title'])
				|| !isset($eventParams['options'])
				|| !is_array($eventParams['options'])
				|| $eventResult->getType() !== Main\EventResult::SUCCESS
			)
			{
				continue;
			}

			$result[] = [
				'title' => $eventParams['title'],
				'page' => self::EXTERNAL_LINKS,
				'options' => $eventParams['options']
			];
		}

		return $result;
	}

	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code): \Bitrix\Main\Error
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}
