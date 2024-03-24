<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Tasks\Util\Replicator\Task;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\CheckList\Task\TaskCheckListFacade;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Item;
use Bitrix\Tasks\Item\Result;
use Bitrix\Tasks\Provider\TaskList;
use Bitrix\Tasks\Provider\TaskQuery;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\Item\SystemLog;

Loc::loadMessages(__FILE__);

/**
 * @deprecated
 * @see \Bitrix\Tasks\Replication\Replicator\RegularTemplateTaskReplicator
 */

final class FromTemplate extends Util\Replicator\Task
{
	private $disabledAC = null;

	protected static function getSourceClass()
	{
		return Template::getClass();
	}

	protected static function getConverterClass()
	{
		return Item\Converter\Task\Template\ToTask::getClass();
	}

	protected static function getFromCheckListFacade()
	{
		return TemplateCheckListFacade::class;
	}

	protected static function getToCheckListFacade()
	{
		return TaskCheckListFacade::class;
	}

	/**
	 * Create sub-tasks for $destination task based on sub-templates of $source
	 *
	 * @param $source
	 * @param $destination
	 * @param array $parameters
	 * @param int $userId
	 * @return Result
	 */
	public function produceSub($source, $destination, array $parameters = array(), $userId = 0)
	{
		$result = new Result();

		Template::enterBatchState();

		$source = $this->getSourceInstance($source, $userId);
		$destination = $this->getDestinationInstance($destination, $userId);

		$created = new Collection();
		$wereErrors = false;

		$destinations = array(
			$destination // root task
		);

		// in case of multitasking create several destinations
		if($this->isMultitaskSource($source, $parameters))
		{
			// create duplicates of $destination for each sub-responsible
			foreach($source['RESPONSIBLES'] as $responsibleId)
			{
				if($responsibleId == $destination['CREATED_BY'])
				{
					continue; // skip creator itself
				}

				$subResult = $this->saveItemFromSource($source, array(
					'PARENT_ID' => $destination->getId(),
					'RESPONSIBLE_ID' => $responsibleId,
				), $userId);

				if($subResult->isSuccess())
				{
					$destinations[] = $subResult->getInstance();
				}
				else
				{
					$wereErrors = true;
				}

				$created->push($subResult);
			}
		}

		// now for each destination create sub-tasks according to the sub-templates, if any
		$data = $this->getSubItemData($source->getId());

		if (empty($data))
		{
			Template::leaveBatchState();
			return $result;
		}

		$order = $this->getCreationOrder($data, $source->getId());

		if(!$order)
		{
			$result->getErrors()->add('ILLEGAL_STRUCTURE.LOOP', Loc::getMessage('TASKS_REPLICATOR_SUBTREE_LOOP'));
		}
		else
		{
			// disable copying disk files for each sub-task
			// todo: impove this later
			$this->getConverter()->setConfig('UF.FILTER', array('!=USER_TYPE_ID' => 'disk_file'));

			foreach($destinations as $destination)
			{
				//////////////////////////////

				$src2dstId = array($source->getId() => $destination->getId());

				$cTree = $order;

				$walkQueue = array($source->getId());
				while(!empty($walkQueue)) // walk sub-item tree
				{
					$topTemplate = array_shift($walkQueue);

					if(is_array($cTree[$topTemplate] ?? null))
					{
						// create all sub template on that tree level
						foreach($cTree[$topTemplate] as $template)
						{
							$dataMixin = array_merge(array(
								'PARENT_ID' => $src2dstId[$topTemplate],
							), $parameters);

							$creationResult = $this->saveItemFromSource($data[$template], $dataMixin, $userId);
							if($creationResult->isSuccess())
							{
								$walkQueue[] = $template; // walk further on that template
								$src2dstId[$template] = $creationResult->getInstance()->getId();
							}
							else
							{
								$wereErrors = true;

								$errors = $creationResult->getErrors();
								$neededError = $errors->find(array('CODE' => 'ACCESS_DENIED.RESPONSIBLE_AND_ORIGINATOR_NOT_ALLOWED'));

								if ($errors->count() == 1 && !$neededError->isEmpty())
								{
									$data[$template]['CREATED_BY'] = $userId;

									$creationResult->getErrors()->clear();
									$creationResult = $this->saveItemFromSource($data[$template], $dataMixin, $userId);
									if ($creationResult->isSuccess())
									{
										$walkQueue[] = $template;
										$src2dstId[$template] = $creationResult->getInstance()->getId();

										$wereErrors = false;
									}
								}
							}

							$created->push($creationResult); // add sub-item creation result
						}
					}
					unset($cTree[$topTemplate]);
				}

				//////////////////////////////
			}

			if($wereErrors)
			{
				$result->addError('SUB_ITEMS_CREATION_FAILURE', 'Some of the sub-tasks was not properly created');
			}

			$result->setData($created);
		}


		Template::leaveBatchState();

		return $result;
	}

