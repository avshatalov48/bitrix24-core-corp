<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use \Bitrix\Main\Loader,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector;

class ImConnectorWechat extends CBitrixComponent
{
	private $cacheId;
	private $connector = 'wechat';
	private $error = array();
	private $messages = array();
	/** @var \Bitrix\ImConnector\Output */
	private $connectorOutput;
	/** @var \Bitrix\ImConnector\Status */
	private $status;

	protected $pageId = 'page_wc';

	private $listOptions = [
		'app_id',
		'app_secret',
		'encrypt_key',
	];

	/**
	 * Check the connection of the necessary modules.
	 *
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
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_WECHAT_MODULE_NOT_INSTALLED_MSGVER_1'));
			return false;
		}
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache()
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	/**
	 * Initialize connector before starting actions
	 */
	protected function initialization()
	{
		$this->connectorOutput = new Output($this->connector, (int)$this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$this->arResult["STATUS"] = $this->status->isStatus();
		$this->arResult["ACTIVE_STATUS"] = $this->status->getActive();
		$this->arResult["CONNECTION_STATUS"] = $this->status->getConnection();
		$this->arResult["REGISTER_STATUS"] = $this->status->getRegister();
		$this->arResult["ERROR_STATUS"] = $this->status->getError();

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);
	}

	public function saveForm()
	{
		//If been sent the current form
		if ($this->request->isPost() && !empty($this->request[$this->connector. '_form']))
		{
			//If the session actual
			if (check_bitrix_sessid())
			{
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

						if(!empty($this->arResult["FORM"]))
						{
							if(empty($this->arResult["REGISTER_STATUS"]))
							{
								$this->connectorOutput->unregister();
							}

							$saved = $this->connectorOutput->saveSettings($this->arResult["FORM"]);

							if($saved->isSuccess())
							{
								$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_OK_SAVE");
								$this->arResult["SAVE_STATUS"] = true;

								$this->status->setError(false);
								$this->arResult["ERROR_STATUS"] = false;
							}
							else
							{
								$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_NO_SAVE");
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
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_NO_DATA_SAVE");
						}

						//Reset cache
						$this->cleanCache();

						if ($this->arResult["SAVE_STATUS"])
						{
							$testConnect = $this->connectorOutput->testConnect();

							if($testConnect->isSuccess())
							{
								$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_OK_CONNECT");

								$this->status->setConnection(true);
								$this->arResult["CONNECTION_STATUS"] = true;

								$this->status->setError(false);
								$this->arResult["ERROR_STATUS"] = false;
							}
							else
							{
								$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_NO_CONNECT");

								$testConnect->getResult();

								$this->status->setConnection(false);
								$this->arResult["CONNECTION_STATUS"] = false;

								$this->status->setRegister(false);
								$this->arResult["REGISTER_STATUS"] = false;

								$this->arResult["STATUS"] = false;
							}

							//Reset cache
							$this->cleanCache();

							if ($this->arResult["CONNECTION_STATUS"])
							{
								$register = $this->connectorOutput->register();

								if($register->isSuccess())
								{
									$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_OK_REGISTER");

									$this->status->setRegister(true);
									$this->arResult["REGISTER_STATUS"] = true;
									$this->arResult["STATUS"] = true;

									$this->status->setError(false);
									$this->arResult["ERROR_STATUS"] = false;
								}
								else
								{
									$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_NO_REGISTER");

									$this->status->setRegister(false);
									$this->arResult["REGISTER_STATUS"] = false;

									$this->arResult["STATUS"] = false;
								}

								//Reset cache
								$this->cleanCache();
							}
						}
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
							$this->arResult["PAGE"] = "";
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
				$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_SESSION_HAS_EXPIRED");
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult["NAME"] = Connector::getNameConnectorReal($this->connector);

		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", array($this->pageId, "open_block"));;
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "open_block"));

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
						if(empty($result["SETTINGS"][$value]))
						{
							$this->arResult["FORM"][$value] = $result["SETTINGS"][$value];
						}
						else
						{
							$this->arResult["SAVE_STATUS"] = true;
							$this->arResult["placeholder"][$value] = true;
						}
					}
				}

				if (!empty($result['URL_WEBHOOK']))
				{
					$this->arResult['URL_WEBHOOK'] = $result['URL_WEBHOOK'];
				}

				if (!empty($result['TOKEN']))
				{
					$this->arResult['TOKEN'] = $result['TOKEN'];
				}

				if (!empty($result['SERVER_IP_ADDRESS']))
				{
					$this->arResult['SERVER_IP_ADDRESS'] = $result['SERVER_IP_ADDRESS'];
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
						$this->arResult["INFO_CONNECTION"] =  array(
							"URL_IM" => $infoConnectData["url_im"]
						);

						$cache->endDataCache($this->arResult["INFO_CONNECTION"]);
					}
					else
					{
						$this->arResult["INFO_CONNECTION"] = array();
						$cache->abortDataCache();
					}
				}
			}
		}

		$this->arResult["CONNECTOR"] = $this->connector;
	}

	/**
	 * @return mixed|void
	 * @throws LoaderException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
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
				ShowError(Loc::getMessage("IMCONNECTOR_COMPONENT_WECHAT_NO_ACTIVE_CONNECTOR"));
			}
		}
	}
}