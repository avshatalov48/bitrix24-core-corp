<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

class OnecStartComponent extends CBitrixComponent
{
	/**
	 * Start Component
	 */
	public function executeComponent()
	{
		global $APPLICATION, $USER;

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		if($request->isPost() && check_bitrix_sessid())
		{
			if(\Bitrix\Main\Loader::includeModule('faceId'))
			{
				if($request['action'] == 'acceptAgreement' && \Bitrix\FaceId\FaceCard::licenceIsRestricted() === false)
				{
					if(\Bitrix\FaceId\FaceCard::agreementIsAccepted($USER->GetID()) === false)
					{
						\Bitrix\Faceid\AgreementTable::add([
							'USER_ID' => $USER->GetID(),
							'NAME' => $USER->GetFullName(),
							'EMAIL' => $USER->GetEmail(),
							'DATE' => new \Bitrix\Main\Type\DateTime,
							'IP_ADDRESS' => \Bitrix\Main\Context::getCurrent()->getRequest()->getRemoteAddress()
						]);
					}

					$APPLICATION->RestartBuffer();

					Header('Content-Type: application/json');
					echo \Bitrix\Main\Web\Json::encode([
						'success' => true
					]);
					\CMain::FinalActions();
					die();
				}
			}
		}

		$componentPage = '';
		$arDefaultUrlTemplates404 = [
			'index' => '',
			'tracker' => 'tracker/',
			'report' => 'report/',
			'exchange' => 'exchange/',
			'realtime' => 'realtime/',
			'facecard' => 'facecard/',
			'doc' => 'doc/',
			'backoffice' => 'backoffice/',
		];

		$arDefaultVariableAliases404 = [];
		$arComponentVariables = [];
		$arVariables = [];
		$arVariableAliases = [];

		if ($this->arParams['SEF_MODE'] === 'Y')
		{
			$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $this->arParams['SEF_URL_TEMPLATES']);
			$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $this->arParams['VARIABLE_ALIASES']);
			$componentPage = CComponentEngine::ParseComponentPath($this->arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

			if (!(is_string($componentPage) && isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
			{
				$componentPage = 'index';
			}

			CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

			foreach ($arUrlTemplates as $url => $value)
			{
				$key = 'PATH_TO_ONEC_' . mb_strtoupper($url);
				$arResult[$key] = isset($this->arParams[$key][0]) ? $this->arParams[$key] : $this->arParams['SEF_FOLDER'].$value;
			}
		}

		if(!$this->checkModuleByPage($componentPage, $error, $redirectUrl))
		{
			if($redirectUrl<>'')
			{
				LocalRedirect($this->arParams['SEF_FOLDER'].$redirectUrl);
			}
			else
			{
				ShowError(implode(', ', $error));
			}

			return;
		}

		$this->arResult =
			array_merge(
				[
					'VARIABLES' => $arVariables,
					'ALIASES' => $this->arParams['SEF_MODE'] == 'Y' ? [] : $arVariableAliases
				],
				$this->arResult
			);

		switch ($componentPage)
		{
			case 'facecard':
				$this->arResult['RESTRICTED_LICENCE'] = \Bitrix\FaceId\FaceCard::licenceIsRestricted();
				$this->arResult['LICENSE_ACCEPTED'] = (
					$componentPage !== 'facecard'
					|| \Bitrix\FaceId\FaceCard::agreementIsAccepted($USER->GetID())
				);
				$this->arResult['LICENSE_TEXT'] = \Bitrix\Faceid\AgreementTable::getAgreementText(true);
				break;
		}
		switch ($componentPage)
		{
			case 'realtime':
			case 'tracker':
			case 'report':
			case 'facecard':
				$this->arResult['APP'] = $this->getApplicationInfo();
				$this->arResult['APP_INACTIVE'] = $this->applicationIsInactive();
				break;
			case 'doc':
				$this->arResult['APP'] = $this->getApplicationDocInfo();
				$this->arResult['APP_INACTIVE'] = $this->applicationDocIsInactive();
				break;
			case 'backoffice':
				$this->arResult['APP'] = $this->getApplicationBackOfficeInfo();
				$this->arResult['APP_INACTIVE'] = $this->applicationBackOfficeIsInactive();
				break;
			case 'index':
				$this->arResult['TILE_ID'] = 'crm-onec';
				$this->arResult['INTEGRATION_TILE_ID'] = 'crm-onec-integration';
				$this->arResult['PLACEMENT_ITEMS_ID'] = 'crm-onec-placement';
				$this->arResult['PLACEMENT_ITEMS'] = [];

				$appSettings = COption::GetOptionString(
					'rest',
					'options_' . \Bitrix\Rest\AppTable::getByClientId(\CRestUtil::BITRIX_1C_APP_CODE)['CLIENT_ID'],
					''
				);
				if (!empty($appSettings))
				{
					$appSettings = unserialize($appSettings, ['allowed_classes' => false]);
				}

				$this->arResult['ITEMS'] = [];
				$this->arResult['INTEGRATION_ITEMS'] = [];

				$this->arResult['ITEMS'][] = [
					'id' => 'realtime',
					'name' => Loc::getMessage('CRM_1C_START_REALTIME'),
					'iconClass' => 'ui-icon ui-icon-service-1c',
					'selected' => isset($appSettings['realtime']) && $appSettings['realtime'] == 'Y' ? true : false,
					'data' => [
						'url' => '/onec/realtime/'
					],
				];

				if (
					\Bitrix\Main\ModuleManager::isModuleInstalled('rest')
					&& \Bitrix\Main\Loader::includeModule('faceId')
					&& \Bitrix\FaceId\FaceId::isAvailable()
				)
				{
					$this->arResult['ITEMS'][] = [
						'id' => 'facecard',
						'name' => Loc::getMessage('CRM_1C_START_FACE_CARD'),
						'iconClass' => 'ui-icon ui-icon-service-1c',
						'iconColor' => '',
						'selected' => isset($appSettings['facecard']) && $appSettings['facecard'] == 'Y' ? true : false,
						'data' => [
							'url' => '/onec/facecard/'
						],
					];
				}

				if (\Bitrix\Main\ModuleManager::isModuleInstalled('rest'))
				{
					$this->arResult['ITEMS'][] = [
						'id' => 'report',
						'name' => Loc::getMessage('CRM_1C_START_REPORT'),
						'iconClass' => 'ui-icon ui-icon-service-1c',
						'selected' => isset($appSettings['report']) && $appSettings['report'] == 'Y' ? true : false,
						'data' => [
							'url' => '/onec/report/'
						],
					];
				}

				$exch1cEnabled = COption::GetOptionString('crm', 'crm_exch1c_enable', 'N');
				if ($exch1cEnabled)
				{
					if ($license_name = COption::GetOptionString('main', '~controller_group_name'))
					{
						preg_match('/(project|tf)$/is', $license_name, $matches);
						if ($matches[0] <> '')
							$exch1cEnabled = false;
					}
				}

				$this->arResult['ITEMS'][] = [
					'id' => 'exchange',
					'name' => Loc::getMessage('CRM_1C_START_EXCHANGE'),
					'iconClass' => 'ui-icon ui-icon-service-1c',
					'selected' => $exch1cEnabled == 'Y' ? true : false,
					'data' => [
						'url' => '/onec/exchange/'
					],
				];

				if (\Bitrix\Main\Loader::includeModule('rest'))
				{
					\Bitrix\Main\Loader::includeModule('sale');

					$this->arResult['ITEMS'][] = [
						'id' => 'doc',
						'name' => Loc::getMessage('CRM_1C_START_DOC'),
						'iconClass' => 'ui-icon ui-icon-service-1c',
						'selected' => $this->applicationDocIsInactive() ? false : true,
						'data' => [
							'url' => '/onec/doc/'
						],
					];

					if ($this->isBackOfficeAvailable())
					{
						$this->arResult['INTEGRATION_ITEMS'][] = [
							'id' => 'backoffice',
							'name' => Loc::getMessage('CRM_1C_START_BACKOFFICE'),
							'iconClass' => 'ui-icon ui-icon-service-red-1c',
							'selected' => $this->applicationBackOfficeIsInactive() ? false : true,
							'data' => [
								'url' => '/onec/backoffice/'
							],
							'badgeNew' => true,
						];
					}

					$placementHandlerList = [];
					if (Loader::includeModule('crm'))
					{
						$placement = \Bitrix\Crm\Integration\Rest\AppPlacement::ONEC_PAGE;
						$placementHandlerList = \Bitrix\Rest\PlacementTable::getHandlersList($placement);
					}

					if(count($placementHandlerList) > 0)
					{
						foreach($placementHandlerList as $placementHandler)
						{
							$this->arResult['PLACEMENT_ITEMS'][] = [
								'ID' => $placementHandler['ID'],
								'NAME' => $placementHandler['TITLE'] <> ''
									? $placementHandler['TITLE']
									: $placementHandler['APP_NAME'],
								'CODE' => $placement,
								'APP_ID' => $placementHandler['APP_ID'],
								'PLACEMENT_ID' => $placementHandler['ID'],
								'OPTIONS' => []

							];
						}
					}
				}

				$this->arResult['SYNCHRO_TILE_ID'] = 'crm-onec-synchro';
				$this->arResult['SYNCHRO_ITEMS'] = [
					[
						'id' => 'invoice',
						'name' => Loc::getMessage('CRM_1C_START_SYNCHRO_INVOICE'),
						'iconClass' => 'ui-icon ui-icon-service-1c',
						'selected' => $exch1cEnabled == 'Y' ? true : false,
						'data' => [
							'url' => '/crm/configs/exch1c/invoice/'
						],
					],
					[
						'id' => 'catalog',
						'name' => Loc::getMessage('CRM_1C_START_SYNCHRO_CATALOG'),
						'iconClass' => 'ui-icon ui-icon-service-1c',
						'selected' => $exch1cEnabled == 'Y' ? true : false,
						'data' => [
							'url' => '/crm/configs/exch1c/catalog/'
						],
					]
				];

				$this->arResult['HELPER_TILE_ID'] = 'crm-onec-helper';
				$this->arResult['HELPER_ITEMS'] = [
					[
						'id' => 'helper',
						'name' => Loc::getMessage('CRM_1C_START_HELPER'),
						'button' => true,
						'data' => [
							'buttonName' => Loc::getMessage('CRM_1C_START_CONNECT'),
						],
					]
				];

				$this->arResult['FORM_PORTAL_URI'] = Loader::includeModule('intranet')
					? \Bitrix\Intranet\Util::CP_BITRIX_PATH
					: ''
				;

				break;

		}

		CJSCore::Init(['popup', 'applayout']);

		$this->includeComponentTemplate($componentPage);
	}

	protected function applicationIsInactive()
	{
		$r = false;
		$appInfo = $this->getApplicationInfo();
		if(!$appInfo || $appInfo['ACTIVE'] === \Bitrix\Rest\AppTable::INACTIVE)
		{
			$r = true;
		}
		return $r;
	}

	protected function getApplicationInfo()
	{
		return \Bitrix\Rest\AppTable::getByClientId('bitrix.1c');
	}

	protected function applicationDocIsInactive()
	{
		$r = false;
		$appInfo = $this->getApplicationDocInfo();
		if(!$appInfo || $appInfo['ACTIVE'] === \Bitrix\Rest\AppTable::INACTIVE)
		{
			$r = true;
		}
		return $r;
	}

	protected function getApplicationDocInfo()
	{
		return \Bitrix\Rest\AppTable::getByClientId('bitrix.1cdoc');
	}

	protected function isBackOfficeAvailable()
	{
		if (Loader::includeModule('bitrix24'))
		{
			if (in_array(\CBitrix24::getLicensePrefix(), ['ru', 'by']))
			{
				return true;
			}
		}
		elseif (Loader::includeModule('intranet') && \CIntranetUtils::getPortalZone() === 'ru')
		{
			return true;
		}

		return false;
	}

	protected function applicationBackOfficeIsInactive()
	{
		$r = false;
		$appInfo = $this->getApplicationBackOfficeInfo();
		if(!$appInfo || $appInfo['ACTIVE'] === \Bitrix\Rest\AppTable::INACTIVE)
		{
			$r = true;
		}
		return $r;
	}

	protected function getApplicationBackOfficeInfo()
	{
		return \Bitrix\Rest\AppTable::getByClientId('bitrix.1ctotal');
	}
	protected function checkModuleByPage($page='', &$error, &$redirectUrl = '')
	{
		$error = [];

		switch ($page)
		{
			case 'index':
				$r = true;

				break;
			case 'facecard':
				if(\Bitrix\Main\Loader::includeModule('faceId') && !\Bitrix\FaceId\FaceId::isAvailable())
				{
					$error[] = 'faceCard';
					$redirectUrl = 'tracker/';
				}

				if(!\Bitrix\Main\Loader::includeModule('faceId'))
				{
					$error[] = 'faceId';
					$redirectUrl = 'tracker/';
				}

				if(!\Bitrix\Main\Loader::includeModule('rest'))
				{
					$error[] = 'rest';
					$redirectUrl = 'exchange/';
				}

				$r = count($error)<=0;

				break;
			case 'tracker':
			case 'report':
			case 'doc':
			case 'backoffice':

				if(!\Bitrix\Main\Loader::includeModule('rest'))
				{
					$error[] = 'rest';
					$redirectUrl = 'exchange/';
				}

				$r = count($error)<=0;
				break;
			case 'realtime':
			case 'exchange':
				$r = true;
				break;
			default;
				$r = false;
		}
		return $r;
	}
}
