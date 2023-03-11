<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage imopenlines
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\ImOpenLines\Widget;

use	Bitrix\ImOpenLines\Crm;
use	Bitrix\ImOpenLines\Chat;
use	Bitrix\ImOpenLines\Tools;
use	Bitrix\ImOpenLines\Error;
use	Bitrix\ImOpenLines\Config;
use	Bitrix\ImOpenLines\Session;
use	Bitrix\ImOpenLines\Crm\Common as CrmCommon;

use Bitrix\Main\ErrorCollection;
use	Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use	Bitrix\Main\SystemException;
use	Bitrix\Main\Localization\Loc;

use Bitrix\Im\User as ImUser;

Loc::loadMessages(__FILE__);

class FormHandler
{
	public const FORM_COMPONENT_NAME = 'bx-imopenlines-form';
	public const EVENT_POSTFIX = 'Openlines';
	private const FORM_CODE = 'welcome';
	private const ERROR_CHAT_LOADING = 'IMOL_FORM_ERROR_CHAT_LOADING';
	private const ERROR_USER_CODE = 'IMOL_FORM_ERROR_USER_CODE';
	private const WELCOME_FORM_FILLED_MESSAGE = 'WELCOME_FORM_FILLED_MESSAGE';

	/** @var ErrorCollection */
	public $errorCollection;
	/** @var array */
	private $eventData;
	/** @var integer */
	private $messageId = 0; /* message id > 0 indicates that form was sent as visual component */
	/** @var boolean */
	private $isWelcomeForm = false; /* flag indicates that form was sent automatically as a welcome form */
	/** @var string */
	private $userCode;
	/** @var string */
	private $connectorId;
	/** @var string */
	private $clientChatId;
	/** @var string */
	private $userId;
	/** @var array */
	private $crmFields;
	/** @var array */
	private $crmEntities;
	/** @var Chat */
	private $chat;
	/** @var Chat */
	private $clientChat;
	/** @var ImUser */
	private $user;
	/** @var string */
	private $configId;
	/** @var Config */
	private $config;
	/** @var Session */
	private $session;
	/** @var boolean */
	private $sessionStarted = false;

	/**
	 * Class can't be instantiated via constructor, only by onOpenlinesFormFilled method
	 */
	private function __construct($eventData)
	{
		$this->errorCollection = new ErrorCollection();

		$this->eventData = $eventData;
		$this->userCode = self::decodeConnectorName($eventData['properties']['openlinesCode']);

		if (isset($eventData['properties']['messageId']))
		{
			$this->messageId = (int)$eventData['properties']['messageId'];
		}

		if (isset($eventData['properties']['isWelcomeForm']))
		{
			$this->isWelcomeForm = $eventData['properties']['isWelcomeForm'] === 'Y';
		}
	}

	/**
	 * Event handler for 'crm::onSiteFormFilledOpenlines' event (class entry point)
	 * @see \Bitrix\Crm\WebForm\ResultEntity::add
	 * @param Event $event
	 *
	 * @return bool
	 * @throws SystemException
	 */
	public static function onOpenlinesFormFilled(Event $event): bool
	{
		$eventData = $event->getParameters();

		$formHandler = new self($eventData);
		$formHandler->init();
		if (!$formHandler->errorCollection->isEmpty())
		{
			throw new SystemException($formHandler->errorCollection[0]->getMessage());
		}

		$formHandler->loadSession();
		$formHandler->updateUser();
		$formHandler->updateChatTitle();
		$formHandler->sendMessages();
		$formHandler->updateCrmBindings();
		$formHandler->updateSession();
		$formHandler->updateFormFilledFlag();

		return true;
	}

