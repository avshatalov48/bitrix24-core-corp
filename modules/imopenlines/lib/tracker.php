<?php

namespace Bitrix\ImOpenLines;

use \Bitrix\Main,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\Crm\Binding\LeadContactTable,
	\Bitrix\Crm\Binding\ContactCompanyTable;

use \Bitrix\ImOpenLines\Tools,
	\Bitrix\ImOpenLines\Model\TrackerTable;

Loc::loadMessages(__FILE__);
Crm::loadMessages();

class Tracker
{
	const FIELD_PHONE = 'PHONE';
	const FIELD_EMAIL = 'EMAIL';
	const FIELD_IM = 'IM';
	const FIELD_ID_FM = 'FM';

	const ACTION_CREATE = 'CREATE';
	const ACTION_EXTEND = 'EXTEND';

	const MESSAGE_ERROR_CREATE = 'CREATE';
	const MESSAGE_ERROR_EXTEND = 'EXTEND';

	const ERROR_IMOL_TRACKER_NO_REQUIRED_PARAMETERS = 'ERROR IMOPENLINES TRACKER NO REQUIRED PARAMETERS';

	/* @var Session $session */
	protected $session;

	/**
	 * @param Session $session
	 * @return bool
	 */
	public function setSession(Session $session)
	{
		$result = false;

		if (!empty($session) && $session instanceof Session)
		{
			$this->session = $session;

			$result = true;
		}

		return $result;
	}

	/**
	 * @return Session
	 */
	public function getSession()
	{
		return $this->session;
	}

	/**
	 * @param $params
	 * @return Result
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function message($params)
	{
		$result = new Result();
		$result->setResult(false);
		$session = $this->getSession();

		if (empty($session))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), Crm::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		if ($result->isSuccess() && Loader::includeModule('crm') && $session->getConfig('CRM') == 'Y')
		{
			$messageOriginId = intval($params['ID']);
			$messageText = self::prepareMessage($params['TEXT']);

			if (isset($params['ID']) && empty($messageOriginId) || $messageText == '')
			{
				$result->addError(new Error(Loc::getMessage('IMOL_TRACKER_ERROR_NO_REQUIRED_PARAMETERS'), self::ERROR_IMOL_TRACKER_NO_REQUIRED_PARAMETERS, __METHOD__));
			}

			if ($result->isSuccess())
			{
				$entitiesSearch = self::checkMessage($messageText);
				$phones = $entitiesSearch['PHONES'];
				$emails = $entitiesSearch['EMAILS'];

				if(!empty($phones) || !empty($emails))
				{
					$crmManager = new Crm($session);
					if($crmManager->isLoaded())
					{
						$crmFieldsManager = $crmManager->getFields();
						if (!empty($phones))
						{
							$crmFieldsManager->setPhones($phones);
						}

						if (!empty($emails))
						{
							$crmFieldsManager->setEmails($emails);
						}

						$crmManager->setModeCreate($session->getConfig('CRM_CREATE'));

						$crmManager->search();
						$crmFieldsManager->setTitle($session->getChat()->getData('TITLE'));

						$crmManager->registrationChanges();
						$crmManager->sendCrmImMessages();
					}
				}
			}
		}

		return $result;
	}

	//OLD

	/**
	 * @deprecated
	 */
	public function cancel($messageId)
	{
		$return = false;

		/*if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$log = Array();
			$delete = Array();

			$chatId = 0;
			$sessionId = 0;

			$orm = Model\TrackerTable::getList(Array(
				'filter' => Array('=MESSAGE_ID' => $messageId)
			));

			while ($row = $orm->fetch())
			{
				$entityType = $row['CRM_ENTITY_TYPE'];
				$entityId = $row['CRM_ENTITY_ID'];
				$action = $row['ACTION'];
				$fieldId = $row['FIELD_ID'];
				$fieldType = $row['FIELD_TYPE'];

				$chatId = $row['CHAT_ID'];
				$sessionId = $row['SESSION_ID'];

				$log[$entityType][$entityId][$action][$fieldId][$fieldType][] = $row['FIELD_VALUE'];
				$delete[] = $row['ID'];
			}

			if (!empty($delete))
			{
				foreach ($log as $entityType => $entityTypeValue)
				{
					if($entityType == Crm::ENTITY_ACTIVITY)
					{
						self::cancelActivity($entityTypeValue);
					}
					else
					{
						self::cancelLeadContactCompany($chatId, $sessionId, $entityType, $entityTypeValue);
					}
				}

				foreach ($delete as $id)
				{
					Model\TrackerTable::delete($id);
				}

				\CIMMessenger::DisableMessageCheck();
				\CIMMessenger::Delete($messageId, null, true);
				\CIMMessenger::EnableMessageCheck();

				$return = true;
			}
		}*/

		return $return;
	}

