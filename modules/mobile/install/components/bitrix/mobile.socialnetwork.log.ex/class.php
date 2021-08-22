<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponent $this */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

if (!Loader::includeModule('mobile'))
{
	return false;
}

if (!Loader::includeModule('socialnetwork'))
{
	ShowError(Loc::getMessage('MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED'));
	return;
}

global $USER;

if (!$USER->isAuthorized())
{
	ShowError(Loc::getMessage('MOBILE_LIVEFEED_NOT_AUTHORIZED'));
	return;
}

final class MobileLivefeed extends \Bitrix\Mobile\Component\LogList
{
	/** @var  ErrorCollection */
	protected $errorCollection;

	protected function listKeysSignedParameters(): array
	{
		return [
			'GROUP_ID',
			'PATH_TO_LOG_ENTRY', 'PATH_TO_LOG_ENTRY_EMPTY',
			'PATH_TO_USER', 'PATH_TO_GROUP',
			'PATH_TO_CRMCOMPANY', 'PATH_TO_CRMCONTACT', 'PATH_TO_CRMLEAD', 'PATH_TO_CRMDEAL',
			'PATH_TO_TASKS_SNM_ROUTER',
			'SET_LOG_CACHE',
			'IMAGE_MAX_WIDTH',
			'DATE_TIME_FORMAT',
			'CHECK_PERMISSIONS_DEST'
		];
	}

	public function getEntryLogIdAction($params)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED', 'MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED'))]);
			return;
		}

		$entityType = (string)($params['entityType'] ?? '');
		$entityId = (int)($params['entityId'] ?? 0);

		if (
			$entityType === ''
			|| $entityId <= 0
		)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('MOBILE_LIVEFEED_WRONG_ENTITY_DATA', 'MOBILE_LIVEFEED_WRONG_ENTITY_DATA'))]);
			return;
		}

		$provider = \Bitrix\Socialnetwork\Livefeed\Provider::init(array(
			'ENTITY_TYPE' => $entityType,
			'ENTITY_ID' => $entityId,
		));
		if ($provider)
		{
			$logId = $provider->getLogId();
		}

		return [
			'logId' => $logId
		];
	}

	public function getEntryContentAction($params)
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			$this->errorCollection->add([new Error(Loc::getMessage('MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED', 'MOBILE_LIVEFEED_SOCIALNETWORK_MODULE_NOT_INSTALLED'))]);
			return;
		}

		$logId = (int)($params['logId'] ?? 0);
		$pinnedContext = (isset($params['pinned']) && $params['pinned'] === 'Y');
		$entityType = (string)($params['entityType'] ?? '');
		$entityId = (int)($params['entityId'] ?? 0);
		$siteTemplateId = (string)($params['siteTemplateId'] ?? 'mobile_app');

		if (
			$logId <= 0
			&& $entityType !== ''
			&& $entityId > 0
		)
		{
			$provider = \Bitrix\Socialnetwork\Livefeed\Provider::init(array(
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityId,
			));
			if ($provider)
			{
				$logId = $provider->getLogId();
			}
		}

		if ($logId <= 0)
		{
			$this->errorCollection->add([new Error(Loc::getMessage('MOBILE_LIVEFEED_WRONG_LOG_ID'), 'MOBILE_LIVEFEED_WRONG_LOG_ID')]);
			return;
		}

		define('BX_MOBILE', true);

		$this->arParams['LOG_ID'] = $logId;
		$this->arParams['SITE_TEMPLATE_ID'] = $siteTemplateId;
		$this->arParams['TARGET'] = ($pinnedContext ? 'ENTRIES_ONLY_PINNED' : 'ENTRIES_ONLY');
		$this->arParams['IS_LIST'] = 'Y';

		return new Engine\Response\Component($this->getName(), '', $this->arParams, [], [
			'serverTimestamp'
		]);
	}

	public function executeComponent()
	{
		global $APPLICATION;

		CPageOption::setOptionString('main', 'nav_page_in_session', 'N');
		$APPLICATION->setPageProperty('BodyClass', ($this->arParams['LOG_ID'] > 0 || $this->arParams['EMPTY_PAGE'] === 'Y' ? 'post-card' : 'lenta-page'));

		$this->arResult = $this->prepareData();

		$this->includeComponentTemplate();
	}
}
