<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Limit;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

class ImConnectorViber extends \CBitrixComponent
{
	private $cacheId;

	private $connector = 'viber';
	private $error = [];
	private $messages = [];
	/** @var Output */
	private $connectorOutput;
	/** @var Status */
	private $status;

	protected $pageId = 'page_vb';

	private $listOptions = ['api_token'];

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 */
	protected function checkModules()
	{
		if (Loader::includeModule('imconnector'))
		{
			return true;
		}
		else
		{
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_VIBER_MODULE_NOT_INSTALLED_MSGVER_1'));
			return false;
		}
	}

	protected function initialization()
	{
		$this->connectorOutput = new Output($this->connector, (int)$this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$this->arResult["STATUS"] = $this->status->isStatus();
		$this->arResult["ACTIVE_STATUS"] = $this->status->getActive();
		$this->arResult["CONNECTION_STATUS"] = $this->status->getConnection();
		$this->arResult["REGISTER_STATUS"] = $this->status->getRegister();
		$this->arResult["ERROR_STATUS"] = $this->status->getError();
		$this->arResult['CAN_USE_CONNECTION'] = Limit::canUseConnector($this->connector);
		$this->arResult['INFO_HELPER_LIMIT'] = Limit::getIdInfoHelperConnector($this->connector);

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);
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
					//If saving
					if($this->request[$this->connector. '_save'])
					{
						foreach ($this->listOptions as $value)
						{
							if(!empty($this->request[$value]))
								$this->arResult["FORM"][$value] = $this->request[$value];
						}

						$data = NULL;

						if(!empty($this->arResult["FORM"]))
						{
							if (!empty($this->arResult['REGISTER_STATUS']))
							{
								$this->connectorOutput->unregister();
							}

							$saved = $this->connectorOutput->saveSettings($this->arResult["FORM"]);

							if($saved-> isSuccess())
							{
								$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_OK_SAVE");
								$this->arResult["SAVE_STATUS"] = true;

								$this->status->setError(false);
								$this->arResult["ERROR_STATUS"] = false;
							}
							else
							{
								$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_NO_SAVE");
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
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_NO_DATA_SAVE");
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
							$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_OK_CONNECT");

							$this->status->setConnection(true);
							$this->arResult["CONNECTION_STATUS"] = true;

							$this->status->setError(false);
							$this->arResult["ERROR_STATUS"] = false;
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_NO_CONNECT");

							$testConnect->getResult();

							$this->status->setConnection(false);
							$this->arResult["CONNECTION_STATUS"] = false;

							$this->status->setRegister(false);
							$this->arResult["REGISTER_STATUS"] = false;

							$this->arResult["STATUS"] = false;
						}

						//Reset cache
						$this->cleanCache();
					}

					//If the check or test connection or save
					if(($this->request[$this->connector. '_register'] || ($this->request[$this->connector. '_save'] && $this->arResult["SAVE_STATUS"]) || $this->request[$this->connector. '_tested']) && $this->arResult["CONNECTION_STATUS"])
					{
						$register = $this->connectorOutput->register();

						if($register->isSuccess())
						{
							$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_OK_REGISTER");

							$this->status->setRegister(true);
							$this->arResult["REGISTER_STATUS"] = true;
							$this->arResult["STATUS"] = true;

							$this->status->setError(false);
							$this->arResult["ERROR_STATUS"] = false;
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_NO_REGISTER");

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
							Status::delete($this->connector, (int)$this->arParams['LINE']);
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
				$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_SESSION_HAS_EXPIRED");
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult["NAME"] = Connector::getNameConnectorReal($this->connector);

		$this->arResult["URL"]["INDEX"] = $APPLICATION->GetCurPageParam($this->pageId . "=index", array($this->pageId, "action"));
		$this->arResult["URL"]["MASTER_NEW"] = $APPLICATION->GetCurPageParam($this->pageId . "=master_new", array($this->pageId, "action"));
		$this->arResult["URL"]["MASTER"] = $APPLICATION->GetCurPageParam($this->pageId . "=master", array($this->pageId, "action"));
		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", array($this->pageId, "action"));
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "action"));
		$this->arResult["URL"]["SIMPLE_FORM_EDIT"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "action"));

		if($this->arResult["ACTIVE_STATUS"])
		{
			if(!empty($this->arResult["PAGE"]) || !empty($this->arParams['MOBILE']))
			{
				$settings = $this->connectorOutput->readSettings();

				$result = $settings->getResult();

				foreach ($this->listOptions as $value)
				{
					if(empty($this->arResult["FORM"][$value]))
					{
						if(empty($result[$value]))
						{
							$this->arResult["FORM"][$value] = $result[$value];
						}
						else
						{
							$this->arResult["SAVE_STATUS"] = true;
							$this->arResult["placeholder"][$value] = true;
						}
					}
				}
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
					$infoConnect = $this->connectorOutput->infoConnect();
					if($infoConnect->isSuccess())
					{
						$infoConnectData = $infoConnect->getData();

						$this->arResult["INFO_CONNECTION"] = array(
							'ID' => $infoConnectData["id"],
							'URL' => $infoConnectData["url"],
							'URL_OTO' => $infoConnectData["url_im"],
							'NAME' => $infoConnectData["name"]
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
				ShowError(Loc::getMessage("IMCONNECTOR_COMPONENT_VIBER_NO_ACTIVE_CONNECTOR"));
			}
		}
	}
};