	/**
	 * @deprecated
	 */
	static protected function cancelLeadContactCompany($chatId, $sessionId, $entityType, $params)
	{
		$crm = new Crm();

		/*foreach ($params as $entityId => $entityIdValue)
		{
			foreach ($entityIdValue as $action => $actionValue)
			{
				$updateCrm = true;

				if ($action == self::ACTION_CREATE)
				{
					$entityData = \Bitrix\ImOpenLines\Crm\Common::get($entityType, $entityId);

					$currentTime = new \Bitrix\Main\Type\DateTime();
					$entityTime = new \Bitrix\Main\Type\DateTime($entityData['DATE_CREATE']);
					$entityTime->add('1 DAY');
					if ($currentTime < $entityTime)
					{
						\Bitrix\ImOpenLines\Crm\Common::delete($entityType, $entityId);

						$chat = new Chat($chatId);
						$chat->updateFieldData([Chat::FIELD_SESSION => [
							'CRM' => 'N',
							'CRM_ENTITY_TYPE' => Crm::ENTITY_NONE,
							'CRM_ENTITY_ID' => 0
						]]);

						Model\SessionTable::update($sessionId, Array(
							'CRM' => 'N',
							'CRM_CREATE' => 'N',
							'CRM_ENTITY_TYPE' => Crm::ENTITY_NONE,
							'CRM_ENTITY_ID' => 0
						));

						$updateCrm = false;
					}
				}

				if($updateCrm)
				{
					foreach ($actionValue as $fieldId => $fieldIdValue)
					{
						if($fieldId == self::FIELD_ID_FM)
						{
							foreach ($fieldIdValue as $fieldType => $fieldTypeValue)
							{
								foreach ($fieldTypeValue as $value)
								{
									\Bitrix\ImOpenLines\Crm\Common::deleteMultiField($entityType, $entityId, $fieldType, $value);
								}
							}
						}
						else
						{
							$updateFields = array();

							foreach ($fieldIdValue as $fieldType => $fieldTypeValue)
							{
								foreach ($fieldTypeValue as $value)
								{
									if($fieldId == Crm::FIELDS_CONTACT)
									{
										$contactIDs = array();

										if($entityType == Crm::ENTITY_LEAD)
										{
											$contactIDs = LeadContactTable::getLeadContactIDs($entityId);
										}
										elseif($entityType == Crm::ENTITY_COMPANY)
										{
											$contactIDs = ContactCompanyTable::getCompanyContactIDs($entityId);
										}

										foreach ($contactIDs as $key => $id)
										{
											if($id == $value)
											{
												unset($contactIDs[$key]);
											}
										}

										$updateFields[$fieldId] = $contactIDs;
									}
									else
									{
										$updateFields[$fieldId] = '';
									}
								}
							}

							if(!empty($updateFields))
							{
								\Bitrix\ImOpenLines\Crm\Common::update(
									$entityType,
									$entityId,
									$updateFields
								);
							}
						}
					}
				}
			}
		}*/
	}

	/**
	 * @deprecated
	 */
	protected static function cancelActivity($params)
	{
		foreach ($params as $entityId => $entityIdValue)
		{
			foreach ($entityIdValue as $action => $actionValue)
			{
				if ($action == self::ACTION_CREATE)
				{
					\CCrmActivity::Delete($entityId);
				}
				else
				{
					$bindings = \CAllCrmActivity::GetBindings($entityId);

					foreach ($bindings as $key => $value)
					{
						unset($bindings[$key]['ID']);
					}

					foreach ($actionValue as $fieldId => $fieldIdValue)
					{
						foreach ($fieldIdValue as $fieldType => $fieldTypeValue)
						{
							foreach ($fieldTypeValue as $value)
							{
								$deleteBinding = array(
									"OWNER_TYPE_ID" => \CCrmOwnerType::ResolveID($fieldId),
									"OWNER_ID" => $value
								);

								if(in_array($deleteBinding, $bindings))
								{
									$key = array_search($deleteBinding, $bindings);

									unset($bindings[$key]);
								}
							}
						}
					}

					\CAllCrmActivity::SaveBindings($entityId, $bindings);
				}
			}
		}
	}

