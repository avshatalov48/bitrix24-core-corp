<?php

namespace Bitrix\ImOpenLines;

use Bitrix\Main\Loader,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Security\Random,
	Bitrix\Main\Localization\Loc;

use Bitrix\Crm\Binding\LeadContactTable,
	Bitrix\Crm\Binding\ContactCompanyTable;

use Bitrix\ImOpenLines\Model\TrackerTable;
use Bitrix\ImConnector\Connector;


class Tracker
{
	public const
		FIELD_PHONE = 'PHONE',
		FIELD_EMAIL = 'EMAIL',
		FIELD_IM = 'IM',
		FIELD_ID_FM = 'FM',

		ACTION_CREATE = 'CREATE',
		ACTION_EXTEND = 'EXTEND',
		ACTION_EXPECT = 'EXPECT',

		MESSAGE_ERROR_CREATE = 'CREATE',
		MESSAGE_ERROR_EXTEND = 'EXTEND'
	;

	public const ERROR_IMOL_TRACKER_NO_REQUIRED_PARAMETERS = 'ERROR IMOPENLINES TRACKER NO REQUIRED PARAMETERS';

	protected const EXPECTATION_LIVE_TIME = '30 days';
	protected const ALIAS_CODE_ATTEMPTS = 3;
	protected const ALIAS_CODE_LENGTH = 10;

	/** The prefix for trckerId. */
	public const PREFIX = 'btrx';


	/* @var Session $session */
	protected $session;

	/**
	 * @param Session $session
	 * @return self
	 */
	public function setSession(Session $session): self
	{
		$this->session = $session;
		return $this;
	}

	/**
	 * @return Session
	 */
	public function getSession(): Session
	{
		return $this->session;
	}

	/**
	 * @param array $params
	 * @return Result
	 */
	public function trackMessage(array $params): Result
	{
		$result = new Result();
		$result->setResult(false);
		$session = $this->getSession();

		self::loadPhrases();

		if (empty($session))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_CRM_ERROR_NO_SESSION'), Crm::ERROR_IMOL_NO_SESSION, __METHOD__));
		}

		if (empty($params['ID']) || empty($params['TEXT']))
		{
			$result->addError(new Error(Loc::getMessage('IMOL_TRACKER_ERROR_NO_REQUIRED_PARAMETERS'), self::ERROR_IMOL_TRACKER_NO_REQUIRED_PARAMETERS, __METHOD__));
		}

