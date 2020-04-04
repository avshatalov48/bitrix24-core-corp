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

class ImConnectorYandex extends \CBitrixComponent
{
	private $cacheId;

	private $connector = 'yandex';
	private $error = array();
	private $messages = array();
	/** @var \Bitrix\ImConnector\Output */
	private $connectorOutput;
	/** @var \Bitrix\ImConnector\Status */
	private $status;

	protected $pageId = 'page_ya';

	private $listOptions = array('api_token');

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
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_YANDEX_MODULE_NOT_INSTALLED'));
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
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache()
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	protected function setStatus($status)
	{
		$this->arResult["STATUS"] = $status;

		$this->status->setActive($status);
		$this->arResult["ACTIVE_STATUS"] = $status;

		$this->status->setConnection($status);
		$this->arResult["CONNECTION_STATUS"] = $status;

		$this->status->setRegister($status);
		$this->arResult["REGISTER_STATUS"] = $status;

		$this->status->setError(false);
		$this->arResult["ERROR_STATUS"] = false;
	}

	public function saveForm()
	{
		//If been sent the current form
		if ($this->request->isPost() && !empty($this->request[$this->connector. '_form']))
		{
			//If the session actual
			if(check_bitrix_sessid())
			{
				//Activation
				if($this->request[$this->connector. '_active'] && empty($this->arResult["ACTIVE_STATUS"]))
				{
					$error = true;
					$testConnect = $this->connectorOutput->testConnect();

					if($testConnect->isSuccess())
					{
						$register = $this->connectorOutput->register();

						if($register->isSuccess())
						{
							$error = false;

							$this->setStatus(true);
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_YANDEX_NO_REGISTER");
						}
					}
					else
					{
						$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_YANDEX_NO_CONNECT");
					}

					if($error === true)
					{
						$this->setStatus(false);
					}

					//Reset cache
					$this->cleanCache();
				}

				if(!empty($this->arResult["ACTIVE_STATUS"]))
				{
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
				$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_YANDEX_SESSION_HAS_EXPIRED");
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult["NAME"] = Connector::getNameConnectorReal($this->connector);

		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", array($this->pageId, "open_block", "action"));
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "open_block", "action"));

		if($this->arResult["ACTIVE_STATUS"] && $this->arResult["STATUS"])
		{
			if(!empty($this->arResult["PAGE"]))
			{
				$cache = Cache::createInstance();

				if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
				{
					$this->arResult["CHAT_ID"] = $cache->getVars();
				}
				elseif ($cache->startDataCache())
				{
					$chatIdRaw = $this->connectorOutput->getChatId();
					if($chatIdRaw->isSuccess())
					{
						$this->arResult["CHAT_ID"] = $chatIdRaw->getResult();

						$cache->endDataCache($this->arResult["CHAT_ID"]);
					}
					else
					{
						$this->arResult["INFO_CONNECTION"] = '';
						$cache->abortDataCache();
					}
				}
			}

			$uri = new Uri($this->arResult["URL"]["DELETE"]);
			$uri->addParams(array('action' => 'disconnect'));
			$this->arResult["URL"]["DELETE"] = $uri->getUri();
		}
		else
		{
			$uri = new Uri($this->arResult["URL"]["SIMPLE_FORM"]);
			$uri->addParams(array('action' => 'connect'));
			$this->arResult["URL"]["SIMPLE_FORM"] = $uri->getUri();
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
				ShowError(Loc::getMessage("IMCONNECTOR_COMPONENT_YANDEX_NO_ACTIVE_CONNECTOR"));
			}
		}
	}
};