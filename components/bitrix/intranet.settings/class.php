<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\Landing\RequisitesLanding;
use Bitrix\Intranet\Settings\CommunicationSettings;
use Bitrix\Intranet\Settings\EmployeeSettings;
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

class SettingsComponent extends CBitrixComponent implements Controllerable, Errorable
{
	protected ErrorCollection $errorCollection;

	public function configureActions(): array
	{
		return [];
	}

	private function getProviders(): array
	{
		$providers = [];
		$sort = 0;
		foreach ([
				ToolsSettings::class,
				PortalSettings::class,
				CommunicationSettings::class,
				EmployeeSettings::class,
				RequisiteSettings::class,
				ScheduleSettings::class,
				GdprSettings::class,
				SecuritySettings::class,
				ConfigurationSettings::class,
			] as $settingsClass
		)
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

		$providers =  array_merge($sortedProviders, $providers);
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
		$currentUser = CurrentUser::get();

		return (Loader::includeModule("intranet") && $currentUser->isAdmin())
			|| (Loader::includeModule("bitrix24") && $currentUser->CanDoOperation('bitrix24_config'));
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
		$this->arResult['MENU_ITEMS'] = [];

		foreach ($providers as $type => $provider)
		{
			$this->arResult['MENU_ITEMS'][$provider->getType() . '_item'] = [
					'NAME' => $provider->getTitle(),
					'ACTIVE' => $this->arResult['START_PAGE'] === $type,
					'ATTRIBUTES' => [
						'title' => $provider->getTitle(),
						'DATA' => [
							'type' => $type,
						],
					],
					'OPERATIVE' => true
				] + ($provider instanceof Intranet\Settings\SettingsExternalPageProviderInterface ?
					[
						'EXTERNAL' => 'Y',
						'EXTENSIONS' => $provider->getJsExtensions()
					] : []
				)
			;

			if ($type === 'tools')
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

		if (!static::hasAccess())
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
		if (!static::hasAccess())
		{
			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('SETTINGS_ACCESS_DENIED')));
			return [];
		}

		$factory = $this->createFactory();
		return array_merge(
			...array_map(
				fn($providerSettings) => $providerSettings->get()->toArray(),
				$factory->buildAll($type)
			)
		);
	}

	/**
	 * @throws ArgumentException
	 */
	public function getSomeAction(array $types): array
	{
		if (!static::hasAccess())
		{
			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('SETTINGS_ACCESS_DENIED')));
			return [];
		}

		$settingsResult = [];
		$settingsFactory = $this->createFactory();
		foreach ($types as $type)
		{
			$settingsResult[$type] = $settingsFactory->build($type)->get()->toArray();
		}

		return $settingsResult;
	}

	public function getLandingAction(int $companyId, int $requisiteId, int $bankRequisiteId): array
	{
		if (!static::hasAccess())
		{
			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage('SETTINGS_ACCESS_DENIED')));
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

	public function getErrors(): array
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code): \Bitrix\Main\Error
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}
