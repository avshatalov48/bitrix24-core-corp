<?php

use Bitrix\Crm\Feature\Entity\FeatureRepository;
use Bitrix\Crm\Tour\TourRepository;
use Bitrix\Main\ModuleManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmFeaturesList extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	protected ?\Bitrix\Main\ErrorCollection $errors;
	private FeatureRepository $featureRepository;
	private TourRepository $tourRepository;

	public function __construct($component = null)
	{
		parent::__construct($component);

		\Bitrix\Main\Loader::requireModule('crm');
		$this->errors = new \Bitrix\Main\ErrorCollection();

		$this->featureRepository = new FeatureRepository();
		$this->tourRepository = new TourRepository();
	}

	public function executeComponent()
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->isCrmAdmin())
		{
			LocalRedirect('/crm/configs/');
		}

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
		elseif ($this->request->get('resetTour'))
		{
			$this->resetTour((string)$this->request->get('resetTour'));
			LocalRedirect('/crm/configs/');
		}

		if (!$this->checkAccess())
		{
			LocalRedirect('/crm/configs/');
		}
		$this->arResult['mode'] = $this->getCurrentMode();

		switch ($this->arResult['mode'])
		{
			case 'features':
				$this->arResult['features'] = $this->prepareFeatures();
				break;
			case 'tours':
				$this->arResult['toursEnabled'] = \Bitrix\Main\Config\Option::get('crm.tour', 'HIDE_ALL_TOURS', 'N') === 'N';
				$this->arResult['tours'] = $this->tourRepository->getAllByCategory();
				break;
		}

		$this->includeComponentTemplate($this->arResult['mode']);
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

	public function enableToursAction()
	{
		if (!$this->checkAccess())
		{
			$this->errors->setError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}

		\Bitrix\Main\Config\Option::set('crm.tour', 'HIDE_ALL_TOURS', 'N');

		return true;
	}

	public function disableToursAction()
	{
		if (!$this->checkAccess())
		{
			$this->errors->setError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}

		\Bitrix\Main\Config\Option::set('crm.tour', 'HIDE_ALL_TOURS', 'Y');

		return true;
	}

	public function resetTourAction(string $tourId)
	{
		if (!$this->checkAccess())
		{
			$this->errors->setError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return null;
		}

		$tour = $this->tourRepository->getById($tourId);
		if ($tour)
		{
			CUserOptions::DeleteOptionsByName($tour['optionCategory'], $tour['optionName']);

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

	private function prepareFeatures(): array
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

	private function resetTour(string $tourId): void
	{
		$tour = $this->tourRepository->getById($tourId);
		if ($tour)
		{
			CUserOptions::DeleteOptionsByName($tour['optionCategory'], $tour['optionName']);
		}
	}

	private function getCurrentMode(): string
	{
		$valueFromRequest = $this->request->get('expertMode');
		if (in_array($valueFromRequest, ['features', 'tours'], true))
		{
			\Bitrix\Main\Config\Option::set('crm', 'expertMode', $valueFromRequest);

			return $valueFromRequest;
		}

		return \Bitrix\Main\Config\Option::get('crm', 'expertMode', 'features');
	}
}
