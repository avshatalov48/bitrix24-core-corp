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
	public const HELPDESK_CODE = '14214014';

	private $cacheId;

	private $connector = Library::ID_EDNA_WHATSAPP_CONNECTOR;
	private $error = [];
	private $messages = [];
	/** @var \Bitrix\ImConnector\Provider\Messageservice\Output */
	private $connectorOutput;
	/** @var Status */
	private $status;
	private $senderIds;

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
		$this->arResult['HELPDESK_CODE'] = 'redirect=detail&code=' . self::HELPDESK_CODE;

		$this->arResult['CONNECTOR_LINES'] = $this->getConnectorLines();
		$this->arResult['SUBJECT_IDS'] = $this->getSubjectIds();
		$this->arResult['FROM_LIST'] = $this->getFromList();
		$this->arResult['SUBJECT_TITLES'] = $this->getSubjectSelectorTitles();
		$this->arResult['HIDE_SUBJECTS_LIST'] = false;

		$this->arResult['ANOTHER_SUBJECT_IDS'] = [];
		foreach ($this->arResult['CONNECTOR_LINES'] as $connectorLineId => $connectorLineData)
		{
			if ($connectorLineId != $this->arParams['LINE'] && isset($connectorLineData['subjectId']))
			{
				$this->arResult['ANOTHER_SUBJECT_IDS'][] = $connectorLineData['subjectId'];
			}
		}

		$this->arResult['IS_CONNECTED_LINE'] = in_array($this->arParams['LINE'], array_keys($this->arResult['CONNECTOR_LINES']));
		if(
			count(array_keys($this->arResult['CONNECTOR_LINES'])) > 0
			&& !$this->arResult['IS_CONNECTED_LINE']
		)
		{
			$this->arResult['PAGE'] = $this->pageId;
		}

		$this->cacheId = Connector::getCacheIdConnector($this->arParams['LINE'], $this->connector);

		if (\Bitrix\MessageService\Providers\Edna\RegionHelper::isInternational())
		{
			$this->arResult['LOC_REGION_POSTFIX'] = '_IO';
		}
		else
		{
			$this->arResult['LOC_REGION_POSTFIX'] = '';
		}
	}

	private function getConnectorLines(): array
	{
		$lines = [];
		$statusConnector = Status::getInstanceAllLine($this->connector);
		foreach ($statusConnector as $lineId => $status)
		{
			if ($status->isStatus())
			{
				$lines[$lineId] = $status->getData();
			}
		}

		return $lines;
	}

	private function getSubjectSelectorTitles(): array
	{
		$fromList = $this->arResult['FROM_LIST'];
		$subjectIds = $this->arResult['SUBJECT_IDS'];
		$connectorLines = $this->arResult['CONNECTOR_LINES'];

		$names = [];
		foreach ($subjectIds as $subjectId)
		{
			$phone = '';
			$lineName = '';
			$lineId = null;

			foreach ($fromList as $from)
			{
				if ($from['id'] == $subjectId)
				{
					$phone = $from['channelPhone'];
					break;
				}
			}

			foreach ($connectorLines as $connectorLineId => $connectorData)
			{
				if ($subjectId == $connectorData['subjectId'])
				{
					$line = \Bitrix\ImOpenLines\Config::getInstance()->get($connectorLineId);
					$lineName = $line['LINE_NAME'];
					$lineId = $line['ID'];
					break;
				}
			}

			$replace = [
				'#SUBJECT_ID#' => $subjectId,
				'#PHONE#' => $phone,
				'#LINE_NAME#' => $lineName,
			];

			if (empty($replace['#LINE_NAME#']))
			{
				$title = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_SUBJECT_ID_PLACEHOLDER_NO_OL', $replace);
			}
			else
			{
				$title = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_SUBJECT_ID_PLACEHOLDER', $replace);
			}

			$names[$subjectId] = [
				'title' => $title,
				'lineId' => $lineId,
				'hint' => !empty($replace['#LINE_NAME#']),
			];
		}

		return $names;
	}

	private function getFirstSubjectId(string $subjectIds): int
	{
		$ids = explode(';', trim($subjectIds));

		return (int)array_shift($ids);
	}

	private function getSubjectIds(): array
	{
		$sender = \Bitrix\MessageService\Sender\SmsManager::getSenderById(\Bitrix\MessageService\Sender\Sms\Ednaru::ID);
		$info = $sender->getOwnerInfo();
		$subjectIds = $info[\Bitrix\MessageService\Providers\Constants\InternalOption::SENDER_ID];

		if (is_array($subjectIds))
		{
			return $subjectIds;
		}

		return [];
	}

	private function getApiKey(): ?string
	{
		$sender = \Bitrix\MessageService\Sender\SmsManager::getSenderById(\Bitrix\MessageService\Sender\Sms\Ednaru::ID);
		$info = $sender->getOwnerInfo();
		return $info[\Bitrix\MessageService\Providers\Constants\InternalOption::API_KEY];
	}

	private function getFromList(): array
	{
		$sender = \Bitrix\MessageService\Sender\SmsManager::getSenderById(\Bitrix\MessageService\Sender\Sms\Ednaru::ID);
		$fromList = $sender->getFromList();
		$subjectIds = $this->getSubjectIds();

		if (count($fromList) != count($subjectIds))
		{
			return $subjectIds;
		}

		foreach ($fromList as $from)
		{
			if (!in_array($from['id'], $subjectIds))
			{
				return $subjectIds;
			}
		}

		return $fromList;
	}

	/**
	 * Reset cache
	 */
	protected function cleanCache(): void
	{
		Connector::cleanCacheConnector($this->arParams['LINE'], $this->cacheId);
	}

	public function saveForm(): bool
	{
		if ($this->request->isPost() && !empty($this->request[$this->connector.'_form']))
		{
			if (check_bitrix_sessid())
			{
				$this->senderIds = $this->request['sender_id'];
				if (isset($this->request['sender_id_0']))
				{
					$senderIds = explode(';', $this->senderIds);
					$senderIds[] = $this->request['sender_id_0'];
					$senderIds = array_reverse(array_unique(array_filter($senderIds)));
					$this->senderIds = implode(';', $senderIds);

					$this->arResult['FORM']['sender_id'] = $this->senderIds;
				}

				if (
					$this->request[$this->connector . '_save']
					&& isset($this->request['sender_id_0'])
				)
				{
					$allLines = Status::getInstanceAllLine($this->connector);

					//create line or change subjectId
					foreach ($allLines as $lineId => $status)
					{
						$data = $status->getData();
						if (
							$data['subjectId'] == $this->request['sender_id_0']
							&& $lineId != $this->arParams['LINE']
						)
						{
							Status::delete($this->connector, $lineId);
						}
					}

					//create new line
					if (empty($this->request['api_key']))
					{
						$this->arResult['FORM']['api_key'] = $this->getApiKey();
					}
					else
					{
						$this->arResult['FORM']['api_key'] = $this->request['api_key'];
					}

					$saved = $this->connectorOutput->register($this->arResult['FORM']);
					if ($saved->isSuccess())
					{
						if (count($senderIds) > 1)
						{
							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_SAVE_NEW');
						}
						else
						{
							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_SAVE_MSGVER_1');
						}


						$this->arResult['placeholder']['api_key'] = $this->hideApiKey($this->arResult['FORM']['api_key']);
						$this->arResult['API_SAVED'] = true;

						$statusData = ['subjectId' => (int)$this->request['sender_id_0']];
						$this->status->setData($statusData);
						$this->arResult['DATA'] = $statusData;

						$this->status->setActive(true);
						$this->arResult['ACTIVE_STATUS'] = true;

						$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_CONNECT');

						$this->status->setConnection(true);
						$this->arResult['CONNECTION_STATUS'] = true;

						$this->status->setRegister(true);
						$this->arResult['REGISTER_STATUS'] = true;
						$this->arResult['STATUS'] = true;

						$this->status->setError(false);
						$this->arResult['ERROR_STATUS'] = false;

						return true;
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
					if ($this->request[$this->connector . '_active'] && empty($this->arResult['ACTIVE_STATUS']))
					{
						$this->status->setActive(true);
						$this->arResult['ACTIVE_STATUS'] = true;

						//Reset cache
						$this->cleanCache();
					}

					if (!empty($this->arResult['ACTIVE_STATUS']))
					{
						//If saving
						if ($this->request[$this->connector . '_save'])
						{
							foreach ($this->listOptions as $value)
							{
								if (!empty($this->request[$value]))
								{
									$this->arResult['FORM'][$value] = $this->request[$value];
								}
							}

							// if change only subjectIds
							if (
								empty($this->arResult['FORM']['api_key'])
								&& $this->isSenderIdsChanged()
							)
							{
								$sender = \Bitrix\MessageService\Sender\SmsManager::getSenderById(\Bitrix\MessageService\Sender\Sms\Ednaru::ID);
								$info = $sender->getOwnerInfo();
								$apiKey = $info[\Bitrix\MessageService\Providers\Constants\InternalOption::API_KEY];

								if (!empty($apiKey))
								{
									$this->arResult['FORM']['api_key'] = $apiKey;
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
									if (count($senderIds) > 1)
									{
										$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_SAVE_NEW');
									}
									else
									{
										$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_SAVE_MSGVER_1');
									}
									$this->arResult['placeholder']['api_key'] = $this->hideApiKey($this->arResult['FORM']['api_key']);
									$this->arResult['API_SAVED'] = true;

									//Set first subjectId
									$statusData = ['subjectId' => $this->getFirstSubjectId($this->senderIds)];
									$this->status->setData($statusData);
									$this->arResult['DATA'] = $statusData;

									$this->status->setConnection(true);
									$this->arResult['CONNECTION_STATUS'] = true;

									$this->status->setRegister(true);
									$this->arResult['REGISTER_STATUS'] = true;
									$this->arResult['STATUS'] = true;

									$this->status->setError(false);
									$this->arResult['ERROR_STATUS'] = false;

									Status::deleteLinesExcept($this->connector, (int)$this->arParams['LINE']);

									if (empty($this->arResult['LINE']))
									{
										$this->arResult['LINE'] = (int)$this->arParams['LINE'] ?? null;
									}

									return true;
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
							return true;
						}

						//If the test connection or save
						if (
							($this->request[$this->connector . '_save'] && $this->arResult['SAVE_STATUS'])
							|| $this->request[$this->connector . '_tested']
						)
						{
							$testConnect = $this->connectorOutput->testConnect();

							if ($testConnect->isSuccess())
							{
								$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_CONNECT');

								//Set first subjectId
								$statusData = ['subjectId' => $this->getFirstSubjectId($this->senderIds)];
								$this->status->setData($statusData);
								$this->arResult['DATA'] = $statusData;

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

								$this->connectorOutput->unregister();
								foreach ($this->getConnectorLines() as $connectorLine => $connectorData)
								{
									Status::delete($this->connector, (int)$connectorLine);
								}
							}

							// Reset cache
							$this->cleanCache();
						}

						// Delete connector
						if ($this->request[$this->connector . '_del'])
						{
							// Delete messageservice connecton only if last subjectId
							if (count($this->getConnectorLines()) < 2)
							{
								$rawDelete = $this->connectorOutput->unregister();
								if (!$rawDelete->isSuccess())
								{
									$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_NO_DISABLE');
								}
							}

							$this->arResult['STATUS'] = false;
							$this->arResult['ACTIVE_STATUS'] = false;
							$this->arResult['CONNECTION_STATUS'] = false;
							$this->arResult['REGISTER_STATUS'] = false;
							$this->arResult['ERROR_STATUS'] = false;
							$this->arResult['PAGE'] = '';

							Status::delete($this->connector, (int)$this->arParams['LINE']);
							$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_DISABLE');

							// Reset cache
							$this->cleanCache();
						}
						elseif ($this->request[$this->connector . '_delall'])
						{
							$rawDelete = $this->connectorOutput->unregister();
							if (!$rawDelete->isSuccess())
							{
								$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_SETTINGS_NO_DISABLE');
							}
							else
							{
								$this->arResult['STATUS'] = false;
								$this->arResult['ACTIVE_STATUS'] = false;
								$this->arResult['CONNECTION_STATUS'] = false;
								$this->arResult['REGISTER_STATUS'] = false;
								$this->arResult['ERROR_STATUS'] = false;
								$this->arResult['PAGE'] = '';

								foreach ($this->getConnectorLines() as $connectorLine => $connectorData)
								{
									Status::delete($this->connector, (int)$connectorLine);
								}
								$this->messages[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_OK_DISABLE');
							}

							// Reset cache
							$this->cleanCache();
						}

					}
				}
			}
			else
			{
				$this->error[] = Loc::getMessage('IMCONNECTOR_COMPONENT_WHATSAPPBYEDNA_SESSION_HAS_EXPIRED');
			}
		}

		return false;
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

		if ($this->arResult['ACTIVE_STATUS'] || !empty($this->arResult['CONNECTOR_LINES']))
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
						elseif($value === 'sender_id' && is_array($result[$value]))
						{
							$this->arResult['FORM'][$value] = implode(';', $result[$value]);
							$this->arResult['SAVE_STATUS'] = true;
						}
						elseif($value === 'api_key')
						{
							$this->arResult['placeholder'][$value] = $this->hideApiKey($result[$value]);
							$this->arResult['API_SAVED'] = true;
							$this->arResult['SAVE_STATUS'] = true;
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

				if (empty($this->arResult['PAGE']))
				{
					$this->arResult['PAGE'] = $this->request[$this->pageId];
				}

				if ($this->saveForm())
				{
					$this->initialization();
				}

				if (empty($this->arResult['LINE']))
				{
					$this->arResult['LINE'] = $this->request['LINE'] ?? null;
				}

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

	private function isSenderIdsChanged(): bool
	{
		if (empty($this->senderIds))
		{
			return false;
		}

		$newSenderIds = explode(';', $this->senderIds);
		$oldSenderIds = $this->getSubjectIds();
		if (count(array_diff($newSenderIds, $oldSenderIds)) > 0)
		{
			return true;
		}

		return false;
	}

	private function hideApiKey(string $input)
	{
		if (strlen($input) <= 11)
		{
			return $input;
		}
		$firstPart = substr($input, 0, 6);
		$lastPart = substr($input, -5);

		return $firstPart . '...' . $lastPart;
	}
}