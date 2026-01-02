<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Call\Integration\AI\Outcome\Insights;
use Bitrix\Main\Context;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Message;
use Bitrix\Im\V2\Message\Params;
use Bitrix\Im\Call\Call;
use Bitrix\Call\Library;
use Bitrix\Call\NotifyService;
use Bitrix\Call\Integration\AI\Task\AITask;
use Bitrix\Call\Integration\AI\Outcome\Overview;
use Bitrix\Call\Integration\AI\Outcome\OutcomeCollection;
use Bitrix\Main\SiteTable;
use Bitrix\Call\Integration\Im\CallFollowupBot;


class ChatMessage
{
	public const COPILOT_COLOR = '#8d51eb';

	public static function generateErrorMessage(\Bitrix\Main\Error $error, Chat $chat, Call $call): ?Message
	{
		$errorMessage = self::getErrorMessage($error, $chat) ?: $error->getMessage();

		$message = new Message();
		$message
			->setMessage($errorMessage)
			->markAsSystem(true);

		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_FAILED,
			'CALL_ID' => $call->getId(),
		]);

		return $message;
	}

	public static function getErrorMessage(\Bitrix\Main\Error $error, Chat $chat): string
	{
		$errorMessage = '';
		$errorCode = $error->getCode();

		if ($error instanceof CallAIError)
		{
			if ($error->isAiGeneratedError())
			{
				$errorMessage = $error->getMessage();
			}
			else
			{
				$errCodes = (new \ReflectionClass(CallAIError::class))->getConstants();
				if (in_array($errorCode, $errCodes, true))
				{
					$errorMessage = $error->getMessage();
				}
			}
		}

		switch ($errorCode)
		{
			case 'AI_ENGINE_ERROR_PROVIDER':
			case 'AI_ENGINE_ERROR_OTHER':
				//$helpUrl = CallAISettings::getHelpUrl();
				break;

			case CallAIError::AI_MODULE_ERROR:
				$errorMessage = self::getMessage('ERROR_AI_MODULE_ERROR');
				break;

			case CallAIError::AI_UNAVAILABLE_ERROR:
			case 'AI_ENGINE_ERROR_SERVICE_IS_NOT_AVAILABLE_BY_TARIFF':
				$errorMessage = self::getMessage('CALL_NOTIFY_COPILOT_ERROR_TARIFF_RESTRICTION');
				break;

			case CallAIError::AI_SETTINGS_ERROR:
				$errorMessage = self::getMessage('CALL_NOTIFY_COPILOT_ERROR_SETTINGS_RESTRICTION', [
					'#LINK#' => CallAISettings::getHelpUrl(),
				]);
				break;

			case CallAIError::AI_NOT_ENOUGH_BAAS_ERROR:
			case 'AI_ENGINE_ERROR_LIMIT_IS_EXCEEDED':
				$errorMessage = self::getMessage('CALL_NOTIFY_COPILOT_ERROR_ERROR_LIMIT_BAAS', [
					'#LINK#' => CallAISettings::getBaasUrl(),
				]);
				break;

			case CallAIError::AI_AGREEMENT_ERROR:
			case 'AI_ENGINE_ERROR_MUST_AGREE_WITH_AGREEMENT':
				if (CallAISettings::isB24Mode())
				{
					//b24
					$users = $chat->getUserIds();
					$hasAdmin = false;
					foreach ($users as $userId)
					{
						if (\CBitrix24::isPortalAdmin($userId))
						{
							$hasAdmin = true;
							break;
						}
					}
					$errorMessage = $hasAdmin
						? self::getMessage('CALL_NOTIFY_COPILOT_ERROR_AGREEMENT_RESTRICTION_B24_ADMIN', ['#LINK#' => '/'])
						: self::getMessage('CALL_NOTIFY_COPILOT_ERROR_AGREEMENT_RESTRICTION_B24');
				}
				else
				{
					//box
					$errorMessage = self::getMessage('CALL_NOTIFY_COPILOT_ERROR_AGREEMENT_RESTRICTION_BOX', [
						'#LINK#' => CallAISettings::getAgreementUrl(),
					]);
				}
				break;
		}

		return $errorMessage;
	}

	public static function generateOverviewMessage(int $callId, OutcomeCollection $outcomeCollection, Chat $chat): ?Message
	{
		/** @var Overview $overview */
		$overview = $outcomeCollection->getOutcomeByType(SenseType::OVERVIEW->value)?->getSenseContent();
		if (!$overview)
		{
			return null;
		}

		$hostUrl = UrlManager::getInstance()->getHostUrl();

		$call = \Bitrix\Im\Call\Registry::getCallWithId($callId);

		$message = new Message();
		//$message->setMessage('[b]'.Loc::getMessage('CALL_NOTIFY_COPILOT').'[/b]');

		$linkMess = self::makeCallStartMessageLink($callId, $chat->getId());
		$message->setMessage(self::getMessage('CALL_NOTIFY_TASK_COMPLETE', [
			'#CALL_START#' => $linkMess,
			'#CALL_ID#' => $callId,
		]));

		$attach = new \CIMMessageParamAttach();
		$attach->SetColor(self::COPILOT_COLOR);
		$delimiter = [/*'COLOR' => self::COPILOT_COLOR,*/ 'SIZE' => 400];
		$spacer = ['SIZE' => 1];

		if (!empty($overview->topic))
		{
			$attach->AddMessage('[b]'.$overview->topic.'[/b]');
		}

		$callUsers = $call->getCallUsers();
		$users = [];
		foreach ($callUsers as $userId => $callUser)
		{
			if ($callUser->getFirstJoined())
			{
				$userName = \Bitrix\Im\User::getInstance($userId)->getFullName();
				$users[$userId] = "[user={$userId}]{$userName}[/user]";
				if (count($users) > 20)
				{
					break;
				}
			}
		}
		if (!isset($users[$call->getInitiatorId()]))
		{
			$userId = $call->getInitiatorId();
			$userName = \Bitrix\Im\User::getInstance($userId)->getFullName();
			$users[$userId] = "[user={$userId}]{$userName}[/user]";
		}
		if ($users)
		{
			if (count($users) > 20)
			{
				$users[] = "...";
			}
			$attach->AddMessage(self::getMessage('CALL_NOTIFY_USERS', ['#USERS#' => implode(', ', $users)]));
		}

		if ($overview->efficiencyValue >= 0)
		{
			$efficiency = sprintf(
				"%d%% (%s)",
				$overview->efficiencyValue,
				match ($overview->efficiencyValue)
				{
					100 => self::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY_100'),
					75 => self::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY_75'),
					50 => self::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY_50'),
					default => self::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY_25')
				}
			);
			$attach->AddMessage('[br][b]' . self::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY', ['#EFFICIENCY#' => $efficiency]) . '[/b]');
		}

		if ($overview?->agenda)
		{
			if ($overview->agenda?->explanation)
			{
				$attach->AddDelimiter($delimiter);
				//$attach->AddMessage('[b]' . Loc::getMessage('CALL_NOTIFY_COPILOT_AGENDA') . '[/b]');
				$attach->AddUser([
					'NAME' => self::getMessage('CALL_NOTIFY_COPILOT_AGENDA'),
					'AVATAR' => $hostUrl.'/bitrix/js/call/images/copilot-message-agenda.svg',
				]);
				$attach->AddMessage($overview->agenda?->explanation);
			}
		}

		if ($overview?->agreements || $overview?->meetings || $overview?->tasks || $overview?->actionItems)
		{
			//$attach->AddMessage('[b]' . Loc::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS') . '[/b]');
			$attach->AddDelimiter($delimiter);
			$attach->AddUser([
				'NAME' => self::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS'),
				'AVATAR' => $hostUrl.'/bitrix/js/call/images/copilot-message-areements.svg',
			]);

			if (!empty($overview->agreements))
			{
				$attach->AddMessage('[b]' . self::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS_COMMON') . '[/b]');
				$number = 0;
				foreach ($overview->agreements as $agreement)
				{
					if (!empty($agreement->agreement))
					{
						$number++;
						$attach->AddMessage("{$number}. " . $agreement->agreement);
					}
				}
			}

			if (!empty($overview->actionItems))
			{
				$attach->AddMessage('[b]' . self::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS_TASKS') . '[/b]');
				$number = 0;
				foreach ($overview->actionItems as $action)
				{
					if (!empty($action->actionItem))
					{
						$number++;
						$attach->AddMessage("{$number}. " . $action->actionItem);
					}
				}
			}
			if (!empty($overview->tasks))
			{
				$attach->AddMessage('[b]' . self::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS_TASKS') . '[/b]');
				$number = 0;
				foreach ($overview->tasks as $task)
				{
					if (!empty($task->task))
					{
						$number++;
						$attach->AddMessage("{$number}. " . $task->task);
					}
				}
			}

			if (!empty($overview->meetings))
			{
				$attach->AddMessage('[b]' . self::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS_MEETINGS') . '[/b]');
				$number = 0;
				foreach ($overview->meetings as $meeting)
				{
					if (!empty($meeting->meeting))
					{
						$number++;
						$attach->AddMessage("{$number}. " . $meeting->meeting);
					}
				}
			}
		}

		/** @var Insights $insights */
		$insights = $outcomeCollection->getOutcomeByType(SenseType::INSIGHTS->value)?->getSenseContent();
		if ($insights)
		{
			if (!empty($insights->insights))
			{
				$attach->AddDelimiter($delimiter);
				//$attach->AddMessage('[b]' . Loc::getMessage('CALL_NOTIFY_COPILOT_INSIGHTS') . '[/b]');
				$attach->AddUser([
					'NAME' => self::getMessage('CALL_NOTIFY_COPILOT_INSIGHTS'),
					'AVATAR' => $hostUrl.'/bitrix/js/call/images/copilot-message-insights.svg',
				]);
				foreach ($insights->insights as $insight)
				{
					if (!empty($insight->detailedInsight))
					{
						$attach->AddMessage($insight->detailedInsight . '[br][br]');
						$attach->AddDelimiter($spacer);
					}
				}
			}
		}

		$link = Library::getCallSliderUrl($callId);
		$attach->AddMessage('[br]'. self::getMessage('CALL_NOTIFY_COPILOT_DETAIL', ['#CALL_DETAIL#' => $link]). '[br]');

		$attach->AddDelimiter(['COLOR' => '#00ace3', 'SIZE' => 400]);
		$link = CallAISettings::getDisclaimerUrl();
		$attach->AddMessage('[i]'. self::getMessage('CALL_NOTIFY_COPILOT_DISCLAIMER', ['#DISCLAIMER#' => $link]). '[/i]');

		$message
			->setAttach($attach)
			->markAsSystem(true);

		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_OVERVIEW,
			'CALL_ID' => $callId,
		]);

		return $message;
	}

	public static function generateTaskCompleteMessage(Outcome $outcome, Chat $chat): ?Message
	{
		$callId = $outcome->getCallId();
		$linkMess = self::makeCallStartMessageLink($callId, $chat->getId());

		$message = new Message();
		$message->setMessage(self::getMessage('CALL_NOTIFY_TASK_COMPLETE', [
			'#CALL_START#' => $linkMess,
			'#CALL_ID#' => $callId,
		]));
		$message->markAsSystem(true);

		return $message;
	}

	public static function generateCallFinishedMessage(Call $call, Chat $chat): ?Message
	{
		$callId = $call->getId();
		$linkMess = self::makeCallStartMessageLink($callId, $chat->getId());

		$message = new Message();
		$message->setMessage(
			self::getMessage('CALL_NOTIFY_TASK_START', [
				'#CALL_START#' => $linkMess,
				'#CALL_ID#' => $callId,
			])
		);
		$message->markAsSystem(true);

		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_START,
			'CALL_ID' => $callId,
		]);

		return $message;
	}

	public static function generateWaitMessage(Call $call, Chat $chat): ?Message
	{
		$callId = $call->getId();
		$linkMess = self::makeCallStartMessageLink($callId, $chat->getId());

		$message = new Message();
		$message->setMessage(
			self::getMessage('CALL_NOTIFY_TASK_WAIT', [
				'#CALL_START#' => $linkMess,
				'#CALL_ID#' => $callId,
			])
		);
		$message->markAsSystem(true);
		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_WAIT,
			'CALL_ID' => $callId,
		]);

		return $message;
	}

	public static function generateTaskStartMessage(int $callId, Chat $chat): ?Message
	{
		$linkMess = self::makeCallStartMessageLink($callId, $chat->getId());

		$message = new Message();
		$message->setMessage(
			self::getMessage('CALL_NOTIFY_TASK_START', [
				'#CALL_START#' => $linkMess,
				'#CALL_ID#' => $callId,
			])
		);
		$message->markAsSystem(true);
		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_START,
			'CALL_ID' => $callId,
		]);

		return $message;
	}

	public static function generateTaskFailedMessage(int $callId, \Bitrix\Main\Error $error, Chat $chat): ?Message
	{
		$linkMess = self::makeCallStartMessageLink($callId, $chat->getId());

		$mess = self::getMessage('CALL_NOTIFY_TASK_FAILED', ['#CALL_START#' => $linkMess, '#CALL_ID#' => $callId]);

		if ($errorMessage = self::getErrorMessage($error, $chat))
		{
			$mess .= '[br]'. $errorMessage;
		}

		$feedbackLink = \Bitrix\Call\Library::getCallAiFeedbackUrl($callId);
		if ($feedbackLink)
		{
			$mess .= '[br]'. self::getMessage('CALL_NOTIFY_FEEDBACK', ['#FEEDBACK_URL#' => $feedbackLink]);
		}

		$message = new Message();
		$message->markAsSystem(true);

		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_FAILED,
			'CALL_ID' => $callId,
		]);

		if (
			$error instanceof CallAIError
			&& $error->recoverable()
		)
		{
			/*
			$continueLink = \Bitrix\Call\Library::getCallSliderUrl($callId, ['followup' => 'restart']);
			$mess .= "[br][url={$continueLink}]". Loc::getMessage('CALL_NOTIFY_COPILOT_CONTINUE').'[/url]';
			*/
			$keyboard = new \Bitrix\Im\Bot\Keyboard(CallFollowupBot::getBotId());
			$button = [
				'COMMAND' => CallFollowupBot::COMMAND_CONTINUE_FOLLOWUP,
				'COMMAND_PARAMS' => 'CALL_ID:' . $callId,
				'TEXT' => self::getMessage('CALL_NOTIFY_COPILOT_CONTINUE'),
			];
			$keyboard->addButton($button);
			$message->getParams()->get(Params::KEYBOARD)->setValue($keyboard);
		}

		$message->setMessage($mess);

		return $message;
	}

	private static function getMessage(string $code, array $replace = null): string
	{
		return Loc::getMessage(
			$code,
			$replace,
			Context::getCurrent()->getSite() ? null : SiteTable::getDefaultLanguageId()
		);
	}

	public static function generateFollowUpDroppedMessage(int $callId, int $actionUserId, Chat $chat): Message
	{
		$linkMess = self::makeCallStartMessageLink($callId, $chat->getId());

		Loader::includeModule('im');

		$user = \Bitrix\Im\User::getInstance($actionUserId);
		$userName = "[user={$actionUserId}]". $user->getFullName(). "[/user]";

		$message = new Message();
		$message->setMessage(self::getMessage('CALL_NOTIFY_TASK_DROPPED_'. ($user->getGender() === 'F' ? 'F' : 'M'), [
			'#CALL_START#' => $linkMess,
			'#CALL_ID#' => $callId,
			'#USER#' => $userName,
		]));
		$message->markAsSystem(true);

		return $message;
	}

	public static function generateTrackDestroyMessage(int $callId, int $actionUserId, int $chatId): ?Message
	{
		$linkMess = self::makeCallStartMessageLink($callId, $chatId);

		Loader::includeModule('im');

		$user = \Bitrix\Im\User::getInstance($actionUserId);
		$userName = "[user={$actionUserId}]". $user->getFullName(). "[/user]";

		$message = new Message();
		$message->setMessage(
			self::getMessage('CALL_NOTIFY_TASK_DESTROY_'. ($user->getGender() === 'F' ? 'F' : 'M'), [
				'#CALL_START#' => $linkMess,
				'#CALL_ID#' => $callId,
				'#USER#' => $userName,
			])
		);
		$message->markAsSystem(true);

		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_DESTROY,
			'CALL_ID' => $callId,
		]);

		return $message;
	}

	private static function makeCallStartMessageLink(int $callId, int $chatId): string
	{
		$callStartMessageId = null;
		if ($startMessage = NotifyService::getInstance()->findMessage($chatId, $callId, NotifyService::MESSAGE_TYPE_START))
		{
			$callStartMessageId = $startMessage->getMessageId();
		}

		$linkMess = '';
		if ($callStartMessageId)
		{
			$linkMess = \Bitrix\Call\Library::getChatMessageUrl($chatId, $callStartMessageId);
		}

		return $linkMess;
	}

	public static function generateOpponentBusyMessage(int $opponentUserId): ?Message
	{
		Loader::includeModule('im');

		Loc::loadMessages($_SERVER["DOCUMENT_ROOT"]. '/bitrix/modules/im/lib/call/integration/chat.php');

		$opponentUser = \Bitrix\Im\User::getInstance($opponentUserId);

		$message = new Message();
		$message->setMessage(
			Loc::getMessage('IM_CALL_INTEGRATION_CHAT_CALL_USER_BUSY_'. ($opponentUser->getGender() === 'F' ? 'F' : 'M'), [
				'#NAME#' => $opponentUser->getFullName()
			])
		);
		$message->setAuthorId($opponentUserId);

		$params = $message->getParams();
		$params->get(Params::NOTIFY)->setValue(true);
		$params->get(Params::COMPONENT_ID)->setValue(NotifyService::MESSAGE_COMPONENT_ID);
		$params->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_BUSY,
			'INITIATOR_ID' => $opponentUserId,
			'MESSAGE_TEXT' => $message->getMessage(),
		]);

		return $message;
	}
}
