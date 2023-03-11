<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use \Bitrix\Main\Loader,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Web\Uri;
use \Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector;

class ImConnectorOlx extends CBitrixComponent
{
	private $cacheId;
	private $connector = 'olx';
	private $error = array();
	private $messages = array();
	/** @var \Bitrix\ImConnector\Output */
	private $connectorOutput;
	/** @var \Bitrix\ImConnector\Status */
	private $status;

	protected $pageId = 'page_olx';

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
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_MODULE_NOT_INSTALLED_MSGVER_1'));
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

		$this->arResult['STATUS'] = $this->status->isStatus();
		$this->arResult['ACTIVE_STATUS'] = $this->status->getActive();
		$this->arResult['CONNECTION_STATUS'] = $this->status->getConnection();
		$this->arResult['REGISTER_STATUS'] = $this->status->getRegister();
		$this->arResult['ERROR_STATUS'] = $this->status->getError();

		if (Loader::includeModule('bitrix24'))
		{
			$currentZone = \CBitrix24::getPortalZone();
		}
		else
		{
			$currentZone = 'pl';
		}

		$this->arResult['OLX_CURRENT_ZONE'] = (in_array($currentZone, ['pl', 'ua'], true) ? $currentZone : 'pl');

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);
	}

	protected function setStatus($status, $resetError = true): void
	{
		$this->arResult['STATUS'] = $status;

		$this->status->setConnection((bool)$status);
		$this->arResult['CONNECTION_STATUS'] = $status;
		$this->status->setRegister((bool)$status);
		$this->arResult['REGISTER_STATUS'] = $status;

		if ($resetError)
		{
			$this->status->setError(false);
			$this->arResult['ERROR_STATUS'] = false;
		}
	}

	public function saveForm(): void
	{
		//If been sent the current form
		if ($this->request->isPost() && !empty($this->request[$this->connector . '_form']) && check_bitrix_sessid())
		{
			//Activation
			if($this->request[$this->connector. '_active'] && empty($this->arResult['ACTIVE_STATUS']))
			{
				$this->status->setActive(true);
				$this->arResult['ACTIVE_STATUS'] = true;

				//Zone
				$this->arResult['CONNECTOR_ZONE'] = $this->request['zone'];

				//Reset cache
				$this->cleanCache();
			}

			if (!empty($this->arResult['ACTIVE_STATUS']))
			{
				if ($this->request[$this->connector. '_del'])
				{
					$rawDelete = $this->connectorOutput->deleteConnector();

					if($rawDelete->isSuccess())
					{
						Status::delete($this->connector, (int)$this->arParams['LINE']);
						$this->arResult['STATUS'] = false;
						$this->arResult['ACTIVE_STATUS'] = false;
						$this->arResult['CONNECTION_STATUS'] = false;
						$this->arResult['REGISTER_STATUS'] = false;
						$this->arResult['ERROR_STATUS'] = false;
						$this->arResult['DATA_STATUS'] = false;
						$this->arResult['PAGE'] = '';
					}
					else
					{
						$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_NO_DISABLE');
					}

					//Reset cache
					$this->cleanCache();
				}
			}
		}
	}

	public function constructionForm(): void
	{
		global $APPLICATION;

		$this->arResult["NAME"] = Connector::getNameConnectorReal($this->connector);

		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", array($this->pageId, "open_block", "action"));
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId, "open_block", "action"));

		if ($this->arResult["ACTIVE_STATUS"])
		{
			//Reset cache
			if(!empty($this->arResult['PAGE']))
			{
				$this->cleanCache();
			}

			$cache = Cache::createInstance();
			if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
			{
				$this->arResult['FORM'] = $cache->getVars();
			}
			elseif ($cache->startDataCache())
			{
				$uri = new Uri(Library::getCurrentUri());
				$params = array('reload' => 'Y', 'ajaxid' => $this->arParams['AJAX_ID'], $this->pageId => 'simple_form');

				$uri->addParams($params);

				$infoOAuth = $this->connectorOutput->getAuthorizationInformation(urlencode($uri->getUri()), $this->arResult['CONNECTOR_ZONE']);
				if ($infoOAuth->isSuccess())
				{
					$this->arResult['FORM'] = $infoOAuth->getResult();

					if (!empty($this->arResult['FORM']['TOKEN']))
					{
						$registerResult = $this->connectorOutput->register();
						if ($registerResult->isSuccess())
						{
							$this->setStatus(true);
							\Bitrix\ImConnector\Connectors\Olx::addAgent();
						}
					}
					$cache->endDataCache($this->arResult['FORM']);
				}
				else
				{
					$this->arResult['FORM'] = [];
					$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_ERROR_REQUEST_INFORMATION_FROM_SERVER');
					$cache->abortDataCache();
				}
			}
		}

		$this->arResult['CONNECTOR'] = $this->connector;
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

				$this->arResult['PAGE'] = $this->request[$this->pageId];
				$this->saveForm();

				$this->constructionForm();

				if (!empty($this->error))
				{
					$this->arResult['error'] = $this->error;
				}

				if (!empty($this->messages))
				{
					$this->arResult['messages'] = $this->messages;
				}

				$this->includeComponentTemplate();
			}
			else
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_OLX_NO_ACTIVE_CONNECTOR'));
			}
		}
	}


}