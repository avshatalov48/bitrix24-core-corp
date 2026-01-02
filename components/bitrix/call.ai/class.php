<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Im\Call\Call;
use Bitrix\Call\Track\TrackCollection;
use Bitrix\Call\Integration\AI;
use Bitrix\Call\Integration\AI\SenseType;
use Bitrix\Call\Integration\AI\MentionService;
use Bitrix\Call\Integration\AI\Outcome\OutcomeCollection;

class CallAiComponent extends \CBitrixComponent
{
	protected ?int $callId;
	protected ?Call $call;
	protected ?OutcomeCollection $outcomeCollection;
	protected ?TrackCollection $trackCollection;

	public function executeComponent(): void
	{
		$this->includeComponentLang('class.php');

		global $APPLICATION;

		if (
			$this->checkModules()
			&& $this->prepareParams()
			&& $this->checkAccess()
			&& $this->prepareResult()
		)
		{
			$APPLICATION->SetTitle(Loc::getMessage('CALL_COMPONENT_COPILOT_DETAIL_V2', [
				'#DATE#' => $this->arResult['CALL_DATE']
			]));
			$this->includeComponentTemplate('template.v2');
		}
	}

	protected function prepareParams(): bool
	{
		$this->callId = (int)$this->arParams['CALL_ID'];
		if (!$this->callId)
		{
			$this->showError(Loc::getMessage('CALL_COMPONENT_CALL_UNDEFINED'), Loc::getMessage('CALL_COMPONENT_ERROR_DESCRIPTION'));
			return false;
		}

		$this->call = \Bitrix\Im\Call\Registry::getCallWithId($this->callId);
		if (!$this->call)
		{
			$this->showError(Loc::getMessage('CALL_COMPONENT_CALL_UNDEFINED'), Loc::getMessage('CALL_COMPONENT_ERROR_DESCRIPTION'));
			return false;
		}

		return true;
	}

