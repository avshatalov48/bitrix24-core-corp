<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Access\Component\PermissionConfig;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Main\Loader::requireModule('biconnector');

class ApacheSupersetConfigPermissionsComponent
	extends CBitrixComponent
	implements Main\Errorable
{
	use Main\ErrorableImplementation;

	/**
	 * @inheritDoc
	 */
	public function executeComponent()
	{
		global $APPLICATION;

		/**
		 * @var \CMain $APPLICATION
		 */

		$this->errorCollection = new Main\ErrorCollection();

		$analyticEvent = new AnalyticsEvent('open_editor', 'BI_Builder', 'roles');
		$analyticEvent->setSection('BI_Builder')->setElement('menu');

		if (!$this->checkAccessPermissions())
		{
			$analyticEvent->setStatus('blocked')->send();
			$this->errorCollection->setError(new Error(Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_WRONG_PERMISSION')));
			$this->printErrors();

			return;
		}

		$isSetTitle = ($this->arParams['SET_TITLE'] ?? 'Y') === 'Y';
		if ($isSetTitle)
		{
			$APPLICATION->SetTitle(
				Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_ROLE_EDIT_COMP_ACCESS_RIGHTS')
			);
		}

		$analyticEvent->setStatus('success')->send();

		$this->initResult();
		$this->includeComponentTemplate();
	}

	/**
	 * @return void
	 */
	private function initResult(): void
	{
		$this->arResult['ERRORS'] = [];
		$this->arResult['ACTION_URI'] = $this->getPath() . '/ajax.php';
		$this->arResult['NAME'] = Loc::getMessage('BICONNECTOR_APACHESUPERSET_CONFIG_PERMISSIONS_ROLE_EDIT_COMP_TEMPLATE_NAME');

		$configPermissions = new PermissionConfig();

		$this->arResult['USER_GROUPS'] = $configPermissions->getUserGroups();
		$this->arResult['ACCESS_RIGHTS'] = $configPermissions->getAccessRights();
	}

	/**
	 * Check can user view and change rights.
	 *
	 * @return bool
	 */
	private function checkAccessPermissions(): bool
	{
		return AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_ACCESS)
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_SETTINGS_EDIT_RIGHTS)
		;
	}

	/**
	 * Show component errors.
	 *
	 * @return void
	 */
	private function printErrors(): void
	{
		Toolbar::deleteFavoriteStar();
		foreach ($this->errorCollection as $error)
		{
			$this->includeErrorComponent($error->getMessage());
		}
	}

	/**
	 * Include errors component.
	 *
	 * @param string $errorMessage
	 * @param string|null $description
	 *
	 * @return void
	 */
	protected function includeErrorComponent(string $errorMessage, string $description = null): void
	{
		global $APPLICATION;

		$APPLICATION->IncludeComponent(
			'bitrix:ui.info.error',
			'',
			[
				'TITLE' => $errorMessage,
				'DESCRIPTION' => $description,
			]
		);
	}
}
