<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Web\Uri;
use \Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector;

class ImConnectorBotframework extends \CBitrixComponent
{
	private $cacheId;

	private $connector = 'botframework';
	private $error = array();
	private $messages = array();
	/** @var \Bitrix\ImConnector\Output */
	private $connectorOutput;
	/** @var \Bitrix\ImConnector\Status */
	private $status;

	protected $pageId = 'page_msbf';

	private $listOptions = array(
		'bot_handle',
		'app_id',
		'app_secret',
		'url_skypebot',
		'url_slack',
		'url_kik',
		'url_groupme',
		'url_twilio',
		'url_msteams',
		'url_webchat',
		'url_email',
		'url_telegram',
		'url_facebook',
	);

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules()
	{
		if (Loader::includeModule('imconnector'))
		{
			return true;
		}
		else
		{
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_BOTFRAMEWORK_MODULE_NOT_INSTALLED'));
			return false;
		}
	}

	protected function initialization()
	{
		$this->connectorOutput = new Output($this->connector, $this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, $this->arParams['LINE']);

		$this->arResult["STATUS"] = $this->status->isStatus();
		$this->arResult["ACTIVE_STATUS"] = $this->status->getActive();
		$this->arResult["CONNECTION_STATUS"] = $this->status->getConnection();
		$this->arResult["REGISTER_STATUS"] = $this->status->getRegister();
		$this->arResult["ERROR_STATUS"] = $this->status->getError();

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);

		Connector::initIconCss();
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache()
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	public function saveForm()
	{
		//If been sent the current form
		if ($this->request->isPost() && !empty($this->request[$this->connector. '_form']))
		{
			//If the session actual
			if(check_bitrix_sessid())
			{
				//Activation bot
				if($this->request[$this->connector. '_active'] && empty($this->arResult["ACTIVE_STATUS"]))
				{
					$this->status->setActive(true);
					$this->arResult["ACTIVE_STATUS"] = true;

					//Reset cache
					$this->cleanCache();
				}

				if(!empty($this->arResult["ACTIVE_STATUS"]))
				{
					//If there is save data
					if($this->request[$this->connector. '_save'])
					{
						foreach ($this->listOptions as $value)
						{
							if(isset($this->request[$value]))
								$this->arResult["FORM"][$value] = $this->request[$value];
						}

						$data = NULL;

						if(!empty($this->arResult["FORM"]))
						{
							if (!empty($this->arResult['REGISTER_STATUS']))
							{
								$this->connectorOutput->unregister();
							}

							$saved = $this->connectorOutput->saveSettingsBotFramework($this->arResult["FORM"]);

							if($saved-> isSuccess())
							{
								$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_BOTFRAMEWORK_OK_SAVE");
								$this->arResult["SAVE_STATUS"] = true;

								$this->status->setError(false);
								$this->arResult["ERROR_STATUS"] = false;
							}
							else
							{
								$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_BOTFRAMEWORK_NO_SAVE");
								$this->arResult["SAVE_STATUS"] = false;

								$this->status->setConnection(false);
								$this->arResult["CONNECTION_STATUS"] = false;
								$this->status->setRegister(false);
								$this->arResult["REGISTER_STATUS"] = false;

								$this->arResult["STATUS"] = false;
							}
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_BOTFRAMEWORK_NO_DATA_SAVE");
						}

						//Reset cache
						$this->cleanCache();
					}

					//If the test connection or save
					if(($this->request[$this->connector. '_save'] && $this->arResult["SAVE_STATUS"]) || $this->request[$this->connector. '_tested'])
					{
						$testConnect = $this->connectorOutput->testConnect();

						if($testConnect->isSuccess())
						{
							$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_BOTFRAMEWORK_OK_CONNECT");

							$this->status->setConnection(true);
							$this->arResult["CONNECTION_STATUS"] = true;

							$this->status->setRegister(true);
							$this->arResult["REGISTER_STATUS"] = true;
							$this->arResult["STATUS"] = true;

							$this->status->setError(false);
							$this->arResult["ERROR_STATUS"] = false;
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_BOTFRAMEWORK_NO_CONNECT");

							$this->status->setConnection(false);
							$this->arResult["CONNECTION_STATUS"] = false;

							$this->status->setRegister(false);
							$this->arResult["REGISTER_STATUS"] = false;

							$this->arResult["STATUS"] = false;
						}

						//Reset cache
						$this->cleanCache();
					}

					if($this->request[$this->connector. '_del'])
					{
						$rawDelete = $this->connectorOutput->deleteConnector();

						if($rawDelete->isSuccess())
						{
							Status::delete($this->connector, $this->arParams['LINE']);
							$this->arResult["STATUS"] = false;
							$this->arResult["ACTIVE_STATUS"] = false;
							$this->arResult["CONNECTION_STATUS"] = false;
							$this->arResult["REGISTER_STATUS"] = false;
							$this->arResult["ERROR_STATUS"] = false;
							$this->arResult["PAGE"] = '';

							//$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_SETTINGS_OK_DISABLE");
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_SETTINGS_NO_DISABLE");
						}

						//Reset cache
						$this->cleanCache();
					}
				}
			}
			else
			{
				$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_BOTFRAMEWORK_SESSION_HAS_EXPIRED");
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult["NAME_TITLE"] = Connector::getNameConnectorReal($this->connector);

		if(strlen($this->arResult["NAME_TITLE"]) > 30)
			$this->arResult["NAME"] = substr($this->arResult["NAME_TITLE"], 0, 27) . '...';
		else
			$this->arResult["NAME"] = $this->arResult["NAME_TITLE"];

		$this->arResult["URL"]["INDEX"] = $APPLICATION->GetCurPageParam($this->pageId . "=index", array($this->pageId, "open_block", "action"));
		$this->arResult["URL"]["MASTER"] = $APPLICATION->GetCurPageParam($this->pageId . "=master", array($this->pageId, "open_block", "action"));
		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", array($this->pageId, "open_block", "action"));
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "open_block", "action"));
		$this->arResult["URL"]["SIMPLE_FORM_EDIT"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "open_block", "action"));

