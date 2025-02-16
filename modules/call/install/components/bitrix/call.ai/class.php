<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Im\Call\Call;
use Bitrix\Call\Track\TrackCollection;
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
			$this->includeComponentTemplate();
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
		$this->arResult['CURRENT_USER_ID'] = \Bitrix\Main\Engine\CurrentUser::get()->getId();

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

			$isEmpty = true;
			$content = $outcome->getSenseContent();
			switch ($outcome->getType())
			{
				case SenseType::TRANSCRIBE->value:
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
					if ($content?->insights)
					{
						foreach ($content->insights as &$row)
						{
							if ($row?->detailed_insight)
							{
								$row->detailed_insight = $mentionService->replaceBbMentions($row->detailed_insight);
								$isEmpty = false;
							}
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

		$this->arResult['CALL_DATE'] = $this->formatDate($this->call->getStartDate());
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

	protected function formatDate(\Bitrix\Main\Type\DateTime $dateTime): string
	{
		$timestamp = $dateTime->getTimestamp();
		$userCulture = \Bitrix\Main\Context::getCurrent()?->getCulture();
		$isCurrentYear = (date('Y') === date('Y', $timestamp));

		$dateFormat = $isCurrentYear ? $userCulture?->getDayMonthFormat() : $userCulture?->getLongDateFormat();
		$dateFormat .= ', '. $userCulture->getShortTimeFormat();

		return formatDate($dateFormat, $timestamp);
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