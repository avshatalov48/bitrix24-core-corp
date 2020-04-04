<?php
namespace Bitrix\ImOpenLines\Im\Messages;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Im;

Loc::loadMessages(__FILE__);

/**
 * Class Crm
 * @package Bitrix\ImOpenLines\Im
 */
class Crm
{
	protected $operatorId = 0;
	protected $chatId = 0;
	protected $userViewChat = false;

	/**
	 * Crm constructor.
	 * @param int $chatId
	 * @param int $operatorId
	 */
	protected function __construct($chatId = 0, $operatorId = 0)
	{
		//TODO: ���������� ��� ������ ��������
		/*$messageCode = 'IMOL_SESSION_'.$crmData['ENTITY_TYPE'].'_EXTEND';

		$keyboard = new \Bitrix\Im\Bot\Keyboard();
		$keyboard->addButton(Array(
			"TEXT" => Loc::getMessage('IMOL_TRACKER_BUTTON_CHANGE'),
			"FUNCTION" => "BX.MessengerCommon.linesChangeCrmEntity(#MESSAGE_ID#);",
			"DISPLAY" => "LINE",
			"CONTEXT" => "DESKTOP",
		));
		$keyboard->addButton(Array(
			"TEXT" => Loc::getMessage('IMOL_TRACKER_BUTTON_CANCEL'),
			"FUNCTION" => "BX.MessengerCommon.linesCancelCrmExtend(#MESSAGE_ID#);",
			"DISPLAY" => "LINE",
		));*/

		//TODO: ������ ��� ������ ���������
		/*if ($keyboard)
		{
			$result = TrackerTable::add(Array(
				'SESSION_ID' => intval($params['SESSION_ID']),
				'CHAT_ID' => $params['CHAT_ID'],
				'MESSAGE_ID' => $messageId,
				'USER_ID' => $params['USER_ID'],
				'ACTION' => Tracker::ACTION_EXTEND,
				'CRM_ENTITY_TYPE' => $crmData['ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $crmData['ENTITY_ID'],
				'FIELD_TYPE' => Tracker::FIELD_IM,
				'FIELD_VALUE' => 'imol|'.$params['USER_CODE'],
			));
			$crmData['CRM_TRACK_ID'] = $result->getId();
		}*/

		$this->chatId = $chatId;
		$this->operatorId = $operatorId;
	}

