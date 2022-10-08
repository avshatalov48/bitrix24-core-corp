<?php

namespace Bitrix\Crm\Controller\Ads;

use Bitrix\Crm\Ads\Pixel\Configuration\Configurator;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Controller;
use Bitrix\Crm\Integration;
use Bitrix\Crm\WebForm\Internals;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Ads\Pixel\Configuration\Configuration;
use Bitrix\Main\NotSupportedException;
use Bitrix\Bitrix24\Feature;

class Conversion extends Controller
{
	public const FACEBOOK_DEAL = 'facebook.deal';
	public const FACEBOOK_WEBFORM = 'facebook.webform';
	public const FACEBOOK_LEAD = 'facebook.lead';
	public const FACEBOOK_PAYMENT = 'facebook.payment';

	protected function getConfigurationNames(): array
	{
		return [
			static::FACEBOOK_PAYMENT,
			static::FACEBOOK_WEBFORM,
			static::FACEBOOK_DEAL,
			static::FACEBOOK_LEAD
		];
	}


	protected function checkPermissions() : bool
	{
		$isCloud = Loader::includeModule("bitrix24");

		if ($isCloud && !Feature::isFeatureEnabled('crm_ad_conversion'))
		{
			$this->addError(
				new Error("Not accessible.", 100)
			);

			return false;
		}

		if (Integration\Bitrix24\Product::isRegionRussian(true))
		{
			return false;
		}

		$user = CurrentUser::get();
		$isUserAdmin = ($isCloud && \CBitrix24::isPortalAdmin($user->getId())) || $user->isAdmin();

		if (!$isUserAdmin)
		{
			$this->addError(new Error("Access Denied.","permissions"));

			return false;
		}

		return count($this->getErrors()) === 0;
	}

	protected function checkModules(): bool
	{
		foreach (['seo','socialservices','crm'] as $module)
		{
			if (!Loader::includeModule($module))
			{
				$this->addError(new Error(
					"Module $module not installed.",
					"modules",
					[
						'module' => $module
					]
				));
			}
		}

		return count($this->getErrors()) === 0;
	}

	protected function checkServices(): bool
	{
		$locator = ServiceLocator::getInstance();

		return
			$locator->has('seo.business.service') &&
			$locator->has('crm.service.ads.conversion.configurator')
		;
	}

	protected function processBeforeAction(Action $action) : bool
	{
		return parent::processBeforeAction($action) &&
			$this->checkPermissions() &&
			$this->checkModules() &&
			$this->checkServices()
		;
	}

	protected function getConfigurator() : ?Configurator
	{
		$serviceLocator = ServiceLocator::getInstance();
		return $serviceLocator->has('crm.service.ads.conversion.configurator')
			? $serviceLocator->get('crm.service.ads.conversion.configurator')
			: null
		;
	}

	/**
	 * check service auth
	 * @return array
	 */
	public function authAction() : array
	{
		if ($service = ServiceLocator::getInstance()->get('seo.business.service'))
		{
			return [
				'available' => true,
				'auth' => $installed = $service::getAuthAdapter($service::FACEBOOK_TYPE)->hasAuth(),
				'profile' => $installed? $service->getAccount($service::FACEBOOK_TYPE)->getProfile() : null
			];
		}
		return [
			'available' => false,
			'auth' => false,
			'profile' => null
		];
	}

	/**
	 * load configuration by parameter
	 *
	 * @param string $type
	 *
	 * @return array
	 * @throws NotSupportedException
	 */
	public function loadAction(string $type): array
	{
		switch ($type)
		{
			case static::FACEBOOK_PAYMENT:
				return $this->paymentConfigurationAction() + $this->authAction();
			case static::FACEBOOK_LEAD:
				return $this->leadConfigurationAction() + $this->authAction();
			case static::FACEBOOK_DEAL:
				return $this->dealConfigurationAction() + $this->authAction();
			case static::FACEBOOK_WEBFORM:
				return $this->formConfigurationAction() + $this->authAction();
			default:
				throw new NotSupportedException("type is not supported");
		}
	}

	/**
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public function logoutAction()
	{
		($service = ServiceLocator::getInstance()->get('seo.business.service'))
			::getAuthAdapter($service::FACEBOOK_TYPE)->removeAuth();
	}

	/**
	 * save configuration
	 * @param string $code
	 * @param mixed $configuration
	 *
	 * @return array
	 */
	public function saveConfigurationAction(string $code, $configuration = []) : array
	{
		if (in_array($code,$this->getConfigurationNames()) && $configurator = $this->getConfigurator())
		{
			return [
				'success' => $configurator->save($code,new Configuration($configuration))
			];
		}
		return [
			'success' => false
		];
	}

	/**
	 * get payment conversion configuration
	 * @return array
	 */
	public function paymentConfigurationAction() : array
	{
		return [
			'configuration' =>
				($configurator = $this->getConfigurator())?
					$configurator->load(static::FACEBOOK_PAYMENT) ?? new Configuration() :
					null
		];
	}

	/**
	 * get lead conversion configuration
	 * @return array
	 */
	public function leadConfigurationAction() : array
	{
		return [
			'configuration' =>
				($configurator = $this->getConfigurator())?
					$configurator->load(static::FACEBOOK_LEAD) ?? new Configuration() :
					null
		];
	}

	/**
	 * get form conversion configuration
	 * @return array
	 */
	public function formConfigurationAction() : array
	{
		$result = [];
		if ($configurator = $this->getConfigurator())
		{
			$config = $configurator->load(static::FACEBOOK_WEBFORM) ?? new Configuration();
			$formsIds = ($config->has('items') && is_array($forms = $config->get('items'))? $forms : []);
			$formsResult = Internals\FormTable::getDefaultTypeList([
				'filter' => [ '=ACTIVE' => 'Y' ],
				'order' => ['ID' => 'DESC'],
				'cache' => ['ttl' => 36000]
			]);

			while ($form = $formsResult->fetch())
			{
				$result[] = [
					'id' => $form['ID'],
					'name' => $form['NAME'],
					'enable' => in_array($form['ID'],$formsIds)
				];
			}
		}

		return [
			'items' => $result,
			'configuration' => $config ?? null
		];
	}

	/**
	 * get deal conversion configuration
	 * @return array
	 */
	public function dealConfigurationAction() : array
	{
		$result = [];
		if ($configurator = $this->getConfigurator())
		{
			$config = $configurator->load(static::FACEBOOK_DEAL) ?? new Configuration();
			$categories = ($config->has('items') && is_array($categories = $config->get('items'))? $categories : [] );
			foreach (DealCategory::getAll(true) as $category)
			{
				$result[] = [
					'id' => $category['ID'],
					'name' => $category['NAME'],
					'enable' => in_array($category['ID'], $categories)
				];
			}
		}

		return [
			'items' => $result,
			'configuration' => $config ?? null
		];
	}

}