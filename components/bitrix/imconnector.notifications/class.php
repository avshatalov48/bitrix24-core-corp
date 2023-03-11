<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\Notifications\Settings;

Loader::includeModule('imconnector');

class ImConnectorNotificationsComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	use Bitrix\Main\ErrorableImplementation;

	private $connector = Library::ID_NOTIFICATIONS_CONNECTOR;
	private $scenarioCode;

	private $error = [];
	private $pageId = 'page_unc';

	/** @var Bitrix\ImConnector\Output */
	private $connectorOutput;
	/** @var Status */
	private $status;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
	}

	protected function checkModules(): bool
	{
		if (!Loader::includeModule('imconnector'))
		{
			ShowError('imconnector is not installed');
			return false;
		}
		if (!Loader::includeModule('imopenlines'))
		{
			ShowError('imopenlines is not installed');
			return false;
		}
		if (!Loader::includeModule('notifications'))
		{
			ShowError('notifications is not installed');
			return false;
		}

		return true;
	}

	protected function initialization()
	{
		$this->connectorOutput = new Output($this->connector, (int)$this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$this->scenarioCode = $request->get('scenario') ?? Settings::SCENARIO_CRM_PAYMENT;
		$this->arResult["SCENARIO_CODE"] = $this->scenarioCode;
		$this->arResult["STATUS"] = $this->status->isStatus();
		$this->arResult["ACTIVE_STATUS"] = $this->status->getActive() && Settings::isScenarioEnabled($this->scenarioCode);
//		$this->arResult["CONNECTION_STATUS"] = $this->status->getConnection();
//		$this->arResult["REGISTER_STATUS"] = $this->status->getRegister();
//		$this->arResult["ERROR_STATUS"] = $this->status->getError();

		$this->arResult["HELP_ARTICLE_CODE"] = $this->scenarioCode == Settings::SCENARIO_VIRTUAL_WHATSAPP ? "14748976" : "13655934";

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);

		$this->arResult["PAGE"] = $this->request[$this->pageId];
	}

	public function saveForm()
	{
		//If been sent the current form
		if (!$this->request->isPost() || empty($this->request[$this->connector . '_form']))
		{
			return;
		}

		//If the session actual
		if (!check_bitrix_sessid())
		{
			$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_NOTIFICATIONS_SESSION_HAS_EXPIRED");
		}
		//Activation bot
		if ($this->request[$this->connector . '_active'] && empty($this->arResult["ACTIVE_STATUS"]))
		{
			/** @var \Bitrix\ImConnector\Result $resultRegister */
			$resultRegister = $this->connectorOutput->register([
				'LINE_ID' => $this->arParams['LINE'],
				'SKIP_TOS' => $this->scenarioCode === Settings::SCENARIO_VIRTUAL_WHATSAPP
			]);

			if ($resultRegister->isSuccess())
			{
				$this->status->setActive(true);
				$this->arResult["ACTIVE_STATUS"] = true;
				$this->status->setConnection(true);
				$this->arResult["CONNECTION_STATUS"] = true;
				$this->status->setRegister(true);
				$this->arResult["REGISTER_STATUS"] = true;
				$this->status->setData($resultRegister->getResult());
				$this->arResult["DATA_STATUS"] = $resultRegister->getResult();

				$this->arResult["STATUS"] = true;

				Status::deleteLinesExcept($this->connector, (int)$this->arParams['LINE']);

				$scenarioEnableResult = Settings::setScenarioStatus($this->arResult['SCENARIO_CODE'], true);
				if (!$scenarioEnableResult->isSuccess())
				{
					$this->error[] = $scenarioEnableResult->getErrors()[0]->getMessage();
					$this->arResult["ACTIVE_STATUS"] = false;
					$this->arResult["STATUS"] = false;
				}
			}
			else
			{
				$this->error[] = $resultRegister->getErrors()[0]->getMessage();
			}
		}

		if (!empty($this->arResult["ACTIVE_STATUS"]))
		{
			if($this->request[$this->connector. '_del'])
			{
				$scenarioDisableResult = Settings::setScenarioStatus($this->arResult['SCENARIO_CODE'], false);
				if (!$scenarioDisableResult->isSuccess())
				{
					$this->error[] = $scenarioDisableResult->getErrors()[0]->getMessage();
				}

				if (!Settings::isAnyScenarioEnabled())
				{
					$resultDelete = $this->connectorOutput->delete();
					if (!$resultDelete->isSuccess())
					{
						$this->error[] = $resultDelete->getErrors()[0]->getMessage();
					}
					else
					{
						Status::delete($this->connector, (int)$this->arParams['LINE']);
					}
				}

				if (empty($this->error))
				{
					$this->arResult["STATUS"] = false;
					$this->arResult["ACTIVE_STATUS"] = false;
					$this->arResult["CONNECTION_STATUS"] = false;
					$this->arResult["REGISTER_STATUS"] = false;
					$this->arResult["ERROR_STATUS"] = false;
					$this->arResult["PAGE"] = '';
				}
			}
		}

		\Bitrix\ImConnector\InfoConnectors::updateInfoConnectors($this->arParams['LINE']);
		if (Loader::includeModule('crm') && method_exists(\Bitrix\Crm\SiteButton\Manager::class, 'updateScriptCacheWithLineId'))
		{
			$lineConfig = \Bitrix\ImOpenLines\Model\ConfigTable::getRow([
				'select' => ['ACTIVE'],
				'filter' => [
					'=ID' => $this->arParams['LINE']
				]
			]);
			if ($lineConfig)
			{
				\Bitrix\Crm\SiteButton\Manager::updateScriptCacheWithLineId(
					$this->arParams['LINE'],
					$lineConfig['ACTIVE'] === 'Y'
				);
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult["NAME"] = Connector::getNameConnectorReal($this->connector);

		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", [$this->pageId, "open_block", "action"]);
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", [$this->pageId, "open_block", "action"]);
		$this->arResult["URL"]["SIMPLE_FORM_EDIT"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", [$this->pageId, "open_block", "action"]);

		if (!$this->arResult["STATUS"])
		{
			$uri = new Uri($this->arResult["URL"]["SIMPLE_FORM"]);
			$uri->addParams(['action' => 'connect']);
			$this->arResult["URL"]["SIMPLE_FORM"] = $uri->getUri();
		}

		$this->arResult["CONNECTOR"] = $this->connector;
	}

	public function executeComponent()
	{
		if ($this->checkModules())
		{
			if (Connector::isConnector($this->connector))
			{
				$this->initialization();

				$this->saveForm();

				$this->constructionForm();

				if (!empty($this->error))
					$this->arResult['error'] = $this->error;

				if (!empty($this->messages))
					$this->arResult['messages'] = $this->messages;

				$page = '';
				if ($this->arResult['SCENARIO_CODE'] === Settings::SCENARIO_VIRTUAL_WHATSAPP)
				{
					$page = 'template_virtual_wa';
				}
				$this->includeComponentTemplate($page);
			}
			else
			{
				ShowError('This connector is not active');
			}
		}
	}

	public function configureActions()
	{
		return [];
	}

	public function getTermsOfServiceAction()
	{
		$result = false;

		$serviceLocator = ServiceLocator::getInstance();
		if($serviceLocator->has('ImConnector.toolsNotifications'))
		{
			/** @var \Bitrix\ImConnector\Tools\Connectors\Notifications $toolsNotifications */
			$toolsNotifications = $serviceLocator->get('ImConnector.toolsNotifications');
			$result = $toolsNotifications->getAgreementTerms();

		}
		if (!$result)
		{
			$this->errorCollection[] = new Error('Could not load agreement text');
			return null;
		}

		$result['okButton'] = Loc::getMessage('IMCONNECTOR_COMPONENT_NOTIFICATIONS_TOS_AGREE');
		return $result;
	}

	public function saveTermsOfServiceAgreementAction()
	{
		global $USER;
		Loader::requireModule('notifications');

		$serviceLocator = ServiceLocator::getInstance();
		if($serviceLocator->has('ImConnector.toolsNotifications'))
		{
			/** @var \Bitrix\ImConnector\Tools\Connectors\Notifications $toolsNotifications */
			$toolsNotifications = $serviceLocator->get('ImConnector.toolsNotifications');
			$toolsNotifications->addUserConsentAgreementTerms();

			\Bitrix\Notifications\Account::saveTOSAgreement(
				$USER->getId(),
				new \Bitrix\Main\Type\DateTime(),
				\Bitrix\Main\Context::getCurrent()->getServer()->getRemoteAddr()
			);
		}
	}

	public function saveSettingsAction(array $scenarioStatus, int $lineId)
	{
		foreach (Settings::getScenarioList() as $scenarioName)
		{
			$scenarioEnabled = isset($scenarioStatus[$scenarioName]) && $scenarioStatus[$scenarioName] === 'Y';
			$result = Settings::setScenarioStatus($scenarioName, $scenarioEnabled);
			if (!$result->isSuccess())
			{
				$this->errorCollection->add($result->getErrors());
				return null;
			}
		}

		\Bitrix\ImConnector\InfoConnectors::updateInfoConnectors($lineId);
	}
}