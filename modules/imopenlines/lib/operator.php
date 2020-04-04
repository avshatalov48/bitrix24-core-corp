<?php

namespace Bitrix\ImOpenLines;

use Bitrix\ImOpenlines\QuickAnswers\ListsDataManager;
use Bitrix\ImOpenlines\QuickAnswers\QuickAnswer;
use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

use Bitrix\ImOpenlines\Security\Permissions;
use Bitrix\ImOpenlines\Security\Helper;

Loc::loadMessages(__FILE__);

class Operator
{
	private $chatId = 0;
	private $userId = 0;
	private $error = null;
	private $moduleLoad = false;

	public function __construct($chatId, $userId = null)
	{
		$imLoad = \Bitrix\Main\Loader::includeModule('im');
		$pullLoad = \Bitrix\Main\Loader::includeModule('pull');
		if ($imLoad && $pullLoad)
		{
			$this->error = new BasicError(null, '', '');
			$this->moduleLoad = true;
		}
		else
		{
			if (!$imLoad)
			{
				$this->error = new BasicError(__METHOD__, 'IM_LOAD_ERROR', Loc::getMessage('IMOL_OPERATOR_ERROR_IM_LOAD'));
			}
			elseif (!$pullLoad)
			{
				$this->error = new BasicError(__METHOD__, 'IM_LOAD_ERROR', Loc::getMessage('IMOL_OPERATOR_ERROR_PULL_LOAD'));
			}
		}

		$this->chatId = intval($chatId);

		if (is_null($userId))
		{
			$userId = $GLOBALS['USER']->GetId();
		}
		$this->userId = intval($userId);
	}

	private function checkAccess()
	{
		if (!$this->moduleLoad)
		{
			return [
				'RESULT' => false
			];
		}

		if ($this->chatId <= 0)
		{
			$this->error = new BasicError(__METHOD__, 'CHAT_ID', Loc::getMessage('IMOL_OPERATOR_ERROR_CHAT_ID'));

			return [
				'RESULT' => false
			];
		}
		if ($this->userId <= 0)
		{
			$this->error = new BasicError(__METHOD__, 'USER_ID', Loc::getMessage('IMOL_OPERATOR_ERROR_USER_ID'));

			return [
				'RESULT' => false
			];
		}

		$orm = \Bitrix\Im\Model\RelationTable::getList([
			"select" => ["ID", "ENTITY_TYPE" => "CHAT.ENTITY_TYPE"],
			"filter" => [
				"=CHAT_ID" => $this->chatId,
				"=USER_ID" => $this->userId,
			],
		]);

		if ($relation = $orm->fetch())
		{
			if ($relation["ENTITY_TYPE"] != "LINES")
			{
				$this->error = new BasicError(__METHOD__, 'CHAT_TYPE', Loc::getMessage('IMOL_OPERATOR_ERROR_CHAT_TYPE'));

				return Array(
					'RESULT' => false
				);
			}
		}
		else
		{
			$ormChat = \Bitrix\Im\Model\ChatTable::getById($this->chatId);
			if($chat = $ormChat->fetch())
			{
				if($chat['TYPE'] == IM_MESSAGE_OPEN_LINE)
				{
					$parsedUserCode = Session\Common::parseUserCode($chat['ENTITY_ID']);
					$lineId = $parsedUserCode['CONFIG_ID'];
					$fieldData = explode("|", $chat['ENTITY_DATA_1']);
					if(!\Bitrix\ImOpenLines\Config::canJoin($lineId, ($fieldData[0] == 'Y'? $fieldData[1]: null), ($fieldData[0] == 'Y'? $fieldData[2]: null)))
					{
						$this->error = new BasicError(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));

						return Array(
							'RESULT' => false
						);
					}
				}
				else
				{
					$this->error = new BasicError(__METHOD__, 'CHAT_TYPE', Loc::getMessage('IMOL_OPERATOR_ERROR_CHAT_TYPE'));

					return Array(
						'RESULT' => false
					);
				}
			}
			else
			{
				$this->error = new BasicError(__METHOD__, 'CHAT_ID', Loc::getMessage('IMOL_OPERATOR_ERROR_CHAT_ID'));

				return Array(
					'RESULT' => false
				);
			}
		}

