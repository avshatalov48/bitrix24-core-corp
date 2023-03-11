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

class ImConnectorFBInstagram extends CBitrixComponent
{
	protected $cacheId;

	protected $connector = 'fbinstagram';
	protected $error = array();
	protected $messages = array();
	/** @var \Bitrix\ImConnector\Output */
	protected $connectorOutput;
	/**@var \Bitrix\ImConnector\Status */
	protected $status;

	protected $pageId = 'page_fbinst';

	protected $InvalidOauthAccessToken = false;

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
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_FBINSTAGRAM_MODULE_NOT_INSTALLED_MSGVER_1'));
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
		$region = Connector::getPortalRegion();
		$this->arResult['NEED_META_RESTRICTION_NOTE'] = Connector::needRestrictionNote($this->connector, $region, LANGUAGE_ID);

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);

		$this->arResult["PAGE"] = $this->request[$this->pageId];
	}

	protected function setStatus($status, $resetError = true)
	{
		$this->arResult["STATUS"] = $status;

		$this->status->setConnection((bool)$status);
		$this->arResult["CONNECTION_STATUS"] = $status;
		$this->status->setRegister((bool)$status);
		$this->arResult["REGISTER_STATUS"] = $status;

		if($resetError)
		{
			$this->status->setError(false);
			$this->arResult["ERROR_STATUS"] = false;
		}
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
					if($this->request[$this->connector. '_del_user'])
					{
						$delUser = $this->connectorOutput->delUserActive($this->request['user_id']);

						if($delUser->isSuccess())
						{
							$this->setStatus(false);

							$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_OK_DEL_USER");
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_NO_DEL_USER");
						}

						//Reset cache
						$this->cleanCache();
					}

					//If you remove the reference to the group
					if($this->request[$this->connector. '_del_page'])
					{
						$delPage = $this->connectorOutput->delPageActive($this->request['page_id']);

						if($delPage->isSuccess())
						{
							$this->setStatus(false);

							$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_OK_DEL_PAGE");
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_NO_DEL_PAGE");
						}

						//Reset cache
						$this->cleanCache();
					}

					//If you bind to the group
					if($this->request[$this->connector. '_authorization_page'])
					{
						$authorizationPage = $this->connectorOutput->authorizationPage($this->request['page_id']);

						if($authorizationPage->isSuccess())
						{
							$this->setStatus(true);

							$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_OK_AUTHORIZATION_PAGE");
						}
						else
						{
							$this->setStatus(false);

							$errorsAuthorizationPage = $authorizationPage->getErrorMessages();

							if(!empty($errorsAuthorizationPage))
							{
								$errorsAuthorizationPage = array_filter($errorsAuthorizationPage);
							}

							if (!empty($errorsAuthorizationPage))
							{
								$this->error = array_merge($this->error, $errorsAuthorizationPage);
							}
							else
							{
								$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_NO_AUTHORIZATION_PAGE");
							}
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
				$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_SESSION_HAS_EXPIRED");
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult["NAME"] = Connector::getNameConnectorReal($this->connector);

		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", array($this->pageId, "open_block", "action"));
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "open_block", "action"));
		$this->arResult["URL"]["SIMPLE_FORM_EDIT"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "open_block", "action"));

		$this->arResult["FORM"]["STEP"] = 1;

		if($this->arResult["ACTIVE_STATUS"])
		{
			//Reset cache
			if(!empty($this->arResult["PAGE"]))
				$this->cleanCache();

			$cache = Cache::createInstance();

			if($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
			{
				$this->arResult["FORM"] = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$uri = new Uri(Library::getCurrentUri());
				$uri->addParams(array('reload' => 'Y', 'ajaxid' => $this->arParams['AJAX_ID'], $this->pageId => 'simple_form'));

				$infoOAuth = $this->connectorOutput->getAuthorizationInformation(urlencode($uri->getUri()));

				if($infoOAuth->isSuccess())
				{
					$this->arResult["FORM"] = $infoOAuth->getData();

					if(!empty($this->arResult["FORM"]["PAGE"]))
					{
						$this->arResult["FORM"]["STEP"] = 3;

						$this->setStatus(true);
					}
					elseif(!empty($this->arResult["FORM"]["PAGES"]))
					{
						$this->arResult["FORM"]["STEP"] = 2;

						$this->setStatus(false, false);
					}
					elseif(!empty($this->arResult["FORM"]["USER"]))
					{
						$this->arResult["FORM"]["STEP"] = 1;

						$this->setStatus(false, false);
					}

					if(!empty($this->arResult["FORM"]["GROUP_DEL"]))
					{
						$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_REMOVED_REFERENCE_TO_PAGE");
					}

					$cache->endDataCache($this->arResult["FORM"]);
				}
				else
				{
					foreach ($infoOAuth->getErrorCollection() as $error)
					{
						if($error->getCode() == Library::ERROR_CONNECTOR_MESSENGER_INVALID_OAUTH_ACCESS_TOKEN)
							$InvalidOauthAccessToken = true;
					}

					if(!empty($InvalidOauthAccessToken ))
					{
						$this->arResult["FORM"] = $infoOAuth->getData();
						$this->arResult["FORM"]["STEP"] = 1;
						$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_INVALID_OAUTH_ACCESS_TOKEN");
					}
					else
					{
						$this->arResult["FORM"] = array();
						$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_ERROR_REQUEST_INFORMATION_FROM_SERVER") . '<br>' . Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_REPEATING_ERROR");
					}

					$cache->abortDataCache();
				}
			}

			//Analytic tags start
			if($this->arResult["FORM"]["STEP"] == 3)
			{
				$uri = new Uri($this->arResult["URL"]["DELETE"]);
				$uri->addParams(array('action' => 'disconnect'));
				$this->arResult["URL"]["DELETE"] = $uri->getUri();

				$uri = new Uri($this->arResult["URL"]["SIMPLE_FORM"]);
				$uri->addParams(array('action' => 'disconnect'));
				$this->arResult["URL"]["SIMPLE_FORM"] = $uri->getUri();

				if ($this->request->get($this->pageId))
				{
					$uri = new Uri($this->arResult["URL"]["SIMPLE_FORM_EDIT"]);
					$uri->addParams(array('action' => 'edit'));
					$this->arResult["URL"]["SIMPLE_FORM_EDIT"] = $uri->getUri();
				}
			}
			elseif ($this->arResult["FORM"]["STEP"] == 2)
			{
				$uri = new Uri($this->arResult["URL"]["SIMPLE_FORM"]);
				$uri->addParams(array('action' => 'connect'));
				$this->arResult["URL"]["SIMPLE_FORM"] = $uri->getUri();
			}
			//Analytic tags end
		}

		$this->arResult["CONNECTOR"] = $this->connector;
	}

	public function executeComponent()
	{
		//$this->includeComponentLang('class.php');
		Loc::loadMessages(__FILE__);

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
				ShowError(Loc::getMessage("IMCONNECTOR_COMPONENT_FBINSTAGRAM_NO_ACTIVE_CONNECTOR"));

				return false;
			}
		}
	}
};