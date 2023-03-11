<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DI\ServiceLocator;

use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Connector;


class ImConnectorNetwork extends \CBitrixComponent
{
	private $cacheId;

	private $connector = 'network';
	private $error = [];
	private $messages = [];
	/** @var Output */
	private $connectorOutput;
	/** @var Status */
	private $status;

	protected $pageId = 'page_nw';

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 * @throws LoaderException
	 */
	protected function checkModules(): bool
	{
		if (Loader::includeModule('imconnector') && Loader::includeModule('imopenlines'))
		{
			return true;
		}
		else
		{
			if(!Loader::includeModule('imconnector'))
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_MODULE_IMCONNECTOR_NOT_INSTALLED'));
			}
			if(!Loader::includeModule('imopenlines'))
			{
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_NETWORK_MODULE_IMOPENLINES_NOT_INSTALLED'));
			}

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
		$this->arResult["DATA_STATUS"] = $this->status->getData();

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
					$resultRegister = $this->connectorOutput->register();

					if($resultRegister->isSuccess())
					{
						$this->status->setActive(true);
						$this->arResult["ACTIVE_STATUS"] = true;
						$this->status->setConnection(true);
						$this->arResult["CONNECTION_STATUS"] = true;
						$this->status->setRegister(true);
						$this->arResult["REGISTER_STATUS"] = true;
						$this->status->setData($resultRegister->getResult());
						$this->arResult["DATA_STATUS"] = $resultRegister->getResult();

						$this->arResult["STATUS"] = true;
					}
					else
					{
						$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_NETWORK_NO_ACTIVE");
					}

					//Reset cache
					$this->cleanCache();
				}