	/**
	 * @deprecated
	 */
	public function change($messageId, $newEntityType, $newEntityId)
	{
		$return = false;
		$messageId = intval($messageId);
		$newEntityId = intval($newEntityId);

		/*if (\Bitrix\Main\Loader::includeModule('crm') && $messageId > 0 && in_array($newEntityType, Array(Crm::ENTITY_COMPANY, Crm::ENTITY_LEAD, Crm::ENTITY_CONTACT)) && $newEntityId > 0)
		{
			$log = Array();
			$delete = Array();

			$sessionId = 0;
			$messageOriginId = 0;

			$action = '';
			$entityType = '';
			$entityId = 0;

			$orm = Model\TrackerTable::getList(Array(
				'filter' => Array('=MESSAGE_ID' => $messageId)
			));

			$return = true;

			while ($row = $orm->fetch())
			{
				$entityType = $row['CRM_ENTITY_TYPE'];
				$entityId = $row['CRM_ENTITY_ID'];
				$action = $row['ACTION'];
				$fieldId = $row['FIELD_ID'];
				$fieldType = $row['FIELD_TYPE'];

				$sessionId = $row['SESSION_ID'];
				$messageOriginId = $row['MESSAGE_ORIGIN_ID'];

				if ($newEntityType == $entityType && $newEntityId == $entityId)
					$return = false;

				$log[$entityType][$entityId][$action][$fieldId][$fieldType][] = $row['FIELD_VALUE'];
				$delete[] = $row['ID'];
			}

			if($return && !empty($delete))
			{
				foreach ($log as $entityType => $entityTypeValue)
				{
					if($entityType == Crm::ENTITY_ACTIVITY)
					{
						self::cancelActivity($entityTypeValue);
					}
					else
					{
						self::changeLeadContactCompany($entityType, $entityTypeValue);
					}
				}

				foreach ($delete as $id)
				{
					Model\TrackerTable::delete($id);
				}

				$return = true;

				if ($messageOriginId)
				{
					$sessionData = Model\SessionTable::getByIdPerformance($sessionId)->fetch();

					$session = new Session();
					$result = $session->load(Array(
						'USER_CODE' => $sessionData['USER_CODE']
					));
					if ($result)
					{
						$messageData = \Bitrix\Im\Model\MessageTable::getById($messageOriginId)->fetch();
						$this->message(Array(
							'SESSION' => $session,
							'MESSAGE' => Array(
								'ID' => $messageData["ID"],
								'TEXT' => $messageData["MESSAGE"],
							),
							'UPDATE_ID' => $messageId,
							'CRM' => Array(
								'ENTITY_TYPE' => $newEntityType,
								'ENTITY_ID' => $newEntityId,
							)
						));
					}
				}
			}
			else
			{
				$return = false;
			}
		}*/

		return $return;
	}