	/**
	 * @param $id
	 * @param $userId
	 * @return Item
	 */
	protected function makeSourceInstance($id, $userId)
	{
		/** @var Item $itemClass */
		$itemClass = static::getSourceClass();

		/** @var Item $item */
		$item = new $itemClass(intval($id), $userId);

		if($this->getConfig('DISABLE_SOURCE_ACCESS_CONTROLLER'))
		{
			if($this->disabledAC === null)
			{
				$ac = $item->getAccessController()->spawn();
				$ac->disable();

				$this->disabledAC = $ac;
			}

			$item->setAccessController($this->disabledAC);
		}

		return $item;
	}

	/**
	 * Returns execution time from replicate parameters or agent
	 *
	 * @param $agentName
	 * @param $replicateParams
	 * @return bool
	 */
	private static function getExecutionTime($agentName, $replicateParams)
	{
		$executionTime = false;

		if (array_key_exists('NEXT_EXECUTION_TIME', $replicateParams))
		{
			$executionTime = $replicateParams['NEXT_EXECUTION_TIME'];
		}
		else
		{
			$agent = \CAgent::getList(array(), array('NAME' => $agentName))->fetch();
			if ($agent)
			{
				$executionTime = $agent['NEXT_EXEC'];
			}
		}

		return $executionTime;
	}

	/**
	 * Checks if task was already created by template in $executionTime
	 *
	 * @param $executionTime
	 * @param $templateId
	 * @return bool
	 */
	private static function taskByTemplateAlreadyExist($templateId, $executionTime)
	{
		try
		{
			$userId = static::getEffectiveUser();

			$query = new TaskQuery($userId);
			$query
				->setSelect(['ID'])
				->setWhere([
					'FORKED_BY_TEMPLATE_ID' => $templateId,
					'CREATED_DATE' => $executionTime
				])
				->setLimit(1)
				->skipAccessCheck();

			$list = new TaskList();
			$tasks = $list->getList($query);

			return !empty($tasks);
		}
		catch (\Exception $exception)
		{
			return false;
		}
	}

	/**
	 * @param int $templateId
	 * @return mixed|string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function forceRepeatTask(int $templateId, int $userId = 0)
	{
		$parameters = [
			"FORCE_EXECUTE" => true,
			"AGENT_NAME_TEMPLATE" => "",
			"RESULT" => null,
		];

		if ($userId)
		{
			$parameters['FORCE_USER'] = $userId;
		}

		return static::repeatTask($templateId, $parameters);
	}

	/**
	 * Agent handler for repeating tasks.
	 * Create new task based on given template.
	 *
	 * @param $templateId
	 * @param array $parameters
	 * @return mixed|string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function repeatTask($templateId, array $parameters = [])
	{
		$forceExecute = false;
		if (array_key_exists('FORCE_EXECUTE', $parameters))
		{
			$forceExecute = $parameters['FORCE_EXECUTE'];
		}

		$templateId = (int)$templateId;
		if (!$templateId)
		{
			return ''; // delete agent
		}

		$userId = static::getEffectiveUser();

		static::liftLogAgent();

		// todo: replace this with item\orm call
		$templateDbRes = \CTaskTemplates::getList(
			[],
			['ID' => $templateId],
			false,
			['USER_IS_ADMIN' => true],
			['*', 'UF_*']
		);
		$template = $templateDbRes->Fetch();
		if ($template && $template['REPLICATE'] === 'Y')
		{
			$agentName = str_replace('#ID#', $templateId, $parameters['AGENT_NAME_TEMPLATE']); // todo: when AGENT_NAME_TEMPLATE is not set?
			$replicateParams = $template['REPLICATE_PARAMS'] = unserialize($template['REPLICATE_PARAMS'], ['allowed_classes' => false]);

			$executionTime = static::getExecutionTime($agentName, $replicateParams);
			if (
				$executionTime
				&&
				(
					static::taskByTemplateAlreadyExist($templateId, $executionTime)
					|| time() < MakeTimeStamp($executionTime)
				)
				&& !$forceExecute
			)
			{
				return $agentName;
			}

			$result = new Util\Replicator\Result();
			if (is_array($parameters['RESULT']))
			{
				$parameters['RESULT']['RESULT'] = $result;
			}

			$createMessage = '';
			$taskId = 0;
			$resumeReplication = true;
			$replicationCancelReason = '';

			if (!$userId)
			{
				$createMessage = Loc::getMessage('TASKS_REPLICATOR_TASK_WAS_NOT_CREATED');
				$result->addError('REPLICATION_FAILED', Loc::getMessage('TASKS_REPLICATOR_CANT_IDENTIFY_USER'));
			}

			// check if CREATOR is alive
			if (!User::isActive($template['CREATED_BY']))
			{
				$resumeReplication = false; // no need to make another try
				$createMessage = Loc::getMessage('TASKS_REPLICATOR_TASK_WAS_NOT_CREATED');
				$result->addError('REPLICATION_FAILED', Loc::getMessage('TASKS_REPLICATOR_CREATOR_INACTIVE'));
			}

			// create task if no error occured
			if ($result->isSuccess())
			{
				// todo: remove this spike
				$userChanged = false;
				$originalUser = null;

				if (intval($template['CREATED_BY']))
				{
					if (array_key_exists('FORCE_USER', $parameters))
					{
						$userId = (int) $parameters['FORCE_USER'];
					}
					else
					{
						$userId = (int) $template['CREATED_BY'];
					}
					$userChanged = true;
					$originalUser = User::getOccurAsId();
					User::setOccurAsId($userId); // not admin in logs, but template creator
				}

				try
				{
					/** @var \Bitrix\Tasks\Util\Replicator\Task $replicator */
					$replicator = new static();
					$replicator->setConfig('DISABLE_SOURCE_ACCESS_CONTROLLER', true); // do not query rights and do not check it
					$produceResult = $replicator->produce($templateId, $userId, array('OVERRIDE_DATA' => array('CREATED_DATE' => $executionTime)));

