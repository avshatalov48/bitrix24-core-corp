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

	/** @var \CIMMessageParamAttach */
	protected $attach;

	/**
	 * Crm constructor.
	 * @param int $chatId
	 * @param int $operatorId
	 */
	protected function __construct($chatId = 0, $operatorId = 0)
	{
		//TODO: Keyboard button for canceling action
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

		//TODO: tracker for revert changes
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
	 * @param array<string, int[]> $entities
	 * @return void
	 */
	public function sendMessageAboutAddEntity(array $entities): void
	{
		$eventType = 'ADD_NEW';

		$this->sendMessageAboutEntity($entities, $eventType);
	}

	/**
	 * @param array<string, int[]> $entities
	 * @return void
	 */
	public function sendMessageAboutExtendEntity(array $entities): void
	{
		$eventType = 'EXTEND';

		$this->sendMessageAboutEntity($entities, $eventType);
	}

	/**
	 * @param array<string, int[]> $entities
	 * @return void
	 */
	public function sendMessageAboutUpdateEntity(array $entities): void
	{
		$eventType = 'UPDATE';

		$this->sendMessageAboutEntity($entities, $eventType);
	}

	/**
	 * Base function.
	 *
	 * @param array<string, int[]> $entities
	 * @param string $eventType
	 * @return void
	 */
	protected function sendMessageAboutEntity(array $entities, string $eventType): void
	{
		foreach ($entities as $entityType => $entityIds)
		{
			$message = Loc::getMessage('IMOL_MESSAGE_CRM_' . $entityType . '_' . $eventType);

			if (empty($message))
			{
				$message = Loc::getMessage('IMOL_MESSAGE_CRM_OTHER_' . $eventType);
			}

			Im::addMessage([
				'TO_CHAT_ID' => $this->chatId,
				'MESSAGE' => '[b]' . $message . '[/b]',
				'SYSTEM' => 'Y',
				'ATTACH' => $this->getEntityCard($entityType, $entityIds),
				'RECENT_ADD' => $this->getUserViewChat(),
			]);
		}
	}

	/**
	 * @param string $entityType
	 * @param int[] $entityIds
	 * @return \CIMMessageParamAttach|null
	 */
	protected function getEntityCard(string $entityType, array $entityIds): ?\CIMMessageParamAttach
	{
		if (
			Loader::includeModule('im')
			&& in_array($entityType, [ImOpenLines\Crm::ENTITY_LEAD, ImOpenLines\Crm::ENTITY_CONTACT, ImOpenLines\Crm::ENTITY_COMPANY, ImOpenLines\Crm::ENTITY_DEAL])
		)
		{
			$attach = new \CIMMessageParamAttach();

			foreach ($entityIds as $entityId)
			{
				$entityData = ImOpenLines\Crm\Common::get(
					$entityType,
					$entityId,
					true,
					[
						'ID',
						'TITLE',
						'FULL_NAME',
						'COMPANY_TITLE',
						'POST',
						'HAS_PHONE',
						'HAS_EMAIL',
					]
				);
				if (!$entityData)
				{
					continue;
				}

				$entityGrid = [];
				if ($entityType == ImOpenLines\Crm::ENTITY_LEAD)
				{
					if (isset($entityData['TITLE']))
					{
						$attach->addLink([
							'NAME' => $entityData['TITLE'],
							'LINK' => ImOpenLines\Crm\Common::getLink($entityType, $entityData['ID']),
						]);
					}

					if (
						!empty($entityData['FULL_NAME'])
						&& mb_strpos($entityData['TITLE'], $entityData['FULL_NAME']) === false
					)
					{
						$entityGrid[] = [
							'DISPLAY' => 'COLUMN',
							'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_FULL_NAME'),
							'VALUE' => $entityData['FULL_NAME']
						];
					}
					if (!empty($entityData['COMPANY_TITLE']))
					{
						$entityGrid[] = [
							'DISPLAY' => 'COLUMN',
							'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_COMPANY_TITLE'),
							'VALUE' => $entityData['COMPANY_TITLE']
						];
					}
					if (!empty($entityData['POST']))
					{
						$entityGrid[] = [
							'DISPLAY' => 'COLUMN',
							'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_POST'),
							'VALUE' => $entityData['POST']
						];
					}
				}
				elseif ($entityType == ImOpenLines\Crm::ENTITY_CONTACT)
				{
					if (isset($entityData['FULL_NAME']))
					{
						$attach->addLink([
							'NAME' => $entityData['FULL_NAME'],
							'LINK' => ImOpenLines\Crm\Common::getLink($entityType, $entityData['ID']),
						]);
					}

					if (!empty($entityData['POST']))
					{
						$entityGrid[] = [
							'DISPLAY' => 'COLUMN',
							'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_POST'),
							'VALUE' => $entityData['POST']
						];
					}
				}
				elseif ($entityType == ImOpenLines\Crm::ENTITY_COMPANY || $entityType == ImOpenLines\Crm::ENTITY_DEAL)
				{
					if (isset($entityData['TITLE']))
					{
						$attach->addLink([
							'NAME' => $entityData['TITLE'],
							'LINK' => ImOpenLines\Crm\Common::getLink($entityType, $entityData['ID']),
						]);
					}
				}

				if (
					isset($entityData['HAS_PHONE'])
					&& $entityData['HAS_PHONE'] == 'Y'
					&& isset($entityData['FM']['PHONE'])
				)
				{
					$fields = [];
					foreach ($entityData['FM']['PHONE'] as $phones)
					{
						foreach ($phones as $phone)
						{
							$fields[] = $phone;
						}
					}
					$entityGrid[] = [
						'DISPLAY' => 'LINE',
						'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_PHONE'),
						'VALUE' => implode('[br]', $fields),
						'HEIGHT' => '20'
					];
				}
				if ($entityData['HAS_EMAIL'] == 'Y' && $entityData['FM']['EMAIL'])
				{
					$fields = [];
					foreach ($entityData['FM']['EMAIL'] as $emails)
					{
						foreach ($emails as $email)
						{
							$fields[] = $email;
						}
					}
					$entityGrid[] = [
						'DISPLAY' => 'LINE',
						'NAME' => Loc::getMessage('IMOL_MESSAGE_CRM_CARD_EMAIL'),
						'VALUE' => implode('[br]', $fields),
						'HEIGHT' => '20'
					];
				}

				$attach->addGrid($entityGrid);
			}

			if (!$attach->isEmpty())
			{
				return $attach;
			}
		}

		return null;
	}
}