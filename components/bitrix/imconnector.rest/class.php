<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector;

class ImRestConnector extends \CBitrixComponent
{
	private $cacheId;

	protected $connector;
	/**@var \Bitrix\ImConnector\Status */
	protected $status;

	protected $pageId;

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
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_REST_MODULE_NOT_INSTALLED'));
			return false;
		}
	}

	protected function initialization()
	{
		$this->connector = $this->arParams['CONNECTOR'];
		$this->pageId = 'page_rest_' . $this->arParams['CONNECTOR'];

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

					if($this->request[$this->connector. '_del'])
					{
						Status::delete($this->connector, $this->arParams['LINE']);
						$this->arResult["STATUS"] = false;
						$this->arResult["ACTIVE_STATUS"] = false;
						$this->arResult["CONNECTION_STATUS"] = false;
						$this->arResult["REGISTER_STATUS"] = false;
						$this->arResult["ERROR_STATUS"] = false;
						$this->arResult["PAGE"] = '';

						//Reset cache
						$this->cleanCache();
					}
				}
			}
			else
			{
				$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_REST_SESSION_HAS_EXPIRED");
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult["NAME"] = Connector::getNameConnectorReal($this->connector);

		$this->arResult["URL"]["DELETE"] = $APPLICATION->GetCurPageParam("", array($this->pageId));
		$this->arResult["URL"]["SIMPLE_FORM"] = $APPLICATION->GetCurPageParam($this->pageId . "=simple_form", array($this->pageId));

		$this->arResult["CONNECTOR"] = $this->connector;

		$raw = \Bitrix\ImConnector\Model\CustomConnectorsTable::getList(array(
			"filter"=>array("=ID_CONNECTOR"=>$this->arResult["CONNECTOR"]),
			"cache"=>array("ttl"=>3600, "cache_joins"=>true))
		);

		$this->arResult["INFO_CONNECTOR"] = $raw->fetch();
		$this->arResult["APPLICATION_CURRENT"] = \Bitrix\Rest\PlacementTable::getHandlersList(\Bitrix\ImConnector\Rest\Helper::PLACEMENT_SETTING_CONNECTOR);;

		$this->arResult["PLACEMENT_OPTIONS"] = array(
			'CONNECTOR' => $this->connector,
			'LINE' => $this->arParams['LINE'],
			'STATUS' => $this->arResult['STATUS'],
			'ACTIVE_STATUS' => $this->arResult['ACTIVE_STATUS'],
			'CONNECTION_STATUS' => $this->arResult['CONNECTION_STATUS'],
			'REGISTER_STATUS' => $this->arResult['REGISTER_STATUS'],
			'ERROR_STATUS' => $this->arResult['ERROR_STATUS']

		);
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if($this->checkModules())
		{
			if(Connector::isConnector($this->arParams['CONNECTOR']))
			{
				$this->initialization();

				$this->saveForm();

				$this->constructionForm();

				$this->includeComponentTemplate();
			}
			else
			{
				ShowError(Loc::getMessage("IMCONNECTOR_COMPONENT_REST_NO_ACTIVE_CONNECTOR"));

				return false;
			}
		}
	}
};