	/**
	 * Event handler for 'crm::onSiteFormFillOpenlines' event (event is fired before CRM entities are created)
	 * @see \Bitrix\Crm\WebForm\ResultEntity::add
	 *
	 * @param Event $event
	 * @return bool
	 */
	public static function onOpenlinesFormFill(Event $event): bool
	{
		$eventData = $event->getParameters();
		$userCode = self::decodeConnectorName($eventData['properties']['openlinesCode']);

		if (empty($userCode))
		{
			$result = new EventResult(EventResult::ERROR, [
				'error' => 'User code error',
				'errorCode' => self::ERROR_USER_CODE,
			]);
			$event->addResult($result);
			return false;
		}

		$session = new Session();
		$session->load([
			'USER_CODE' => $userCode,
			'SKIP_CREATE' => 'Y'
		]);

		$crmManager = new Crm($session);
		$assignedUserId = $crmManager->getResponsibleCrmId();

		$event->addResult(new EventResult(EventResult::SUCCESS, [
			'assignedById' => $assignedUserId
		]));

		return true;
	}

	/**
	 * Parse OL code, read crm data, init chat, config and user
	 */
	private function init(): bool
	{
		$parsedOpenlinesCode = Chat::parseLinesChatEntityId($this->userCode);
		$this->connectorId = $parsedOpenlinesCode['connectorId'];
		$this->configId = $parsedOpenlinesCode['lineId'];
		$this->clientChatId = $parsedOpenlinesCode['connectorChatId'];
		$this->userId = $parsedOpenlinesCode['connectorUserId'];

		if (!$this->configId || !$this->clientChatId || !$this->userId)
		{
			$this->errorCollection[] = new Error('User code error', self::ERROR_USER_CODE);

			return false;
		}

		if (!$this->initCrmFields())
		{
			return false;
		}
		if (!$this->initChat())
		{
			return false;
		}
		$this->initConfig();
		$this->initUser();

		return true;
	}

	/**
	 * Parse entities from event data, initialize needed fields (name, phone, email).
	 */
	private function initCrmFields(): bool
	{
		$reader = new Crm\Reader();
		if (!$reader->errorCollection->isEmpty())
		{
			$this->errorCollection->add($reader->errorCollection->getValues());

			return false;
		}
		$this->crmEntities = $this->eventData['result']['entities'];

		$fields = $reader->getFieldsFromMixedEntities($this->eventData['result']['entities']);
		$this->crmFields['FIRST_NAME'] = $fields['FIRST_NAME'];
		$this->crmFields['LAST_NAME'] = $fields['LAST_NAME'];
		$this->crmFields['PHONE'] = $fields['PHONE'];
		$this->crmFields['EMAIL'] = $fields['EMAIL'];

		return true;
	}

	/**
	 * Initialize operator chat and user chat
	 */
	private function initChat(): bool
	{
		if (
			$this->session instanceof Session
			&& $this->session->getChat() instanceof Chat
		)
		{
			$this->chat = $this->session->getChat();
		}
		else
		{
			$this->chat = new Chat();
			if ($this->session instanceof Session)
			{
				$this->session->setChat($this->chat);
			}
		}
		if (!$this->chat->isDataLoaded())
		{
			$chatLoadResult = $this->chat->load(['USER_CODE' => $this->userCode, 'USER_ID' => $this->userId]);
			if (!$chatLoadResult)
			{
				$this->errorCollection->setError(new Error('Chat loading error', self::ERROR_CHAT_LOADING));

				return false;
			}
		}

		$this->clientChat = new Chat($this->clientChatId);

		return true;
	}

	/**
	 * Initialize config by configId
	 */
	private function initConfig(): bool
	{
		$configManager = new Config();
		$this->config = $configManager->get($this->configId);

		return true;
	}

	/**
	 * Initialize user by userId
	 */
	private function initUser(): bool
	{
		$this->user = ImUser::getInstance($this->userId);

		return true;
	}

