<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Loader,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\LoaderException,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Web\Uri;
use \Bitrix\ImConnector\Output,
	\Bitrix\ImConnector\Status,
	\Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\Connector;

class ImConnectorOk extends \CBitrixComponent
{
	private $cacheId;

	private $connector = 'ok';
	private $error = [];
	private $messages = [];
	/** @var \Bitrix\ImConnector\Output */
	private $connectorOutput;
	/** @var \Bitrix\ImConnector\Status */
	private $status;

	protected $pageId = 'page_ok';

	protected $listOptions = [
		'api_key',
		'group_name',
		'group_link'
	];

	protected $listRequiredOptions = [
		'group_name',
		'group_link'
	];

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules(): bool
	{
		$result = false;

		if (Loader::includeModule('imconnector'))
		{
			$result = true;
		}
		else
		{
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_OK_MODULE_NOT_INSTALLED_MSGVER_1'));
		}

		return $result;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function initialization(): void
	{
		$this->connectorOutput = new Output($this->connector, (int)$this->arParams['LINE']);

		$this->status = Status::getInstance($this->connector, (int)$this->arParams['LINE']);

		$this->arResult['STATUS'] = $this->status->isStatus();
		$this->arResult['ACTIVE_STATUS'] = $this->status->getActive();
		$this->arResult['CONNECTION_STATUS'] = $this->status->getConnection();
		$this->arResult['REGISTER_STATUS'] = $this->status->getRegister();
		$this->arResult['ERROR_STATUS'] = $this->status->getError();

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache()
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	/**
	 * @throws Exception
	 */
	public function saveForm()
	{
		//If been sent the current form
		if (
			$this->request->isPost() &&
			!empty($this->request[$this->connector. '_form'])
		)
		{
			//If the session actual
			if(check_bitrix_sessid())
			{
				//Activation bot
				if($this->request[$this->connector. '_active'] && empty($this->arResult['ACTIVE_STATUS']))
				{
					$this->status->setActive(true);
					$this->arResult['ACTIVE_STATUS'] = true;

					//Reset cache
					$this->cleanCache();
				}

				if(!empty($this->arResult['ACTIVE_STATUS']))
				{
					//If saving
					if($this->request[$this->connector. '_save'])
					{
						$requiredFieldsFilled = true;

						foreach ($this->listOptions as $value)
						{
							if(isset($this->request[$value]))
							{
								$this->arResult['FORM'][$value] = $this->request[$value];
							}

							if(
								empty($this->request[$value]) &&
								in_array($value, $this->listRequiredOptions, false)
							)
							{
								$requiredFieldsFilled = false;
							}
						}

						if(
							!empty($this->arResult['FORM']) &&
							$requiredFieldsFilled === true
						)
						{
							if (!empty($this->arResult['REGISTER_STATUS']))
							{
								$this->connectorOutput->unregister();
							}

							$saved = $this->connectorOutput->saveSettings($this->arResult['FORM']);

							if($saved-> isSuccess())
							{
								$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_OK_SAVE');
								$this->arResult['SAVE_STATUS'] = true;

								$this->status->setError(false);
								$this->arResult['ERROR_STATUS'] = false;
							}
							else
							{
								$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_NO_SAVE');
								$this->arResult['SAVE_STATUS'] = false;

								$this->status->setConnection(false);
								$this->arResult['CONNECTION_STATUS'] = false;
								$this->status->setRegister(false);
								$this->arResult['REGISTER_STATUS'] = false;

								$this->arResult['STATUS'] = false;
							}
						}
						elseif($requiredFieldsFilled !== true)
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_NO_GROUP_DATA_SAVE');
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_NO_DATA_SAVE');
						}

						//Reset cache
						$this->cleanCache();
					}

					//If the test connection or save
					if(
						(
							$this->request[$this->connector. '_save'] &&
							$this->arResult['SAVE_STATUS']
						) ||
						$this->request[$this->connector. '_tested']
					)
					{
						$testConnect = $this->connectorOutput->testConnect();

						if($testConnect->isSuccess())
						{
							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_OK_CONNECT');

							$this->status->setConnection(true);
							$this->arResult['CONNECTION_STATUS'] = true;

							$this->status->setError(false);
							$this->arResult['ERROR_STATUS'] = false;
						}
						else
						{
							if($testConnect->getErrorCollection()->getErrorByCode('CONNECTOR_ERROR_PARAM_SESSION_EXPIRED') !== null)
							{
								$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_ERROR_PARAM_SESSION_EXPIRED');
							}
							else
							{
								$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_NO_CONNECT');
							}

							$testConnect->getResult();

							$this->status->setConnection(false);
							$this->arResult['CONNECTION_STATUS'] = false;

							$this->status->setRegister(false);
							$this->arResult['REGISTER_STATUS'] = false;

							$this->arResult['STATUS'] = false;
						}

						//Reset cache
						$this->cleanCache();
					}

					//If the check or test connection or save
					if(
						(
							$this->request[$this->connector. '_register'] ||
							(
								$this->request[$this->connector. '_save'] &&
								$this->arResult['SAVE_STATUS']
							) ||
							$this->request[$this->connector. '_tested']
						) &&
						$this->arResult['CONNECTION_STATUS']
					)
					{
						$register = $this->connectorOutput->register();

						if($register->isSuccess())
						{
							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_OK_REGISTER');

							$this->status->setRegister(true);
							$this->arResult['REGISTER_STATUS'] = true;
							$this->arResult['STATUS'] = true;

							$this->status->setError(false);
							$this->arResult['ERROR_STATUS'] = false;
						}
						else
						{
							if($register->getErrorCollection()->getErrorByCode('CONNECTOR_ERROR_PARAM_SESSION_EXPIRED') !== null)
							{
								$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_ERROR_PARAM_SESSION_EXPIRED');
							}
							else
							{
								$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_NO_CONNECT');
							}

							$this->status->setRegister(false);
							$this->arResult['REGISTER_STATUS'] = false;

							$this->arResult['STATUS'] = false;
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
							$this->arResult['STATUS'] = false;
							$this->arResult['ACTIVE_STATUS'] = false;
							$this->arResult['CONNECTION_STATUS'] = false;
							$this->arResult['REGISTER_STATUS'] = false;
							$this->arResult['ERROR_STATUS'] = false;
							$this->arResult['PAGE'] = '';

							//$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_OK_DISABLE');
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
			else
			{
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_OK_SESSION_HAS_EXPIRED');
			}
		}
	}

	public function constructionForm()
	{
		global $APPLICATION;

		$this->arResult['NAME'] = Connector::getNameConnectorReal($this->connector);

		$this->arResult['URL']['DELETE'] = $APPLICATION->GetCurPageParam('', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['SIMPLE_FORM'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $APPLICATION->GetCurPageParam($this->pageId . '=simple_form', [$this->pageId, 'open_block', 'action']);

		if($this->arResult['ACTIVE_STATUS'])
		{
			if(!empty($this->arResult['PAGE']))
			{
				$settings = $this->connectorOutput->readSettings();

				$result = $settings->getResult();

				foreach ($this->listOptions as $value)
				{
					if (empty($this->arResult['FORM'][$value]))
					{
						if (empty($result[$value]))
						{
							$this->arResult['FORM'][$value] = $result[$value] ?? '';
						}
						else
						{
							$this->arResult['SAVE_STATUS'] = true;

							if ($result[$value] == '#HIDDEN#')
							{
								$this->arResult['placeholder'][$value] = true;
							}
							else
							{
								$this->arResult['FORM'][$value] = $result[$value];
							}
						}
					}
				}
			}

			if($this->arResult['STATUS'])
			{
				$cache = Cache::createInstance();

				if ($cache->initCache(Library::CACHE_TIME_COMPONENT, $this->cacheId, Library::CACHE_DIR_COMPONENT))
				{
					$this->arResult['INFO_CONNECTION'] = $cache->getVars();
				}
				elseif ($cache->startDataCache())
				{
					$infoConnect = $this->connectorOutput->infoConnect();
					if($infoConnect->isSuccess())
					{
						$infoConnectData = $infoConnect->getData();

						$this->arResult['INFO_CONNECTION'] = [
							'ID' => $infoConnectData['id'],
							'URL' => $infoConnectData['url'],
							'NAME' => $infoConnectData['name']
						];

						$cache->endDataCache($this->arResult['INFO_CONNECTION']);
					}
					else
					{
						$this->arResult['INFO_CONNECTION'] = [];
						$cache->abortDataCache();
					}
				}

				$uri = new Uri($this->arResult['URL']['DELETE']);
				$uri->addParams(['action' => 'disconnect']);
				$this->arResult['URL']['DELETE'] = $uri->getUri();

				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
				$uri->addParams(['action' => 'edit']);
				$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $uri->getUri();
			}
			else
			{
				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
				$uri->addParams(['action' => 'connect']);
				$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $uri->getUri();
			}
		}

		$this->arResult['CONNECTOR'] = $this->connector;
	}

	/**
	 * @return mixed|void
	 * @throws LoaderException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
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
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_OK_NO_ACTIVE_CONNECTOR'));
			}
		}
	}
};