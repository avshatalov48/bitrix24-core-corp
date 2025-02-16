<?php

namespace Bitrix\Call\Integration\AI;

use Bitrix\Call\Integration\AI\Outcome\Insights;
use Bitrix\Main\Engine\UrlManager;
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

		// todo: Add COMPONENT_ID  for call message
		//$message->getParams()->get(Params::COMPONENT_ID)->setValue(NotifyService::MESSAGE_COMPONENT_ID);
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

		if (
			$error instanceof CallAIError
			&& $error->isAiGeneratedError()
		)
		{
			$errorMessage = $error->getMessage();
		}

		switch ($errorCode)
		{
			case CallAIError::AI_RECORD_TOO_SHORT:
			case CallAIError::AI_EMPTY_PAYLOAD_ERROR:
				$errorMessage = $error->getMessage();
				break;

			case 'AI_ENGINE_ERROR_PROVIDER':
			case 'AI_ENGINE_ERROR_OTHER':
				//$helpUrl = CallAISettings::getHelpUrl();
				break;

			case CallAIError::AI_UNAVAILABLE_ERROR:
			case 'AI_ENGINE_ERROR_SERVICE_IS_NOT_AVAILABLE_BY_TARIFF':
				$errorMessage = Loc::getMessage('CALL_NOTIFY_COPILOT_ERROR_TARIFF_RESTRICTION');
				break;

			case CallAIError::AI_SETTINGS_ERROR:
				$errorMessage = Loc::getMessage('CALL_NOTIFY_COPILOT_ERROR_SETTINGS_RESTRICTION', [
					'#LINK#' => CallAISettings::getHelpUrl(),
				]);
				break;

			case CallAIError::AI_NOT_ENOUGH_BAAS_ERROR:
			case 'AI_ENGINE_ERROR_LIMIT_IS_EXCEEDED':
				$errorMessage = Loc::getMessage('CALL_NOTIFY_COPILOT_ERROR_ERROR_LIMIT_BAAS', [
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
						? Loc::getMessage('CALL_NOTIFY_COPILOT_ERROR_AGREEMENT_RESTRICTION_B24_ADMIN', ['#LINK#' => '/'])
						: Loc::getMessage('CALL_NOTIFY_COPILOT_ERROR_AGREEMENT_RESTRICTION_B24');
				}
				else
				{
					//box
					$errorMessage = Loc::getMessage('CALL_NOTIFY_COPILOT_ERROR_AGREEMENT_RESTRICTION_BOX', [
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

		$linkMess = self::makeCallStartMessageLink($callId, $chat);
		$message->setMessage(Loc::getMessage('CALL_NOTIFY_TASK_COMPLETE', [
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
			$attach->AddMessage(Loc::getMessage('CALL_NOTIFY_USERS', ['#USERS#' => implode(', ', $users)]));
		}

		if ($overview->efficiencyValue >= 0)
		{
			$efficiency = sprintf(
				"%d%% (%s)",
				$overview->efficiencyValue,
				match ($overview->efficiencyValue)
				{
					100 => Loc::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY_100'),
					75 => Loc::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY_75'),
					50 => Loc::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY_50'),
					25 => Loc::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY_25')
				}
			);
			$attach->AddMessage(Loc::getMessage('CALL_NOTIFY_COPILOT_EFFICIENCY', ['#EFFICIENCY#' => $efficiency]));
		}

		if ($overview?->agenda)
		{
			if ($overview->agenda?->explanation || $overview->agenda?->quote)
			{
				$attach->AddDelimiter($delimiter);
				//$attach->AddMessage('[b]' . Loc::getMessage('CALL_NOTIFY_COPILOT_AGENDA') . '[/b]');
				$attach->AddUser([
					'NAME' => Loc::getMessage('CALL_NOTIFY_COPILOT_AGENDA'),
					'AVATAR' => $hostUrl.'/bitrix/js/call/images/copilot-message-agenda.svg',
				]);
				$attach->AddMessage($overview->agenda?->explanation . "\n" . $overview->agenda?->quote);
			}
		}

		if ($overview?->agreements || $overview?->meetings || $overview?->tasks)
		{
			//$attach->AddMessage('[b]' . Loc::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS') . '[/b]');
			$attach->AddDelimiter($delimiter);
			$attach->AddUser([
				'NAME' => Loc::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS'),
				'AVATAR' => $hostUrl.'/bitrix/js/call/images/copilot-message-areements.svg',
			]);

			if (!empty($overview->agreements))
			{
				$attach->AddMessage('[b]' . Loc::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS_COMMON') . '[/b]');
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

			if (!empty($overview->tasks))
			{
				$attach->AddMessage('[b]' . Loc::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS_TASKS') . '[/b]');
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
				$attach->AddMessage('[b]' . Loc::getMessage('CALL_NOTIFY_COPILOT_AGREEMENTS_MEETINGS') . '[/b]');
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
					'NAME' => Loc::getMessage('CALL_NOTIFY_COPILOT_INSIGHTS'),
					'AVATAR' => $hostUrl.'/bitrix/js/call/images/copilot-message-insights.svg',
				]);
				foreach ($insights->insights as $insight)
				{
					if (!empty($insight->detailed_insight))
					{
						$attach->AddMessage($insight->detailed_insight . '[br][br]');
						$attach->AddDelimiter($spacer);
					}
				}
			}
		}

		$link = Library::getCallSliderUrl($callId);
		$attach->AddMessage('[br]'. Loc::getMessage('CALL_NOTIFY_COPILOT_DETAIL', ['#CALL_DETAIL#' => $link]). '[br]');

		$attach->AddDelimiter(['COLOR' => '#00ace3', 'SIZE' => 400]);
		$link = CallAISettings::getDisclaimerUrl();
		$attach->AddMessage('[i]'. Loc::getMessage('CALL_NOTIFY_COPILOT_DISCLAIMER', ['#DISCLAIMER#' => $link]). '[/i]');

		$message
			->setAttach($attach)
			->markAsSystem(true);

		//todo: Add COMPONENT_ID  for call message
		//$message->getParams()->get(Params::COMPONENT_ID)->setValue(NotifyService::MESSAGE_COMPONENT_ID);
		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_OVERVIEW,
			'CALL_ID' => $callId,
		]);

		return $message;
	}

	public static function generateTaskCompleteMessage(Outcome $outcome, Chat $chat): ?Message
	{
		$callId = $outcome->getCallId();
		$linkMess = self::makeCallStartMessageLink($callId, $chat);

		$message = new Message();
		$message->setMessage(Loc::getMessage('CALL_NOTIFY_TASK_COMPLETE', [
			'#CALL_START#' => $linkMess,
			'#CALL_ID#' => $callId,
		]));
		$message->markAsSystem(true);

		return $message;
	}

	public static function generateCallFinishedMessage(Call $call, Chat $chat): ?Message
	{
		$callId = $call->getId();
		$linkMess = self::makeCallStartMessageLink($callId, $chat);

		$message = new Message();
		$message->setMessage(
			Loc::getMessage('CALL_NOTIFY_TASK_START', [
				'#CALL_START#' => $linkMess,
				'#CALL_ID#' => $callId,
			])
		);
		$message->markAsSystem(true);

		//todo: Add COMPONENT_ID  for call message
		//$message->getParams()->get(Params::COMPONENT_ID)->setValue(NotifyService::MESSAGE_COMPONENT_ID);
		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_START,
			'CALL_ID' => $callId,
		]);

		return $message;
	}

	public static function generateTaskStartMessage(AITask $task, Chat $chat): ?Message
	{
		$callId = $task->getCallId();
		$linkMess = self::makeCallStartMessageLink($callId, $chat);

		$message = new Message();
		$message->setMessage(
			Loc::getMessage('CALL_NOTIFY_TASK_START', [
				'#CALL_START#' => $linkMess,
				'#CALL_ID#' => $callId,
			])
		);
		$message->markAsSystem(true);

		return $message;
	}

	public static function generateTaskFailedMessage(int $callId, \Bitrix\Main\Error $error, Chat $chat): ?Message
	{
		$linkMess = self::makeCallStartMessageLink($callId, $chat);

		$mess = Loc::getMessage('CALL_NOTIFY_TASK_FAILED', ['#CALL_START#' => $linkMess, '#CALL_ID#' => $callId]);

		if ($errorMessage = self::getErrorMessage($error, $chat))
		{
			$mess .= '[br]'. $errorMessage;
		}

		$feedbackLink = \Bitrix\Call\Library::getCallAiFeedbackUrl($callId);
		if ($feedbackLink)
		{
			$mess .= '[br]'. Loc::getMessage('CALL_NOTIFY_FEEDBACK', ['#FEEDBACK_URL#' => $feedbackLink]);
		}

		$message = new Message();
		$message->setMessage($mess);
		$message->markAsSystem(true);

		//todo: Add COMPONENT_ID  for call message
		//$message->getParams()->get(Params::COMPONENT_ID)->setValue(NotifyService::MESSAGE_COMPONENT_ID);
		$message->getParams()->get(Params::COMPONENT_PARAMS)->setValue([
			'MESSAGE_TYPE' => NotifyService::MESSAGE_TYPE_AI_FAILED,
			'CALL_ID' => $callId,
		]);

		return $message;
	}

	private static function makeCallStartMessageLink(int $callId, Chat $chat): string
	{
		$callStartMessageId = null;
		if ($startMessage = NotifyService::getInstance()->findMessage($chat, $callId, NotifyService::MESSAGE_TYPE_START))
		{
			$callStartMessageId = $startMessage->getMessageId();
		}

		$linkMess = '';
		if ($callStartMessageId)
		{
			$linkMess = \Bitrix\Call\Library::getChatMessageUrl($chat->getChatId(), $callStartMessageId);
		}

		return $linkMess;
	}
}