		if (
			$result->isSuccess()
			&& Loader::includeModule('crm')
			&& $session->getConfig('CRM') === 'Y'
			&& $session->getConfig('CRM_CHAT_TRACKER') === 'Y'
		)
		{
			$messageOriginId = (int)$params['ID'];
			$messageText = self::prepareMessage($params['TEXT']);

			if ($messageOriginId == 0 || $messageText == '')
			{
				$result->addError(new Error(Loc::getMessage('IMOL_TRACKER_ERROR_NO_REQUIRED_PARAMETERS'), self::ERROR_IMOL_TRACKER_NO_REQUIRED_PARAMETERS, __METHOD__));
			}

			if ($result->isSuccess())
			{
				$entitiesSearch = self::checkMessage($messageText);
				$phones = $entitiesSearch['PHONES'];
				$emails = $entitiesSearch['EMAILS'];

				if (!empty($phones) || !empty($emails))
				{
					$crmManager = $session->getCrmManager();
					if ($crmManager->isLoaded())
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

						$crmManager
							->setModeCreate($session->getConfig('CRM_CREATE'))
							->search()
						;

						$crmFieldsManager->setTitle($session->getChat()->getData('TITLE'));

						$crmManager->registrationChanges();
						$crmManager->sendCrmImMessages();
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Generates link to redirect into external messenger.
	 *
	 * @param int $lineId
	 * @param string $connectorId
	 * @param array{int, array{ENTITY_TYPE_ID: int, ENTITY_ID: int}} $crmEntities
	 * @return array{web: string, mob: string}
	 */
	public function getMessengerLink(int $lineId, string $connectorId, array $crmEntities = []): array
	{
		Loader::includeModule('imconnector');

		$aliasCode = null;
		$trackId  = $this->getExpectationTrackId($crmEntities);
		if ($connectorId == \Bitrix\ImConnector\Library::ID_NOTIFICATIONS_CONNECTOR)
		{
			Loader::includeModule('notifications');

			for ($i = 0; $i < self::ALIAS_CODE_ATTEMPTS; $i++)
			{
				$result = \Bitrix\Notifications\Alias::createForScenario(\Bitrix\Notifications\Settings::SCENARIO_VIRTUAL_WHATSAPP);
				if ($result->isSuccess())
				{
					$aliasCode = $result->getData()['CODE'];
					break;
				}
			}

			if (!$aliasCode)
			{
				return [];
			}

			if ($track = $this->findExpectationByTrackId($trackId))
			{
				$trackId = $track['ID'];
			}
		}

		$compositeCode = trim($aliasCode . $trackId);

		return Connector::getImMessengerUrl($lineId, $connectorId, $compositeCode);
	}

	/**
	 * Generates trackId code for crm entities enum.
	 *
	 * @param array{int, array{ENTITY_TYPE_ID: int, ENTITY_ID: int}} $params
	 * @return string
	 */
	public function getExpectationTrackId(array $params): string
	{
		if (!Loader::includeModule('crm'))
		{
			return '';
		}

		$filter = [
			'=ACTION' => self::ACTION_EXPECT,
			'>DATE_CREATE' => (new DateTime())->add('-'.self::EXPECTATION_LIVE_TIME),
		];

		$add = [];
		foreach ($params as $entity)
		{
			switch ($entity['ENTITY_TYPE_ID'])
			{
				case \CCrmOwnerType::Deal:
					$add['CRM_DEAL_ID'] = $entity['ENTITY_ID'];
					$filter['=CRM_DEAL_ID'] = $entity['ENTITY_ID'];
					if (empty($add['CRM_ENTITY_ID']))
					{
						$add['CRM_ENTITY_TYPE'] = \CCrmOwnerType::DealName;
						$add['CRM_ENTITY_ID'] = $entity['ENTITY_ID'];
					}
					break;
				case \CCrmOwnerType::Lead:
					$add['CRM_LEAD_ID'] = $entity['ENTITY_ID'];
					$filter['=CRM_LEAD_ID'] = $entity['ENTITY_ID'];
					if (empty($add['CRM_ENTITY_ID']))
					{
						$add['CRM_ENTITY_TYPE'] = \CCrmOwnerType::LeadName;
						$add['CRM_ENTITY_ID'] = $entity['ENTITY_ID'];
					}
					break;
				case \CCrmOwnerType::Contact:
					$add['CRM_CONTACT_ID'] = $entity['ENTITY_ID'];
					$filter['=CRM_CONTACT_ID'] = $entity['ENTITY_ID'];
					break;
				case \CCrmOwnerType::Company:
					$add['CRM_COMPANY_ID'] = $entity['ENTITY_ID'];
					$filter['=CRM_COMPANY_ID'] = $entity['ENTITY_ID'];
					break;
				default:
					$add['CRM_ENTITY_TYPE'] = \CCrmOwnerType::ResolveID($entity['ENTITY_TYPE_ID']);
					$add['CRM_ENTITY_ID'] = $entity['ENTITY_ID'];
					$filter['=CRM_ENTITY_TYPE'] = $add['CRM_ENTITY_TYPE'];
					$filter['=CRM_ENTITY_ID'] = $entity['ENTITY_ID'];
			}
		}

		$findResult = TrackerTable::getList([
			'select' => ['TRACK_ID'],
			'filter' => $filter,
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		]);
		if (
			($row = $findResult->fetch())
			&& !empty($row['TRACK_ID'])
		)
		{
			return $row['TRACK_ID'];
		}

		$trackId = self::PREFIX . Random::getString(self::ALIAS_CODE_LENGTH);

		$add['ACTION'] = self::ACTION_EXPECT;
		$add['TRACK_ID'] = $trackId;

		$addResult = TrackerTable::add($add);
		if (!$addResult->isSuccess())
		{
			return '';
		}

		return $trackId;
	}

	/**
	 * @param string $trackId
	 * @return array|null
	 */
	public function findExpectationByTrackId(string $trackId): ?array
	{
		$filter = [
			[
				'LOGIC' => 'OR',
				'=TRACK_ID' => $trackId,
				'=ID' => $trackId,
			],
			'=ACTION' => self::ACTION_EXPECT,
			'>DATE_CREATE' => (new DateTime())->add('-'.self::EXPECTATION_LIVE_TIME),
		];
		$select = ['ID'];
		foreach (TrackerTable::getMap() as $field => $fieldParam)
		{
			if (strpos($field, 'CRM_') === 0)
			{
				$select[] = $field;
			}
		}
		$findResult = TrackerTable::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		]);
		if ($row = $findResult->fetch())
		{
			return $row;
		}

		return null;
	}

	/**
	 * @param string $trackId
	 * @param Chat $chat
	 * @return void
	 */
	public function bindExpectationToChat(string $trackId, Chat $chat): void
	{
		if (!Loader::includeModule('crm'))
		{
			return;
		}

		$expectation = $this->findExpectationByTrackId($trackId);
		if ($expectation)
		{
			$crmManager = $this->getSession()->getCrmManager();
			$crmManager
				//->setSkipSearch()
				->setSkipCreate()
				->setSkipAutomationTriggerFirstMessage();

			$selector = $crmManager->getEntityManageFacility()->getSelector();

			$entityType = $expectation['CRM_ENTITY_TYPE'] ?? null;
			$entityId = (int)($expectation['CRM_ENTITY_ID'] ?? 0);
			$contactId = (int)($expectation['CRM_CONTACT_ID'] ?? 0);
			$companyId = (int)($expectation['CRM_COMPANY_ID'] ?? 0);
			$dealId = (int)($expectation['CRM_DEAL_ID'] ?? 0);
			$leadId = (int)($expectation['CRM_LEAD_ID'] ?? 0);

			$crmFields = [];
			$updateSession = [];
			if ($dealId)
			{
				$crmFields['DEAL'] = $dealId;
				$updateSession['CRM_CREATE_DEAL'] = 'Y';
				if (!$entityId)
				{
					$entityType = \CCrmOwnerType::DealName;
					$entityId = $dealId;
				}
				$selector->setEntity(\CCrmOwnerType::Deal, $entityId);
			}
			if ($leadId)
			{
				$crmFields['LEAD'] = $leadId;
				$updateSession['CRM_CREATE_LEAD'] = 'Y';
				if (!$entityId)
				{
					$entityType = \CCrmOwnerType::LeadName;
					$entityId = $leadId;
				}
				$selector->setEntity(\CCrmOwnerType::Lead, $leadId);
			}
			if ($contactId)
			{
				$crmFields['CONTACT'] = $contactId;
				$updateSession['CRM_CREATE_CONTACT'] = 'Y';
				$selector->setEntity(\CCrmOwnerType::Contact, $contactId);
			}
			if ($companyId)
			{
				$crmFields['COMPANY'] = $companyId;
				$updateSession['CRM_CREATE_COMPANY'] = 'Y';
				$selector->setEntity(\CCrmOwnerType::Company, $companyId);
			}
			if ($entityType && $entityId)
			{
				$crmFields['ENTITY_TYPE'] = $entityType;
				$crmFields['ENTITY_ID'] = $entityId;
				$selector->setEntity(\CCrmOwnerType::ResolveID($entityType), $entityId);
			}

			if ($crmFields)
			{
				$registerActivityResult = $crmManager->registrationChanges();
				if ($registerActivityResult->isSuccess())
				{
					$crmManager->sendCrmImMessages();

					$updateSession['CRM_ACTIVITY_ID'] = $registerActivityResult->getResult();
					$this->getSession()->updateCrmFlags($updateSession);

					$crmFields['CRM'] = 'Y';
					$chat->setCrmFlag($crmFields);

					$crmManager->updateUserConnector();
				}
			}
		}
	}

	//region OLD

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

		$map = Model\TrackerTable::getEntity();
		foreach ($update as $key => $value)
		{
			if (!$map->hasField($key))
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
	//endregion

	/**
	 * @param $messageText
	 * @return array
	 */
	protected static function checkMessage($messageText): array
	{
		return [

			//Phone
			'PHONES' => Tools\Phone::parseText($messageText),

			//Email
			'EMAILS' => Tools\Email::parseText($messageText),
		];
	}

	/**
	 * @param string $text
	 * @return string
	 */
	protected static function prepareMessage($text): string
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

	public static function loadPhrases(): void
	{
		Loc::loadMessages(__FILE__);
		Crm::loadMessages();
	}
}