	protected function prepareResult(): bool
	{
		$this->arResult['CALL_ID'] = $this->callId;

		$currentUserId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		$this->arResult['CURRENT_USER_ID'] = $currentUserId;

		$roles = $this->call->getUserRoles([$currentUserId]);
		$this->arResult['CAN_DELETE'] = isset($roles[$currentUserId]) && in_array($roles[$currentUserId], ['ADMIN', 'MANAGER']);

		$mentionService = MentionService::getInstance();
		$mentionService->loadMentionsForCall($this->callId);

		$this->outcomeCollection = OutcomeCollection::getOutcomesByCallId($this->callId) ?? [];
		foreach ($this->outcomeCollection as $outcome)
		{
			$type = strtoupper($outcome->getType());
			if (isset($this->arResult[$type]))
			{
				continue;// take only one
			}

			/** @var AI\Outcome\Transcription|AI\Outcome\Summary|AI\Outcome\Overview|AI\Outcome\Insights $content */
			$content = $outcome->getSenseContent();
			switch ($outcome->getType())
			{
				case SenseType::TRANSCRIBE->value:
					/** @var AI\Outcome\Transcription $content */
					foreach ($content->transcriptions as $i => &$row)
					{
						if (!empty($row->text))
						{
							$row->text = $mentionService->replaceBbMentions($row->text);
							$isEmpty = false;
						}
					}
					break;

				case SenseType::SUMMARY->value:
					/** @var AI\Outcome\Summary $content */
					foreach ($content->summary as $i => &$row)
					{
						if (!empty($row->title) || !empty($row->summary))
						{
							$row->title = $mentionService->replaceBbMentions($row->title);
							$row->summary = $mentionService->replaceBbMentions($row->summary);
							$isEmpty = false;
						}
					}
					break;

				case SenseType::OVERVIEW->value:
					/** @var AI\Outcome\Overview $content */
					if ($content?->detailedTakeaways)
					{
						$content->detailedTakeaways = $mentionService->replaceBBMentions($content->detailedTakeaways);
						$isEmpty = false;
					}
					if ($content?->topic)
					{
						$content->topic = $mentionService->replaceBBMentions($content->topic);
						$isEmpty = false;
					}
					if ($content?->agenda)
					{
						if ($content->agenda?->explanation)
						{
							$content->agenda->explanation = $mentionService->replaceBbMentions($content->agenda->explanation);
							$isEmpty = false;
						}
						if ($content->agenda?->quote)
						{
							$content->agenda->quote = $mentionService->replaceBbMentions($content->agenda->quote);
							$isEmpty = false;
						}
					}
					if ($content?->agreements)
					{
						foreach ($content->agreements as &$row)
						{
							if ($row?->agreement)
							{
								$row->agreement = $mentionService->replaceBbMentions($row->agreement);
								$isEmpty = false;
								if ($row?->quote)
								{
									$row->quote = $mentionService->replaceBbMentions($row->quote);
								}
							}
						}
					}
					if ($content?->meetings)
					{
						foreach ($content->meetings as &$row)
						{
							if ($row?->meeting)
							{
								$meeting = $row->meeting;
								$row->meeting = $mentionService->replaceBbMentions($meeting);
								$row->meetingMentionLess = $mentionService->removeBbMentions($meeting);
								$isEmpty = false;
								if ($row?->quote)
								{
									$row->quote = $mentionService->replaceBbMentions($row->quote);
								}
							}
						}
					}
					if ($content?->actionItems)
					{
						foreach ($content->actionItems as &$row)
						{
							if ($row?->actionItem)
							{
								$actionItem = $row->actionItem;
								$row->actionItem = $mentionService->replaceBbMentions($actionItem);
								$row->actionItemMentionLess = $mentionService->removeBbMentions($actionItem);
								$isEmpty = false;
								if ($row?->quote)
								{
									$row->quote = $mentionService->replaceBbMentions($row->quote);
								}
							}
						}
					}
					if ($content?->tasks)
					{
						foreach ($content->tasks as &$row)
						{
							if ($row?->task)
							{
								$task = $row->task;
								$row->task = $mentionService->replaceBbMentions($task);
								$row->taskMentionLess = $mentionService->removeBbMentions($task);
								$isEmpty = false;
								if ($row?->quote)
								{
									$row->quote = $mentionService->replaceBbMentions($row->quote);
								}
							}
						}
					}
					break;

				case SenseType::INSIGHTS->value:
					$fields = [
						'insights' => 'detailedInsight',
						'meetingStrengths' => 'strength_explanation',
						'meetingWeaknesses' => 'weakness_explanation',
					];
					foreach ($fields as $field => $subField)
					{
						if ($content?->{$field})
						{
							foreach ($content->{$field} as &$row)
							{
								if ($row?->{$subField})
								{
									$row->{$subField} = $mentionService->replaceBbMentions($row->{$subField});
									$isEmpty = false;
								}
							}
						}
					}
					foreach (['speechStyleInfluence', 'engagementLevel', 'areasOfResponsibility', 'finalRecommendations'] as $field)
					{
						if ($content?->{$field})
						{
							$content->{$field} = $mentionService->replaceBbMentions($content->{$field});
						}
					}
					break;
			}
			if ($isEmpty === false)
			{
				$this->arResult[$type] = $content;
			}
		}

		$this->trackCollection = TrackCollection::getRecordings($this->callId) ?? [];
		$this->arResult['RECORD'] = [];
		foreach ($this->trackCollection as $track)
		{
			$this->arResult['RECORD'] = $track->toArray();
			break;// take only one
		}

		if (
			empty($this->arResult['OVERVIEW'])
			&& empty($this->arResult['INSIGHTS'])
			&& empty($this->arResult['SUMMARY'])
			&& empty($this->arResult['TRANSCRIBE'])
			&& empty($this->arResult['RECORD'])
		)
		{
			$this->showError(Loc::getMessage('CALL_COMPONENT_ERROR_TITLE'), Loc::getMessage('CALL_COMPONENT_ERROR_DESCRIPTION'));
			return false;
		}

		$feedbackLink = \Bitrix\Call\Library::getCallAiFeedbackUrl($this->callId);
		if ($feedbackLink)
		{
			$this->arResult['FEEDBACK_URL'] = $feedbackLink;
		}

		$startTime = null;
		$endTime = null;
		if ($this->call->getStartDate())
		{
			$startTime = $this->call->getStartDate()->toUserTime();
			$this->arResult['CALL_DATE'] = $this->formatDate($startTime);
			$this->arResult['CALL_START_TIME'] = $this->formatTime($startTime);
		}
		if ($this->call->getEndDate())
		{
			$endTime = $this->call->getEndDate()->toUserTime();
			$this->arResult['CALL_END_TIME'] = $this->formatTime($endTime);
		}
		if ($startTime && $endTime)
		{
			$this->arResult['CALL_DURATION'] = $this->formatDuration($startTime, $endTime);
		}

		$this->arResult['USER_COUNT'] = $this->getUserCount();

		return true;
	}