	/**
	 * @param int $chatId
	 * @param int $operatorId
	 * @return Crm|bool
	 */
	public static function init($chatId = 0, $operatorId = 0)
	{
		$result = false;

		$chatId = intval($chatId);
		$operatorId = intval($operatorId);

		if($chatId > 0)
		{
			$result = new self($chatId, $operatorId);
			$result->initUserViewChat();
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	protected function initUserViewChat()
	{
		if($this->operatorId > 0)
		{
			$this->userViewChat = \CIMContactList::InRecent($this->operatorId, IM_MESSAGE_OPEN_LINE, $this->chatId);
		}
		else
		{
			$this->userViewChat = true;
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function getUserViewChat()
	{
		$result = 'N';

		if($this->userViewChat != false)
		{
			$result = 'Y';
		}

		return $result;
	}

	/**
	 * @param $entityType
	 * @param $entityId
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function sendMessageAboutAddEntity($entityType, $entityId)
	{
		$eventType = 'ADD_NEW';

		return $this->sendMessageAboutEntity($entityType, $entityId, $eventType);
	}

	/**
	 * @param $entityType
	 * @param $entityId
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function sendMessageAboutExtendEntity($entityType, $entityId)
	{
		$eventType = 'EXTEND';

		return $this->sendMessageAboutEntity($entityType, $entityId, $eventType);
	}

	/**
	 * @param $entityType
	 * @param $entityId
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function sendMessageAboutUpdateEntity($entityType, $entityId)
	{
		$eventType = 'UPDATE';

		return $this->sendMessageAboutEntity($entityType, $entityId, $eventType);
	}

	/**
	 * Base function.
	 *
	 * @param $entityType
	 * @param $entityId
	 * @param $eventType
	 * @return mixed
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function sendMessageAboutEntity($entityType, $entityId, $eventType)
	{
		$result = Im::addMessage(Array(
			"TO_CHAT_ID" => $this->chatId,
			"MESSAGE" => '[b]'.Loc::getMessage('IMOL_MESSAGE_CRM_' . $entityType . '_' . $eventType).'[/b]',
			"SYSTEM" => 'Y',
			"ATTACH" => $this->getEntityCard($entityType, $entityId),
			"RECENT_ADD" => $this->getUserViewChat(),
			//"KEYBOARD" => $keyboard
		));

		return $result;
	}

	/**
	 * @param $entityType
	 * @param $entityId
	 * @return \CIMMessageParamAttach|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function getEntityCard($entityType, $entityId)
	{
		$result = null;

		if(Loader::includeModule('im') && in_array($entityType, Array(ImOpenLines\Crm::ENTITY_LEAD, ImOpenLines\Crm::ENTITY_CONTACT, ImOpenLines\Crm::ENTITY_COMPANY, ImOpenLines\Crm::ENTITY_DEAL)))
		{
			$entityData = ImOpenLines\Crm\Common::get($entityType, $entityId, true);

			$attach = new \CIMMessageParamAttach();

			$entityGrid = Array();
			if ($entityType == ImOpenLines\Crm::ENTITY_LEAD)
			{
				if (isset($entityData['TITLE']))
				{
					$attach->AddLink(Array(
						'NAME' => $entityData['TITLE'],
						'LINK' => ImOpenLines\Crm\Common::getLink($entityType, $entityData['ID']),
					));
				}

				if (!empty($entityData['FULL_NAME']) && strpos($entityData['TITLE'], $entityData['FULL_NAME']) === false)
				{
					$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_FULL_NAME'), 'VALUE' => $entityData['FULL_NAME']);
				}
				if (!empty($entityData['COMPANY_TITLE']))
				{
					$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_COMPANY_TITLE'), 'VALUE' => $entityData['COMPANY_TITLE']);
				}
				if (!empty($entityData['POST']))
				{
					$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_POST'), 'VALUE' => $entityData['POST']);
				}
			}
			elseif($entityType == ImOpenLines\Crm::ENTITY_CONTACT)
			{
				if (isset($entityData['FULL_NAME']))
				{
					$attach->AddLink(Array(
						'NAME' => $entityData['FULL_NAME'],
						'LINK' => ImOpenLines\Crm\Common::getLink($entityType, $entityData['ID']),
					));
				}

				if (!empty($entityData['POST']))
				{
					$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_POST'), 'VALUE' => $entityData['POST']);
				}
			}
			elseif($entityType == ImOpenLines\Crm::ENTITY_COMPANY || $entityType == ImOpenLines\Crm::ENTITY_DEAL)
			{
				if (isset($entityData['TITLE']))
				{
					$attach->AddLink(Array(
						'NAME' => $entityData['TITLE'],
						'LINK' => ImOpenLines\Crm\Common::getLink($entityType, $entityData['ID']),
					));
				}
			}

			if ($entityData['HAS_PHONE'] == 'Y' && isset($entityData['FM']['PHONE']))
			{
				$fields = Array();
				foreach ($entityData['FM']['PHONE'] as $phones)
				{
					foreach ($phones as $phone)
					{
						$fields[] = $phone;
					}
				}
				$entityGrid[] = Array('DISPLAY' => 'LINE', 'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_PHONE'), 'VALUE' => implode('[br]', $fields), 'HEIGHT' => '20');
			}
			if ($entityData['HAS_EMAIL'] == 'Y' && $entityData['FM']['EMAIL'])
			{
				$fields = Array();
				foreach ($entityData['FM']['EMAIL'] as $emails)
				{
					foreach ($emails as $email)
					{
						$fields[] = $email;
					}
				}
				$entityGrid[] = Array('DISPLAY' => 'LINE', 'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_EMAIL'), 'VALUE' => implode('[br]', $fields), 'HEIGHT' => '20');
			}
			$attach->AddGrid($entityGrid);

			$result = $attach;
		}

		return $result;
	}
}