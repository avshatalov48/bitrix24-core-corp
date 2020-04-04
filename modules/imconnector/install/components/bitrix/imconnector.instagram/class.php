<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Web\Uri,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector;

class ImConnectorInstagram extends \CBitrixComponent
{
	private $cacheId;

	protected $connector = 'instagram';
	protected $error = array();
	protected $messages = array();
	/** @var \Bitrix\ImConnector\Output */
	protected $connectorOutput;
	/**@var \Bitrix\ImConnector\Status */
	protected $status;

	protected $pageId = 'page_im';

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
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_INSTAGRAM_MODULE_NOT_INSTALLED'));
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

		$this->cacheId = serialize(array($this->connector, $this->arParams['LINE']));

		$this->arResult["PAGE"] = $this->request[$this->pageId];
	}

	protected function setStatus($status)
	{
		$this->arResult["STATUS"] = $status;

		$this->status->setConnection($status);
		$this->arResult["CONNECTION_STATUS"] = $status;
		$this->status->setRegister($status);
		$this->arResult["REGISTER_STATUS"] = $status;

		$this->status->setError(false);
		$this->arResult["ERROR_STATUS"] = false;
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache()
	{
		$cache = Cache::createInstance();
		$cache->clean($this->cacheId, Library::CACHE_DIR_COMPONENT);
		$cache->clean($this->arParams['LINE'], Library::CACHE_DIR_INFO_CONNECTORS_LINE);
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
					$this->status->setActive(true);
					$this->arResult["ACTIVE_STATUS"] = true;

					//Reset cache
					$this->cleanCache();
				}

				if(!empty($this->arResult["ACTIVE_STATUS"]))
				{
					//If you remove the reference to the user
					if ($this->request[$this->connector . '_del_user'])
					{
						$delUser = $this->connectorOutput->delUserOAuth($this->request['user_id']);

						if ($delUser->isSuccess())
						{
							$this->setStatus(false);

							$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_INSTAGRAM_OK_DEL_USER");
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_INSTAGRAM_NO_DEL_USER");
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
				$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_INSTAGRAM_SESSION_HAS_EXPIRED");
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult["NAME"] = Connector::getNameConnectorReal($this->connector);

		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", array($this->pageId, "action"));
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "action"));

		$this->arResult["FORM"]["STEP"] = 1;

		if($this->arResult["ACTIVE_STATUS"])
		{
			//Reset cache
			if(!empty($this->arResult["PAGE"]))
				$this->cleanCache();

			$cache = Cache::createInstance();
			if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
			{
				$this->arResult["FORM"] = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$uri = new Uri(Library::getCurrentUri());
				$uri->addParams(array('reload' => 'Y', 'ajaxid' => $this->arParams['AJAX_ID'], $this->pageId => 'simple_form', 'action' => 'connect'));

				//TODO: Double url encoding, as In contact when you return decode once.
				$infoOAuth = $this->connectorOutput->getAuthorizationInformation(urlencode(urlencode($uri->getUri())));

				if($infoOAuth->isSuccess())
				{
					$this->arResult["FORM"] = $infoOAuth->getData();

					if(!empty($this->arResult["FORM"]["USER"]["INFO"]))
					{
						$this->arResult["FORM"]["STEP"] = 2;

						$this->setStatus(true);
					}
					elseif(!empty($this->arResult["FORM"]["USER"]))
					{
						$this->arResult["FORM"]["STEP"] = 1;

						$this->setStatus(false);
					}

					$cache->endDataCache($this->arResult["FORM"]);
				}
				else
				{
					$this->arResult["FORM"] = array();
					$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_INSTAGRAM_ERROR_REQUEST_INFORMATION_FROM_SERVER");
					$cache->abortDataCache();
				}
			}

			if ($this->arResult["FORM"]["STEP"] == 2)
			{
				$uri = new Uri($this->arResult["URL"]["DELETE"]);
				$uri->addParams(array('action' => 'disconnect'));
				$this->arResult["URL"]["DELETE"] = $uri->getUri();
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
				ShowError(Loc::getMessage("IMCONNECTOR_COMPONENT_INSTAGRAM_NO_ACTIVE_CONNECTOR"));

				return false;
			}
		}
	}
};