	protected function getUserCount(): int
	{
		$callUsers = $this->call->getCallUsers();
		$cnt = 0;
		foreach ($callUsers as $callUser)
		{
			if ($callUser->getFirstJoined())
			{
				$cnt ++;
			}
		}

		return $cnt;
	}

	protected function formatDate(DateTime $dateTime): string
	{
		$timestamp = $dateTime->getTimestamp();
		$userCulture = \Bitrix\Main\Context::getCurrent()?->getCulture();
		$isCurrentYear = (date('Y') === date('Y', $timestamp));

		$dateFormat = $isCurrentYear ? $userCulture?->getDayMonthFormat() : $userCulture?->getLongDateFormat();
		$dateFormat .= ', '. $userCulture->getShortTimeFormat();

		return \FormatDate($dateFormat, $timestamp);
	}

	protected function formatTime(DateTime $dateTime): string
	{
		$timestamp = $dateTime->getTimestamp();
		$timeFormat = \Bitrix\Main\Context::getCurrent()?->getCulture()?->getShortTimeFormat() ?? 'H:i';

		return \FormatDate($timeFormat, $timestamp);
	}

	protected function formatDuration(DateTime $startTime, DateTime $endTime): string
	{
		Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/im/lib/call/integration/chat.php');

		$interval = $startTime->getDiff($endTime);

		[$hours, $minutes, $seconds] = explode(' ', $interval->format('%H %I %S'));
		$result = [];

		if ((int)$hours > 0)
		{
			$result[] = Loc::getMessage("IM_CALL_INTEGRATION_CHAT_CALL_DURATION_HOURS", [
				"#HOURS#" => (int)$hours
			]);
		}

		if ((int)$minutes > 0)
		{
			$result[] = Loc::getMessage("IM_CALL_INTEGRATION_CHAT_CALL_DURATION_MINUTES", [
				"#MINUTES#" => (int)$minutes
			]);
		}

		if ((int)$seconds > 0 && !((int)$hours > 0))
		{
			$result[] = Loc::getMessage("IM_CALL_INTEGRATION_CHAT_CALL_DURATION_SECONDS", [
				"#SECONDS#" => (int)$seconds
			]);
		}

		return implode(" ", $result);
	}

	protected function checkAccess(): bool
	{
		$currentUserId = \Bitrix\Main\Engine\CurrentUser::get()->getId();
		$hasAccess = $this->call->checkAccess($currentUserId);
		if (!$hasAccess)
		{
			$this->showError(Loc::getMessage('CALL_COMPONENT_ACCESS_DENIED'), Loc::getMessage('CALL_COMPONENT_ERROR_DESCRIPTION'));
		}

		return $hasAccess;
	}

	protected function showError(string $error, string $errorDesc = ''): void
	{
		$this->arResult['ERROR'] = $error;
		$this->arResult['ERROR_DESC'] = $errorDesc;
		$this->includeComponentTemplate('error');
	}

	protected function checkModules(): bool
	{
		if (!Loader::includeModule('im'))
		{
			$this->showError(Loc::getMessage('CALL_COMPONENT_MODULE_IM_NOT_INSTALLED'));

			return false;
		}

		if (!Loader::includeModule('call'))
		{
			$this->showError(Loc::getMessage('CALL_COMPONENT_MODULE_CALL_NOT_INSTALLED'));

			return false;
		}

		return true;
	}
}