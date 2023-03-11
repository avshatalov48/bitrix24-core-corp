<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Localization\Loc;
use Bitrix\ImConnector\Output;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Connector;

class ImConnectorWhatsappByEdna extends \CBitrixComponent
{
	private $cacheId;

	private $connector = Library::ID_EDNA_WHATSAPP_CONNECTOR;
	private $error = [];
	private $messages = [];
	/** @var \Bitrix\ImConnector\Provider\Messageservice\Output */
	private $connectorOutput;
	/** @var Status */
	private $status;

	protected $pageId = 'page_wabe';

	private $listOptions = ['sender_id', 'api_key'];

	/**
	 * Check the connection of the necessary modules.
	 * @return bool
	 */
	protected function checkModules(): bool
	{
		if (!Loader::includeModule('imconnector'))
		{
			ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_MODULE_NOT_INSTALLED_MSGVER_1'));

			return false;
		}

		return true;
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

		$this->arResult['HELPDESK_CODE'] = 'redirect=detail&code=14214014';

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);
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
		if ($this->request->isPost() && !empty($this->request[$this->connector.'_form']))
		{
			if (check_bitrix_sessid())
			{
				if ($this->request[$this->connector.'_active'] && empty($this->arResult['ACTIVE_STATUS']))
				{
					$this->status->setActive(true);
					$this->arResult['ACTIVE_STATUS'] = true;

					//Reset cache
					$this->cleanCache();
				}

				if (!empty($this->arResult['ACTIVE_STATUS']))
				{
					//If saving
					if ($this->request[$this->connector.'_save'])
					{
						foreach ($this->listOptions as $value)
						{
							if (!empty($this->request[$value]))
							{
								$this->arResult['FORM'][$value] = $this->request[$value];
							}
						}

						if (!empty($this->arResult['FORM']))
						{
							foreach ($this->arResult['FORM'] as $cell => $value)
							{
								if (!empty($value))
								{
									$value = trim(htmlspecialcharsbx($value));

									$this->arResult['FORM'][$cell] = $value;
								}
							}

							$saved = $this->connectorOutput->register($this->arResult['FORM']);

							if ($saved->isSuccess())
							{
								$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_SAVE');
								$this->arResult['SAVE_STATUS'] = true;

								$this->status->setError(false);
								$this->arResult['ERROR_STATUS'] = false;
							}
							else
							{
								$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_NO_SAVE');
								$this->arResult['SAVE_STATUS'] = false;

								$this->status->setConnection(false);
								$this->arResult['CONNECTION_STATUS'] = false;
								$this->status->setRegister(false);
								$this->arResult['REGISTER_STATUS'] = false;

								$this->arResult['STATUS'] = false;
							}
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_NO_DATA_SAVE');
						}

						//Reset cache
						$this->cleanCache();
					}

					//If the test connection or save
					if (($this->request[$this->connector.'_save'] && $this->arResult['SAVE_STATUS']) ||
						$this->request[$this->connector.'_tested'])
					{
						$testConnect = $this->connectorOutput->testConnect();

						if ($testConnect->isSuccess())
						{
							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_CONNECT');

							$this->status->setConnection(true);
							$this->arResult['CONNECTION_STATUS'] = true;

							$this->status->setRegister(true);
							$this->arResult['REGISTER_STATUS'] = true;
							$this->arResult['STATUS'] = true;

							$this->status->setError(false);
							$this->arResult['ERROR_STATUS'] = false;

							Status::deleteLinesExcept($this->connector, (int)$this->arParams['LINE']);
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_NO_CONNECT');

							$this->status->setConnection(false);
							$this->arResult['CONNECTION_STATUS'] = false;

							$this->status->setRegister(false);
							$this->arResult['REGISTER_STATUS'] = false;

							$this->arResult['STATUS'] = false;
						}

						//Reset cache
						$this->cleanCache();
					}

					if ($this->request[$this->connector.'_del'])
					{
						$rawDelete = $this->connectorOutput->unregister();

						if ($rawDelete->isSuccess())
						{
							$this->arResult['STATUS'] = false;
							$this->arResult['ACTIVE_STATUS'] = false;
							$this->arResult['CONNECTION_STATUS'] = false;
							$this->arResult['REGISTER_STATUS'] = false;
							$this->arResult['ERROR_STATUS'] = false;
							$this->arResult['PAGE'] = '';
						}
						else
						{
							$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_NO_DISABLE');
						}

						Status::delete($this->connector, (int)$this->arParams['LINE']);

						//Reset cache
						$this->cleanCache();
					}
				}
			}
			else
			{
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_SESSION_HAS_EXPIRED');
			}
		}
	}

	public function constructionForm(): void
	{
		global $APPLICATION;

		$this->arResult['NAME'] = Connector::getNameConnectorReal($this->connector);

		$this->arResult['URL']['INDEX'] = $APPLICATION->GetCurPageParam(
			$this->pageId.'=index',
			[$this->pageId, 'open_block', 'action']
		);
		$this->arResult['URL']['DELETE'] = $APPLICATION->GetCurPageParam('', [$this->pageId, 'open_block', 'action']);
		$this->arResult['URL']['SIMPLE_FORM'] = $APPLICATION->GetCurPageParam(
			$this->pageId.'=simple_form',
			[$this->pageId, 'open_block', 'action']
		);
		$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $APPLICATION->GetCurPageParam(
			$this->pageId.'=simple_form',
			[$this->pageId, 'open_block', 'action']
		);

		if ($this->arResult['ACTIVE_STATUS'])
		{
			if (!empty($this->arResult['PAGE']))
			{
				$settings = $this->connectorOutput->readSettings();
				$result = $settings->getData();

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
							$this->arResult['placeholder'][$value] = true;
						}
					}
				}
			}

			$callbackUrl = $this->connectorOutput->getCallbackUrl()->getData();
			$this->arResult['URL_WEBHOOK'] = $callbackUrl['url'];

			if ($this->arResult['STATUS'])
			{
				$uri = new Uri($this->arResult['URL']['DELETE']);
				$uri->addParams(['action' => 'disconnect']);
				$this->arResult['URL']['DELETE'] = $uri->getUri();

				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
				$uri->addParams(['action' => 'edit']);
			}
			else
			{
				$uri = new Uri($this->arResult['URL']['SIMPLE_FORM_EDIT']);
				$uri->addParams(['action' => 'connect']);
			}
			$this->arResult['URL']['SIMPLE_FORM_EDIT'] = $uri->getUri();
		}

		$this->arResult['CONNECTOR'] = $this->connector;
	}

	public function executeComponent()
	{
		$this->includeComponentLang('class.php');

		if ($this->checkModules())
		{
			if (Connector::isConnector($this->connector))
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
				ShowError(Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_NO_ACTIVE_CONNECTOR'));
			}
		}
	}
}