		if($this->arResult["ACTIVE_STATUS"])
		{
			if(!empty($this->arResult["PAGE"]))
			{
				$settings = $this->connectorOutput->getAuthorizationInformation();

				$result = $settings->getResult();

				foreach ($this->listOptions as $value)
				{
					if(empty($this->arResult["FORM"][$value]))
					{
						if(empty($result['SETTINGS'][$value]))
						{
							$this->arResult["FORM"][$value] = $result['SETTINGS'][$value];
						}
						else
						{
							$this->arResult["SAVE_STATUS"] = true;
							if($result['SETTINGS'][$value] == '#HIDDEN#')
								$this->arResult["placeholder"][$value] = true;
							else
								$this->arResult["FORM"][$value] = $result['SETTINGS'][$value];
						}
					}
				}

				if(!empty($result['URL_WEBHOOK']))
					$this->arResult["URL_WEBHOOK"] = $result['URL_WEBHOOK'];

				if(empty($this->request['open_block']))
					$this->arResult["OPEN_BLOCK"] = '';
				else
					$this->arResult["OPEN_BLOCK"] = 'Y';
			}

			if($this->arResult["STATUS"])
			{
				$cache = Cache::createInstance();

				if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
				{
					$this->arResult["INFO_CONNECTION"] = $cache->getVars();
				}
				elseif ($cache->startDataCache())
				{
					$infoConnectLink = $this->connectorOutput->infoConnectLink();
					if($infoConnectLink->isSuccess())
					{
						$infoConnectLinkData = $infoConnectLink->getData();

						$this->arResult["INFO_CONNECTION"] = array(
							'ID' => $infoConnectLinkData["ID"],
							'URL' => $infoConnectLinkData["URL"],
							'URL_SKYPE' => $infoConnectLinkData["URL_SKYPE"],
							'URL_SLACK' => $infoConnectLinkData["URL_SLACK"],
							'URL_KIK' => $infoConnectLinkData["URL_KIK"],
							'URL_GROUPME' => $infoConnectLinkData["URL_GROUPME"],
							'URL_SMS' => $infoConnectLinkData["URL_SMS"],
							//'URL_MSTEAMS' => $infoConnectLinkData["URL_MSTEAMS"],
							'URL_WEBCHAT' => $infoConnectLinkData["URL_WEBCHAT"],
							'URL_EMAIL' => $infoConnectLinkData["URL_EMAIL"],
							'URL_TELEGRAM' => $infoConnectLinkData["URL_TELEGRAM"],
							'URL_FACEBOOK' => $infoConnectLinkData["URL_FACEBOOK"],
							'URL_DIRECTLINE' => $infoConnectLinkData["URL_DIRECTLINE"],
						);

						$cache->endDataCache($this->arResult["INFO_CONNECTION"]);
					}
					else
					{
						$this->arResult["INFO_CONNECTION"] = array();
						$cache->abortDataCache();
					}
				}

				$uri = new Uri($this->arResult["URL"]["DELETE"]);
				$uri->addParams(array('action' => 'disconnect'));
				$this->arResult["URL"]["DELETE"] = $uri->getUri();

				$uri = new Uri($this->arResult["URL"]["SIMPLE_FORM_EDIT"]);
				$uri->addParams(array('action' => 'edit'));
				$this->arResult["URL"]["SIMPLE_FORM_EDIT"] = $uri->getUri();
			}
			else
			{
				$uri = new Uri($this->arResult["URL"]["SIMPLE_FORM_EDIT"]);
				$uri->addParams(array('action' => 'connect'));
				$this->arResult["URL"]["SIMPLE_FORM_EDIT"] = $uri->getUri();
			}
		}

		$this->arResult["CONNECTOR"] = $this->connector;
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if($this->checkModules())
		{
			if(Connector::isConnector($this->connector))
			{
				$this->initialization();

				$this->arResult["PAGE"] = $this->request[$this->pageId];

				$this->saveForm();

				$this->constructionForm();

				if(!empty($this->error))
					$this->arResult['error'] = $this->error;

				if(!empty($this->messages))
					$this->arResult['messages'] = $this->messages;

				$this->includeComponentTemplate();
			}
			else
			{
				ShowError(Loc::getMessage("IMCONNECTOR_COMPONENT_BOTFRAMEWORK_NO_ACTIVE_CONNECTOR"));
			}
		}
	}
};