					if ($produceResult->isSuccess())
					{
						static::incrementReplicationCount($templateId, $template['TPARAM_REPLICATION_COUNT']);

						$task = $produceResult->getInstance();
						$subInstanceResult = $produceResult->getSubInstanceResult();

						$result->setInstance($task);
						if (Collection::isA($subInstanceResult))
						{
							$result->setSubInstanceResult($produceResult->getSubInstanceResult());
						}

						$taskId = $task->getId();

                        $createMessage = Loc::getMessage('TASKS_REPLICATOR_TASK_CREATED');

						if ($taskId)
						{
							$createMessage .= ' (#'.$taskId.')';
						}
					}
					else
					{
						$createMessage = Loc::getMessage('TASKS_REPLICATOR_TASK_WAS_NOT_CREATED');
					}

					$result->adoptErrors($produceResult);
				}
				catch(\Exception $e) // catch EACH exception, as we dont want the agent to repeat every 10 minutes in case of smth is wrong
				{
					$createMessage = Loc::getMessage('TASKS_REPLICATOR_TASK_POSSIBLY_WAS_NOT_CREATED');
					if ($taskId)
					{
						$createMessage = Loc::getMessage('TASKS_REPLICATOR_TASK_CREATED').' (#'.$taskId.')';
					}

					$result->addException($e, Loc::getMessage('TASKS_REPLICATOR_INTERNAL_ERROR'));
				}

				// switch an original hit user back, unless we want some strange things to happen
				if ($userChanged)
				{
					User::setOccurAsId($originalUser);
				}
			}

			if ($createMessage !== '')
			{
				static::sendToSysLog($templateId, intval($taskId), $createMessage, $result->getErrors());
			}

			// calculate next execution time

			if ($resumeReplication)
			{
				$currentUserTimezone = User::getTimeZoneOffsetCurrentUser();
				$lastTime = $executionTime;
				$iterationCount = 0;

				do
				{
					$nextResult = static::getNextTime($template, $lastTime);
					$nextData = $nextResult->getData();
					$nextTime = $nextData['TIME'];

					// next time is legal, but goes before or equals to the current time
					if (($nextTime && MakeTimeStamp($lastTime) >= MakeTimeStamp($nextTime)) || ($iterationCount > 10000))
					{
						if ($iterationCount > 10000)
						{
							$message = 'insane iteration count reached while calculating next execution time';
						}
						else
						{
							$creator = $template['CREATED_BY'];
							$creatorTimezone =  User::getTimeZoneOffset($creator);

							$eDebug = array(
								$creator,
								time(),
								$currentUserTimezone,
								$creatorTimezone,
								$replicateParams['TIME'],
								$replicateParams['TIMEZONE_OFFSET'],
								$iterationCount
							);
							$message = 'getNextTime() loop detected for replication by template '.$templateId.' ('.$nextTime.' => '.$lastTime.') ('.implode(', ', $eDebug).')';
						}

						Util::log($message); // write to b24 exception log
						static::sendToSysLog($templateId, 0, Loc::getMessage('TASKS_REPLICATOR_PROCESS_ERROR'), null, true);

						$nextTime = false; // possible endless loop, this agent must be stopped
						break;
					}

					// $nextTime in current user`s time (or server time, if no user)

					$lastTime = $nextTime;
					// we can compare one user`s time only with another user`s time, we canna just take time() value
					$cTime = time() + $currentUserTimezone;

					$iterationCount++;
				}
				while (($nextResult->isSuccess() && $nextTime) && MakeTimeStamp($nextTime) < $cTime);

				if ($nextTime)
				{
					// we can not use CAgent::Update() here, kz the agent will be updated again just after this function ends ...
					global $pPERIOD;

					// still have $nextTime in current user timezone, we need server time now, so:
					$nextTime = MakeTimeStamp($nextTime) - $currentUserTimezone;
					$nextTimeFormatted = UI::formatDateTime($nextTime);

					$replicateParams['NEXT_EXECUTION_TIME'] = $nextTimeFormatted;

					$template = new \CTaskTemplates();
					$template->Update(
						$templateId,
						array('REPLICATE_PARAMS' => serialize($replicateParams)),
						array('SKIP_AGENT_PROCESSING' => true)
					);

					// ... but we may set some global var called $pPERIOD
					// "why ' - time()'?" you may ask. see CAgent::ExecuteAgents(), in the last sql we got:
					// NEXT_EXEC=DATE_ADD(".($arAgent["IS_PERIOD"]=="Y"? "NEXT_EXEC" : "now()").", INTERVAL ".$pPERIOD." SECOND),
					$pPERIOD = $nextTime - time();

					$timeZoneFromGmtInSeconds = date('Z', time());

					static::sendToSysLog(
						$templateId,
						0,
						Loc::getMessage('TASKS_REPLICATOR_NEXT_TIME', array(
							'#TIME#' => $nextTimeFormatted.' ('.UI::formatTimezoneOffsetUTC($timeZoneFromGmtInSeconds).')',
							'#PERIOD#' => $pPERIOD,
							'#SECONDS#' => Loc::getMessagePlural('TASKS_REPLICATOR_SECOND', $pPERIOD),
						))
					);

					return $agentName; // keep agent working
				}
				else
				{
					$firstError = $nextResult->getErrors()->first();
					if ($firstError)
					{
						$replicationCancelReason = $firstError->getMessage();
					}
				}
			}

			static::sendToSysLog(
				$templateId,
				0,
				Loc::getMessage('TASKS_REPLICATOR_PROCESS_STOPPED').($replicationCancelReason != ''? ': '.$replicationCancelReason : '')
			);
		}

		return ''; // agent will be simply deleted
	}

	/**
	 * Calculates next time agent should be scheduled at
	 *
	 * @param array $templateData
	 * @param bool $agentTime
	 * @param bool $nowTime
	 * @return Util\Result
	 */
	public static function getNextTime(array $templateData, $agentTime = false, $nowTime = false)
	{
		$result = new Util\Result();

		if (!is_array($templateData['REPLICATE_PARAMS']))
		{
			$templateData['REPLICATE_PARAMS'] = array();
		}
		$arParams = \CTaskTemplates::parseReplicationParams($templateData['REPLICATE_PARAMS']); // todo: replace with just $template['REPLICATE_PARAMS']

		// get users and their time zone offsets
		$currentTimeZoneOffset = User::getTimeZoneOffsetCurrentUser(); // set 0 to imitate working on agent

		if (isset($arParams['TIMEZONE_OFFSET']))
		{
			$creatorTimeZoneOffset = $arParams['TIMEZONE_OFFSET'];
		}
		else
		{
			$creatorTimeZoneOffset = User::getTimeZoneOffset($templateData['CREATED_BY']);
		}

		// prepare time to be forced to
		$creatorPreferredTime = UI::parseTimeAmount(date("H:i", strtotime($arParams["TIME"]) - $creatorTimeZoneOffset), 'HH:MI');

		// prepare base time
		$baseTime = time(); // server time
		if ($nowTime)
		{
			$nowTime = MakeTimeStamp($nowTime); // from string to stamp
			if ($nowTime) // time parsed normally
			{
				// $agentTime is in current user`s time, but we want server time here
				$nowTime -= $currentTimeZoneOffset;
				$baseTime = $nowTime;
			}
		}

		if ($agentTime) // agent were found and had legal next_time
		{
			$agentTime = MakeTimeStamp($agentTime); // from string to stamp
			if ($agentTime) // time parsed normally
			{
				// $agentTime is in current user`s time, but we want server time here
				$agentTime -= $currentTimeZoneOffset;
				$baseTime = $agentTime;
			}
		}

		// prepare time limits
		$startTime = 0;
		if ($arParams["START_DATE"])
		{
			$startTime = MakeTimeStamp($arParams["START_DATE"]); // from string to stamp
			$startTime -= $creatorTimeZoneOffset;
		}

		$endTime = PHP_INT_MAX; // never ending
		if ($arParams["END_DATE"])
		{
			$endTime = MakeTimeStamp($arParams["END_DATE"]); // from string to stamp
			$endTime -= $creatorTimeZoneOffset;
		}

		// now get max of dates and add time
		$baseTime = max($baseTime, $startTime);

		// now calculate next time based on current $baseTime

		$arPeriods = array("daily", "weekly", "monthly", "yearly");
		$arOrdinals = array("first", "second", "third", "fourth", "last");
		$arWeekDays = array("mon", "tue", "wed", "thu", "fri", "sat", "sun");
		$type = in_array($arParams["PERIOD"], $arPeriods) ? $arParams["PERIOD"] : "daily";

		$nextTime = 0;
		switch ($type)
		{
			case "daily":
				$nextTime = static::getDailyDate($baseTime, $arParams, $creatorPreferredTime);
				break;

			case "weekly":
				$nextTime = static::getWeeklyDate($baseTime, $arParams, $creatorPreferredTime);
				break;

			case "monthly":
				$nextTime = static::getMonthlyDate($baseTime, $arParams, $creatorPreferredTime, $arOrdinals, $arWeekDays);
				break;

			case "yearly":
				$nextTime = static::getYearlyDate($baseTime, $arParams, $creatorPreferredTime, $arOrdinals, $arWeekDays);
				break;
		}

		// now check if we can proceed
		$proceed = false;

		if ($nextTime)
		{
			$proceed = true;

			// about end date
			if (array_key_exists("REPEAT_TILL", $arParams) && $arParams['REPEAT_TILL'] != 'endless')
			{
				if ($arParams['REPEAT_TILL'] == 'date')
				{
					$proceed = !($endTime && $nextTime > $endTime);

					if (!$proceed)
					{
						$result->addError('STOP_CONDITION.END_DATE_REACHED', Loc::getMessage('TASKS_REPLICATOR_END_DATE_REACHED'));
					}
				}
				elseif ($arParams['REPEAT_TILL'] == 'times' && $templateData)
				{
					$proceed = intval($templateData['TPARAM_REPLICATION_COUNT']) < intval($arParams['TIMES']);

					if (!$proceed)
					{
						$result->addError('STOP_CONDITION.LIMIT_REACHED', Loc::getMessage('TASKS_REPLICATOR_LIMIT_REACHED'));
					}
				}
			}
		}
		else // $nextTime was not calculated
		{
			if ($result->getErrors()->isEmpty())
			{
				$result->addError('STOP_CONDITION.ILLEGAL_NEXT_TIME', Loc::getMessage('TASKS_REPLICATOR_ILLEGAL_NEXT_TIME'));
			}
		}

		// to current time zone if we can proceed
		if ($proceed)
		{
			$nextTime += $currentTimeZoneOffset;
		}
		else
		{
			$nextTime = 0;
		}

		$result->setData(array(
			'TIME' => ($nextTime? UI::formatDateTime($nextTime) : '')
		));

		return $result;
	}

	protected static function getDailyDate($baseTime, $replicateParams, $preferredTime)
	{
		$num =
			(int)$replicateParams["EVERY_DAY"]
			+ (int)($replicateParams["DAILY_MONTH_INTERVAL"] ?? 0)
		;

		$date = static::stripTime($baseTime) + $preferredTime;

		if ($date <= $baseTime)
		{
			$date += 86400 * $num;
		}

		if ($replicateParams["WORKDAY_ONLY"] == "Y")
		{
			// get server datetime as string and create an utc-datetime object with this string, as Calendar works only with utc datetime object
			$dateInst = Util\Type\DateTime::createFromUserTimeGmt(UI::formatDateTime($date));
			$calendar = new Util\Calendar();

			if (!$calendar->isWorkTime($dateInst))
			{
				$cwt = $calendar->getClosestWorkTime($dateInst); // get closest time in UTC
				$cwt = $cwt->convertToLocalTime(); // change timezone to server timezone

				$date = $cwt->getTimestamp(); // set server timestamp
				$date = static::stripTime($date) + $preferredTime;
			}
		}

		return $date;
	}

	protected static function getWeeklyDate($baseTime, $arParams, $preferredTime)
	{
		$weekNumber = intval($arParams["EVERY_WEEK"]);
		$currentDay = date("N", $baseTime); // day 1 - 7
		$days = is_array($arParams["WEEK_DAYS"]) && sizeof(array_filter($arParams["WEEK_DAYS"])) ? $arParams["WEEK_DAYS"] : array(1); // days 1 - 7

		$preferredDateTime = static::stripTime($baseTime) + $preferredTime;
		$date = $preferredDateTime;

		// check if we need to create task today
		if (in_array($currentDay, $days))
		{
			if ($date > $baseTime)
			{
				return $date;
			}
		}

		// check if we have "chosen day" ahead, till the end of the week
		$nextDay = false;
		for ($i = $currentDay + 1; $i <= 7; $i++)
		{
			if(in_array($i, $days))
			{
				$nextDay = $i;
				break;
			}
		}

		if ($nextDay)
		{
			// next available day found, so just move there
			$date = $preferredDateTime + ($nextDay - $currentDay) * 86400;
		}
		else
		{
			// we are at the end of the week, and there are no chosen days to pick
			// so we skip $weekNumber weeks and add the first available day
			reset($days);
			$firstDay = current($days);
			$restOfWeek = 7 - $currentDay;

			$date = $preferredDateTime + ($weekNumber > 1? ($weekNumber - 1) : 0) * 7 * 86400 + ($restOfWeek + $firstDay) * 86400;
		}

		return $date;
	}

	protected static function getMonthlyDate($startDate, $replicateParams, $preferredTime, $ordinals, $weekDays)
	{
		$subType = $replicateParams["MONTHLY_TYPE"] == 2 ? "weekday" : "monthday";
		if ($subType == "weekday")
		{
			$ordinal = array_key_exists($replicateParams["MONTHLY_WEEK_DAY_NUM"], $ordinals) ? $ordinals[$replicateParams["MONTHLY_WEEK_DAY_NUM"]] : $ordinals[0];
			$weekDay = array_key_exists($replicateParams["MONTHLY_WEEK_DAY"], $weekDays) ? $weekDays[$replicateParams["MONTHLY_WEEK_DAY"]] : $weekDays[0];
			$num = intval($replicateParams["MONTHLY_MONTH_NUM_2"]) > 0 ? intval($replicateParams["MONTHLY_MONTH_NUM_2"]) : 1;

			$date = strtotime($ordinal." ".$weekDay." of this month") + $preferredTime;

			if ($date <= $startDate)
			{
				$date = static::addMonths(new \DateTime(date('Y-m-d H:i:s', $date)), $num)->getTimestamp();
				$date = strtotime($ordinal." ".$weekDay." of ".date("Y-m-d", $date)) + $preferredTime;
			}
		}
		else
		{
			$day = intval($replicateParams["MONTHLY_DAY_NUM"]) >= 1 && intval($replicateParams["MONTHLY_DAY_NUM"]) <= 31 ? intval($replicateParams["MONTHLY_DAY_NUM"]) : 1;
			$num = intval($replicateParams["MONTHLY_MONTH_NUM_1"]) > 0 ? intval($replicateParams["MONTHLY_MONTH_NUM_1"]) : 1;

			$date = static::stripTime(strtotime(date("Y-m-".sprintf("%02d", $day), $startDate))) + $preferredTime;

			if ($date <= $startDate)
			{
				$date = static::addMonths(new \DateTime(date('Y-m-d H:i:s', $date)), $num)->getTimestamp();
				$date = strtotime(date("Y-m-".sprintf("%02d", $day), $date)) + $preferredTime;
			}
		}

		return $date;
	}

	protected static function getYearlyDate($startDate, $replicateParams, $preferredTime, $ordinals, $weekDays)
	{
		$subType = $replicateParams["YEARLY_TYPE"] == 2 ? "weekday" : "monthday";
		if ($subType == "weekday")
		{
			$ordinal = array_key_exists($replicateParams["YEARLY_WEEK_DAY_NUM"], $ordinals) ? $ordinals[$replicateParams["YEARLY_WEEK_DAY_NUM"]] : $ordinals[0];
			$weekDay = array_key_exists($replicateParams["YEARLY_WEEK_DAY"], $weekDays) ? $weekDays[$replicateParams["YEARLY_WEEK_DAY"]] : $weekDays[0];
			$month = intval($replicateParams["YEARLY_MONTH_2"]) >= 0 && intval($replicateParams["YEARLY_MONTH_2"]) < 12 ? intval($replicateParams["YEARLY_MONTH_2"]) : 0;
			$month += 1;

			$date = strtotime($ordinal." ".$weekDay." of ".date("Y", $startDate)."-".sprintf("%02d", $month)."-01") + $preferredTime;

			if ($date <= $startDate)
			{
				$date = strtotime($ordinal." ".$weekDay." of ".(date("Y", $startDate) + 1)."-".sprintf("%02d", $month)."-01") + $preferredTime;
			}
		}
		else
		{
			$day = intval($replicateParams["YEARLY_DAY_NUM"]) >= 1 && intval($replicateParams["YEARLY_DAY_NUM"]) <= 31 ? intval($replicateParams["YEARLY_DAY_NUM"]) : 1;
			$month = intval($replicateParams["YEARLY_MONTH_1"]) >= 0 && intval($replicateParams["YEARLY_MONTH_1"]) < 12 ? intval($replicateParams["YEARLY_MONTH_1"]) : 0;
			$month += 1;

			$date = strtotime(date("Y", $startDate)."-".sprintf("%02d", $month)."-".sprintf("%02d", $day)) + $preferredTime;

			if ($date <= $startDate)
			{
				$date = strtotime((date("Y", $startDate) + 1)."-".sprintf("%02d", $month)."-".sprintf("%02d", $day)) + $preferredTime;
			}
		}

		return $date;
	}

	private static function addMonths(\DateTime $date, $months)
	{
		$years = floor(abs($months / 12));
		$leap = ($date->format('d') >= 29);
		$m = 12 * ($months >= 0? 1 : -1);

		for ($a = 1; $a < $years; $a++)
		{
			$date = static::addMonths($date, $m);
		}
		$months -= ($a - 1) * $m;

		$resultDate = clone $date;
		if ($months != 0)
		{
			$modifier = $months.' months';
			$date->modify($modifier);

			if ($date->format('m') % 12 != (12 + $months + $resultDate->format('m')) % 12)
			{
				$day = $date->format('d');
				$resultDate->modify("-{$day} days");
			}
			$resultDate->modify($modifier);
		}

		$y = $resultDate->format('Y');
		if ($leap && ($y % 4) == 0 && ($y % 100) != 0 && $resultDate->format('d') == 28)
		{
			$resultDate->modify('+1 day');
		}

		return $resultDate;
	}

	public static function reInstallAgent($templateId, array $templateData)
	{
		// todo: get rid of use of CTasks one day...
		$name = 'CTasks::RepeatTaskByTemplateId('.$templateId.');';

		// First, remove all agents for this template

		self::unInstallAgent($templateId);

		// Set up new agent
		if ($templateData['REPLICATE'] === 'Y')
		{
			$nextTimeResult = static::getNextTime($templateData);
			if ($nextTimeResult->isSuccess())
			{
				$nextTimeData = $nextTimeResult->getData();
				$nextTime = $nextTimeData['TIME'];

				if($nextTime)
				{

					\CAgent::addAgent(
						$name,
						'tasks',
						'N',        // is periodic?
						86400,        // interval
						$nextTime,    // datecheck
						'Y',        // is active?
						$nextTime    // next_exec
					);
				}
				else
				{
					static::sendToSysLog(
						$templateId,
						0,
						Loc::getMessage('TASKS_REPLICATOR_PROCESS_STOPPED'). ' '.Loc::getMessage('TASKS_REPLICATOR_PROCESS_ERROR')
					);
				}
			}
		}
	}

	public static function resurrectFallenTemplates()
	{
		global $DB;

		$fallenTemplates = $DB->Query("
			SELECT Template.ID,
				   Template.CREATED_BY,
				   Template.REPLICATE,
				   Template.REPLICATE_PARAMS,
				   Template.TPARAM_REPLICATION_COUNT
			FROM b_tasks_template Template
				 INNER JOIN b_tasks_syslog Log ON Log.ENTITY_ID = Template.ID
			WHERE Log.TYPE = 3 AND
				  Log.CREATED_DATE >= STR_TO_DATE('2018-02-21 00:00:00', '%Y-%m-%d %H:%i:%s')
		");

		$alreadyUpdated = array();

		while ($fallenTemplate = $fallenTemplates->Fetch())
		{
			$templateId = $fallenTemplate['ID'];
			$templateData = array(
				'CREATED_BY' => $fallenTemplate['CREATED_BY'],
				'REPLICATE' => $fallenTemplate['REPLICATE'],
				'REPLICATE_PARAMS' => unserialize($fallenTemplate['REPLICATE_PARAMS'], ['allowed_classes' => false]),
				'TPARAM_REPLICATION_COUNT' => $fallenTemplate['TPARAM_REPLICATION_COUNT']
			);

			if (!in_array($templateId, $alreadyUpdated))
			{
				static::reInstallAgent($templateId, $templateData);
				$alreadyUpdated[] = $templateId;
			}
		}

		return '';
	}

	public static function unInstallAgent($id)
	{

		\CAgent::removeAgent('CTasks::RepeatTaskByTemplateId('.$id.');', 'tasks');


		\CAgent::removeAgent('CTasks::RepeatTaskByTemplateId('.$id.', 0);', 'tasks');


		\CAgent::removeAgent('CTasks::RepeatTaskByTemplateId('.$id.', 1);', 'tasks');
	}

	/**
	 * Returns true if $source is a multitask template (template with multiple responsibles)
	 *
	 * @param $source
	 * @param array $parameters
	 * @return bool
	 */
	protected function isMultitaskSource($source, array $parameters = array())
	{
		$enabled = !array_key_exists('MULTITASKING', $parameters) || $parameters['MULTITASKING'] != false;

		return
			Item\Task\Template::isA($source) && // it is a template
			$source['MULTITASK'] == 'Y' && // multitask is on in the template
			count($source['RESPONSIBLES']) && // there are responsibles to produce tasks for
			$enabled; // multitasking was not disabled
	}

	/**
	 * Returns sub-templates data (in array format) for the template with ID == $id
	 *
	 * @param $id
	 * @return array
	 */
	private function getSubItemData($id)
	{
		$result = array();

		$id = intval($id);
		if(!$id)
		{
			return $result;
		}

		// todo: move it to \Bitrix\Tasks\Item\Task\Template::find(array('select' => array('*', 'SE_CHECKLIST')))
		// todo: do not forget about access controller
		$res = \CTaskTemplates::getList(array('BASE_TEMPLATE_ID' => 'asc'), array('BASE_TEMPLATE_ID' => $id), false, array('INCLUDE_TEMPLATE_SUBTREE' => true), array('*', 'UF_*', 'BASE_TEMPLATE_ID'));
		while($item = $res->fetch())
		{
			if($item['ID'] == $id)
			{
				continue;
			}

			// unpack values
			$item['RESPONSIBLES'] = unserialize($item['RESPONSIBLES'] ?? '', ['allowed_classes' => false]);
			$item['ACCOMPLICES'] = unserialize($item['ACCOMPLICES'] ?? '', ['allowed_classes' => false]);
			$item['AUDITORS'] = unserialize($item['AUDITORS'] ?? '', ['allowed_classes' => false]);
			$item['TAGS'] = unserialize($item['TAGS'] ?? '', ['allowed_classes' => false]);
			$item['REPLICATE_PARAMS'] = unserialize($item['REPLICATE_PARAMS'] ?? '', ['allowed_classes' => false]);
			$item['DEPENDS_ON'] = unserialize($item['DEPENDS_ON'] ?? '', ['allowed_classes' => false]);

			$result[$item['ID']] = $item;
		}

		// get checklist data
		// todo: convert getListByTemplateDependency() to a runtime mixin for the template entity
		$res = \Bitrix\Tasks\Internals\Task\Template\CheckListTable::getListByTemplateDependency($id, array(
			'order' => array('SORT' => 'ASC'),
			'select' => array('ID', 'TEMPLATE_ID', 'CHECKED', 'SORT', 'TITLE')
		));
		while($item = $res->fetch())
		{
			if(isset($result[$item['TEMPLATE_ID']]))
			{
				$result[$item['TEMPLATE_ID']]['SE_CHECKLIST'][$item['ID']] = $item;
			}
		}

		return $result;
	}

	/**
	 * Adds some debug info to the system log for the template with ID == $templateId
	 *
	 * @param $templateId
	 * @param $taskId
	 * @param $message
	 * @param Util\Error\Collection|null $errors
	 * @param bool $forceTypeError
	 */
	private static function sendToSysLog($templateId, $taskId, $message, Util\Error\Collection $errors = null, $forceTypeError = false)
	{
		$record = new SystemLog(array(
			'ENTITY_TYPE' => 1,
			'ENTITY_ID' => $templateId,
			'MESSAGE' => $message,
		));
		if($taskId)
		{
			$record['PARAM_A'] = $taskId;
		}

		if($forceTypeError)
		{
			$record['TYPE'] = SystemLog::TYPE_ERROR;
		}
		elseif($errors instanceof Util\Error\Collection && !$errors->isEmpty())
		{
			$record['TYPE'] = $errors->find(array('TYPE' => Util\Error::TYPE_FATAL))->isEmpty() ? SystemLog::TYPE_WARNING : SystemLog::TYPE_ERROR;
		}

		$record['ERROR'] = $errors;
		$record->save();
	}

	/**
	 * Increments replication counter of the template with ID == $templateId
	 *
	 * @param $templateId
	 * @param $replicationCount
	 */
	private static function incrementReplicationCount($templateId, &$replicationCount)
	{
		// todo: replace the following with $template->incrementReplicationCount()->save() when ready

		$template = Item\Task\Template::getInstance($templateId, static::getEffectiveUser());
		$templateInst = new \CTaskTemplates();
		$templateInst->update($templateId, array(
			'TPARAM_REPLICATION_COUNT' => intval($template['TPARAM_REPLICATION_COUNT']) + 1
		));

		$replicationCount++;
	}

	private static function getEffectiveUser()
	{
		return User::getAdminId();
	}

	private static function stripTime($nextTime)
	{
		$m = (int) date("n", $nextTime);
		$d = (int) date("j", $nextTime);
		$y = (int) date("Y", $nextTime);

		return mktime(0, 0, 0, $m, $d, $y);
	}

	private static function printDebugTime($l, $t)
	{
		Util::printDebug($l.UI::formatDateTime($t));
	}

	/**
	 * Check if template->sub-templates relation tree is correct and return it
	 *
	 * @param array $subEntitiesData
	 * @param $srcId
	 * @return array|bool
	 */
	private function getCreationOrder(array $subEntitiesData, $srcId)
	{
		$walkQueue = array($srcId);
		$treeBundles = array();

		foreach($subEntitiesData as $subTemplate)
		{
			$treeBundles[$subTemplate['BASE_TEMPLATE_ID']][] = $subTemplate['ID'];
		}

		$tree = $treeBundles;
		$met = array();
		while(!empty($walkQueue))
		{
			$topTemplate = array_shift($walkQueue);
			if(isset($met[$topTemplate])) // hey, i`ve met this guy before!
			{
				return false;
			}
			$met[$topTemplate] = true;

			if(is_array($treeBundles[$topTemplate] ?? null))
			{
				foreach($treeBundles[$topTemplate] as $template)
				{
					$walkQueue[] = $template;
				}
			}
			unset($treeBundles[$topTemplate]);
		}

		return $tree;
	}

	private static function liftLogAgent()
	{
		\Bitrix\Tasks\Util\AgentManager::checkAgentIsAlive('rotateSystemLog', 259200);
	}
}