	/**
	 * @deprecated
	 */
	static protected function changeLeadContactCompany($entityType, $params)
	{
		$crm = new Crm();

		foreach ($params as $entityId => $entityIdValue)
		{
			foreach ($entityIdValue as $action => $actionValue)
			{
				$updateCrm = true;

				if ($action == self::ACTION_CREATE)
				{
					$entityData = \Bitrix\ImOpenLines\Crm\Common::get($entityType, $entityId, true);

					$currentTime = new \Bitrix\Main\Type\DateTime();
					$entityTime = new \Bitrix\Main\Type\DateTime($entityData['DATE_CREATE']);
					$entityTime->add('1 DAY');
					if ($currentTime < $entityTime)
					{
						\Bitrix\ImOpenLines\Crm\Common::delete($entityType, $entityId);
						$updateCrm = false;
					}
				}

				if($updateCrm)
				{
					foreach ($actionValue as $fieldId => $fieldIdValue)
					{
						if($fieldId == self::FIELD_ID_FM)
						{
							foreach ($fieldIdValue as $fieldType => $fieldTypeValue)
							{
								foreach ($fieldTypeValue as $value)
								{
									\Bitrix\ImOpenLines\Crm\Common::deleteMultiField($entityType, $entityId, $fieldType, $value);
								}
							}
						}
						else
						{
							$updateFields = array();

							foreach ($fieldIdValue as $fieldType => $fieldTypeValue)
							{
								foreach ($fieldTypeValue as $value)
								{
									if($fieldId == Crm::FIELDS_CONTACT)
									{
										$contactIDs = array();

										if($entityType == Crm::ENTITY_LEAD)
										{
											$contactIDs = LeadContactTable::getLeadContactIDs($entityId);
										}
										elseif($entityType == Crm::ENTITY_COMPANY)
										{
											$contactIDs = ContactCompanyTable::getCompanyContactIDs($entityId);
										}

										foreach ($contactIDs as $key => $id)
										{
											if($id == $value)
											{
												unset($contactIDs[$key]);
											}
										}

										$updateFields[$fieldId] = $contactIDs;
									}
									else
									{
										$updateFields[$fieldId] = '';
									}
								}
							}

							if(!empty($updateFields))
							{
								\Bitrix\ImOpenLines\Crm\Common::update(
									$entityType,
									$entityId,
									$updateFields
								);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @deprecated
	 */
	public function updateLog($params)
	{
		$id = intval($params['ID']);
		if ($id <= 0)
		{
			return false;
		}

		$update = $params['UPDATE'];
		if (!is_array($update))
		{
			return false;
		}

		$map = Model\TrackerTable::getMap();
		foreach ($update as $key => $value)
		{
			if (!isset($map[$key]))
			{
				unset($update[$key]);
			}
		}
		if (count($update) <= 0)
		{
			return false;
		}

		Model\TrackerTable::update($params['ID'], $params['UPDATE']);

		return true;
	}

	/**
	 * @deprecated
	 * TODO: delete
	 */
	public function sendLimitMessage($params)
	{
		$chatId = intval($params['CHAT_ID']);
		if ($chatId <= 0)
			return false;

		if ($params['MESSAGE_TYPE'] == self::MESSAGE_ERROR_CREATE)
		{
			$message =  Loc::getMessage('IMOL_TRACKER_LIMIT_1');
		}
		else
		{
			$message =  Loc::getMessage('IMOL_TRACKER_LIMIT_2');
		}

		$message = str_replace(Array('#LINK_START#', '#LINK_END#'), '', $message);

		$keyboard = new \Bitrix\Im\Bot\Keyboard();
		$keyboard->addButton(Array(
			"TEXT" => Loc::getMessage('IMOL_TRACKER_LIMIT_BUTTON'),
			"LINK" => "/settings/license_all.php",
			"DISPLAY" => "LINE",
			"CONTEXT" => "DESKTOP",
		));

		$userViewChat = \CIMContactList::InRecent($params['OPERATOR_ID'], IM_MESSAGE_OPEN_LINE, $chatId);

		Im::addMessage(Array(
			"TO_CHAT_ID" => $chatId,
			"MESSAGE" => $message,
			"SYSTEM" => 'Y',
			"KEYBOARD" => $keyboard,
			"RECENT_ADD" => $userViewChat? 'Y': 'N'
		));

		return true;
	}
	//END OLD

	/**
	 * @param $messageText
	 * @return array
	 * @throws Main\LoaderException
	 */
	protected static function checkMessage($messageText)
	{
		$result = Array(
			'PHONES' => [],
			'EMAILS' => [],
		);

		//Phone
		$result['PHONES'] = Tools\Phone::parseText($messageText);

		//Email
		$result['EMAILS'] = Tools\Email::parseText($messageText);

		return $result;
	}

	protected static function prepareMessage($text)
	{
		$textParser = new \CTextParser();
		$textParser->allow = array("HTML" => "N", "USER" => "N",  "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");

		$text = preg_replace("/\[[buis]\](.*?)\[\/[buis]\]/i", "$1", $text);
		$text = $textParser->convertText($text);

		$text = preg_replace('/<([\w]+)[^>]*>(.*?)<\/\1>/i', "", $text);
		$text = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $text);
		$text = preg_replace("/\[CHAT=([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$2", $text);
		$text = preg_replace("/\[SEND(?:=(.+?))?\](.+?)?\[\/SEND\]/i", " ", $text);
		$text = preg_replace("/\[PUT(?:=(.+?))?\](.+?)?\[\/PUT\]/i", " ", $text);
		$text = preg_replace("/\[CALL(?:=(.+?))?\](.+?)?\[\/CALL\]/i", " ", $text);
		$text = preg_replace("/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/i", " ", $text);
		$text = preg_replace("/\[ATTACH=([0-9]{1,})\]/i", " ", $text);
		$text = preg_replace("/\[ICON\=([^\]]*)\]/i", " ", $text);
		$text = preg_replace('#\-{54}.+?\-{54}#s', " ", str_replace(array("#BR#"), Array(" "), $text));

		return $text;
	}
}
