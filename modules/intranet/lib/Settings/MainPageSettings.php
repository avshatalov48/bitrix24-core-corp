<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\Integration;
use Bitrix\Intranet\MainPage;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

class MainPageSettings extends AbstractSettings
{
	public const TYPE = 'mainpage';

	public function save(): Result
	{
		return new Result();
	}

	public function get(): SettingsInterface
	{
		$mainPageUrl = new MainPage\Url();
		$integrationManager = new Integration\Landing\MainPage\Manager();
		$publisher = new MainPage\Publisher();
		$isMainpageEnable = Loader::includeModule('bitrix24') && Feature::isFeatureEnabled('main_page');

		$componentClass = \CBitrixComponent::includeComponentClass('bitrix:landing.base');
		if ($componentClass)
		{
			$component = new $componentClass;
			$feedbackParams =
				$component
					? $component->getFeedbackParameters('mainpage')
					: []
			;
			$feedbackParams = [
				'id' => $feedbackParams['ID'] ?? 'mainpage_feedback',
				'forms' => $feedbackParams['FORMS'] ?? [],
				'presets' => $feedbackParams['PRESETS'] ?? [],
				'portalUri' => $feedbackParams['PORTAL_URI'] ?? null,
			];
		}

		$this->data['main-page'] = [
			'urlCreate' => $mainPageUrl->getCreate()->getUri(),
			'urlEdit' => $mainPageUrl->getEdit()->getUri(),
			'urlPublic' => $mainPageUrl->getPublic()->getUri(),
			'urlPartners' => $mainPageUrl->getPublic()->getUri(),
			'urlImport' => $mainPageUrl->getImport()->getUri(),
			'urlExport' => $mainPageUrl->getExport()->getUri(),
			'previewImg' => $integrationManager->getPreviewImg(),
			'isSiteExists' => $integrationManager->isSiteExists(),
			'isPageExists' => $integrationManager->isPageExists(),
			'isPublished' => $publisher->isPublished(),
			'isEnable' => $isMainpageEnable,
			'feedbackParams' => $feedbackParams ?? [],
			'title' => $integrationManager->getTitle(),
		];

		return $this;
	}

	public function find(string $query): array
	{
		return [];
	}
}