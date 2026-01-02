<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Intranet\Integration;
use Bitrix\Intranet\MainPage;
use Bitrix\Main\Result;
use Bitrix\UI\Form\FormProvider;
use Bitrix\UI\Form\UrlProvider;
use Bitrix\Main\Loader;

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

		$componentClass = \CBitrixComponent::includeComponentClass('bitrix:landing.base');
		if ($componentClass)
		{
			$component = new $componentClass;
			$feedbackParams =
				$component
					? $component->getFeedbackParameters('partner')
					: []
			;
			$feedbackParams['PRESETS']['SOURCE'] = 'MainPageSettings';
			$isUiLoaded = Loader::includeModule('ui');
			$feedbackParams = [
				'id' => $feedbackParams['ID'] ?? 'mainpage_feedback',
				'forms' => $isUiLoaded ? (new FormProvider)->getPartnerFormList() : [],
				'presets' => $feedbackParams['PRESETS'],
				'portalUri' => $isUiLoaded ? (new UrlProvider())->getPartnerPortalUrl() : null,
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
			'canEdit' => (new MainPage\Access)->canEdit(),
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
