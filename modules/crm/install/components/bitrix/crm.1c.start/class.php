<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

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
						\Bitrix\Faceid\AgreementTable::add(array(
							'USER_ID' => $USER->GetID(),
							'NAME' => $USER->GetFullName(),
							'EMAIL' => $USER->GetEmail(),
							'DATE' => new \Bitrix\Main\Type\DateTime,
							'IP_ADDRESS' => \Bitrix\Main\Context::getCurrent()->getRequest()->getRemoteAddress()
						));
					}

					$APPLICATION->RestartBuffer();

					Header('Content-Type: application/json');
					echo \Bitrix\Main\Web\Json::encode(array(
						'success' => true
					));
					\CMain::FinalActions();
					die();
				}
			}
		}

		$componentPage = '';
		$arDefaultUrlTemplates404 = array(
			'index' => '',
			'tracker' => 'tracker/',
			'report' => 'report/',
			'exchange' => 'exchange/',
			'realtime' => 'realtime/',
			'facecard' => 'facecard/',
			'doc' => 'doc/'
		);

		$arDefaultVariableAliases404 = array();
		$arComponentVariables = array();
		$arVariables = array();
		$arVariableAliases = array();

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
				$key = 'PATH_TO_ONEC_'.strtoupper($url);
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
				array(
					'VARIABLES' => $arVariables,
					'ALIASES' => $this->arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases
				),
				$this->arResult
			);

		switch ($componentPage)
		{
			case 'facecard':
				$this->arResult['RESTRICTED_LICENCE'] = \Bitrix\FaceId\FaceCard::licenceIsRestricted();
				$this->arResult['LICENSE_ACCEPTED'] = $componentPage !== 'facecard' || \Bitrix\FaceId\FaceCard::agreementIsAccepted($USER->GetID());
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
			case 'index':
				$this->arResult['TILE_ID'] = "crm-onec";

				$appSettings = COption::GetOptionString("rest", "options_".\Bitrix\Rest\AppTable::getByClientId(\CRestUtil::BITRIX_1C_APP_CODE)['CLIENT_ID'], "");
				if (!empty($appSettings))
				{
					$appSettings = unserialize($appSettings);
				}

				$this->arResult['ITEMS'] = array();

				$this->arResult['ITEMS'][] = array(
					"id" => "realtime",
					"name" => Loc::getMessage("CRM_1C_START_REALTIME"),
					"iconClass" => "ui-icon ui-icon-service-1c",
					"selected" => isset($appSettings["realtime"]) && $appSettings["realtime"] == "Y" ? true : false,
					"data" => array(
						"url" => "/onec/realtime/"
					)
				);

				if (
					\Bitrix\Main\ModuleManager::isModuleInstalled("rest")
					&& \Bitrix\Main\Loader::includeModule('faceId')
					&& \Bitrix\FaceId\FaceId::isAvailable()
				)
				{
					$this->arResult['ITEMS'][] = array(
						"id" => "facecard",
						"name" => Loc::getMessage("CRM_1C_START_FACE_CARD"),
						"iconClass" => "ui-icon ui-icon-service-1c",
						"iconColor" => "",
						"selected" => isset($appSettings["facecard"]) && $appSettings["facecard"] == "Y" ? true : false,
						"data" => array(
							"url" => "/onec/facecard/"
						)
					);
				}

				if (\Bitrix\Main\ModuleManager::isModuleInstalled("rest"))
				{
					$this->arResult['ITEMS'][] = array(
						"id" => "report",
						"name" => Loc::getMessage("CRM_1C_START_REPORT"),
						"iconClass" => "ui-icon ui-icon-service-1c",
						"selected" => isset($appSettings["report"]) && $appSettings["report"] == "Y" ? true : false,
						"data" => array(
							"url" => "/onec/report/"
						)
					);
				}

				$exch1cEnabled = COption::GetOptionString('crm', 'crm_exch1c_enable', 'N');
				if ($exch1cEnabled)
				{
					if ($license_name = COption::GetOptionString("main", "~controller_group_name"))
					{
						preg_match("/(project|tf)$/is", $license_name, $matches);
						if (strlen($matches[0]) > 0)
							$exch1cEnabled = false;
					}
				}

				$this->arResult['ITEMS'][] = array(
					"id" => "exchange",
					"name" => Loc::getMessage("CRM_1C_START_EXCHANGE"),
					"iconClass" => "ui-icon ui-icon-service-1c",
					"selected" => $exch1cEnabled == "Y" ? true : false,
					"data" => array(
						"url" => "/onec/exchange/"
					)
				);

				if (\Bitrix\Main\ModuleManager::isModuleInstalled("rest"))
				{
					$this->arResult['ITEMS'][] = array(
						"id" => "doc",
						"name" => Loc::getMessage("CRM_1C_START_DOC"),
						"iconClass" => "ui-icon ui-icon-service-1c",
						"selected" => $this->applicationDocIsInactive() ? false : true,
						"data" => array(
							"url" => "/onec/doc/"
						)
					);
				}

				$this->arResult['SYNCHRO_TILE_ID'] = "crm-onec-synchro";
				$this->arResult['SYNCHRO_ITEMS'] = array(
					array(
						"id" => "invoice",
						"name" => Loc::getMessage("CRM_1C_START_SYNCHRO_INVOICE"),
						"iconClass" => "ui-icon ui-icon-service-1c",
						"selected" => $exch1cEnabled == "Y" ? true : false,
						"data" => array(
							"url" => "/crm/configs/exch1c/invoice/"
						)
					),
					array(
						"id" => "catalog",
						"name" => Loc::getMessage("CRM_1C_START_SYNCHRO_CATALOG"),
						"iconClass" => "ui-icon ui-icon-service-1c",
						"selected" => $exch1cEnabled == "Y" ? true : false,
						"data" => array(
							"url" => "/crm/configs/exch1c/catalog/"
						)
					)
				);

				break;

		}

		CJSCore::Init(array('popup', 'applayout'));

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

	protected function checkModuleByPage($page='', &$error, &$redirectUrl = '')
	{
		$error = array();
		
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