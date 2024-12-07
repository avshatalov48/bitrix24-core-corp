<?php

use Bitrix\Crm\Feature\Entity\FeatureRepository;
use Bitrix\Main\ModuleManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmFeaturesList extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	protected ?\Bitrix\Main\ErrorCollection $errors;
	private FeatureRepository $featureRepository;

	public function __construct($component = null)
	{
		parent::__construct($component);

		\Bitrix\Main\Loader::requireModule('crm');
		$this->featureRepository = new FeatureRepository();
	}

	public function executeComponent()
	{
		if ($this->request->get('enableFeature'))
		{
			$this->enableFeature((string)$this->request->get('enableFeature'));
			LocalRedirect('/crm/configs/');
		}
		elseif ($this->request->get('disableFeature'))
		{
			$this->disableFeature((string)$this->request->get('disableFeature'));
			LocalRedirect('/crm/configs/');
		}

		if (!$this->checkAccess())
		{
			LocalRedirect('/crm/configs/');
		}

		$this->arResult['items'] = $this->prepareItems();

		$this->includeComponentTemplate();
	}

	public function enableFeatureAction(string $featureId)
	{
		if (!$this->checkAccess())
		{
			$this->errors->setError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}
		$feature = $this->featureRepository->getById($featureId);
		if ($feature)
		{
			$feature->enable();

			return true;
		}
		else
		{
			$this->errors->setError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());

			return null;
		}
	}

	public function disableFeatureAction(string $featureId)
	{
		if (!$this->checkAccess())
		{
			$this->errors->setError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}
		$feature = $this->featureRepository->getById($featureId);
		if ($feature)
		{
			$feature->disable();

			return true;
		}
		else
		{
			$this->errors->setError(\Bitrix\Crm\Controller\ErrorCode::getNotFoundError());

			return null;
		}
	}

	public function configureActions()
	{
	}

	private function prepareItems(): array
	{
		return $this->featureRepository->getAllByCategory();
	}

	protected function checkAccess(): bool
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->isCrmAdmin())
		{
			return false;
		}
		if (\Bitrix\Crm\Settings\Crm::isEtalon() || ModuleManager::isModuleInstalled('bxtest') || ModuleManager::isModuleInstalled('updateserverlight'))
		{
			return true;
		}

		return false;
	}

	private function enableFeature(string $featureId): void
	{
		$feature = $this->featureRepository->getById($featureId);
		if ($feature && $feature->allowSwitchBySecretLink())
		{
			$feature->enable();
		}
	}

	private function disableFeature(string $featureId): void
	{
		$feature = $this->featureRepository->getById($featureId);
		if ($feature && $feature->allowSwitchBySecretLink())
		{
			$feature->disable();
		}
	}
}
