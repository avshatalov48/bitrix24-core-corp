<?php

namespace Bitrix\Tasks\Integration\IM\Notification\UseCase;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\IM\Notification;
use Bitrix\Tasks\Integration\SocialNetwork\Collab\Provider\CollabProvider;
use Bitrix\Tasks\Internals\Notification\Message;
use Bitrix\Tasks\Internals\Notification\User;
use Bitrix\Tasks\Internals\Notification\UserRepositoryInterface;
use Bitrix\Tasks\Internals\Registry\GroupRegistry;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class TaskUpdated
{
	public function getNotification(Message $message): ?Notification
	{
		$metadata = $message->getMetaData();
		$task = $metadata->getTask();
		$userRepository = $metadata->getUserRepository();
		$recepient = $message->getRecepient();

		if ($task === null || $userRepository === null)
		{
			return null;
		}

		// $arChanges contains datetimes IN SERVER TIME, NOT CLIENT
		$arChanges = $metadata->getChanges();
		if (empty($arChanges))
		{
			return null;
		}

		$trackedFields = $metadata->getParams()['tracked_fields'] ?? [];
		$nameTemplate = $metadata->getParams()['user_params']['NAME_TEMPLATE'] ?? null;
		$description = '';

		foreach ($arChanges as $key => $value)
		{
			if ($key === 'DESCRIPTION')
			{
				$description .= Loc::getMessage('TASKS_MESSAGE_DESCRIPTION_UPDATED', null, $recepient->getLang());
				$description .= "\r\n";
				continue;
			}

			if ($key === 'ACCOMPLICES' || $key === 'AUDITORS')
			{
				$fromUsers = explode(',', $value['FROM_VALUE']);
				$toUsers = explode(',', $value['TO_VALUE']);

				$addedUsers = $this->getUniqueExcludedUsers($toUsers, $fromUsers);
				$commaseparatedUsers = $this->getCommaSeparatedUserNames($userRepository, $addedUsers, $nameTemplate);
				if ($commaseparatedUsers)
				{
					$description .= Loc::getMessage("TASKS_MESSAGE_{$key}_ADDED", null, $recepient->getLang());
					$description .= $commaseparatedUsers . "\r\n";
				}

				$removedUsers = $this->getUniqueExcludedUsers($fromUsers, $toUsers);
				$commaseparatedUsers = $this->getCommaSeparatedUserNames($userRepository, $removedUsers, $nameTemplate);
				if ($commaseparatedUsers)
				{
					$description .= Loc::getMessage("TASKS_MESSAGE_{$key}_REMOVED", null, $recepient->getLang());
					$description .= $commaseparatedUsers . "\r\n";
				}
				continue;
			}

			$resolvedKey = $this->mapKey($this->replaceKey($value['FROM_VALUE'], $value['TO_VALUE'], $key));

			$actionMessage = Loc::getMessage('TASKS_MESSAGE_' . $resolvedKey, null, $recepient->getLang());
			if(empty($actionMessage) && isset($trackedFields[$key]) && !empty($trackedFields[$key]['TITLE']))
			{
				$actionMessage = $trackedFields[$key]['TITLE'];
			}

			if(!empty($actionMessage))
			{
				// here we can display value changed for some fields
				$changeMessage = $actionMessage;
				$tmpStr = '';
				switch($key)
				{
					case 'TIME_ESTIMATE':
						$tmpStr .= $this->formatTimeHHMM($value['FROM_VALUE'], $recepient, true)
							.' -> '
							.$this->formatTimeHHMM($value['TO_VALUE'], $recepient, true);
						break;

					case 'TITLE':
						$tmpStr .= $value['FROM_VALUE'].' -> '.$value['TO_VALUE'];
						break;

					case 'RESPONSIBLE_ID':
						$changeMessage = "\r\n" . $changeMessage;
						$tmpStr .= "\r\n" . $this->getCommaSeparatedUserNames($userRepository, [$value['FROM_VALUE']], $nameTemplate)
							. ' -> '
							.$this->getCommaSeparatedUserNames($userRepository, [$value['TO_VALUE']], $nameTemplate)
						;
						break;

					case 'DEADLINE':
					case 'START_DATE_PLAN':
					case 'END_DATE_PLAN':
						$recepientTimeZoneOffset = $userRepository->getUserTimeZoneOffset($recepient->getId());
						// $arChanges ALREADY contains server time, no need to substract user timezone again
						$utsFromValue = $value['FROM_VALUE'];// - $curUserTzOffset;
						$utsToValue = $value['TO_VALUE'];// - $curUserTzOffset;
						// Make bitrix timestamp for given user
						$bitrixTsFromValue = $utsFromValue + $recepientTimeZoneOffset;
						$bitrixTsToValue = $utsToValue + $recepientTimeZoneOffset;

						$timeDescription = '';
						if($utsFromValue > 360000) // is correct timestamp?
						{
							$fromValueAsString = \Bitrix\Tasks\UI::formatDateTime(
								$bitrixTsFromValue, '^'.\Bitrix\Tasks\UI::getDateTimeFormat()
							);
							$timeDescription .= $fromValueAsString;
						}

						$timeDescription .= ' --> ';

						if($utsToValue > 360000) // is correct timestamp?
						{
							$toValueAsString = \Bitrix\Tasks\UI::formatDateTime(
								$bitrixTsToValue, '^'.\Bitrix\Tasks\UI::getDateTimeFormat()
							);
							$timeDescription .= $toValueAsString;
						}

						$tmpStr .= $timeDescription;
						break;

					case 'TAGS':
						$tmpStr .= ($value['FROM_VALUE']? str_replace(",", ", ", $value["FROM_VALUE"])." -> " : "")
							.($value["TO_VALUE"]? str_replace(",", ", ", $value["TO_VALUE"]) : Loc::getMessage("TASKS_MESSAGE_NO_VALUE", null, $recepient->getLang()));
						break;

					case 'PRIORITY':
						$tmpStr .= Loc::getMessage(
							'TASKS_PRIORITY_' . $value['FROM_VALUE'],
							null,
							$recepient->getLang()) . ' -> ' . Loc::getMessage('TASKS_PRIORITY_' . $value['TO_VALUE'], null, $recepient->getLang());
						break;

					case 'GROUP_ID':
						$fromGroupId = (int)$value['FROM_VALUE'];
						$toGroupId = (int)$value['TO_VALUE'];

						if($fromGroupId)
						{
							$arGroupFrom = $this->getSocNetGroup($fromGroupId);
							{
								if(isset($arGroupFrom['NAME']))
								{
									$tmpStr .= $arGroupFrom['NAME'] . ' -> ';
								}
							}
						}
						if($toGroupId)
						{
							$arGroupTo = $this->getSocNetGroup($toGroupId);
							{
								if(isset($arGroupTo['NAME']))
								{
									$tmpStr .= $arGroupTo['NAME'];
								}
							}
						}
						else
						{
							$tmpStr .= Loc::getMessage('TASKS_MESSAGE_NO_VALUE', null, $recepient->getLang());
						}

						break;

					case 'PARENT_ID':
						if($value['FROM_VALUE'])
						{
							$fromTaskId = (int)$value['FROM_VALUE'];
							$rsTaskFrom = TaskRegistry::getInstance()->get($fromTaskId);
							{
								if(isset($rsTaskFrom['TITLE']))
								{
									$tmpStr .= \Bitrix\Main\Text\Emoji::decode($rsTaskFrom['TITLE']) . ' -> ';
								}
							}
						}
						if($value['TO_VALUE'])
						{
							$toTaskId = (int)$value['TO_VALUE'];
							$rsTaskTo = TaskRegistry::getInstance()->get($toTaskId);
							{
								if(isset($rsTaskTo['TITLE']))
								{
									$tmpStr .= \Bitrix\Main\Text\Emoji::decode($rsTaskTo['TITLE']);
								}
							}
						}
						else
						{
							$tmpStr .= Loc::getMessage('TASKS_MESSAGE_NO_VALUE', null, $recepient->getLang());
						}
						break;

					case 'DEPENDS_ON':
						$arTasksFromStr = array();
						if($value['FROM_VALUE'])
						{
							$fromTaskId = (int)$value['FROM_VALUE'];
							$rsTaskFrom = TaskRegistry::getInstance()->get($fromTaskId);
							if (isset($rsTaskFrom['TITLE']))
							{
								$arTasksFromStr[] = \Bitrix\Main\Text\Emoji::decode($rsTaskFrom['TITLE']);
							}
						}
						$arTasksToStr = array();
						if($value['TO_VALUE'])
						{
							$toTaskId = (int)$value['TO_VALUE'];
							$rsTaskTo = TaskRegistry::getInstance()->get($toTaskId);
							if (isset($rsTaskTo['TITLE']))
							{
								$arTasksToStr[] = \Bitrix\Main\Text\Emoji::decode($rsTaskTo['TITLE']);
							}
						}
						$tmpStr .= ($arTasksFromStr? implode(", ", $arTasksFromStr)." -> " : "").($arTasksToStr? implode(", ", $arTasksToStr) : Loc::getMessage("TASKS_MESSAGE_NO_VALUE", null, $recepient->getLang()));
						break;

					case 'MARK':
						$tmpStr .= (!$value['FROM_VALUE']? Loc::getMessage('TASKS_MARK_NONE', null, $recepient->getLang()) : Loc::getMessage("TASKS_MARK_".$value["FROM_VALUE"], null, $recepient->getLang()))." -> ".(!$value["TO_VALUE"]? Loc::getMessage("TASKS_MARK_NONE", null, $recepient->getLang()) : Loc::getMessage("TASKS_MARK_".$value["TO_VALUE"], null, $recepient->getLang()));
						break;

					case 'ADD_IN_REPORT':
						$tmpStr .= ($value['FROM_VALUE'] === 'Y'? Loc::getMessage("TASKS_MESSAGE_IN_REPORT_YES", null, $recepient->getLang()) : Loc::getMessage("TASKS_MESSAGE_IN_REPORT_NO", null, $recepient->getLang()))." -> ".($value["TO_VALUE"] == "Y"? Loc::getMessage("TASKS_MESSAGE_IN_REPORT_YES", null, $recepient->getLang()) : Loc::getMessage("TASKS_MESSAGE_IN_REPORT_NO", null, $recepient->getLang()));
						break;

					case 'DELETED_FILES':
						$tmpStr .= $value['FROM_VALUE'];
						$tmpStr .= $value['TO_VALUE'];
						break;

					case 'NEW_FILES':
						$tmpStr .= $value['TO_VALUE'];
						break;
				}
				if ($tmpStr !== '')
				{
					$changeMessage .= ': ' . trim($tmpStr);
				}

				$description .= $changeMessage;
				$description .= "\r\n";
			}
		}

		if ($description === '') // not supported case
		{
			return null;
		}

		$locKey = 'TASKS_TASK_CHANGED_MESSAGE';
		$notification = new Notification(
			$locKey,
			$message
		);

		$responsibleId = $task->getResponsibleId();
		$prevResponsibleId = (int)($metadata->getPreviousFields()['RESPONSIBLE_ID'] ?? null);
		if ($responsibleId !== $prevResponsibleId && $recepient->getId() === $responsibleId)
		{
			$notification->setParams([
				'NOTIFY_EVENT' => 'task_assigned',
			]);
		}

		$title = new Notification\Task\Title($task);
		$notification->addTemplate(new Notification\Template('#TASK_TITLE#', $title->getFormatted($recepient->getLang())));
		$notification->addTemplate(new Notification\Template('#TASK_EXTRA#', $description));

		return $notification;
	}

	private function getCommaSeparatedUserNames(UserRepositoryInterface $userRepository, array $usersIds, ?string $nameTemplate): string
	{
		$users = [];

		foreach ($usersIds as $userId)
		{
			$user = $userRepository->getUserById($userId);
			if ($user instanceof User)
			{
				$users[] = $user->toString($nameTemplate);
			}
		}

		return implode(', ', $users);
	}

	private function getUniqueExcludedUsers(array $from, array $to): array
	{
		$users = array_unique(array_diff($from, $to));
		return array_filter(
			$users,
			static function ($id) {
				return (int)$id > 0;
			}
		);
	}

	/**
	 * @param int|null $in
	 * @param User $recepient
	 * @param bool $bDataInSeconds
	 * @return string
	 */
	private function formatTimeHHMM(?int $in, User $recepient, bool $bDataInSeconds = false): string
	{
		if ($in === null)
			return '';

		if ($bDataInSeconds)
			$minutes = (int) round($in / 60, 0);

		$hours = (int) ($minutes / 60);

		if ($minutes < 60)
		{
			$duration = $minutes . ' ' . Loc::getMessagePlural(
					'TASKS_TASK_DURATION_MINUTES',
					$minutes,
					null,
					$recepient->getLang()
				);
		}
		elseif ($minutesInResid = $minutes % 60)
		{
			$duration = $hours
				. ' '
				. Loc::getMessagePlural(
					'TASKS_TASK_DURATION_HOURS',
					$hours,
					null,
					$recepient->getLang()
				)
				. ' '
				. $minutesInResid
				. ' '
				. Loc::getMessagePlural(
					'TASKS_TASK_DURATION_MINUTES',
					$minutesInResid,
					null,
					$recepient->getLang()
				);
		}
		else
		{
			$duration = $hours . ' ' . Loc::getMessagePlural(
					'TASKS_TASK_DURATION_HOURS',
					$hours,
					null,
					$recepient->getLang()
				);
		}

		if ($bDataInSeconds && ($in < 3600) && $secondsInResid = $in % 60)
		{
			$duration .= ' ' . $secondsInResid
				. ' '
				. Loc::getMessagePlural(
					'TASKS_TASK_DURATION_SECONDS',
					$secondsInResid,
					null,
					$recepient->getLang()
				);
		}

		return ($duration);
	}

	private function getSocNetGroup(int $id): ?array
	{
		if(!\Bitrix\Main\Loader::includeModule('socialnetwork'))
		{
			return null;
		}

		$group = GroupRegistry::getInstance()->get($id);
		if (!empty($group['NAME']))
		{
			$group['NAME'] = \Bitrix\Main\Text\Emoji::decode($group['NAME']);
		}

		return $group;
	}

	private function replaceKey(mixed $fromValue, mixed $toValue, string $key): ?string
	{
		return match ($key)
		{
			'GROUP_ID' => CollabProvider::getInstance()?->isCollab((int)$toValue) ? 'COLLAB_ID' : $key,
			default => $key,
		};
	}

	private function mapKey(string $key): string
	{
		return match ($key)
		{
			'RESPONSIBLE_ID' => 'ASSIGNEE',
			'START_DATE_PLAN' => 'START_DATE_PLAN',
			'END_DATE_PLAN' => 'END_DATE_PLAN',
			'MARK' => 'MARK_MSGVER_1',
			default => $key,
		};
	}
}