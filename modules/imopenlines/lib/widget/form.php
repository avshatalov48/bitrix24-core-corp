<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\ImOpenLines\Widget;

use \Bitrix\ImOpenLines\Im,
	\Bitrix\ImOpenLines\Log,
	\Bitrix\ImOpenLines\Crm,
	\Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Tools,
	\Bitrix\ImOpenLines\Error,
	\Bitrix\ImOpenLines\Result,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Im\User as ImUser,
	\Bitrix\Im\Model\RelationTable;

Loc::loadMessages(__FILE__);

class Form
{
	const FORM_OFFLINE = 'OFFLINE';
	const FORM_WELCOME = 'WELCOME';
	const FORM_HISTORY = 'HISTORY';

	protected $chatId = 0;
	protected $userId = 0;

	/**
	 * Form constructor.
	 * @param $chatId
	 * @param $userId
	 */
	public function __construct($chatId, $userId = null)
	{
		$this->chatId = intval($chatId);

		if ($userId)
		{
			$this->userId = intval($userId);
		}
		else
		{
			global $USER;
			$this->userId = $USER->GetID();
		}
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function moduleLoad()
	{
		$result = new Result();

		if(!Loader::includeModule('im'))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_LCC_ERROR_IM_LOAD'), 'IM_LOAD_ERROR', __METHOD__));
		}
		if(!Loader::includeModule('pull'))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_LCC_ERROR_PULL_LOAD'), 'IM_LOAD_ERROR', __METHOD__));
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	protected function dataLoad()
	{
		$result = new Result();

		if ($this->chatId <= 0)
		{
			$result->addError(new Error(Loc::getMessage('IMOL_LCC_ERROR_CHAT_ID'), 'CHAT_ID', __METHOD__));
		}
		if ($this->userId <= 0)
		{
			$result->addError(new Error(Loc::getMessage('IMOL_LCC_ERROR_USER_ID'), 'USER_ID', __METHOD__));
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function checkAccess()
	{
		$result = new Result();

		$resultModuleLoad = $this->moduleLoad();

		if($resultModuleLoad->isSuccess())
		{
			$resultDataLoad = $this->dataLoad();

			if($resultDataLoad->isSuccess())
			{
				$orm = RelationTable::getList(array(
					"select" => array("ID", "ENTITY_TYPE" => "CHAT.ENTITY_TYPE"),
					"filter" => array(
						"=CHAT_ID" => $this->chatId,
						"=USER_ID" => $this->userId,
					),
				));
				if ($relation = $orm->fetch())
				{
					if ($relation["ENTITY_TYPE"] != "LIVECHAT")
					{
						$result->addError(new Error(Loc::getMessage('IMOL_LCC_ERROR_CHAT_TYPE'), 'CHAT_TYPE', __METHOD__));
					}
				}
				else
				{
					$result->addError(new Error(Loc::getMessage('IMOL_LCC_ERROR_ACCESS_DENIED'), 'ACCESS_DENIED', __METHOD__));
				}
			}
			else
			{
				$result->addErrors($resultDataLoad->getErrors());
			}
		}
		else
		{
			$result->addErrors($resultModuleLoad->getErrors());
		}

		return $result;
	}

	/**
	 * @param $type
	 * @param $fields
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function saveForm($type, $fields)
	{
		$result = new Result();
		$sendHistory = false;

		if (!in_array($type, Array(self::FORM_OFFLINE, self::FORM_WELCOME, self::FORM_HISTORY)))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_LCC_ERROR_FORM_ID'), 'FORM_ID', __METHOD__));
		}

		if($result->isSuccess())
		{
			$resultModuleLoad = $this->moduleLoad();

			if(!$resultModuleLoad->isSuccess())
			{
				$result->addErrors($resultModuleLoad->getErrors());
			}
		}

		if($result->isSuccess())
		{
			$resultDataLoad = $this->dataLoad();

			if(!$resultDataLoad->isSuccess())
			{
				$result->addErrors($resultDataLoad->getErrors());
			}
		}

		if($result->isSuccess())
		{
			$resultAccess = $this->checkAccess();

			if(!$resultAccess->isSuccess())
			{
				$result->addErrors($resultAccess->getErrors());
			}
		}

		if($result->isSuccess())
		{
			Log::write(Array(
				'FORM' => Array($type, $fields)
			), 'CLIENT FORM CRM');

			$chat = new Chat($this->chatId);
			list($configId) = explode('|', $chat->getData('ENTITY_ID'));

			$resultChatLoad = $chat->load(Array(
				'USER_CODE' => 'livechat|'.$configId.'|'.$this->chatId.'|'.$this->userId,
			));

			if ($resultChatLoad)
			{
				$configManager = new Config();
				$config = $configManager->get($configId);

				if (isset($fields['EMAIL']) && empty($fields['EMAIL']))
				{
					unset($fields['EMAIL']);
				}
				if (isset($fields['PHONE']) && empty($fields['PHONE']))
				{
					unset($fields['PHONE']);
				}
				if (isset($fields['NAME']) && empty($fields['NAME']))
				{
					$fields['NAME'] = \Bitrix\ImOpenLines\LiveChat::getDefaultGuestName();
				}

				$user = ImUser::getInstance($this->userId);

				$userUpdate = Array();
				$chatUpdate = Array();
				$messageParams = Array();

				$formSend = false;

				if ($type == self::FORM_WELCOME || $type == self::FORM_OFFLINE)
				{
					if (isset($fields['NAME']))
					{
						list($userName, $userLastName) = explode(" ", $fields['NAME'], 2);
						if ($userName && $userLastName)
						{
							if ($userName != $user->getName() || $userLastName != $user->getLastName())
							{
								$userUpdate['NAME'] = $userName;
								$userUpdate['LAST_NAME'] = $userLastName;
							}
						}
						else if ($userName && $user->getName() != $userName)
						{
							$userUpdate['NAME'] = $userName;
						}
					}
					if (isset($fields['EMAIL']) && Tools\Email::validate($fields['EMAIL']) && !Tools\Email::isSame($user->getEmail(), $fields['EMAIL']))
					{
						$userUpdate['EMAIL'] = $fields['EMAIL'];
					}
					if (isset($fields['PHONE']) && Tools\Phone::validate($fields['PHONE']) && !Tools\Phone::isSame($user->getPhone(ImUser::PHONE_MOBILE), $fields['PHONE']))
					{
						$userUpdate['PERSONAL_MOBILE'] = $fields['PHONE'];
					}

					$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
					$attach->AddGrid(Array(
						Array(
							"NAME" => Loc::getMessage('IMOL_LCC_FORM_NAME'),
							"VALUE" => isset($fields['NAME'])? $fields['NAME']: Loc::getMessage('IMOL_LCC_FORM_NONE'),
							"DISPLAY" => "COLUMN"
						),
						Array(
							"NAME" => Loc::getMessage('IMOL_LCC_FORM_EMAIL'),
							"VALUE" => isset($fields['EMAIL'])? $fields['EMAIL']: Loc::getMessage('IMOL_LCC_FORM_NONE'),
							"DISPLAY" => "COLUMN"
						),
						Array(
							"NAME" => Loc::getMessage('IMOL_LCC_FORM_PHONE'),
							"VALUE" => isset($fields['PHONE'])? $fields['PHONE']: Loc::getMessage('IMOL_LCC_FORM_NONE'),
							"DISPLAY" => "COLUMN"
						),
					));

					if (!empty($fields))
					{
						$messageParams = Array(
							"FROM_USER_ID" => $this->userId,
							"MESSAGE" => '[B]'.Loc::getMessage('IMOL_LCC_FORM_SUBMIT').'[/B]',
							"ATTACH" => $attach,
							"SKIP_CONNECTOR" => 'Y',
						);
					}

					$formSend = true;
				}
				else if ($type == self::FORM_HISTORY)
				{
					$userUpdate = Array();
					if (isset($fields['EMAIL']) && Tools\Email::validate($fields['EMAIL']) && !$user->getEmail())
					{
						$userUpdate['EMAIL'] = $fields['EMAIL'];
					}

					$liveChat = new Chat($this->chatId);
					$chatFieldSession = $liveChat->getFieldData(Chat::FIELD_LIVECHAT);

					if (isset($fields['EMAIL']) && Tools\Email::validate($fields['EMAIL']) && $chatFieldSession['SESSION_ID'])
					{
						$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);
						$attach->AddGrid(Array(
							Array(
								"NAME" => Loc::getMessage('IMOL_LCC_FORM_EMAIL'),
								"VALUE" => $fields['EMAIL'],
							),
						));
						$messageParams = Array(
							"FROM_USER_ID" => $this->userId,
							"MESSAGE" => '[B]'.Loc::getMessage('IMOL_LCC_FORM_HISTORY_2', Array("#LINK#" => "[URL=/online/?IM_HISTORY=imol|".$chatFieldSession['SESSION_ID']."]".$chatFieldSession['SESSION_ID']."[/URL]")).'[/B]',
							"ATTACH" => $attach,
							"SKIP_CONNECTOR" => 'Y',
							"SYSTEM" => 'Y',
						);

						\Bitrix\ImOpenLines\Mail::sendSessionHistory($chatFieldSession['SESSION_ID'], $fields['EMAIL']);
					}
					/*
					$liveChat->updateFieldData([Chat::FIELD_LIVECHAT => [
						'SESSION_ID' => 0,
						'SHOW_FORM' => 'N'
					]]);
					*/
				}

				// update user entity
				if (!empty($userUpdate))
				{
					$userClass = new \CUser();
					$userClass->Update($this->userId, $userUpdate);
					ImUser::clearStaticCache();
				}

				if (isset($userUpdate['NAME']) || isset($userUpdate['LAST_NAME']))
				{
					$titleParams = $chat->getTitle($config['LINE_NAME'], trim($userUpdate['NAME'].' '.$userUpdate['LAST_NAME']));
					$chatUpdate['TITLE'] = $titleParams['TITLE'];
				}

				// update chat entity
				if (!empty($chatUpdate))
				{
					$chat->update($chatUpdate);
				}

				// publish info message to chat
				$session = false;
				$sessionStart = false;

				if (!empty($messageParams))
				{
					if (!$session)
					{
						$session = new Session();
						$sessionStart = $session->load(Array(
							'USER_CODE' => $chat->getData('ENTITY_ID'),
							'SKIP_CREATE' => 'Y'
						));
					}
					$messageParams['TO_CHAT_ID'] = $chat->getData('ID');

					if($session->getData('OPERATOR_ID') > 0)
					{
						$userViewChat = \CIMContactList::InRecent($session->getData('OPERATOR_ID'), IM_MESSAGE_OPEN_LINE, $session->getData('CHAT_ID'));
					}
					else
					{
						$userViewChat  = true;
					}

					$messageParams['RECENT_ADD'] = $session->isNowCreated() || $userViewChat? 'Y': 'N';

					Im::addMessageLiveChat($messageParams);
				}

				if(
					$type == self::FORM_HISTORY &&
					empty($userUpdate) &&
					isset($fields['EMAIL']) &&
					Tools\Email::validate($fields['EMAIL']) &&
					!Tools\Email::isSame($user->getEmail(), $fields['EMAIL'])
				)
				{
					$sendHistory = true;
				}
				//UPDATE CRM ENTITY
				if($sendHistory == true || !empty($userUpdate))
				{
					if (!$session)
					{
						$session = new Session();
						$sessionStart = $session->load(Array(
							'USER_CODE' => $chat->getData('ENTITY_ID'),
						));
					}

					if ($sessionStart && $session->getConfig('CRM') == 'Y') //additionally check line config not create crm
					{
						$crmManager = new Crm($session);
						if($crmManager->isLoaded())
						{
							$crmFieldsManager = $crmManager->getFields();
							$crmFieldsManager->setDataFromUser($user->getId());

							if ($sendHistory == true)
							{
								$crmManager->setIgnoreSearchPerson();
								$crmManager->setIgnoreSearchPhones();
								$crmFieldsManager->addEmail($fields['EMAIL']);
							}
							elseif (!empty($userUpdate))
							{
								if(!empty($fields['PHONE']))
								{
									$crmFieldsManager->addPhone($fields['PHONE']);
								}
								if(!empty($fields['EMAIL']))
								{
									$crmFieldsManager->addEmail($fields['EMAIL']);
								}
								if(!empty($userName))
								{
									$crmFieldsManager->setPersonName($userName);
								}
								if(!empty($userLastName))
								{
									$crmFieldsManager->setPersonLastName($userLastName);
								}
							}

							if($session->getConfig('CRM_CREATE') != Config::CRM_CREATE_LEAD)
							{
								$crmManager->setSkipCreate();
							}

							$crmManager->search();
							$crmFieldsManager->setTitle($session->getChat()->getData('TITLE'));

							$crmManager->registrationChanges();
							$crmManager->sendCrmImMessages();
						}
					}
				}
				//END UPDATE CRM ENTITY

				if ($formSend)
				{
					if (!$session)
					{
						$session = new Session();
						$sessionStart = $session->load(Array(
							'USER_CODE' => $chat->getData('ENTITY_ID'),
						));
					}
					if ($sessionStart)
					{
						$session->update(Array(
							'SEND_FORM' => strtolower($type)
						));
					}
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('IMOL_LCC_ERROR_ACCESS_DENIED'), 'FORM_ID', __METHOD__));
			}
		}

		return $result;
	}
}