				if(!empty($this->arResult["ACTIVE_STATUS"]))
				{
					//If saving
					if($this->request[$this->connector. '_save'])
					{
						$this->arResult["FORM"] = array(
							'NAME' => $this->request['name'],
							'DESCRIPTION' => $this->request['description'],
							'WELCOME_MESSAGE' => $this->request['welcome_message'],
						);

						$dataUpdate = array(
							'NAME' => $this->request['name'],
							'DESC' => $this->request['description'],
							'FIRST_MESSAGE' => $this->request['welcome_message'],
						);

						if(empty($this->request['searchable']))
						{
							$dataUpdate['HIDDEN'] = 'Y';
							$this->arResult["FORM"]['SEARCHABLE'] = false;
						}
						else
						{
							$dataUpdate['HIDDEN'] = 'N';
							$this->arResult["FORM"]['SEARCHABLE'] = true;
						}


						//avatar
						if($this->request->get('avatar_del') == 'Y' && $this->arResult["DATA_STATUS"]['AVATAR'] > 0)
						{
							CFile::Delete($this->arResult["DATA_STATUS"]['AVATAR']);
							$dataUpdate['AVATAR'] = '';
							$this->arResult["DATA_STATUS"]['AVATAR'] = '';
						}

						$file = $this->request->getFile('avatar');

						if(!empty($file))
						{
							if (!$file['error'])
							{
								if($file["type"] != "image/png" && $file["type"] != "image/jpeg")
								{
									$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_NETWORK_FILE_IS_NOT_A_SUPPORTED_TYPE");
								}
								else
								{
									$file['MODULE_ID'] = "imopenlines";

									if($this->arResult["DATA_STATUS"]['AVATAR'] > 0)
									{
										$file['del'] = 'Y';
										$file['old_file'] = $this->arResult["DATA_STATUS"]['AVATAR'];
									}

									if($fileId = CFile::SaveFile($file, 'imopenlines/network'))
									{
										$dataUpdate['AVATAR'] = $fileId;
										$this->arResult["DATA_STATUS"]['AVATAR'] = $fileId;
									}
								}
							}
						}
						//end avatar

						$resultUpdate = $this->connectorOutput->update($dataUpdate);

						if($resultUpdate->isSuccess())
						{
							$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_NETWORK_OK_SAVE");
							$this->arResult["SAVE_STATUS"] = true;

							$this->status->setError(false);
							$this->arResult["ERROR_STATUS"] = false;

							$this->status->setConnection(true);
							$this->arResult["CONNECTION_STATUS"] = true;
							$this->status->setRegister(true);
							$this->arResult["REGISTER_STATUS"] = true;

							$dataStatus = array_merge($this->arResult["DATA_STATUS"], $dataUpdate);

							$this->status->setData($dataStatus);
							$this->arResult["DATA_STATUS"] = $dataStatus;
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_NETWORK_NO_SAVE");
							$this->arResult["SAVE_STATUS"] = false;

							$this->status->setConnection(false);
							$this->arResult["CONNECTION_STATUS"] = false;
							$this->status->setRegister(false);
							$this->arResult["REGISTER_STATUS"] = false;

							$this->arResult["STATUS"] = false;
						}

						//Reset cache
						$this->cleanCache();
					}

					if($this->request[$this->connector. '_del'])
					{
						$resultDelete = $this->connectorOutput->delete();

						if($resultDelete->isSuccess())
						{
							//$this->messages[] = Loc::getMessage("IMCONNECTOR_COMPONENT_SETTINGS_OK_DISABLE");
						}
						else
						{
							$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_SETTINGS_NO_DISABLE");
						}

						Status::delete($this->connector, (int)$this->arParams['LINE']);
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
				$this->error[] = Loc::getMessage("IMCONNECTOR_COMPONENT_NETWORK_SESSION_HAS_EXPIRED");
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

		if(!empty($this->arResult['DATA_STATUS']))
		{
			if(empty($this->arResult['DATA_STATUS']['CODE']))
			{
				$this->arResult['FORM']['CODE'] = '';
			}
			else
			{
				$this->arResult['FORM']['CODE'] = $this->arResult['DATA_STATUS']['CODE'];
			}

			$serviceLocator = ServiceLocator::getInstance();
			if($serviceLocator->has('ImConnector.toolsNetwork'))
			{
				/** @var \Bitrix\ImConnector\Tools\Connectors\Network $toolsNetwork */
				$toolsNetwork = $serviceLocator->get('ImConnector.toolsNetwork');
				$this->arResult['FORM']['URL'] = $toolsNetwork->getPublicLink($this->arResult['FORM']['CODE']);
			}

			if(empty($this->arResult['DATA_STATUS']['NAME']))
			{
				$this->arResult['FORM']['NAME'] = '';
			}
			else
			{
				$this->arResult['FORM']['NAME'] = $this->arResult['DATA_STATUS']['NAME'];
			}

			if(empty($this->arResult['DATA_STATUS']['DESC']))
			{
				$this->arResult['FORM']['DESCRIPTION'] = '';
			}
			else
			{
				$this->arResult['FORM']['DESCRIPTION'] = $this->arResult['DATA_STATUS']['DESC'];
			}

			if(empty($this->arResult['DATA_STATUS']['FIRST_MESSAGE']))
			{
				$this->arResult['FORM']['WELCOME_MESSAGE'] = '';
			}
			else
			{
				$this->arResult['FORM']['WELCOME_MESSAGE'] = $this->arResult['DATA_STATUS']['FIRST_MESSAGE'];
			}

			if(empty($this->arResult['DATA_STATUS']['AVATAR']))
			{
				$this->arResult['FORM']['AVATAR'] = NULL;
				$this->arResult['FORM']['AVATAR_LINK'] = NULL;
			}
			else
			{
				$this->arResult['FORM']['AVATAR'] = $this->arResult['DATA_STATUS']['AVATAR'];
				$this->arResult['FORM']['AVATAR_LINK'] = CFile::GetPath($this->arResult['DATA_STATUS']['AVATAR']);
			}


			if(
				empty($this->arResult['DATA_STATUS']['HIDDEN'])
				|| $this->arResult['DATA_STATUS']['HIDDEN'] === 'N')
			{
				$this->arResult['FORM']['SEARCHABLE'] = true;
			}
			else
			{
				$this->arResult['FORM']['SEARCHABLE'] = false;
			}

			$uri = new Uri($this->arResult['URL']['DELETE']);
			$uri->addParams(['action' => 'disconnect']);
			$this->arResult['URL']['DELETE'] = $uri->getUri();

			$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
			$uri->addParams(['action' => 'edit']);
			$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $uri->getUri();
		}

		if (!$this->arResult['STATUS'])
		{
			$uri = new Uri($this->arResult['URL']['SIMPLE_FORM']);
			$uri->addParams(['action' => 'connect']);
			$this->arResult['URL']['SIMPLE_FORM'] = $uri->getUri();
		}

		$this->arResult['CONNECTOR'] = $this->connector;
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
				ShowError(Loc::getMessage("IMCONNECTOR_COMPONENT_NETWORK_NO_ACTIVE_CONNECTOR"));
			}
		}
	}
};