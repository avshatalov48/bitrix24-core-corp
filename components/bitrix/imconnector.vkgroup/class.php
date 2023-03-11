<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Localization\Loc;

use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

/**
 * Class ImConnectorVkgroup
 */
class ImConnectorVkgroup extends \CBitrixComponent
{
	private $cacheId;

	protected $connector = 'vkgroup';
	protected $error = [];
	protected $messages = [];
	/** @var Output */
	protected $connectorOutput;
	/**@var Status */
	protected $status;

	protected $pageId = 'page_vkg';

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

		ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_MODULE_NOT_INSTALLED_MSGVER_1'));
		return false;
	}


	protected function initialization(): void
	{
		$this->connectorOutput = new Output($this->connector, (int)$this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$this->arResult['STATUS'] = $this->status->isStatus();
		$this->arResult['ACTIVE_STATUS'] = $this->status->getActive();
		$this->arResult['CONNECTION_STATUS'] = $this->status->getConnection();
		$this->arResult['REGISTER_STATUS'] = $this->status->getRegister();
		$this->arResult['ERROR_STATUS'] = $this->status->getError();
		$this->arResult['DATA_STATUS'] = $this->status->getData();

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);

		$this->arResult['PAGE'] = $this->request[$this->pageId];
		$this->arResult['GROUP_ORDERS'] = $this->request['group_orders'] === 'Y';
	}

	/**
	 * @param $status
	 * @param $resetError
	 */
	protected function setStatus($status, $resetError = true): void
	{
		$this->arResult['STATUS'] = $status;

		$this->status->setConnection((bool)$status);
		$this->arResult['CONNECTION_STATUS'] = $status;
		$this->status->setRegister((bool)$status);
		$this->arResult['REGISTER_STATUS'] = $status;

		if($resetError)
		{
			$this->status->setError(false);
			$this->arResult['ERROR_STATUS'] = false;
		}
	}

	/**
	 * @param $status
	 */
	protected function setDataStatus($status): void
	{
		$data = [
			'get_order_messages' => $status ? 'Y' : 'N'
		];
		$this->status->setData($data);
		$this->arResult['DATA_STATUS'] = $data;
	}

	/**
	 * Return url without group orders parameter
	 *
	 * @param $url
	 *
	 * @return string
	 */
	protected function getOriginalConnectorUrl($url): string
	{
		$uri = new Uri($url);
		$uri->deleteParams(['group_orders']);
		$uri->addParams(
			[
				'sessid' => bitrix_sessid(),
				$this->connector. '_active' => 'Y',
				$this->connector. '_form' => 'Y'
			]
		);

		return $uri->getUri();
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache(): void
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	public function saveForm(): void
	{
		//If been sent the current form
		if (
			$this->request->isPost()
			&&
			!empty($this->request[$this->connector. '_form'])
		)
		{
			//If the session actual
			if (check_bitrix_sessid())
			{
				//Activation
				if (
					$this->request[$this->connector. '_active']
					&& empty($this->arResult['ACTIVE_STATUS'])
				)
				{
					$this->status->setActive(true);
					$this->arResult['ACTIVE_STATUS'] = true;

					//Reset cache
					$this->cleanCache();
				}

				if (!empty($this->arResult['ACTIVE_STATUS']))
				{
					//If you remove the reference to the user
					if ($this->request[$this->connector . '_del_user'])
					{
						$delUser = $this->connectorOutput->delUserOAuth($this->request['user_id']);

						if ($delUser->isSuccess())
						{
							$this->setStatus(false);
							$this->connectorOutput->setMessageReplyReception(false);
							$this->setDataStatus(false);

							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_OK_DEL_USER');
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_NO_DEL_USER');
						}

						//Reset cache
						$this->cleanCache();
					}

					//If you remove the reference to the group
					if ($this->request[$this->connector . '_del_group'])
					{
						$delGroup = $this->connectorOutput->delGroupOAuth($this->request['group_id']);

						if ($delGroup->isSuccess())
						{
							$this->setStatus(false);
							$this->connectorOutput->setMessageReplyReception(false);
							$this->setDataStatus(false);

							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_OK_DEL_ENTITY');
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_NO_DEL_ENTITY');
						}

						//Reset cache
						$this->cleanCache();
					}

					if ($this->request[$this->connector. '_del'])
					{
						$rawDelete = $this->connectorOutput->deleteConnector();

						if ($rawDelete->isSuccess())
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

					if ($this->request[$this->connector. '_save_orders'])
					{
						$isActivation = $this->request['get_order_messages'] === 'Y';
						$saveResult = $this->connectorOutput->setMessageReplyReception($isActivation);

						if ($saveResult->isSuccess())
						{
							$this->setDataStatus($isActivation);

							$messageCode = $isActivation ? 'IMCONNECTOR_COMPONENT_VKGROUP_ORDER_OK_ADD_ENTITY' : 'IMCONNECTOR_COMPONENT_VKGROUP_OK_DEL_ENTITY';
							$this->messages[] = Loc::getMessage($messageCode);
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_NO_DEL_ENTITY');
						}

						$this->cleanCache();
					}
				}
			}
			else
			{
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_SESSION_HAS_EXPIRED');
			}
		}
	}

	public function constructionForm(): void
	{
		global $APPLICATION;

		$this->arResult['NAME'] = Connector::getNameConnectorReal($this->connector);

		$this->arResult['URL']['DELETE'] = $APPLICATION->GetCurPageParam('', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['SIMPLE_FORM'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['ORIGINAL_FORM'] = $this->getOriginalConnectorUrl($this->arResult['URL']['SIMPLE_FORM']);
		$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId, 'open_block', 'action']);

		$this->arResult['FORM']['STEP'] = 1;

		if($this->arResult['ACTIVE_STATUS'])
		{
			//Reset cache
			if (!empty($this->arResult['PAGE']))
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
				$params = ['reload' => 'Y', 'ajaxid' => $this->arParams['AJAX_ID'], $this->pageId => 'simple_form'];

				if ($this->arResult['STATUS'])
				{
					$params['action'] = 'edit';
				}

				$uri->addParams($params);

				//TODO: Double url encoding, as In contact when you return decode once.
				$infoOAuth = $this->connectorOutput->getAuthorizationInformation(urlencode(urlencode($uri->getUri())));
				if ($infoOAuth->isSuccess())
				{
					$this->arResult['FORM'] = $infoOAuth->getData();

					if (!empty($this->arResult['FORM']['GROUP']))
					{
						$this->arResult['FORM']['STEP'] = 3;

						$this->setStatus(true);
					}
					elseif (!empty($this->arResult['FORM']['GROUPS']))
					{
						//analytic tags adding
						$this->arResult['FORM']['GROUPS'] = $this->setGroupsUriAction($this->arResult['FORM']['GROUPS'], 'connect');
						$this->arResult['FORM']['STEP'] = 2;

						$this->setStatus(false, false);
					}
					elseif (!empty($this->arResult['FORM']['USER']))
					{
						$this->arResult['FORM']['STEP'] = 1;

						$this->setStatus(false, false);
					}

					if (!empty($this->arResult['FORM']['GROUP_DEL']))
					{
						$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_REMOVED_REFERENCE_TO_ENTITY');
					}

					$cache->endDataCache($this->arResult['FORM']);
				}
				else
				{
					$this->arResult['FORM'] = [];
					$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_ERROR_REQUEST_INFORMATION_FROM_SERVER');
					$cache->abortDataCache();
				}
			}

			//Analytic tags start
			if ((int)$this->arResult['FORM']['STEP'] === 3)
			{
				$uri = new Uri($this->arResult['URL']['DELETE']);
				$uri->addParams(['action' => 'disconnect']);
				$this->arResult['URL']['DELETE'] = $uri->getUri();

				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM']);
				$uri->addParams(['action' => 'disconnect']);
				$this->arResult['URL']['SIMPLE_FORM'] = $uri->getUri();

				//condition for vk orders virtual connector
				if ($this->request->get('group_orders') === 'Y')
				{
					$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
					$uri->addParams(['action' => 'connect']);
					$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $uri->getUri();
				}
			}
			elseif ((int)$this->arResult['FORM']['STEP'] === 2)
			{
				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM']);
				$uri->addParams(['action' => 'connect']);
				$this->arResult['URL']['SIMPLE_FORM'] = $uri->getUri();
			}
			//Analytic tags end
		}

		$this->arResult['CONNECTOR'] = $this->connector;
	}

	/**
	 * @param $groupList
	 * @param $action
	 */
	protected function setGroupsUriAction($groupList, $action)
	{
		foreach ($groupList as &$group)
		{
			if (!empty($group['URI']))
			{
				$uri = new Uri($group['URI']);
				$query = $uri->getQuery();
				$currentParams = [];
				parse_str($query, $currentParams);
				$state = urldecode($currentParams['state']);
				$stateUri = new Uri($state);
				$stateUri->deleteParams(['action']);
				$stateUri->addParams(['action' => $action]);
				$state = urlencode($stateUri->getUri());
				$uri->deleteParams(['state']);
				$uri->addParams(['state' => $state]);
				$group['URI'] = $uri->getUri();
			}
		}

		return $groupList;
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
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_VKGROUP_NO_ACTIVE_CONNECTOR'));

				return false;
			}
		}
	}
};