	/**
	 * Check, validate and return fields to update in User (array with NAME, PHONE, EMAIL keys)
	 */
	private function getUserFieldsToUpdate(): array
	{
		$fieldsToUpdate = [];

		if ($this->crmFields['FIRST_NAME'])
		{
			if ($this->user->getName() !== $this->crmFields['FIRST_NAME'])
			{
				$fieldsToUpdate['NAME'] = $this->crmFields['FIRST_NAME'];
			}
		}
		if ($this->crmFields['LAST_NAME'])
		{
			if ($this->user->getLastName() !== $this->crmFields['LAST_NAME'])
			{
				$fieldsToUpdate['LAST_NAME'] = $this->crmFields['LAST_NAME'];
			}
		}
		//validate, check and update email
		$email = $this->getEmailFieldValue();
		if (
			$email
			&& Tools\Email::validate($email)
			&& !Tools\Email::isSame($this->user->getEmail(), $email)
		)
		{
			$fieldsToUpdate['EMAIL'] = Tools\Email::normalize($email);
		}
		//validate, check and update phone
		if (
			$this->crmFields['PHONE']
			&& Tools\Phone::validate($this->crmFields['PHONE'])
			&& !Tools\Phone::isSame($this->user->getPhone(ImUser::PHONE_MOBILE), $this->crmFields['PHONE'])
		)
		{
			$fieldsToUpdate['PERSONAL_MOBILE'] = $this->crmFields['PHONE'];
		}

		return $fieldsToUpdate;
	}

	/**
	 * Return string ("#USER_NAME# - #LINE_NAME#") to update chat title
	 */
	private function prepareChatTitle(): string
	{
		$title = '';

		if ($this->crmFields['FIRST_NAME'] || $this->crmFields['LAST_NAME'])
		{
			$titleParams = $this->chat->getTitle(
				$this->config['LINE_NAME'],
				trim($this->crmFields['FIRST_NAME'] . ' ' . $this->crmFields['LAST_NAME'])
			);
			$title = $titleParams['TITLE'];
		}

		return $title;
	}

	/**
	 * Prepare attach for message about filling the form
	 */
	private function prepareFormFilledAttach(): \CIMMessageParamAttach
	{
		$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::CHAT);

		$activityId = 0;
		foreach ($this->crmEntities as $entity)
		{
			if ($entity['ENTITY_TYPE'] === \CCrmOwnerType::ActivityName)
			{
				$activityId = $entity['ENTITY_ID'];
			}
		}

		if ($activityId === 0)
		{
			return $attach;
		}

		$activityLink = Crm\Common::getLink(\CCrmOwnerType::ActivityName, $activityId);

		$attach->AddLink([
			'NAME' => Loc::getMessage('IMOL_LCC_FORM_ACTIVITY_LINK'),
			'LINK' => $activityLink
		]);