		return Array(
			'RESULT' => true
		);
	}

	/**
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function answer()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->answer($this->userId);

		return true;
	}

	/**
	 * Skip the dialogue.
	 *
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function skip()
	{
		$result = false;

		$access = $this->checkAccess();
		if ($access['RESULT'])
		{
			$chat = new Chat($this->chatId);
			$result = $chat->skip($this->userId);
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function transfer(array $params)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'] || empty($params['TRANSFER_ID']))
		{
			return false;
		}
		if ($this->userId == $params['TRANSFER_ID'])
		{
			return false;
		}

		if (substr($params['TRANSFER_ID'], 0, 5) == 'queue')
		{
			\CUserCounter::Increment($this->userId, 'imopenlines_transfer_count_'.substr($params['TRANSFER_ID'], 5));
		}

		$chat = new Chat($this->chatId);
		$chat->transfer([
			'FROM' => $this->userId,
			'TO' => $params['TRANSFER_ID']
		]);

		return true;
	}

	public function setSilentMode($active = true)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->setSilentMode($active);

		return true;
	}

	/**
	 * @param bool $active
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function setPinMode($active = true)
	{
		$result = false;

		$access = $this->checkAccess();
		if ($access['RESULT'])
		{
			$chat = new Chat($this->chatId);
			$chat->setPauseFlag([
				'ACTIVE' => $active,
				'USER_ID' => $this->userId
			]);

			$result = true;
		}

		return $result;
	}

	public function closeDialog()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->finish();

		return true;
	}

	public function markSpam()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->markSpamAndFinish($this->userId);

		return true;
	}

	public function interceptSession()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->intercept($this->userId);

		return true;
	}

	public function createLead()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$result = $chat->createLead();
		if ($result)
		{
			$this->error = new BasicError(__METHOD__, 'CREATE_ERROR', 'CREATE_ERROR');
		}

		return $result;
	}

	public function cancelCrmExtend($messageId)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Tracker();
		return $chat->cancel($messageId);
	}

	/**
	 * @deprecated
	 */
	public function changeCrmEntity($messageId, $entityType, $entityId)
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Tracker();
		return $chat->change($messageId, $entityType, $entityId);
	}

	public function joinSession()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->join($this->userId, false);

		return true;
	}

	public function openChat($userCode)
	{
		if (\Bitrix\Im\User::getInstance($this->userId)->isExtranet())
			return false;

		$chat = new Chat();
		$result = $chat->load(Array(
			'USER_CODE' => $userCode,
			'ONLY_LOAD' => 'Y',
		));
		if ($result)
		{
			$parsedUserCode = Session\Common::parseUserCode($userCode);
			$lineId = $parsedUserCode['CONFIG_ID'];
			if ($chat->getData('AUTHOR_ID') != $this->userId)
			{
				$sessionField = $chat->getFieldData(Chat::FIELD_SESSION);
				$sessionCrmField = $chat->getFieldData(Chat::FIELD_CRM);
				$result = false;
				if(empty($sessionCrmField))
				{
					if (\Bitrix\ImOpenLines\Config::canJoin($lineId, $sessionField['CRM_ENTITY_TYPE'], $sessionField['CRM_ENTITY_ID']))
					{
						$result = true;
					}
				}
				else
				{
					foreach ($sessionCrmField as $crmEntityType => $crmEntityId)
					{
						if (\Bitrix\ImOpenLines\Config::canJoin($lineId, $crmEntityType, $crmEntityId))
						{
							$result = true;
						}
					}
				}
			}
		}

		if ($result)
		{
			return $chat->getData();
		}
		else
		{
			$this->error = new BasicError(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
			return false;
		}
	}

	public function voteAsHead($sessionId, $rating = null, $comment = null)
	{
		Session::voteAsHead($sessionId, $rating, $comment);

		return true;
	}

	public function startSession()
	{
		$access = $this->checkAccess();
		if (!$access['RESULT'])
		{
			return false;
		}

		$chat = new Chat($this->chatId);
		$chat->startSession($this->userId);

		return true;
	}

	/**
	 * @param $messageId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function startSessionByMessage($messageId)
	{
		$result = false;

		$access = $this->checkAccess();
		if ($access['RESULT'])
		{
			$chat = new Chat($this->chatId);
			$chat->startSessionByMessage($this->userId, $messageId);

			$result = true;
		}

		return $result;
	}

	/**
	 * @param $messageId
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function saveToQuickAnswers($messageId)
	{
		$message = \CIMMessenger::GetById($messageId);
		if($message)
		{
			$lineId = Session\Common::getConfigIdByChatId($this->chatId);
			if($lineId > 0)
			{
				$listsDataManager = new ListsDataManager($lineId);
				if($listsDataManager->isHasRights())
				{
					QuickAnswer::setDataManager($listsDataManager);
					$answer = reset(QuickAnswer::getList(array('MESSAGEID' => $messageId)));
					if($answer)
					{
						$answer->update(array('TEXT' => $message['MESSAGE']));
					}
					else
					{
						$answer = reset(QuickAnswer::getList(array('TEXT' => $message['MESSAGE'])));
						if(!$answer)
						{
							$answer = QuickAnswer::add(array(
								'TEXT' => $message['MESSAGE'],
								'MESSAGEID' => $messageId,
							));
						}
					}
					if($answer && $answer->getId() > 0)
					{
						return true;
					}
				}
			}
		}

		$this->error = new BasicError(__METHOD__, 'CANT_SAVE_QUICK_ANSWER', Loc::getMessage('IMOL_OPERATOR_ERROR_CANT_SAVE_QUICK_ANSWER'));
		return false;
	}

	public function getSessionHistory($sessionId)
	{
		$sessionId = intval($sessionId);
		if ($sessionId <= 0)
		{
			$this->error = new BasicError(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
			return false;
		}

		$orm = Model\SessionTable::getByIdPerformance($sessionId);
		$session = $orm->fetch();
		if (!$session)
		{
			$this->error = new BasicError(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
			return false;
		}

		if ($session['OPERATOR_ID'] != $this->userId && !isset($session[$this->userId]))
		{
			$permission = Permissions::createWithCurrentUser();
			$allowedUserIds = Helper::getAllowedUserIds(
				Helper::getCurrentUserId(),
				$permission->getPermission(Permissions::ENTITY_HISTORY, Permissions::ACTION_VIEW)
			);
			if (is_array($allowedUserIds) && !in_array($session['OPERATOR_ID'], $allowedUserIds) &&
				\Bitrix\ImOpenLines\Crm\Common::hasAccessToEntitiesBindingActivity($session['CRM_ACTIVITY_ID'])->getResult() == false
			)
			{
				$this->error = new BasicError(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
				return false;
			}
		}

		$chatId = $session['CHAT_ID'];

		$CIMChat = new \CIMChat();
		$result = $CIMChat->GetLastMessageLimit($chatId, $session['START_ID'], $session['END_ID'], true, false);
		if ($result && isset($result['message']))
		{
			foreach ($result['message'] as $id => $ar)
				$result['message'][$id]['recipientId'] = 'chat'.$ar['recipientId'];

			$result['usersMessage']['chat'.$chatId] = $result['usersMessage'][$chatId];
			unset($result['usersMessage'][$chatId]);
		}
		else
		{
			$this->error = new BasicError(__METHOD__, 'ACCESS_DENIED', Loc::getMessage('IMOL_OPERATOR_ERROR_ACCESS_DENIED'));
			return false;
		}

		$chatData = \Bitrix\Im\Model\ChatTable::getList(
			array(
				'select' => array('ENTITY_ID', 'ENTITY_DATA_1'),
				'filter' => array('ID' => $chatId)
			)
		)->fetch();
		$crmEntityType = null;
		$crmEntityId = null;
		if ($chatData['ENTITY_DATA_1'])
		{
			$chatFieldData = explode('|', $chatData['ENTITY_DATA_1']);
			if ($chatFieldData[0] == 'Y')
			{
				$crmEntityType = $chatFieldData[1];
				$crmEntityId = $chatFieldData[2];
			}
		}

		$result['sessionId'] = $sessionId;
		$result['canJoin'] = \Bitrix\ImOpenLines\Config::canJoin($session['CONFIG_ID'], $crmEntityType, $crmEntityId)? 'Y':'N';
		$result['canVoteAsHead'] = \Bitrix\ImOpenLines\Config::canVoteAsHead($session['CONFIG_ID'])? 'Y':'N';
		$result['sessionVoteHead'] = intval($session['VOTE_HEAD']);
		$result['sessionCommentHead'] = $session['COMMENT_HEAD'];

		$result['openlines']['canVoteAsHead'][$session['CONFIG_ID']] = \Bitrix\ImOpenLines\Config::canVoteAsHead($session['CONFIG_ID']);

		return $result;
	}

	public function getError()
	{
		return $this->error;
	}
}