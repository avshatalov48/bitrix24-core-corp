<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

class ImConnectorBaseConnector extends \CBitrixComponent
{
	private $cacheId;

	protected $connector = 'baseconnector';
	protected $error = array();
	protected $messages = array();
	/**@var \Bitrix\ImConnector\Status */
	protected $status;

	protected $pageId = 'page_baseconnector';

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
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_BASECONNECTOR_MODULE_NOT_INSTALLED_MSGVER_1'));
			return false;
		}
	}

	protected function initialization()
	{
		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$this->arResult['STATUS'] = $this->status->isStatus();
		$this->arResult['ACTIVE_STATUS'] = $this->status->getActive();
		$this->arResult['CONNECTION_STATUS'] = $this->status->getConnection();
		$this->arResult['REGISTER_STATUS'] = $this->status->getRegister();
		$this->arResult['ERROR_STATUS'] = $this->status->getError();

		$this->cacheId = serialize(array($this->connector, $this->arParams['LINE']));

		$this->arResult['PAGE'] = $this->request[$this->pageId];
	}

	protected function setStatus($status)
	{
		$this->arResult['STATUS'] = $status;

		$this->status->setConnection((bool)$status);
		$this->arResult['CONNECTION_STATUS'] = $status;
		$this->status->setRegister((bool)$status);
		$this->arResult['REGISTER_STATUS'] = $status;

		$this->status->setError(false);
		$this->arResult['ERROR_STATUS'] = false;
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
				if($this->request[$this->connector. '_active'] && empty($this->arResult['ACTIVE_STATUS']))
				{
					$this->status->setActive(true);
					$this->arResult['ACTIVE_STATUS'] = true;

					$this->arResult['CONNECTION_STATUS'] = true;
					$this->status->setConnection(true);
					$this->arResult['REGISTER_STATUS'] = true;
					$this->status->setRegister(true);

					$this->arResult['STATUS'] = true;
					//Reset cache
					$this->cleanCache();
				}

				if(!empty($this->arResult['ACTIVE_STATUS']))
				{

					if($this->request[$this->connector. '_del'])
					{
						Status::delete($this->connector, (int)$this->arParams['LINE']);
						$this->arResult['STATUS'] = false;
						$this->arResult['ACTIVE_STATUS'] = false;
						$this->arResult['CONNECTION_STATUS'] = false;
						$this->arResult['REGISTER_STATUS'] = false;
						$this->arResult['ERROR_STATUS'] = false;
						$this->arResult['PAGE'] = '';

						//Reset cache
						$this->cleanCache();
					}
				}
			}
			else
			{
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_BASECONNECTOR_SESSION_HAS_EXPIRED');
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult['NAME'] = Connector::getNameConnectorReal($this->connector);

		$this->arResult['URL']['DELETE'] = $APPLICATION->GetCurPageParam('', array($this->pageId));
		$this->arResult['URL']['SIMPLE_FORM'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId]);

		$this->arResult['FORM']['STEP'] = 1;

		if($this->arResult['ACTIVE_STATUS'])
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

				$cache->endDataCache($this->arResult['FORM']);
			}
		}

		$this->arResult['CONNECTOR'] = $this->connector;
	}

	/**
	 * @return false
	 */
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
				{
					$this->arResult['error'] = $this->error;
				}

				if(!empty($this->messages))
				{
					$this->arResult['messages'] = $this->messages;
				}

				$this->includeComponentTemplate();
			}
			else
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_BASECONNECTOR_NO_ACTIVE_CONNECTOR'));

				return false;
			}
		}
	}
}