		return $attach;
	}

	/**
	 * Load and initialize session by ENTITY_ID
	 */
	private function loadSession(): bool
	{
		if ($this->isWelcomeForm && !$this->messageId)
		{
			return false;
		}

		$this->session = new Session();
		if ($this->chat instanceof Chat)
		{
			$this->session->setChat($this->chat);
		}
		$this->sessionStarted = $this->session->load([
			'USER_CODE' => $this->chat->getData('ENTITY_ID'),
			'SKIP_CREATE' => 'Y'
		]);

		if (
			!($this->chat instanceof Chat)
			&& ($this->session->getChat() instanceof Chat)
		)
		{
			$this->chat = $this->session->getChat();
		}

		return true;
	}

	/**
	 * Update session SEND_FORM parameter
	 */
	private function updateSession(): bool
	{
		if ($this->isWelcomeForm && !$this->messageId)
		{
			return false;
		}

		if ($this->sessionStarted)
		{
			$this->session->update([
				'SEND_FORM' => mb_strtolower(self::FORM_CODE)
			]);
		}

		return true;
	}

	/**
	 * Checks if message to operator should be shown in messenger recent list
	 */
	private function isAddingToRecentNeeded(): bool
	{
		if ($this->isWelcomeForm && !$this->messageId)
		{
			return false;
		}

		if ($this->session->getData('OPERATOR_ID') > 0)
		{
			$isAddingNeeded = \CIMContactList::InRecent(
				$this->session->getData('OPERATOR_ID'),
				IM_MESSAGE_OPEN_LINE,
				$this->session->getData('CHAT_ID')
			);
		}
		else
		{
			$isAddingNeeded = true;
		}

		return $isAddingNeeded;
	}

	/**
	 * Add OL code to CRM entity multifield (lead or contact)
	 */
	private function addLinesBindingToCrm(): bool
	{
		$imolCode = 'imol|' . $this->userCode;

		$multiFields = ['IM' => []];
		$communicationType = CrmCommon::getCommunicationType($this->userCode);
		$multiFields['IM'][$communicationType][] = $imolCode;
		$fieldsToUpdate['FM'] = CrmCommon::formatMultifieldFields($multiFields);

		$entityToUpdate = null;
		foreach ($this->crmEntities as $entity)
		{
			if ($entity['ENTITY_TYPE'] === Crm::ENTITY_LEAD || $entity['ENTITY_TYPE'] === Crm::ENTITY_CONTACT)
			{
				$entityToUpdate = [
					'TYPE' => $entity['ENTITY_TYPE'],
					'ID' => $entity['ENTITY_ID']
				];
			}
		}

		if (!$entityToUpdate)
		{
			return false;
		}

		CrmCommon::update($entityToUpdate['TYPE'], $entityToUpdate['ID'], $fieldsToUpdate);

		return true;
	}

	/**
	 * Add CRM bindings to chat fields ENTITY_DATA_1 and ENTITY_DATA_2
	 */
	private function addCrmBindingToLines(): bool
	{
		$updateFields = [];
		$updateSession = [];
		foreach ($this->crmEntities as $entity)
		{
			switch ($entity['ENTITY_TYPE'])
			{
				case \CCrmOwnerType::LeadName:
					$updateFields['LEAD'] = $entity['ENTITY_ID'];
					break;

				case \CCrmOwnerType::DealName:
					$updateFields['DEAL'] = $entity['ENTITY_ID'];
					break;

				case \CCrmOwnerType::ContactName:
					$updateFields['CONTACT'] = $entity['ENTITY_ID'];
					break;

				case \CCrmOwnerType::CompanyName:
					$updateFields['COMPANY'] = $entity['ENTITY_ID'];
					break;
			}
		}

		//For backward compatibility, the most up-to-date entity.
		if (empty($updateFields))
		{
			return false;
		}

		if (!empty($updateFields['DEAL']))
		{
			$updateFields['ENTITY_TYPE'] = \CCrmOwnerType::DealName;
			$updateFields['ENTITY_ID'] = $updateFields['DEAL'];
			$updateSession['CRM_CREATE_DEAL'] = 'Y';
		}
		else if (!empty($updateFields['LEAD']))
		{
			$updateFields['ENTITY_TYPE'] = \CCrmOwnerType::LeadName;
			$updateFields['ENTITY_ID'] = $updateFields['LEAD'];
			$updateSession['CRM_CREATE_LEAD'] = 'Y';
		}
		else if (!empty($updateFields['COMPANY']))
		{
			$updateFields['ENTITY_TYPE'] = \CCrmOwnerType::CompanyName;
			$updateFields['ENTITY_ID'] = $updateFields['COMPANY'];
			$updateSession['CRM_CREATE_COMPANY'] = 'Y';
		}
		else if (!empty($updateFields['CONTACT']))
		{
			$updateFields['ENTITY_TYPE'] = \CCrmOwnerType::ContactName;
			$updateFields['ENTITY_ID'] = $updateFields['CONTACT'];
			$updateSession['CRM_CREATE_CONTACT'] = 'Y';
		}

		$updateFields['CRM'] = 'Y';

		if ($this->chat instanceof Chat)
		{
			$this->chat->setCrmFlag($updateFields);
		}
		if ($this->session instanceof Session)
		{
			$this->session->updateCrmFlags($updateSession);
		}

		return true;
	}

	/**
	 * Create chat activity in CRM entities timeline
	 */
	private function createCrmActivity(): bool
	{
		if ($this->isWelcomeForm && !$this->messageId)
		{
			return false;
		}

		$crmManager = new Crm($this->session);
		$bindings = [];
		$entities = [];
		foreach ($this->crmEntities as $entity)
		{
			if (in_array($entity['ENTITY_TYPE'], [Crm::ENTITY_CONTACT, Crm::ENTITY_DEAL, Crm::ENTITY_LEAD], true))
			{
				$entityTypeId = \CCrmOwnerType::ResolveId($entity['ENTITY_TYPE']);
				$bindings[] = [
					'OWNER_TYPE_ID' => $entityTypeId,
					'OWNER_ID' => $entity['ENTITY_ID']
				];
				$entities[] = [
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => $entity['ENTITY_ID']
				];
			}
		}
		$userCode = $this->session->getData('USER_CODE');
		if (!$userCode)
		{
			return false;
		}

		$activityName = Loc::getMessage('IMOL_CRM_CREATE_ACTIVITY_2',
			['#LEAD_NAME#' => $this->chat->getData('TITLE'), '#CONNECTOR_NAME#' => CrmCommon::getSourceName($userCode)]
		);

		$result = Crm\Activity::add([
			'LINE_ID' => $this->configId,
			'NAME' => $activityName,
			'SESSION_ID' => $this->session->getData('ID'),
			'MODE' => $this->session->getData('MODE'),
			'BINDINGS' => $bindings,
			'OPERATOR_ID' => $crmManager->getResponsibleCrmId(),
			'USER_CODE' => $userCode,
			'CONNECTOR_ID' => $this->connectorId,
			'ENTITES' => $entities
		]);

		if ($result->isSuccess())
		{
			$this->session->updateCrmFlags([
				'CRM_ACTIVITY_ID' => $result->getResult()
			]);
		}

		return $result->isSuccess();
	}

	/**
	 * Send message to client to show filled status of form (only for "Before dialog start" option)
	 */
	private function sendFormFilledMessageToClient()
	{
		$messageParams = [
			'MESSAGE_TYPE' => IM_MESSAGE_OPEN_LINE,
			"TO_CHAT_ID" => $this->clientChat->getData('ID'),
			"SYSTEM" => 'Y',
			'SKIP_CONNECTOR' => 'Y',
			"MESSAGE" => self::WELCOME_FORM_FILLED_MESSAGE,
			"PARAMS" => [
				"COMPONENT_ID" => self::FORM_COMPONENT_NAME,
				"IS_WELCOME_FORM" => 'Y',
				"CRM_FORM_FILLED" => 'Y',
				"CRM_FORM_ID" => '',
				"CRM_FORM_SEC" => ''
			]
		];

		return \CIMMessenger::Add($messageParams);
	}

	/**
	 * Send message to operator with form name and link to crm activity
	 */
	private function sendFormFilledMessageToOperator()
	{
		$crmForm = new \Bitrix\Crm\WebForm\Form($this->eventData['id']);
		$welcomeFormName = $crmForm->get()['NAME'];

		$messageParams = [
			"TO_CHAT_ID" => $this->chat->getData('ID'),
			"MESSAGE_TYPE" => IM_MESSAGE_OPEN_LINE,
			"SYSTEM" => 'Y',
			"SKIP_CONNECTOR" => 'Y',
			"MESSAGE" => '[B]' . Loc::getMessage('IMOL_LCC_FORM_SUBMIT') . ' "' . $welcomeFormName . '"[/B]',
			"ATTACH" => $this->prepareFormFilledAttach(),
		];

		$messageParams['RECENT_ADD'] = 'N';
		$addToRecent = $this->isAddingToRecentNeeded();
		if (($this->sessionStarted && $this->session->isNowCreated()) || $addToRecent)
		{
			$messageParams['RECENT_ADD'] = 'Y';
		}

		return \CIMMessenger::Add($messageParams);
	}

	/**
	 * Send message to operator about creating and updating CRM entities
	 */
	private function sendCrmEntitiesMessages(): bool
	{
		if ($this->isWelcomeForm && !$this->messageId)
		{
			return false;
		}

		$messageManager = \Bitrix\ImOpenLines\Im\Messages\Crm::init(
			$this->session->getData('CHAT_ID'),
			$this->session->getData('OPERATOR_ID')
		);

		if (!$messageManager)
		{
			return false;
		}

		$createdEntities = [];
		$updatedEntities = [];
		foreach ($this->crmEntities as $entity)
		{
			if ($entity['ENTITY_TYPE'] === Crm::ENTITY_ACTIVITY)
			{
				continue;
			}

			if ($entity['IS_DUPLICATE'] === true)
			{
				$updatedEntities[$entity['ENTITY_TYPE']][] = $entity['ENTITY_ID'];
			}
			else
			{
				$createdEntities[$entity['ENTITY_TYPE']][] = $entity['ENTITY_ID'];
			}
		}

		if (!empty($createdEntities))
		{
			$messageManager->sendMessageAboutAddEntity($createdEntities);
		}

		if (!empty($updatedEntities))
		{
			$messageManager->sendMessageAboutExtendEntity($updatedEntities);
		}

		return true;
	}

	/**
	 * Update user name, phone and email (if needed)
	 */
	private function updateUser(): bool
	{
		$fieldsToUpdate = $this->getUserFieldsToUpdate();
		if (!empty($fieldsToUpdate))
		{
			$userClass = new \CUser();
			$userClass->Update($this->userId, $fieldsToUpdate);
			ImUser::clearStaticCache();
		}

		return true;
	}

	/**
	 * Update chat title (if needed)
	 */
	private function updateChatTitle(): bool
	{
		$newTitle = $this->prepareChatTitle();

		if ($newTitle)
		{
			$this->chat->update(['TITLE' => $newTitle]);
		}

		return true;
	}

	/**
	 * Send messages about filled form and crm entities to operator and client
	 */
	private function sendMessages(): bool
	{
		if ($this->isWelcomeForm && !$this->messageId)
		{
			$this->sendFormFilledMessageToClient();
		}

		$this->sendFormFilledMessageToOperator();
		$this->sendCrmEntitiesMessages();

		return true;
	}

	/**
	 * Update crm bindings (if needed)
	 */
	private function updateCrmBindings(): bool
	{
		$sessionField = $this->chat->getFieldData(Chat::FIELD_SESSION);
		// OL already created CRM entities
		if (isset($sessionField['CRM']) && $sessionField['CRM'] === 'Y')
		{
			return false;
		}

		$this->addLinesBindingToCrm();
		$this->addCrmBindingToLines();
		$this->createCrmActivity();

		return true;
	}

	private function updateFormFilledFlag(): bool
	{
		if (!$this->messageId)
		{
			return false;
		}

		\CIMMessageParam::Set($this->messageId, ['CRM_FORM_FILLED' => 'Y']);
		\CIMMessageParam::SendPull($this->messageId);

		return true;
	}

	private function getEmailFieldValue()
	{
		if (empty($this->eventData['fields']))
		{
			return null;
		}

		$email = null;
		foreach ($this->eventData['fields'] as $entity)
		{
			if (empty($entity['FM']) || empty($entity['FM']['EMAIL']))
			{
				continue;
			}

			$firstEmailKey = array_key_first($entity['FM']['EMAIL']);
			if (empty($entity['FM']['EMAIL'][$firstEmailKey]) || empty($entity['FM']['EMAIL'][$firstEmailKey]['VALUE']))
			{
				continue;
			}

			$email = $entity['FM']['EMAIL'][$firstEmailKey]['VALUE'];
		}

		return $email;
	}

	/**
	 * Build message text about sending a form to chat for operator side
	 *
	 * @param string $formName
	 * @return string
	 */
	public static function buildSentFormMessageForOperator(string $formName): string
	{
		return '[B]' . Loc::getMessage('IMOL_LCC_FORM_SENT') . ' "' . $formName . '"[/B]';
	}

	/**
	 * Build message text about sending a form to chat for client side
	 *
	 * @param string $formLink
	 * @return string
	 */
	public static function buildSentFormMessageForClient(string $formLink): string
	{
		return Loc::getMessage('IMOL_LCC_FILL_FORM') . '[BR]' . $formLink;
	}

	/**
	 * Custom connector names can contain '_', which is reserved for CRM-forms personalization
	 * After form is filled we will replace symbols again
	 */
	public static function encodeConnectorName(string $userCode): string
	{
		return str_replace('_', '!', $userCode);
	}

	/**
	 * Restore original connector name
	 */
	public static function decodeConnectorName(string $userCode): string
	{
		return str_replace('!', '_', $userCode);
	}
}
