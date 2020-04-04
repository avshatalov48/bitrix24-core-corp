<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

use Bitrix\Tasks\Internals\Notification\Task\ThrottleTable;
use Bitrix\Tasks\Util\AgentManager;
use Bitrix\Tasks\Util\User;
use Bitrix\Main\Localization\Loc;

class CTaskNotifications
{
	const PUSH_MESSAGE_MAX_LENGTH = 255;

	private static $arBuiltInTasksXmlIds = array(
		'6dfecf46063cd844ebeecf1873cff791',
		'148c0ccdbd25870eb632557e3327cb1c',
		'0cde03b1a29df438ba454327249a0750',
		'c5156d1b21fc626340295523a1074a8c',
		'c20c713f668b08f2804a3d007d724196',
		'52106f124d9f1b50d6315df50c696c93',
		'00000010000000000000000000000001',
		'00000010000000000000000000000002'
	);

	// im delivery buffer
	private static $bufferize = false;
	private static $buffer = array();

	// additional data cache
	private static $cacheData = false;
	private static $cache = array();

	// enable\disable notifications
	private static $suppressIM = false;

	// enable\disable addition author to the sonet log recipients
	private static $sonetLogNotifyAuthor = false;

	########################
	# main actions

	public static function sendAddMessage($arFields, $arParams = array())
	{
		$isBbCodeDescription = true;
		if (isset($arFields['DESCRIPTION_IN_BBCODE']) && ($arFields['DESCRIPTION_IN_BBCODE'] === 'N'))
			$isBbCodeDescription = false;

		if (isset($arFields['XML_ID']) && strlen($arFields['XML_ID']))
		{
			// Don't send any messages when created built-in tasks
			if (in_array($arFields['XML_ID'], self::$arBuiltInTasksXmlIds, true))
				return;
		}

		$cacheWasEnabled = self::enableStaticCache();

		$spawnedByAgent = false;

		if (is_array($arParams))
		{
			if (
				isset($arParams['SPAWNED_BY_AGENT'])
				&& (
					($arParams['SPAWNED_BY_AGENT'] === 'Y')
					|| ($arParams['SPAWNED_BY_AGENT'] === true)
				)
			)
			{
				$spawnedByAgent = true;
			}
		}

		$arUsers = CTaskNotifications::__GetUsers($arFields);

		$createdBy = 0;
		if (isset($arFields['CREATED_BY']) && $arFields['CREATED_BY'] > 0)
		{
			$createdBy = (int)$arFields['CREATED_BY'];
		}

		$bExcludeLoggedUser = true;
		if (
			$spawnedByAgent ||
			(
				$createdBy &&
				$createdBy !== User::getId() &&
				array_key_exists(User::getId(), $arUsers)
			)
		)
		{
			$bExcludeLoggedUser = false;
		}

		$arRecipientsIDs = CTaskNotifications::GetRecipientsIDs($arFields, $bExcludeLoggedUser);

		$effectiveUserId = false;

		if ($spawnedByAgent)
		{
			$effectiveUserId = ($createdBy? $createdBy : 1);
		}
		elseif (User::getId())
		{
			$effectiveUserId = User::getId();
		}
		elseif ($createdBy)
		{
			$effectiveUserId = $createdBy;
		}

		if (sizeof($arRecipientsIDs) && ($effectiveUserId !== false))
		{
			$arRecipientsIDs = array_unique($arRecipientsIDs);

			$strResponsible = CTaskNotifications::__Users2String($arFields["RESPONSIBLE_ID"], $arUsers, $arFields["NAME_TEMPLATE"]);

			$invariantDescription = GetMessage("TASKS_MESSAGE_RESPONSIBLE_ID").': [COLOR=#000]'.$strResponsible."[/COLOR]\r\n";

			if ($strAccomplices = CTaskNotifications::__Users2String($arFields["ACCOMPLICES"], $arUsers, $arFields["NAME_TEMPLATE"]))
				$invariantDescription .= GetMessage("TASKS_MESSAGE_ACCOMPLICES").": [COLOR=#000]".$strAccomplices."[/COLOR]\r\n";

			if ($strAuditors = CTaskNotifications::__Users2String($arFields["AUDITORS"], $arUsers, $arFields["NAME_TEMPLATE"]))
				$invariantDescription .= GetMessage("TASKS_MESSAGE_AUDITORS").": [COLOR=#000]".$strAuditors."[/COLOR]\r\n";

			// There is can be different messages for users (caused by differnent users' timezones)
			$arVolatileDescriptions = array();

			// Is there correct deadline (it cause volatile part of message for different timezones)?
			if (
				$arFields["DEADLINE"]
				&& (MakeTimeStamp($arFields['DEADLINE']) > 0)
			)
			{
				// Get unix timestamp for DEADLINE
				$utsDeadline = MakeTimeStamp($arFields['DEADLINE']) - self::getUserTimeZoneOffset();

				// Collect recipients' timezones
				foreach ($arRecipientsIDs as $userId)
				{
					$tzOffset = (int) self::getUserTimeZoneOffset($userId);

					if ( ! isset($arVolatileDescriptions[$tzOffset]) )
					{
						// Make bitrix timestamp for given user
						$bitrixTsDeadline = $utsDeadline + $tzOffset;

						$deadlineAsString = \Bitrix\Tasks\UI::formatDateTime($bitrixTsDeadline, '^'.\Bitrix\Tasks\UI::getDateTimeFormat());

						$arVolatileDescriptions[$tzOffset] = array(
							'recipients'  => array(),
							'description' => GetMessage('TASKS_MESSAGE_DEADLINE')
								. ': [COLOR=#000]' . $deadlineAsString . "[/COLOR]\r\n"
						);
					}

					$arVolatileDescriptions[$tzOffset]['recipients'][] = $userId;
				}
			}

			// If there is no volatile part of descriptions, send to all recipients at once
			if (empty($arVolatileDescriptions))
			{
				$arVolatileDescriptions[] = array(
					'recipients'  => $arRecipientsIDs,
					'description' => ''
				);
			}

			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = $effectiveUserId;

			$descs = array();
			foreach ($arVolatileDescriptions as $arData)
			{
				foreach($arData['recipients'] as $uid)
				{
					$descs[$uid] = $arData['description'];
				}
			}

			$taskName = self::formatTaskName($arFields['ID'], $arFields['TITLE'], $arFields['GROUP_ID'], false);
			$addMessage = self::getGenderMessage($occurAsUserId, "TASKS_NEW_TASK_MESSAGE");

			$instant = str_replace(
				array(
					"#TASK_TITLE#"
				),
				array(
					$taskName
				),
				$addMessage
			);

			$email = str_replace(
				array(
					"#TASK_TITLE#"
				),
				array(
					strip_tags($taskName)
				),
				$addMessage
			);

			CTaskNotifications::sendMessageEx($arFields["ID"], $occurAsUserId, $arRecipientsIDs, array(
				'INSTANT' => $instant,
				'EMAIL' => $email,
				'PUSH' => self::makePushMessage('TASKS_NEW_TASK_MESSAGE', $occurAsUserId, $arFields)
			), array(
				'ENTITY_CODE' => 'TASK',
				'ENTITY_OPERATION' => 'ADD',
				'EVENT_DATA' => array(
					'ACTION'   => 'TASK_ADD',
					'arFields' => $arFields
				),
				'CALLBACK' => array(
					'BEFORE_SEND' => function($message) use($isBbCodeDescription, $invariantDescription, $descs)
					{
						$description = $invariantDescription.$descs[$message['TO_USER_IDS'][0]];

						$message['MESSAGE']['INSTANT'] = str_replace(
							array(
								"#TASK_EXTRA#"
							),
							array(
								$description
							),
							$message['MESSAGE']['INSTANT']
						);

						if ($isBbCodeDescription)
						{
							$parser = new CTextParser();
							$description = str_replace(
								"\t",
								' &nbsp; &nbsp;',
								$parser->convertText($description)
							);
						}

						$message['MESSAGE']['EMAIL'] = str_replace(
							array(
								"#TASK_EXTRA#"
							),
							array(
								$description
							),
							$message['MESSAGE']['EMAIL']
						);

						return $message;
					}
				)
			));
		}

		// sonet log, not for CRM
		if (
			!isset($arFields["UF_CRM_TASK"])
			|| (
				is_array($arFields["UF_CRM_TASK"])
				&& (
					!isset($arFields["UF_CRM_TASK"][0])
					|| strlen($arFields["UF_CRM_TASK"][0]) <= 0
				)
			)
			|| (
				!is_array($arFields["UF_CRM_TASK"])
				&& strlen($arFields["UF_CRM_TASK"]) <= 0
			)
		)
		{
			self::SendMessageToSocNet($arFields, $spawnedByAgent);
		}

		if($cacheWasEnabled)
		{
			self::disableStaticCache();
		}
	}

	public static function sendUpdateMessage($arFields, $arTask, $bSpawnedByAgent = false, array $parameters = array())
	{
		$occurAsUserId = self::getOccurAsUserId($arFields, $arTask, $bSpawnedByAgent, $parameters);
		$effectiveUserId = self::getEffectiveUserId($arFields, $arTask, $bSpawnedByAgent, $parameters);
		// generally, $occurAsUserId === $effectiveUserId, but sometimes dont

		/*
		$bSpawnedByAgent === true means that this task was "created\updated" on angent, and
		in case of that, message author is defined as $arTask['CRAETED_BY'] (below)
		*/

		if(!$bSpawnedByAgent && $parameters['THROTTLE_MESSAGES'])
		{
			AgentManager::checkAgentIsAlive("notificationThrottleRelease", 300);
			ThrottleTable::submitUpdateMessage($arTask['ID'], $occurAsUserId, $arTask, $arFields);
			return;
		}

		$isBbCodeDescription = true;
		if (isset($arFields['DESCRIPTION_IN_BBCODE']))
		{
			if ($arFields['DESCRIPTION_IN_BBCODE'] === 'N')
				$isBbCodeDescription = false;
		}
		elseif (isset($arTask['DESCRIPTION_IN_BBCODE']))
		{
			if ($arTask['DESCRIPTION_IN_BBCODE'] === 'N')
				$isBbCodeDescription = false;
		}

		$taskReassignedTo = null;

		if (
			isset($arFields['RESPONSIBLE_ID'])
			&& ($arFields['RESPONSIBLE_ID'] > 0)
			&& ($arFields['RESPONSIBLE_ID'] != $arTask['RESPONSIBLE_ID'])
		)
		{
			$taskReassignedTo = $arFields['RESPONSIBLE_ID'];
		}

		foreach (array('CREATED_BY', 'RESPONSIBLE_ID', 'ACCOMPLICES', 'AUDITORS', 'TITLE') as $field)
		{
			if ( ! isset($arFields[$field])
				&& isset($arTask[$field])
			)
			{
				$arFields[$field] = $arTask[$field];
			}
		}

		$cacheWasEnabled = self::enableStaticCache();

		// $arChanges contains datetimes IN SERVER TIME, NOT CLIENT
		$arChanges = CTaskLog::GetChanges($arTask, $arFields);
		$trackedFields = CTaskLog::getTrackedFields();

		$arMerged = array(
			'ADDITIONAL_RECIPIENTS' => array()
		);

		// Pack prev users ids to ADDITIONAL_RECIPIENTS, to ensure,
		// that they all will receive message
		{
			if (isset($arTask['CREATED_BY']))
				$arMerged['ADDITIONAL_RECIPIENTS'][] = $arTask['CREATED_BY'];

			if (isset($arTask['RESPONSIBLE_ID']))
				$arMerged['ADDITIONAL_RECIPIENTS'][] = $arTask['RESPONSIBLE_ID'];

			if (isset($arTask['ACCOMPLICES']) && is_array($arTask['ACCOMPLICES']))
				foreach ($arTask['ACCOMPLICES'] as $userId)
					$arMerged['ADDITIONAL_RECIPIENTS'][] = $userId;

			if (isset($arTask['AUDITORS']) && is_array($arTask['AUDITORS']))
				foreach ($arTask['AUDITORS'] as $userId)
					$arMerged['ADDITIONAL_RECIPIENTS'][] = $userId;
		}

		if (isset($arFields['ADDITIONAL_RECIPIENTS']))
		{
			$arFields['ADDITIONAL_RECIPIENTS'] = array_merge (
				$arFields['ADDITIONAL_RECIPIENTS'],
				$arMerged['ADDITIONAL_RECIPIENTS']
			);
		}
		else
		{
			$arFields['ADDITIONAL_RECIPIENTS'] = $arMerged['ADDITIONAL_RECIPIENTS'];
		}

		$arUsers = CTaskNotifications::__GetUsers($arFields);

		$ignoreAuthor = isset($parameters['IGNORE_AUTHOR']) ? !!$parameters['IGNORE_AUTHOR'] : true;
		$arRecipientsIDs = array_unique(CTaskNotifications::GetRecipientsIDs($arFields, $ignoreAuthor, false, $occurAsUserId));

		if (
			( ! empty($arRecipientsIDs) )
			&& (User::getId() || $arFields["CREATED_BY"])
		)
		{
			$curUserTzOffset = (int) self::getUserTimeZoneOffset();
			$arInvariantChangesStrs = array();
			$arVolatileDescriptions = array();
			$arRecipientsIDsByTimezone = array();
			$i = 0;
			foreach ($arChanges as $key => $value)
			{
				if($key == 'DESCRIPTION')
				{
					$arInvariantChangesStrs[] = GetMessage('TASKS_MESSAGE_DESCRIPTION_UPDATED');
					continue;
				}

				++$i;
				$actionMessage = GetMessage("TASKS_MESSAGE_".$key);
				if($actionMessage == '' && isset($trackedFields[$key]) && $trackedFields[$key]['TITLE'] != '')
				{
					$actionMessage = $trackedFields[$key]['TITLE'];
				}

				if (strlen($actionMessage))
				{
					// here we can display value changed for some fields
					$changeMessage = $actionMessage;
					$tmpStr = '';
					switch ($key)
					{
						case 'TIME_ESTIMATE':
							$tmpStr .= self::formatTimeHHMM($value["FROM_VALUE"], true)
								. " -> "
								. self::formatTimeHHMM($value["TO_VALUE"], true);
						break;

						case "TITLE":
							$tmpStr .= $value["FROM_VALUE"]." -> ".$value["TO_VALUE"];
							break;

						case "RESPONSIBLE_ID":
							$tmpStr .=
								CTaskNotifications::__Users2String($value["FROM_VALUE"], $arUsers, $arFields["NAME_TEMPLATE"])
								. ' -> '
								. CTaskNotifications::__Users2String($value["TO_VALUE"], $arUsers, $arFields["NAME_TEMPLATE"]);
							break;

						case "ACCOMPLICES":
						case "AUDITORS":
							$tmpStr .=
								CTaskNotifications::__Users2String(explode(",", $value["FROM_VALUE"]), $arUsers, $arFields["NAME_TEMPLATE"])
								. ' -> '
								. CTaskNotifications::__Users2String(explode(",", $value["TO_VALUE"]), $arUsers, $arFields["NAME_TEMPLATE"])
								;
							break;

						case "DEADLINE":
						case "START_DATE_PLAN":
						case "END_DATE_PLAN":
							// $arChanges ALREADY contains server time, no need to substract user timezone again
							$utsFromValue = $value['FROM_VALUE'];// - $curUserTzOffset;
							$utsToValue   = $value['TO_VALUE'];// - $curUserTzOffset;

							// It will be replaced below to formatted string with correct dates for different timezones
							$placeholder = '###PLACEHOLDER###' . $i . '###';
							$tmpStr .= $placeholder;

							// Collect recipients' timezones
							foreach ($arRecipientsIDs as $userId)
							{
								$tzOffset = (int) self::getUserTimeZoneOffset($userId);

								if ( ! isset($arVolatileDescriptions[$tzOffset]) )
									$arVolatileDescriptions[$tzOffset] = array();

								if ( ! isset($arVolatileDescriptions[$tzOffset][$placeholder]) )
								{
									// Make bitrix timestamps for given user
									$bitrixTsFromValue = $utsFromValue + $tzOffset;
									$bitrixTsToValue   = $utsToValue + $tzOffset;

									$description = '';

									if ($utsFromValue > 360000)		// is correct timestamp?
									{
										$fromValueAsString = \Bitrix\Tasks\UI::formatDateTime($bitrixTsFromValue, '^'.\Bitrix\Tasks\UI::getDateTimeFormat());

										$description .= $fromValueAsString;
									}

									$description .= ' --> ';

									if ($utsToValue > 360000)		// is correct timestamp?
									{
										$toValueAsString = \Bitrix\Tasks\UI::formatDateTime($bitrixTsToValue, '^'.\Bitrix\Tasks\UI::getDateTimeFormat());

										$description .= $toValueAsString;
									}

									$arVolatileDescriptions[$tzOffset][$placeholder] = $description;
								}

								$arRecipientsIDsByTimezone[$tzOffset][] = $userId;
							}
							break;

						case "TAGS":
							$tmpStr .= ($value["FROM_VALUE"] ? str_replace(",", ", ", $value["FROM_VALUE"])." -> " : "").($value["TO_VALUE"] ? str_replace(",", ", ", $value["TO_VALUE"]) : GetMessage("TASKS_MESSAGE_NO_VALUE"));
							break;

						case "PRIORITY":
							$tmpStr .= GetMessage("TASKS_PRIORITY_".$value["FROM_VALUE"])." -> ".GetMessage("TASKS_PRIORITY_".$value["TO_VALUE"]);
							break;

						case "GROUP_ID":
							if ($value["FROM_VALUE"] && self::checkUserCanViewGroup($effectiveUserId, $value["FROM_VALUE"]))
							{
								$arGroupFrom = self::getSocNetGroup($value["FROM_VALUE"]);
								{
									if ($arGroupFrom)
									{
										$tmpStr .= $arGroupFrom["NAME"]." -> ";
									}
								}
							}
							if ($value["TO_VALUE"] && self::checkUserCanViewGroup($effectiveUserId, $value["TO_VALUE"]))
							{
								$arGroupTo = self::getSocNetGroup($value["TO_VALUE"]);
								{
									if ($arGroupTo)
									{
										$tmpStr .= $arGroupTo["NAME"];
									}
								}
							}
							else
							{
								$tmpStr .= GetMessage("TASKS_MESSAGE_NO_VALUE");
							}

							break;

						case "PARENT_ID":
							if ($value["FROM_VALUE"])
							{
								$rsTaskFrom = CTasks::GetList(array(), array("ID" => $value["FROM_VALUE"]), array('ID', 'TITLE'));
								{
									if ($arTaskFrom = $rsTaskFrom->GetNext())
									{
										$tmpStr .= $arTaskFrom["TITLE"]." -> ";
									}
								}
							}
							if ($value["TO_VALUE"])
							{
								$rsTaskTo = CTasks::GetList(array(), array("ID" => $value["TO_VALUE"]), array('ID', 'TITLE'));
								{
									if ($arTaskTo = $rsTaskTo->GetNext())
									{
										$tmpStr .= $arTaskTo["TITLE"];
									}
								}
							}
							else
							{
								$tmpStr .= GetMessage("TASKS_MESSAGE_NO_VALUE");
							}
							break;

						case "DEPENDS_ON":
							$arTasksFromStr = array();
							if ($value["FROM_VALUE"])
							{
								$rsTasksFrom = CTasks::GetList(array(), array("ID" => explode(",", $value["FROM_VALUE"])), array('ID', 'TITLE'));
								while ($arTaskFrom = $rsTasksFrom->GetNext())
								{
									$arTasksFromStr[] = $arTaskFrom["TITLE"];
								}
							}
							$arTasksToStr = array();
							if ($value["TO_VALUE"])
							{
								$rsTasksTo = CTasks::GetList(array(), array("ID" => explode(",", $value["TO_VALUE"])), array('ID', 'TITLE'));
								while ($arTaskTo = $rsTasksTo->GetNext())
								{
									$arTasksToStr[] = $arTaskTo["TITLE"];
								}
							}
							$tmpStr .= ($arTasksFromStr ? implode(", ", $arTasksFromStr)." -> " : "").($arTasksToStr ? implode(", ", $arTasksToStr) : GetMessage("TASKS_MESSAGE_NO_VALUE"));
							break;

						case "MARK":
							$tmpStr .= (!$value["FROM_VALUE"] ? GetMessage("TASKS_MARK_NONE") : GetMessage("TASKS_MARK_".$value["FROM_VALUE"]))." -> ".(!$value["TO_VALUE"] ? GetMessage("TASKS_MARK_NONE") : GetMessage("TASKS_MARK_".$value["TO_VALUE"]));
							break;

						case "ADD_IN_REPORT":
							$tmpStr .= ($value["FROM_VALUE"] == "Y" ? GetMessage("TASKS_MESSAGE_IN_REPORT_YES") : GetMessage("TASKS_MESSAGE_IN_REPORT_NO"))." -> ".($value["TO_VALUE"] == "Y" ? GetMessage("TASKS_MESSAGE_IN_REPORT_YES") : GetMessage("TASKS_MESSAGE_IN_REPORT_NO"));
							break;

						case "DELETED_FILES":
							$tmpStr .= $value["FROM_VALUE"];
							$tmpStr .= $value["TO_VALUE"];
							break;

						case "NEW_FILES":
							$tmpStr .= $value["TO_VALUE"];
							break;
					}
					if((string) $tmpStr != '')
					{
						$changeMessage .= ": [COLOR=#000]".$tmpStr."[/COLOR]";
					}

					$arInvariantChangesStrs[] = $changeMessage;
				}
			}

			$recp2tz = array();
			foreach($arRecipientsIDsByTimezone as $tz => $rcp)
			{
				foreach($rcp as $uid)
				{
					$recp2tz[$uid] = $tz;
				}
			}

			$invariantDescription = null;

			if ( ! empty($arInvariantChangesStrs) )
				$invariantDescription = implode("\r\n", $arInvariantChangesStrs);

			if (
				($invariantDescription !== null)
				&& ( ! empty($arRecipientsIDs) )
			)
			{
				// If there is no volatile part of descriptions, send to all recipients at once
				if (empty($arVolatileDescriptions))
				{
					$arVolatileDescriptions['some_timezone'] = array();
					$arRecipientsIDsByTimezone['some_timezone']  = $arRecipientsIDs;
				}

				$updateMessage = self::getGenderMessage($occurAsUserId, 'TASKS_TASK_CHANGED_MESSAGE');
				$taskName = self::formatTaskName($arTask['ID'], $arTask['TITLE'], $arTask['GROUP_ID'], false);

				$instant = str_replace(
					array(
						"#TASK_TITLE#"
					),
					array(
						$taskName
					),
					$updateMessage
				);

				$email = str_replace(
					array(
						"#TASK_TITLE#"
					),
					array(
						strip_tags($taskName)
					),
					$updateMessage
				);
				CTaskNotifications::sendMessageEx($arTask["ID"], $occurAsUserId, $arRecipientsIDs, array(
					'INSTANT' => $instant,
					'EMAIL' => $email,
					'PUSH' => self::makePushMessage('TASKS_TASK_CHANGED_MESSAGE', $occurAsUserId, $arTask)
				), array(
					'ENTITY_CODE' => 'TASK',
					'ENTITY_OPERATION' => 'UPDATE',
					'EVENT_DATA' => array(
						'ACTION'   => 'TASK_UPDATE',
						'arFields' => $arFields,
						'arChanges' => $arChanges
					),
					'TASK_DATA' => $arTask,
					'TASK_ASSIGNED_TO' => $taskReassignedTo,
					'CALLBACK' => array(
						'BEFORE_SEND' => function($message) use($isBbCodeDescription, $invariantDescription, $arVolatileDescriptions, $recp2tz)
						{
							$rcp = $message['TO_USER_IDS'][0];
							$volatile = $arVolatileDescriptions[$recp2tz[$rcp]];

							if(is_array($volatile))
							{
								$description = str_replace(array_keys($volatile), $volatile, $invariantDescription);
							}
							else
							{
								$description = $invariantDescription;
							}

							$message['MESSAGE']['INSTANT'] = str_replace(
								array(
									"#TASK_EXTRA#"
								),
								array(
									$description
								),
								$message['MESSAGE']['INSTANT']
							);

							if ($isBbCodeDescription)
							{
								$parser = new CTextParser();
								$description = str_replace(
									"\t",
									' &nbsp; &nbsp;',
									$parser->convertText($description)
								);
							}

							$message['MESSAGE']['EMAIL'] = str_replace(
								array(
									"#TASK_EXTRA#"
								),
								array(
									$description
								),
								$message['MESSAGE']['EMAIL']
							);

							return $message;
						}
					)
				));
			}
		}

		// sonet log
		self::SendMessageToSocNet($arFields, $bSpawnedByAgent, $arChanges, $arTask, $parameters);

		if($cacheWasEnabled)
		{
			self::disableStaticCache();
		}
	}

	function SendDeleteMessage($arFields)
	{
		$cacheWasEnabled = CTaskNotifications::enableStaticCache();

		$arRecipientsIDs = CTaskNotifications::GetRecipientsIDs($arFields);
		if (sizeof($arRecipientsIDs) && (User::getId() || $arFields["CREATED_BY"]))
		{
			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if ( ! $occurAsUserId )
				$occurAsUserId = User::getId() ? User::getId() : $arFields["CREATED_BY"];

			$messageInstant = str_replace(
				"#TASK_TITLE#",
				self::formatTaskName(
					$arFields['ID'],
					$arFields['TITLE'],
					$arFields['GROUP_ID'],
					false
				),
				self::getGenderMessage($occurAsUserId, 'TASKS_TASK_DELETED_MESSAGE')
			);

			CTaskNotifications::sendMessageEx($arFields["ID"], $occurAsUserId, $arRecipientsIDs, array(
				'INSTANT' => $messageInstant,
				//'EMAIL' => $messageEmail,
				'PUSH' => CTaskNotifications::makePushMessage('TASKS_TASK_DELETED_MESSAGE', $occurAsUserId, $arFields)
			), array(
				'EVENT_DATA' => array(
					'ACTION'   => 'TASK_DELETE',
					'arFields' => $arFields
				),
			));

			/*
			CTaskNotifications::SendMessage($occurAsUserId, $arRecipientsIDs,
				$message, $arFields['ID'], null,
				array(
					'ACTION'   => 'TASK_DELETE',
					'arFields' => $arFields
				)
			);
			*/
		}

		// sonet log
		if (CModule::IncludeModule("socialnetwork"))
		{
			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "tasks",
					"SOURCE_ID" => $arFields["ID"]
				),
				false,
				false,
				array("ID")
			);
			while ($arRes = $dbRes->Fetch())
				CSocNetLog::Delete($arRes["ID"]);
		}

		if($cacheWasEnabled)
		{
			CTaskNotifications::disableStaticCache();
		}
	}

	public static function SendStatusMessage($arTask, $status, $arFields = array())
	{
		global $DB;

		$cacheWasEnabled = CTaskNotifications::enableStaticCache();

		$status = intval($status);
		if ($status > 0 && $status < 8)
		{
			$arRecipientsIDs = CTaskNotifications::GetRecipientsIDs(array_merge($arTask, $arFields));
			if (sizeof($arRecipientsIDs) && (User::getId() || $arTask["CREATED_BY"]))
			{
				$occurAsUserId = CTasksTools::getOccurAsUserId();
				if ( ! $occurAsUserId )
					$occurAsUserId = User::getId() ? User::getId() : $arTask["CREATED_BY"];

				// If task was redone
				if (($status == CTasks::STATE_NEW || $status == CTasks::STATE_PENDING) &&
					($arTask['REAL_STATUS'] == CTasks::STATE_SUPPOSEDLY_COMPLETED))
				{
					$statusMessage = CTaskNotifications::getGenderMessage($occurAsUserId, 'TASKS_TASK_STATUS_MESSAGE_REDOED');
					$messagePush = CTaskNotifications::makePushMessage('TASKS_TASK_STATUS_MESSAGE_REDOED', $occurAsUserId, $arTask);
				}
				elseif ($status == CTasks::STATE_PENDING && $arTask['REAL_STATUS'] == CTasks::STATE_DEFERRED)
				{
					$statusMessage = CTaskNotifications::getGenderMessage($occurAsUserId, 'TASKS_TASK_STATUS_MESSAGE_1');
					$messagePush = CTaskNotifications::makePushMessage('TASKS_TASK_STATUS_MESSAGE_1', $occurAsUserId, $arTask);
				}
				else
				{
					$statusMessage = CTaskNotifications::getGenderMessage($occurAsUserId, 'TASKS_TASK_STATUS_MESSAGE_'.$status);
					$messagePush = CTaskNotifications::makePushMessage('TASKS_TASK_STATUS_MESSAGE_'.$status, $occurAsUserId, $arTask);
				}

				$message = str_replace(
					"#TASK_TITLE#",
					self::formatTaskName($arTask['ID'], $arTask['TITLE'], $arTask['GROUP_ID'],false),
					$statusMessage
				);
				$message_email = str_replace(
					"#TASK_TITLE#",
					self::formatTaskName($arTask['ID'], $arTask['TITLE'], $arTask['GROUP_ID'],false),
					$statusMessage
				);

				if ($status == CTasks::STATE_DECLINED)
				{
					$message = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message);
					$message_email = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message_email);
					$messagePush = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $messagePush);
				}

				CTaskNotifications::sendMessageEx($arTask["ID"], $occurAsUserId, $arRecipientsIDs, array(
					'INSTANT' => $message,
					'EMAIL' => $message_email,
					'PUSH' => $messagePush
				), array(
					'ENTITY_CODE' => 'TASK',
					'ENTITY_OPERATION' => 'STATUS',
					'EVENT_DATA' => array(
						'ACTION'   => 'TASK_STATUS_CHANGED_MESSAGE',
						'arTask'   => $arTask,
						'arFields' => $arFields
					),
				));

				/*
				CTaskNotifications::SendMessage($occurAsUserId, $arRecipientsIDs,
					$message, $arTask["ID"], $message_email,
					array(
						'ACTION'   => 'TASK_STATUS_CHANGED_MESSAGE',
						'arTask'   => $arTask,
						'arFields' => $arFields
					)
				);
				*/
			}
		}

		// sonet log
		if (CModule::IncludeModule("socialnetwork"))
		{
			if ($status == CTasks::STATE_PENDING)
				$message = GetMessage("TASKS_SONET_TASK_STATUS_MESSAGE_" . CTasks::STATE_NEW);
			else
				$message = GetMessage("TASKS_SONET_TASK_STATUS_MESSAGE_" . $status);

			if ($status == CTasks::STATE_DECLINED)
				$message = str_replace("#TASK_DECLINE_REASON#", $arTask["DECLINE_REASON"], $message);

			$bCrmTask = self::isCrmTask($arTask);

			$arSoFields = array(
				"TITLE" => $arTask["TITLE"],
				"=LOG_UPDATE" => (
					strlen($arTask["CHANGED_DATE"]) > 0?
						(MakeTimeStamp($arTask["CHANGED_DATE"], CSite::GetDateFormat("FULL", SITE_ID)) > time()+CTimeZone::GetOffset()?
							$DB->CharToDateFunction($arTask["CHANGED_DATE"], "FULL", SITE_ID) :
							$DB->CurrentTimeFunction()) :
						$DB->CurrentTimeFunction()
				),
				"MESSAGE" => "",
				"TEXT_MESSAGE" => $message,
				"PARAMS" => serialize(
					array(
						"TYPE" => "status",
						'CHANGED_BY' => $arFields['CHANGED_BY'],
						'PREV_REAL_STATUS' => isset($arTask['REAL_STATUS']) ? $arTask['REAL_STATUS'] : false
					)
				)
			);

			$arSoFields['=LOG_DATE'] = $arSoFields['=LOG_UPDATE'];

			// All tasks posts in live feed should be from director
			if (isset($arFields['CREATED_BY']))
				$arSoFields["USER_ID"] = $arFields['CREATED_BY'];

			$loggedInUserId = false;
			if (User::getId())
				$loggedInUserId = (int) User::getId();

			$arLogFilter = self::getSonetLogFilter($arTask["ID"], $bCrmTask);

			if (empty($arLogFilter))
			{
				return (null);
			}

			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				$arLogFilter,
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID")
			);

			while ($arRes = $dbRes->Fetch())
			{
				CSocNetLog::Update($arRes['ID'], $arSoFields);

				$authorUserId = (int) $arTask['CREATED_BY'];

				// Add author to list of users that view log about task in livefeed
				// But only when some other person change task
				if ($authorUserId !== $loggedInUserId)
				{
					$authorGroupCode = 'U' . $authorUserId;

					$rsRights = CSocNetLogRights::GetList(
						array(),
						array(
							'LOG_ID'     => $arRes['ID'],
							'GROUP_CODE' => $authorGroupCode
						)
					);

					// If task's author hasn't rights yet, give them
					if ( ! ($arRights = $rsRights->fetch()) )
						CSocNetLogRights::Add($arRes["ID"], array($authorGroupCode));
				}
			}
		}

		if($cacheWasEnabled)
		{
			CTaskNotifications::disableStaticCache();
		}
	}

	############################
	# low-level action functions

	public static function sendMessageEx($taskId, $fromUser, array $toUsers, array $messages = array(), array $parameters = array())
	{
		if (!(IsModuleInstalled("im") && CModule::IncludeModule("im")))
		{
			return false;
		}

		if (!$fromUser || empty($toUsers) || (string) $messages['INSTANT'] == '')
		{
			return (false);
		}

		if(!isset($messages['EMAIL']))
		{
			$messages['EMAIL'] = $messages['INSTANT'];
		}

		$eventData = $parameters['EVENT_DATA'];
		unset($parameters['EVENT_DATA']);
		$notifyEvent = $parameters['NOTIFY_EVENT'];
		unset($parameters['NOTIFY_EVENT']);
		$callbacks = $parameters['CALLBACK'];
		unset($parameters['CALLBACK']);

		if((string) $parameters['ENTITY_CODE'] != '')
		{
			$entityCode = $parameters['ENTITY_CODE'];
			unset($parameters['ENTITY_CODE']);
		}
		else
		{
			$entityCode = 'TASK';
		}

		if((string) $parameters['ENTITY_OPERATION'] != '')
		{
			$entityOperation = $parameters['ENTITY_OPERATION'];
			unset($parameters['ENTITY_OPERATION']);
		}
		else
		{
			$entityOperation = 'ADD';
		}

		self::addToNotificationBuffer(array(
			'FROM_USER_ID' 	=> $fromUser,
			'TO_USER_IDS' 	=> $toUsers,
			'TASK_ID' 		=> intval($taskId),
			'MESSAGE' 		=> $messages,
			'EVENT_DATA' 	=> $eventData,
			'NOTIFY_EVENT' => $notifyEvent,
			'ENTITY_CODE' 	=> $entityCode,
			'ENTITY_OPERATION' 	=> $entityOperation,
			'CALLBACK' => $callbacks,
			'ADDITIONAL_DATA' => $parameters,
		));

		if(!self::$bufferize)
		{
			self::flushNotificationBuffer(false);
		}

		return true;
	}

	protected static function SendMessageToSocNet($arFields, $bSpawnedByAgent, $arChanges = null, $arTask = null, array $parameters = array())
	{
		global $DB;

		$effectiveUserId = self::getEffectiveUserId($arFields, array(), $bSpawnedByAgent, $parameters);

		if ( ! CModule::IncludeModule('socialnetwork') )
			return (null);

		$arLogFilter = array();
		$bCrmTask = false;

		if (!empty($arTask))
		{
			$bCrmTask = self::isCrmTask($arTask);
			$arLogFilter = self::getSonetLogFilter($arTask["ID"], $bCrmTask);
			if (empty($arLogFilter))
			{
				return (null);
			}
		}
		static $arCheckedUsers = array();		// users that checked for their existing
		static $cachedSiteTimeFormat = -1;

		// select "real" author

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = $effectiveUserId;

		if ($cachedSiteTimeFormat === -1)
			$cachedSiteTimeFormat = CSite::GetDateFormat('FULL', SITE_ID);

		static $cachedAllSitesIds = -1;

		if ($cachedAllSitesIds === -1)
		{
			$cachedAllSitesIds = array();

			$dbSite = CSite::GetList(
				$by = 'sort',
				$order = 'desc',
				array('ACTIVE' => 'Y')
			);

			while ($arSite = $dbSite->Fetch())
				$cachedAllSitesIds[] = $arSite['ID'];
		}

		// Check that user exists
		if ( ! in_array( (int) $arFields["CREATED_BY"], $arCheckedUsers, true) )
		{
			$rsUser = CUser::GetList(
				$by = 'ID',
				$order = 'ASC',
				array('ID' => $arFields["CREATED_BY"]),
				array('FIELDS' => array('ID'))
			);

			if ( ! ($arUser = $rsUser->Fetch()) )
				return (false);

			$arCheckedUsers[] = (int) $arFields["CREATED_BY"];
		}

		if (is_array($arChanges))
		{
			if (
				count($arChanges) == 0
				&& !empty($arLogFilter)
			)
			{
				$rsSocNetLogItems = CSocNetLog::GetList(
					array("ID" => "DESC"),
					$arLogFilter,
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID")
				);

				while ($arRes = $rsSocNetLogItems->Fetch())
				{
					$authorUserId = false;
					if (isset($arFields['CREATED_BY']))
						$authorUserId = (int) $arFields['CREATED_BY'];
					elseif (isset($arTask['CREATED_BY']))
						$authorUserId = (int) $arTask['CREATED_BY'];

					// Add author to list of users that view log about task in livefeed
					// But only when some other person change task
					// or if added FORUM_TOPIC_ID
					if (
						($authorUserId !== $effectiveUserId)
						|| (
							($arTask['FORUM_TOPIC_ID'] == 0)
							&& isset($arFields['FORUM_TOPIC_ID'])
							&& ($arFields['FORUM_TOPIC_ID'] > 0)
						)
					)
					{
						$authorGroupCode = 'U' . $authorUserId;

						$rsRights = CSocNetLogRights::GetList(
							array(),
							array(
								'LOG_ID'     => $arRes['ID'],
								'GROUP_CODE' => $authorGroupCode
							)
						);

						// If task's author hasn't rights yet, give them
						if ( ! ($arRights = $rsRights->fetch()) )
							CSocNetLogRights::Add($arRes["ID"], array($authorGroupCode));
					}
				}

				return (null);
			}
			elseif ((count($arChanges) == 1) && isset($arChanges['STATUS']))
			{
				return (null);	// if only status changes - don't send message, because it will be sent by SendStatusMessage()
			}
		}

		if ($bSpawnedByAgent === 'Y')
			$bSpawnedByAgent = true;
		elseif ($bSpawnedByAgent === 'N')
			$bSpawnedByAgent = false;

		if ( ! is_bool($bSpawnedByAgent) )
			return (false);

		$taskId = false;
		if (is_array($arFields) && isset($arFields['ID']) && ($arFields['ID'] > 0))
			$taskId = $arFields['ID'];
		elseif (is_array($arTask) && isset($arTask['ID']) && ($arTask['ID'] > 0))
			$taskId = $arTask['ID'];

		// We will mark this to false, if we send update message and log item already exists
		$bSocNetAddNewItem = true;

		$logDate = $DB->CurrentTimeFunction();
		$curTimeTimestamp = time() + CTimeZone::GetOffset();

		if (!$bCrmTask)
		{
			$arSoFields = array(
				'EVENT_ID' => 'tasks',
				'TITLE' => $arFields['TITLE'],
				'MESSAGE' => '',
				'MODULE_ID' => 'tasks'
			);
		}
		else
		{
			$arSoFields = array();
		}

		// If changes and task data given => we are prepare "update" message,
		// or "add" message otherwise
		if (is_array($arChanges) && is_array($arTask))
		{	// Prepare "update" message here
			if (strlen($arFields["CHANGED_DATE"]) > 0)
			{
				$createdDateTimestamp = MakeTimeStamp(
					$arFields["CHANGED_DATE"],
					$cachedSiteTimeFormat
				);

				if ($createdDateTimestamp > $curTimeTimestamp)
				{
					$logDate = $DB->CharToDateFunction(
						$arFields["CHANGED_DATE"],
						"FULL",
						SITE_ID
					);
				}
			}

			$arChangesFields = array_keys($arChanges);
			$arSoFields['TEXT_MESSAGE'] = str_replace(
				'#CHANGES#',
				implode(
					', ',
					CTaskNotifications::__Fields2Names($arChangesFields)
				),
				GetMessage('TASKS_SONET_TASK_CHANGED_MESSAGE')
			);

			if (!$bCrmTask)
			{
				if (
					(($arFields["GROUP_ID"] === NULL) && $arTask['GROUP_ID'])	// If tasks has group and it not deleted
					|| ($arFields['GROUP_ID'])	// Or new group_id set
				)
				{
					$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_GROUP;
					$arSoFields["ENTITY_ID"] = ($arFields["GROUP_ID"] ? $arFields["GROUP_ID"] : $arTask['GROUP_ID']);
				}
				else
				{
					$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_USER;
					$arSoFields["ENTITY_ID"] = ($arFields["CREATED_BY"] ? $arFields["CREATED_BY"] : $arTask["CREATED_BY"]);
				}
			}

			$arSoFields['PARAMS'] = serialize(
				array(
					'TYPE' => 'modify',
					'CHANGED_FIELDS' => $arChangesFields,
					'CREATED_BY'  => ($arFields["CREATED_BY"] ? $arFields["CREATED_BY"] : $arTask["CREATED_BY"]),
					'CHANGED_BY' => ($occurAsUserId ? $occurAsUserId : $arFields['CHANGED_BY']),
					'PREV_REAL_STATUS' => isset($arTask['REAL_STATUS']) ? $arTask['REAL_STATUS'] : false
				)
			);

			if (!empty($arLogFilter))
			{
				// Determine, does item exists in sonet log
				$rsSocNetLogItems = CSocNetLog::GetList(
					array("ID" => "DESC"),
					$arLogFilter,
					false,
					false,
					array("ID", "ENTITY_TYPE", "ENTITY_ID")
				);

				if ($rsSocNetLogItems->Fetch())
				{
					$bSocNetAddNewItem = false;		// item already exists, update it, not create.
				}
			}
		}
		else	// Prepare "add" message here
		{
			if (strlen($arFields["CREATED_DATE"]) > 0)
			{
				$createdDateTimestamp = MakeTimeStamp(
					$arFields["CREATED_DATE"],
					$cachedSiteTimeFormat
				);

				if ($createdDateTimestamp > $curTimeTimestamp)
				{
					$logDate = $DB->CharToDateFunction(
						$arFields["CREATED_DATE"],
						"FULL",
						SITE_ID
					);
				}
			}

			$arSoFields['TEXT_MESSAGE'] = GetMessage('TASKS_SONET_NEW_TASK_MESSAGE');

			if($arFields["GROUP_ID"])
			{
				$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_GROUP;
				$arSoFields["ENTITY_ID"] = $arFields["GROUP_ID"];
			}
			else
			{
				$arSoFields["ENTITY_TYPE"] = SONET_ENTITY_USER;
				$arSoFields["ENTITY_ID"] = $arFields["CREATED_BY"];
			}

			$arParamsLog = array(
				'TYPE' => 'create',
				'CREATED_BY' => ($arFields["CREATED_BY"] ? $arFields["CREATED_BY"] : $arTask["CREATED_BY"]),
				'PREV_REAL_STATUS' => isset($arTask['REAL_STATUS']) ? $arTask['REAL_STATUS'] : false
			);

			if ($occurAsUserId)
				$arParamsLog["CREATED_BY"] = $occurAsUserId;

			$arSoFields['PARAMS'] = serialize($arParamsLog);
		}

		// rating entity id (ilike)
		$arSoFields["RATING_ENTITY_ID"] =  $taskId;
		$arSoFields["RATING_TYPE_ID"] = "TASK";

		// Do we need add new item to socnet?
		// We adds new item, if it is not exists.
		$logID = false;

		if (
			IsModuleInstalled("webdav")
			|| IsModuleInstalled("disk")
		)
		{
			$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("TASKS_TASK", "UF_TASK_WEBDAV_FILES", $taskId, LANGUAGE_ID);
			if ($ufDocID)
			{
				$arSoFields["UF_SONET_LOG_DOC"] = $ufDocID;
			}
		}

		if ($bSocNetAddNewItem)
		{
			$arSoFields['=LOG_DATE']       = $logDate;
			$arSoFields['CALLBACK_FUNC']   = false;
			$arSoFields['SOURCE_ID']       = $taskId;
			$arSoFields['ENABLE_COMMENTS'] = 'Y';
			$arSoFields['URL']             = ''; // url is user-specific, cant keep in database
			$arSoFields['USER_ID']         = $arFields['CREATED_BY'];
			$arSoFields['TITLE_TEMPLATE']  = '#TITLE#';

			// Set all sites because any user from any site may be
			// added to task in future. For example, new auditor, etc.
			$arSoFields['SITE_ID'] = $cachedAllSitesIds;

			$logID = CSocNetLog::Add($arSoFields, false);
			if (intval($logID) > 0)
			{
				$logFields = array(
					"TMP_ID" => $logID,
					"TAG" => array()
				);

				$rsTags = CTaskTags::getList(
					array(),
					array('TASK_ID' => $taskId)
				);

				while ($arTag = $rsTags->fetch())
				{
					$logFields["TAG"][] = $arTag['NAME'];
				}

				CSocNetLog::Update($logID, $logFields);
				$arTaskParticipant = CTaskNotifications::GetRecipientsIDs(
					$arFields,
					false		// don't exclude current user
				);

				// Exclude author by default
				$arLogCanViewedBy = (
					!self::$sonetLogNotifyAuthor
						? array_diff($arTaskParticipant, array($arFields['CREATED_BY']))
						: $arTaskParticipant
				);
				$arRights = CTaskNotifications::__UserIDs2Rights($arLogCanViewedBy);

				if (isset($arFields['GROUP_ID']))
				{
					$arRights = array_merge(
						$arRights,
						self::prepareRightsCodesForViewInGroupLiveFeed($logID, $arFields['GROUP_ID'])
					);
				}

				CSocNetLogRights::Add($logID, $arRights);
				CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT", $logID);
			}
		}
		elseif (!empty($arLogFilter))	// Update existing log item
		{
			$arSoFields['=LOG_DATE']   = $logDate;
			$arSoFields['=LOG_UPDATE'] = $logDate;

			// All tasks posts in live feed should be from director
			if (isset($arFields['CREATED_BY']))
				$arSoFields['USER_ID'] = $arFields['CREATED_BY'];
			elseif (isset($arTask['CREATED_BY']))
				$arSoFields['USER_ID'] = $arTask['CREATED_BY'];
			elseif ($occurAsUserId)
				$arSoFields['USER_ID'] = $occurAsUserId;
			else
				unset ($arSoFields['USER_ID']);

			$rsSocNetLogItems = CSocNetLog::GetList(
				array("ID" => "DESC"),
				$arLogFilter,
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID")
			);

			while ($arRes = $rsSocNetLogItems->Fetch())
			{
				$arSoFields["TAG"] = array();
				$rsTags = CTaskTags::getList(
					array(),
					array('TASK_ID' => $taskId)
				);

				while ($arTag = $rsTags->fetch())
				{
					$arSoFields["TAG"][] = $arTag['NAME'];
				}

				CSocNetLog::Update($arRes["ID"], $arSoFields);

				self::setSonetLogRights(array(
					'LOG_ID' => $arRes['ID'],
					'EFFECTIVE_USER_ID' => $effectiveUserId
				), $arFields, $arTask);
			}
		}

		return ($logID);
	}

	public static function isCrmTask(array $task)
	{
		return (
			isset($task)
			&& isset($task["UF_CRM_TASK"])
			&& (
				(
					is_array($task["UF_CRM_TASK"])
					&& (
						isset($task["UF_CRM_TASK"][0])
						&& strlen($task["UF_CRM_TASK"][0]) > 0
					)
				)
				||
				(
					!is_array($task["UF_CRM_TASK"])
					&& strlen($task["UF_CRM_TASK"]) > 0
				)
			)
		);
	}

	public static function getSonetLogFilter($taskId, $crm)
	{
		$filter = array();

		if (!$crm)
		{
			$filter = array(
				"EVENT_ID" => "tasks",
				"SOURCE_ID" => $taskId
			);
		}
		elseif (\Bitrix\Main\Loader::includeModule("crm"))
		{
			$res = CCrmActivity::getList(
				array(),
				array(
					'TYPE_ID' => CCrmActivityType::Task,
					'ASSOCIATED_ENTITY_ID' => $taskId,
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				false,
				array('ID')
			);

			if ($activity = $res->fetch())
			{
				$filter = array(
					"EVENT_ID" => "crm_activity_add",
					"ENTITY_ID" => $activity
				);
			}
		}

		return $filter;
	}

	public static function setSonetLogRights(array $params, array $fields, array $task)
	{
		if (
			!isset($params['LOG_ID'])
			|| intval($params['LOG_ID']) <= 0
			|| !isset($params['EFFECTIVE_USER_ID'])
			|| intval($params['EFFECTIVE_USER_ID']) <= 0
		)
		{
			return;
		}

		$authorMustBeExcluded = false;

		$taskParticipantsList = CTaskNotifications::getRecipientsIDs(
			$fields,	// Only new tasks' participiants should view log event, fixed due to http://jabber.bx/view.php?id=34504
			false,		// don't exclude current user
			true		// exclude additional recipients (because there are previous members of task)
		);

		$authorUserId = false;
		if (isset($fields['CREATED_BY']))
			$authorUserId = (int) $fields['CREATED_BY'];
		elseif (isset($task['CREATED_BY']))
			$authorUserId = (int) $task['CREATED_BY'];

		// Get current rights
		$res = CSocNetLogRights::getList(
			array(),
			array('LOG_ID' => intval($params['LOG_ID']))
		);

		$currentRightsList = array();
		while ($right = $res->fetch())
		{
			$currentRightsList[] = $right['GROUP_CODE'];
		}

		$effectiveUserId = (int) $params['EFFECTIVE_USER_ID'];

		// If author changes the task and author doesn't have
		// access to task yet, don't give access to him.
		if ($authorUserId === $effectiveUserId)
		{
			$authorGroupCode = 'U' . $authorUserId;

			// If task's author hasn't rights yet, still exclude him
			if ( ! in_array($authorGroupCode, $currentRightsList, true) )
				$authorMustBeExcluded = true;
		}

		$logCanViewedByList = ($authorMustBeExcluded ? array_diff($taskParticipantsList, array($authorUserId)) : $taskParticipantsList);

		$newRightsList = CTaskNotifications::__UserIDs2Rights($logCanViewedByList);

		$groupChanged = (
			isset($fields['GROUP_ID'], $task['GROUP_ID'])
			&& ($fields['GROUP_ID'])
			&& ($fields['GROUP_ID'] != $task['GROUP_ID'])
		);

		// If rights really changed, update them
		if (
			count(array_diff($currentRightsList, $newRightsList))
			|| count(array_diff($newRightsList, $currentRightsList))
			|| $groupChanged
		)
		{
			if (isset($fields['GROUP_ID']))
			{
				$newRightsList = array_merge(
					$newRightsList,
					self::prepareRightsCodesForViewInGroupLiveFeed($params['LOG_ID'], $fields['GROUP_ID'])
				);
			}
			elseif (isset($task['GROUP_ID']))
			{
				$newRightsList = array_merge(
					$newRightsList,
					self::prepareRightsCodesForViewInGroupLiveFeed($params['LOG_ID'], $task['GROUP_ID'])
				);
			}

			CSocNetLogRights::deleteByLogID(intval($params['LOG_ID']));
			CSocNetLogRights::add($params['LOG_ID'], $newRightsList);
		}
	}

	########################
	# throttle functions

	public static function throttleRelease()
	{
		$items = ThrottleTable::getUpdateMessages();

		if(is_array($items) && !empty($items))
		{
			$cacheAFWasDisabled = \CTasks::disableCacheAutoClear();
			$notifADWasDisabled = \CTaskNotifications::disableAutoDeliver();

			// this function may be called on agent, so DO NOT relay on global user as an author, use field AUTHOR_ID instead
			foreach($items as $item)
			{
				self::SendUpdateMessage($item['STATE_LAST'], $item['STATE_ORIG'], false, array(
					'AUTHOR_ID' => $item['AUTHOR_ID'],
					'IGNORE_AUTHOR' => isset($item['IGNORE_RECEPIENTS'][$item['AUTHOR_ID']])
				));
			}

			if($notifADWasDisabled)
			{
				\CTaskNotifications::enableAutoDeliver();
			}
			if($cacheAFWasDisabled)
			{
				\CTasks::enableCacheAutoClear();
			}
		}
	}

	########################
	# buffer-deal functions

	protected static function addToNotificationBuffer(array $message)
	{
		if(self::$suppressIM) // im notifications disabled
		{
			return;
		}

		self::$buffer[] = $message;
	}

	private static function flushNotificationBuffer($doGrouping = true)
	{
		if(is_array(self::$buffer) && !empty(self::$buffer))
		{
			// get all users
			$users = array();
			foreach(self::$buffer as $i => $message)
			{
				if(is_array($message['TO_USER_IDS']))
				{
					foreach ($message['TO_USER_IDS'] as $userId)
					{
						$users[$userId] = true;
					}
				}
			}

			$users = self::getUsers(array_keys($users));

			$sites = \Bitrix\Tasks\Util\Site::getPair();
			$byUser = array();
			$mailed = array();
			foreach(self::$buffer as $i => $message)
			{
				if(is_array($message['TO_USER_IDS']))
				{
					foreach($message['TO_USER_IDS'] as $userId)
					{
						if(!isset($users[$userId])) // no user found for that id
						{
							continue;
						}

						// determine notify event here, if it was not given
						if((string) $message['NOTIFY_EVENT'] == '')
						{
							$notifyEvent = 'manage';

							if ($message['ADDITIONAL_DATA']['TASK_ASSIGNED_TO'] !== null)
							{
								if ($userId == $message['ADDITIONAL_DATA']['TASK_ASSIGNED_TO'])
								{
									$notifyEvent = 'task_assigned';
								}
							}

							$message['NOTIFY_EVENT'] = $notifyEvent;
						}

						if(\Bitrix\Tasks\Integration\Mail\User::isEmail($users[$userId])) // must send message to email users separately
						{
							if(!isset($mailed[$i]))
							{
								$mMessage = $message;
								$mMessage['TO_USER_IDS'] = array();

								$mailed[$i] = $mMessage;
							}
							$mailed[$i]['TO_USER_IDS'][] = $userId;
						}
						else
						{
							$byUser[$userId][$message['TASK_ID']] = $message;
						}
					}
				}
			}

			// send regular messages
			foreach($byUser as $userId => $messages)
			{
				$unGroupped = array();

				if(
					count($messages) > 1 && $doGrouping
				) // new way
				{
					// send for each action type, notification type and author separately
					$deepGrouping = array();

					foreach($messages as $taskId => $message)
					{
						// we do not group entities that differ from 'TASK' and NOTIFY_EVENTS that differ from 'manage'
						if($message['ENTITY_CODE'] != 'TASK' || $message['NOTIFY_EVENT'] != 'manage')
						{
							$unGroupped[$taskId] = $message;
							continue;
						}

						// if type is unknown, let it be "update"
						$type = (string) $message['EVENT_DATA']['ACTION'] !== '' ? $message['EVENT_DATA']['ACTION'] : 'TASK_UPDATE';
						if($type != 'TASK_ADD' && $type != 'TASK_UPDATE' && $type != 'TASK_DELETE' && $type != 'TASK_STATUS_CHANGED_MESSAGE')
						{
							// unknown action type. nothing to report about
							continue;
						}

						$fromUserId = $message['FROM_USER_ID'];
						if((string) $fromUserId == '') // empty author is not allowed
						{
							continue;
						}

						$deepGrouping[$type][$fromUserId][$message['NOTIFY_EVENT']][] = $taskId;
					}

					if(!empty($deepGrouping))
					{
						foreach($deepGrouping as $type => $byAuthor)
						{
							foreach($byAuthor as $authorId => $byEvent)
							{
								foreach($byEvent as $event => $taskIds)
								{
									$path = CTaskNotifications::getNotificationPathMultiple($users[$userId], $taskIds, true);

									$instantTemplate = self::getGenderMessage($authorId, 'TASKS_TASKS_'.$type.'_MESSAGE');
									$emailTemplate = self::getGenderMessage($authorId, 'TASKS_TASKS_'.$type.'_MESSAGE_EMAIL');
									$pushTemplate = self::getGenderMessage($authorId, 'TASKS_TASKS_'.$type.'_MESSAGE_PUSH');

									$instant = self::placeLinkAnchor($instantTemplate, $path, 'BBCODE');
									$email = self::placeLinkAnchor($emailTemplate, $path, 'EMAIL');
									$push = self::placeLinkAnchor($pushTemplate, $path, 'NONE');
									$push = self::placeUserName($push, $authorId);

									$tag = static::formatImNotificationTag($userId, $taskIds, 'TASKS');

									$arMessageFields = array(
										"TO_USER_ID" => $userId,
										"FROM_USER_ID" => $authorId,
										"NOTIFY_TYPE" => IM_NOTIFY_FROM,
										"NOTIFY_MODULE" => 'tasks',
										"NOTIFY_EVENT" => $event,
										"NOTIFY_MESSAGE" => $instant,
										"NOTIFY_MESSAGE_OUT" => $email,
										"NOTIFY_TAG" => $tag,

										// push
										"PUSH_MESSAGE" => substr($push, 0, self::PUSH_MESSAGE_MAX_LENGTH),
									);

									\Bitrix\Tasks\Integration\Im::notifyAdd($arMessageFields);
								}
							}
						}
					}
				}
				else // old way
				{
					$unGroupped = $messages;
				}

				// send each message separately
				foreach($unGroupped as $taskId => $message)
				{
					$pathToTask = self::getNotificationPath($users[$userId], $taskId, true, $sites);
					$pathToTask = self::addParameters($pathToTask, $message['ADDITIONAL_DATA']['TASK_URL']);

					$message['ENTITY_CODE'] = ToUpper($message['ENTITY_CODE']);

					// replace #TASK_URL_BEGIN# placeholder
					$message['MESSAGE']['INSTANT'] = self::placeLinkAnchor($message['MESSAGE']['INSTANT'], $pathToTask, 'BBCODE');
					$message['MESSAGE']['EMAIL'] = self::placeLinkAnchor($message['MESSAGE']['EMAIL'], $pathToTask, 'EMAIL');
					if((string) $message['MESSAGE']['PUSH'] != '')
					{
						$message['MESSAGE']['PUSH'] = self::placeLinkAnchor($message['MESSAGE']['PUSH'], $pathToTask, 'NONE');
					}

					// replace #TASK_TITLE# placeholder, if any
					if(is_array($message['ADDITIONAL_DATA']['TASK_DATA']))
					{
						$taskData = $message['ADDITIONAL_DATA']['TASK_DATA'];
						$taskTitle = CTaskNotifications::formatTaskName($taskData["ID"], $taskData["TITLE"], $taskData["GROUP_ID"]);

						$message['MESSAGE']['INSTANT'] = str_replace('#TASK_TITLE#', $taskTitle, $message['MESSAGE']['INSTANT']);
						$message['MESSAGE']['EMAIL'] = str_replace('#TASK_TITLE#', strip_tags($taskTitle), $message['MESSAGE']['INSTANT']);
						if((string) $message['MESSAGE']['PUSH'] != '')
						{
							$message['MESSAGE']['PUSH'] = str_replace('#TASK_TITLE#', $taskTitle, $message['MESSAGE']['PUSH']);
						}
					}

					$message['TO_USER_IDS'] = array($userId);

					// message callbacks here
					if(is_callable($message['CALLBACK']['BEFORE_SEND']))
					{
						$message = call_user_func_array($message['CALLBACK']['BEFORE_SEND'], array($message));
					}

					// event process here
					if(!static::fireMessageEvent($message))
					{
						continue;
					}

					$userId = $message['TO_USER_IDS'][0]; // it may have changed on event

					// make IM parameters

					// todo make tag format more suitable
					$entityIds = array();
					if('COMMENT' == $message['ENTITY_CODE'])
					{
						$entityIds = array(intval($message['EVENT_DATA']['MESSAGE_ID']));
					}
					$tag = static::formatImNotificationTag($userId, array($taskId), $message['ENTITY_CODE'], $entityIds);
					$type = (string) $message['EVENT_DATA']['ACTION'] !== '' ? $message['EVENT_DATA']['ACTION'] : 'TASK_UPDATE';

					$arMessageFields = array(
						"TO_USER_ID" => $userId,
						"FROM_USER_ID" => $message['FROM_USER_ID'],
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "tasks",
						"NOTIFY_EVENT" => $message['NOTIFY_EVENT'],
						"NOTIFY_TAG" => $tag,
						"NOTIFY_SUB_TAG" => $tag."|".$type,
						"NOTIFY_MESSAGE" => $message['MESSAGE']['INSTANT'],
						"NOTIFY_MESSAGE_OUT" => $message['MESSAGE']['EMAIL'],
						"PARAMS" => array(
							"taskId" => $message['TASK_ID'],
							"operation" => $message['ENTITY_OPERATION']
						)
					);

					if((string) $message['ADDITIONAL_DATA']['NOTIFY_ANSWER'])
					{
						// enabling notify answer for desktop
						$arMessageFields['NOTIFY_ANSWER'] = 'Y';
					}

					if((string) $message['MESSAGE']['PUSH'] != '')
					{
						// add push message
						$arMessageFields['PUSH_MESSAGE'] = self::placeLinkAnchor($message['MESSAGE']['PUSH'], $pathToTask, 'NONE');

						// user should be able to open the task window to see the changes ...
						// see /mobile/install/components/bitrix/mobile.rtc/templates/.default/script.js for handling details
						$arMessageFields['PUSH_PARAMS'] = array(
							'ACTION' => 'tasks',
							'TAG' => $tag,
						);

						if((string) $message['ADDITIONAL_DATA']['NOTIFY_ANSWER'])
						{
							// ... and open an answer dialog in mobile
							$arMessageFields['PUSH_PARAMS'] = array_merge($arMessageFields['PUSH_PARAMS'], array(
								'CATEGORY' => 'ANSWER',
								'URL' => SITE_DIR.'mobile/ajax.php?mobile_action=task_answer',
								'PARAMS' => Array(
									'TASK_ID' => $taskId
								)
							));
						}
					}

					\Bitrix\Tasks\Integration\IM::notifyAdd($arMessageFields);
				}
			}

			// send email messages
			foreach($mailed as $message)
			{
				self::notifyByMail($message, $sites["INTRANET"]);
			}
		}

		self::$buffer = array();
	}

	protected static function fireMessageEvent(array &$message)
	{
		if(!is_array($message['EVENT_DATA']))
		{
			$message['EVENT_DATA'] = array();
		}

		$message['EVENT_DATA']['fromUserID']      =& $message['FROM_USER_ID'];
		$message['EVENT_DATA']['arRecipientsIDs'] =& $message['TO_USER_IDS'];
		$message['EVENT_DATA']['message']         =& $message['MESSAGE']['INSTANT'];
		$message['EVENT_DATA']['message_email']   =& $message['MESSAGE']['EMAIL'];
		$message['EVENT_DATA']['message_push']    =& $message['MESSAGE']['PUSH'];

		$skipMessage = false;
		foreach(GetModuleEvents('tasks', 'OnBeforeTaskNotificationSend', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($message['EVENT_DATA'])) === false)
			{
				$skipMessage = true;
				break;
			}
		}

		return !$skipMessage;
	}

	########################
	# event handlers

	// this is for making notifications work when using "ilike"
	// see CRatings::AddRatingVote() and CIMEvent::OnAddRatingVote() for the context of usage
	public static function OnGetRatingContentOwner($params)
	{
		if(intval($params['ENTITY_ID']) && $params['ENTITY_TYPE_ID'] == 'TASK')
		{
			list($oTaskItems, $rsData) = CTaskItem::fetchList(CTasksTools::GetCommanderInChief(), array(), array('=ID' => $params['ENTITY_ID']), array(), array('ID', 'CREATED_BY'));
			unset($rsData);

			if($oTaskItems[0] instanceof CTaskItem)
			{
				$data = $oTaskItems[0]->getData(false);
				if(intval($data['CREATED_BY']))
					return intval($data['CREATED_BY']);
			}
		}

		return false;
	}

	// this is for replacing the default message when user presses "ilike" button
	// see CIMEvent::GetMessageRatingVote() for the context of usage
	public static function OnGetMessageRatingVote(&$params, &$forEmail)
	{
		static $intranetInstalled = null;

		if ($intranetInstalled === null)
		{
			$intranetInstalled = \Bitrix\Main\ModuleManager::isModuleInstalled('intranet');
		}

		if($params['ENTITY_TYPE_ID'] == 'TASK' && !$forEmail)
		{
			$type = (
				$params['VALUE'] >= 0
					? ($intranetInstalled ? 'REACT' : 'LIKE')
					: 'DISLIKE'
			);

			$genderSuffix = '';
			if (
				$type == 'REACT'
				&& !empty($params['USER_ID'])
				&& intval($params['USER_ID']) > 0
			)
			{
				$res = \Bitrix\Main\UserTable::getList(array(
					'filter' => array(
						'ID' => intval($params['USER_ID'])
					),
					'select' => array('PERSONAL_GENDER')
				));
				if ($userFields = $res->fetch())
				{
					switch ($userFields['PERSONAL_GENDER'])
					{
						case "M":
						case "F":
							$genderSuffix = '_'.$userFields['PERSONAL_GENDER'];
							break;
						default:
							$genderSuffix = '';
					}
				}
			}

			$langMessage = GetMessage('TASKS_NOTIFICATIONS_I_'.$type.'_TASK'.$genderSuffix);
			if((string) $langMessage != '')
			{
				$taskTitle = self::formatTaskName($params['ENTITY_ID'], $params['ENTITY_TITLE']);

				$params['MESSAGE'] = str_replace(
					'#LINK#',
					(string) $params['ENTITY_LINK'] != '' ? '<a href="'.$params['ENTITY_LINK'].'" class="bx-notifier-item-action">'.$taskTitle.'</a>': '<i>'.$taskTitle.'</i>', $langMessage);
			}

			if ($intranetInstalled)
			{
				$params['MESSAGE'] .= "\n".str_replace("#REACTION#", \CRatingsComponentsMain::getRatingLikeMessage(!empty($params['REACTION']) ? $params['REACTION'] : ''), Bitrix\Main\Localization\Loc::getMessage("TASKS_NOTIFICATIONS_I_REACTION"));
			}
		}
	}

	// this is for processing action "answer" when getting comment notification
	public static function OnAnswerNotify($module, $tag, $text, $arNotify)
	{
		if ($module == "tasks" && (string) $text != '')
		{
			$tagData = self::parseImNotificationTag($tag);

			if($tagData['ENTITY'] == 'COMMENT')
			{
				if(!CModule::IncludeModule('forum') || !$GLOBALS['USER'] || !method_exists($GLOBALS['USER'], 'GetId'))
				{
					throw new \Bitrix\Main\SystemException(); // this will break json and make notify window glow red :)
				}
				else
				{
					try
					{
						if (self::addAnswer($tagData['TASK_ID'], $text))
						{
							return Loc::getMessage('TASKS_IM_ANSWER_SUCCESS');
						}
					}
					catch(\TasksException $e)
					{
						$message = unserialize($e->getMessage());

						return array(
							'result' => false,
							'text' => $message[0]
						);
					}
				}
			}
		}
	}

	public static function addAnswer($taskId, $text)
	{
		$task = new CTaskItem($taskId, $GLOBALS['USER']->GetId());

		$commentId = CTaskCommentItem::add($task, array(
			'POST_MESSAGE' => $text
		));

		if (
			$commentId > 0
			&& \Bitrix\Main\Loader::includeModule('socialnetwork')
		)
		{
			$res = \Bitrix\Socialnetwork\LogCommentTable::getList(array(
				'filter' => array(
					'EVENT_ID' => array('crm_activity_add_comment', 'tasks_comment'),
					'SOURCE_ID' => $commentId
				),
				'select' => array('ID', 'LOG_ID')
			));
			if ($logCommentFields = $res->fetch())
			{
				$res = \Bitrix\Socialnetwork\LogTable::getList(array(
					'filter' => array(
						"=ID" => $logCommentFields['LOG_ID']
					),
					'select' => array("ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "SOURCE_ID")
				));
				if ($logEntry = $res->fetch())
				{
					$logCommentFields = \Bitrix\Socialnetwork\Item\LogComment::getById($logCommentFields['ID'])->getFields();

					$res = \CSite::getByID(SITE_ID);
					$site = $res->fetch();

					$userPage = \Bitrix\Main\Config\Option::get('socialnetwork', 'user_page', $site['DIR'].'company/personal/');
					$userPath = $userPage.'user/'.$logEntry['USER_ID'].'/';

					\Bitrix\Socialnetwork\ComponentHelper::addLiveComment(
						$logCommentFields,
						$logEntry,
						\CSocNetLogTools::findLogCommentEventByLogEventID($logEntry["EVENT_ID"]),
						array(
							"ACTION" => 'ADD',
							"SOURCE_ID" => $logCommentFields['SOURCE_ID'],
							"TIME_FORMAT" => \CSite::getTimeFormat(),
							"PATH_TO_USER" => $userPath,
							"NAME_TEMPLATE" => \CSite::getNameFormat(null, SITE_ID),
							"SHOW_LOGIN" => "N",
							"AVATAR_SIZE" => 100,
							"LANGUAGE_ID" => $site["LANGUAGE_ID"],
							"SITE_ID" => SITE_ID,
							"PULL" => "Y",
						)
					);
				}
			}
		}

		return $commentId;
	}

	########################
	# formatters

	private static function formatTimeHHMM($in, $bDataInSeconds = false)
	{
		if ($in === NULL)
			return '';

		if ($bDataInSeconds)
			$minutes = (int) round($in / 60, 0);

		$hours = (int) ($minutes / 60);

		if ($minutes < 60)
		{
			$duration = $minutes . ' ' . CTasksTools::getMessagePlural(
					$minutes,
					'TASKS_TASK_DURATION_MINUTES'
				);
		}
		elseif ($minutesInResid = $minutes % 60)
		{
			$duration = $hours
				. ' '
				. CTasksTools::getMessagePlural(
					$hours,
					'TASKS_TASK_DURATION_HOURS'
				)
				. ' '
				. (int) $minutesInResid
				. ' '
				. CTasksTools::getMessagePlural(
					(int) $minutesInResid,
					'TASKS_TASK_DURATION_MINUTES'
				);
		}
		else
		{
			$duration = $hours . ' ' . CTasksTools::getMessagePlural(
					$hours,
					'TASKS_TASK_DURATION_HOURS'
				);
		}

		if ($bDataInSeconds && ($in < 3600))
		{
			if ($secondsInResid = $in % 60)
			{
				$duration .= ' ' . (int) $secondsInResid
					. ' '
					. CTasksTools::getMessagePlural(
						(int) $secondsInResid,
						'TASKS_TASK_DURATION_SECONDS'
					);
			}
		}

		return ($duration);
	}

	/**
	 * @param $arTask
	 * @param string $message
	 * @param string $message_24_1
	 * @param string $message_24_2
	 * @param string $changes_24
	 * @param string $nameTemplate
	 * @return string
	 *
	 * @deprecated
	 */
	public static function formatTask4Log($arTask, $message = '', $message_24_1 = '', $message_24_2 = '', $changes_24 = '', $nameTemplate = '')
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.task.livefeed',
			'',
			array(
				'TASK' => $arTask,
				'MESSAGE' => $message,
				'MESSAGE_24_1' => $message_24_1,
				'MESSAGE_24_2' => $message_24_2,
				'CHANGES_24' => $changes_24,
				'NAME_TEMPLATE'	=> $nameTemplate
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * @param $arFields
	 * @param $arParams
	 * @return array
	 * @deprecated
	 */
	public static function formatTask4SocialNetwork($arFields, $arParams)
	{
		return \Bitrix\Tasks\Integration\SocialNetwork\UI\Task::formatFeedEntry($arFields, $arParams);
	}

	/**
	 * @param $taskId
	 * @param $title
	 * @param int $groupId
	 * @param bool $bUrl
	 * @return string
	 *
	 * @access private
	 */
	private static function formatTaskName($taskId, $title, $groupId = 0, $bUrl = false)
	{
		$name = '[#' . $taskId . '] ';

		if ($bUrl)
			$name .= '[URL=#PATH_TO_TASK#]';

		$name .= $title;

		if ($bUrl)
			$name .= '[/URL]';

		if ($groupId && CModule::IncludeModule('socialnetwork'))
		{
			$arGroup = self::getSocNetGroup($groupId);

			if (is_string($arGroup['NAME']) && ($arGroup['NAME'] !== ''))
				$name .= ' (' . GetMessage('TASKS_NOTIFICATIONS_IN_GROUP') . ' ' . $arGroup['NAME'] . ')';
		}

		return ($name);
	}

	private static function formatImNotificationTag($userId, array $taskIds, $entityCode, array $entityIds = array())
	{
		$tag = "TASKS|".$entityCode."|".implode(':', $taskIds)."|".intval($userId);

		if(!empty($entityIds))
		{
			$tag .= '|'.implode(':', $entityIds);
		}

		return $tag;
	}

	private static function parseImNotificationTag($tag)
	{
		list($module, $entity, $id, $userId) = explode('|', $tag);

		return array(
			'ENTITY' => $entity,
			'TASK_ID' => $id,
		);
	}

	private static function makePushMessage($messageCode, $userId, array $taskData)
	{
		$messageCode = self::getGenderMessage($userId, $messageCode.'_PUSH');
		$user = self::getUser($userId);
		$taskName = self::formatTaskName($taskData['ID'], $taskData['TITLE'], $taskData['GROUP_ID'], false);

		return CTaskNotifications::cropMessage($messageCode, array(
			'USER_NAME' => CUser::FormatName(CSite::GetNameFormat(false), $user),
			'TASK_TITLE' => $taskName
		), CTaskNotifications::PUSH_MESSAGE_MAX_LENGTH);
	}

	public static function getGenderMessage($userId, $messageCode)
	{
		$user = CTaskNotifications::getUser($userId);

		if($user['PERSONAL_GENDER'] == 'M' || $user['PERSONAL_GENDER'] == 'F')
		{
			$message = GetMessage($messageCode . '_' . $user['PERSONAL_GENDER']);

			if((string) $message == '') // no gender message?
			{
				$message = GetMessage($messageCode.'_N');
			}
		}
		else
		{
			// no gender? try to get neutral
			$message = GetMessage($messageCode.'_N');
			if((string) $message == '') // no neutral message? fall back to Male gender
			{
				$message = GetMessage($messageCode . '_M');
			}
		}

		return $message;
	}

	public static function cropMessage($template, array $replaces = array(), $length = false)
	{
		if($length === false)
		{
			$result = str_replace(array_keys($replaces), $replaces, $template);
		}
		else
		{
			$left = $length - strlen(preg_replace('/#[a-zA-Z_0-9]+#/', '', $template));
			$result = $template;

			// todo: make more clever algorithm here
			foreach($replaces as $placeHolder => $value)
			{
				$fullValue = $value;
				$placeHolder = '#'.$placeHolder.'#';

				if($left <= 0)
				{
					$result = str_replace($placeHolder, '', $result);
					continue;
				}

				if(strlen($value) > $left)
				{
					$value = substr($value, 0, $left - 3).'...';
				}

				$result = str_replace($placeHolder, $value, $result);
				$left -= strlen($fullValue);
			}
		}

		return $result;
	}

	private static function placeUserName($message, $userId)
	{
		return str_replace('#USER_NAME#', CUser::FormatName(CSite::GetNameFormat(false), self::getUser($userId)), $message);
	}

	protected static function placeLinkAnchor($message, $url, $mode = 'NONE')
	{
		if($mode == 'BBCODE')
		{
			$message = str_replace(
				array(
					'#TASK_URL_BEGIN#',
					'#URL_END#'
				),
				array(
					"[URL=".$url."]",
					"[/URL]"
				),
				$message
			);
		}
		else
		{
			$message = str_replace(
				array(
					'#TASK_URL_BEGIN#',
					'#URL_END#'
				),
				array(
					'',
					''
				),
				$message
			);

			if($mode == 'EMAIL')
			{
				$message .= ' #BR# '.GetMessage('TASKS_MESSAGE_LINK_GENERAL').': '.$url; // #BR# will be converted to \n by IM
			}
		}

		return $message;
	}

	/**
	 * IM notification BBCODE support:
	 * HTML, VIDEO, SMILE - NO
	 * ALL STANDARD - YES
	 * ADDITIONAL: USER - YES
	 */
	public static function clearNotificationText($text)
	{
		return preg_replace(
			array(
				'|\[DISK\sFILE\sID=[n]*\d+\]|',
				'|\[DOCUMENT\sID=\d+\]|'
			),
			'',
			$text
		);
	}

	protected static function addParameters($url, $parameters = array())
	{
		if(!is_array($parameters))
		{
			$parameters = array();
		}

		if(is_array($parameters['PARAMETERS']))
		{
			$url = CHTTP::urlAddParams($url, $parameters['PARAMETERS']);
		}

		if((string) $parameters['HASH'] != '')
		{
			$url .= '#'.$parameters['HASH'];
		}

		return $url;
	}

	########################
	# static data getters

	/**
	 * Returns notificaton path for a set of tasks
	 */
	protected static function getNotificationPathMultiple(array $arUser, array $taskIds, $bUseServerName = true)
	{
		$sites = \Bitrix\Tasks\Util\Site::getPair();

		if(self::checkUserIsIntranet($arUser["ID"]))
		{
			$site = $sites['INTRANET'];
		}
		else
		{
			$site = $sites['EXTRANET'];
		}

		// detect site name
		$serverName = '';
		if($bUseServerName)
		{
			$serverName = tasksServerName($site['SERVER_NAME']);
		}

		$pathTemplate = COption::GetOptionString('tasks', 'paths_task_user', '', $site['SITE_ID']);
		if((string) $pathTemplate == '')
		{
			$pathTemplate = "/company/personal/user/#user_id#/tasks/";
		}
		$url = $serverName.CComponentEngine::MakePathFromTemplate(
			$pathTemplate,
			array(
				'user_id' => $arUser['ID'],
				'USER_ID' => $arUser['ID'],
			)
		);

		return $url;
	}

	public static function getNotificationPath($arUser, $taskID, $bUseServerName = true, $arSites = array())
	{
		if(!is_array($arUser) || !intval($taskID))
		{
			return false;
		}

		static $siteCache = array();

		$siteID = false;
		$arTask = static::getTaskBaseByTaskId($taskID);

		if (is_array($arTask) && !empty($arTask))
		{
			if(!is_array($arSites) || empty($arSites))
			{
				$arSites = \Bitrix\Tasks\Util\Site::getPair();
			}

			// we have extranet and the current user is an extranet user
			$bExtranet = 	\Bitrix\Tasks\Integration\Extranet\User::isExtranet($arUser["ID"]);
			// task is in a group
			$useGroup = 	$arTask['GROUP_ID'] && self::checkUserCanViewGroupExtended($arUser['ID'], $arTask['GROUP_ID']);

			// detect site id
			if($bExtranet)
			{
				$siteID = (string) CExtranet::GetExtranetSiteID();
			}
			else
			{
				if($useGroup)
				{
					$groupSiteList = self::getSocNetGroupSiteList($arTask['GROUP_ID']);
					foreach($groupSiteList as $groupSite)
					{
						if (
							isset($arSites['EXTRANET']['SITE_ID'])
							&& $groupSite['LID'] == $arSites['EXTRANET']['SITE_ID']
						)
						{
							continue;
						}

						$siteID = $groupSite['LID'];
						$siteCache[$groupSite['LID']] = $groupSite;
						break;
					}
				}
				else
				{
					$userDataDb = \CUser::GetList($by, $order, ['ID' => $arUser['ID']], ['FIELDS' => ['ID', 'LID']]);
					if ($userData = $userDataDb->Fetch())
					{
						$siteID = $userData['LID'];
					}
				}

				if(!$siteID) // still not detected, use just intranet site
				{
					if(isset($arSites['INTRANET']['SITE_ID']))
						$siteID = $arSites['INTRANET']['SITE_ID'];
					else
						$siteID = (string) SITE_ID;
				}
			}

			// get site
			if(!isset($siteCache[$siteID]))
			{
				if((string) $siteID != '')
				{
					$siteCache[$siteID] = \Bitrix\Main\SiteTable::getList(array(
						'filter' => array('=LID' => $siteID),
						'select' => array('SITE_ID' => 'LID', 'DIR', 'SERVER_NAME'),
						'limit' => 1
					))->fetch();
				}
			}

			if(!is_array($siteCache[$siteID]))
			{
				return false;// still no site??? abort!
			}

			// choose template
			if($useGroup)
			{
				$pathTemplate = str_replace(
					array('#group_id#', '#GROUP_ID#'),
					$arTask["GROUP_ID"],
					CTasksTools::GetOptionPathTaskGroupEntry(
						$siteID,
						"/workgroups/group/#group_id#/tasks/task/view/#task_id#/"
					)
				);
				$workgroupsPage = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", $siteID);
				$pathTemplate = "#GROUPS_PATH#".substr($pathTemplate, strlen($workgroupsPage), strlen($pathTemplate)-strlen($workgroupsPage));
				$processed = CSocNetLogTools::ProcessPath(array("TASK_URL" => $pathTemplate), $arUser['ID'], $siteID);
				$pathTemplate = $processed['URLS']['TASK_URL'];
			}
			else
			{
				$pathTemplate = CTasksTools::GetOptionPathTaskUserEntry(
					$siteID,
					"/company/personal/user/#user_id#/tasks/task/view/#task_id#/"
				);
			}

			// detect site name
			$serverName = '';
			if($bUseServerName)
			{
				$serverName = tasksServerName($siteCache[$siteID]['SERVER_NAME']);
			}

			$strUrl = $serverName
				. CComponentEngine::MakePathFromTemplate(
					$pathTemplate,
					array(
						'user_id' => $arUser['ID'],
						'USER_ID' => $arUser['ID'],
						'task_id' => $taskID,
						'TASK_ID' => $taskID,
						'action'  => 'view'
					)
				);

			return ($strUrl);
		}

		return false;
	}

	private static function prepareRightsCodesForViewInGroupLiveFeed($logID, $groupId)
	{
		$arRights = array();

		if ($groupId)
			$arRights = array('SG' . $groupId);

		return ($arRights);
	}

	private static function getEffectiveUserId(array $arFields = array(), array $arTask = array(), $bSpawnedByAgent = false, array $parameters = array())
	{
		if(isset($parameters['AUTHOR_ID']))
		{
			$effectiveUserId = intval($parameters['AUTHOR_ID']);
		}
		else
		{
			if(User::getId() && $bSpawnedByAgent !== true && $bSpawnedByAgent !== 'Y')
			{
				$effectiveUserId = (int) User::getId();
			}
			else
			{
				if (isset($arFields['CREATED_BY']) && ($arFields['CREATED_BY'] > 0))
				{
					$effectiveUserId = (int) $arFields['CREATED_BY'];
				}
				elseif(isset($arTask['CREATED_BY']) && ($arTask['CREATED_BY'] > 0))
				{
					$effectiveUserId = (int) $arTask['CREATED_BY'];
				}
				else
				{
					$effectiveUserId = CTasksTools::GetCommanderInChief();
				}
			}
		}

		return $effectiveUserId;
	}

	private static function getOccurAsUserId(array $arFields = array(), array $arTask = array(), $bSpawnedByAgent = false, array $parameters = array())
	{
		if(isset($parameters['AUTHOR_ID']))
		{
			$occurAsUserId = intval($parameters['AUTHOR_ID']);
		}
		else
		{
			$occurAsUserId = CTasksTools::getOccurAsUserId();
			if(!$occurAsUserId )
			{
				$occurAsUserId = self::getEffectiveUserId($arFields, $arTask, $bSpawnedByAgent, $parameters);
			}
		}

		return $occurAsUserId;
	}

	private static function getUserTimeZoneOffset($userId = 'current')
	{
		if(!isset(self::$cache['TIMEZONE'][$userId]) || !self::$cacheData)
		{
			self::$cache['TIMEZONE'][$userId] = CTasksTools::getTimeZoneOffset($userId == 'current' ? false : $userId);
		}

		return self::$cache['TIMEZONE'][$userId];
	}

	private static function checkUserIsIntranet($userId)
	{
		if(!isset(self::$cache['INTRANET_USERS'][$userId]) || !self::$cacheData)
		{
			self::$cache['INTRANET_USERS'][$userId] = CTasksTools::IsIntranetUser($userId);
		}

		return self::$cache['INTRANET_USERS'][$userId];
	}

	private static function getSocNetGroupSite($id)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return array();
		}

		if(!isset(self::$cache['GROUP_SITE'][$id]) || !self::$cacheData)
		{
			$item = CSocNetGroup::GetSite($id)->fetch();
			if(!empty($item))
			{
				self::$cache['GROUP_SITE'][$id] = $item;
			}
		}

		return self::$cache['GROUP_SITE'][$id];
	}

	private static function getSocNetGroupSiteList($id)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return array();
		}

		$bitrix24Installed = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');
		$extranetInstalled = \Bitrix\Main\ModuleManager::isModuleInstalled('extranet');

		if(!isset(self::$cache['GROUP_SITE_LIST'][$id]) || !self::$cacheData)
		{
			self::$cache['GROUP_SITE_LIST'][$id] = array();
			$res = CSocNetGroup::GetSite($id);
			while($item = $res->fetch())
			{
				if (
					$item['ACTIVE'] == 'N'
					|| (
						!$extranetInstalled
						&& (
							(
								$bitrix24Installed
								&& $item['LID'] == 'ex'
							)
							|| $item['LID'] == \Bitrix\Main\Config\Option::get("extranet", "extranet_site") // extranet uninstalled with 'Save data' option
						)
					)
				)
				{
					continue;
				}

				self::$cache['GROUP_SITE_LIST'][$id][] = $item;
			}
		}

		return self::$cache['GROUP_SITE_LIST'][$id];
	}

	private static function getSocNetGroup($id)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return array();
		}

		if(!isset(self::$cache['GROUPS'][$id]) || !self::$cacheData)
		{
			$item = CSocNetGroup::GetList(array(), array('ID' => $id), false, false, array('ID', 'NAME'))->fetch();
			if(!empty($item))
			{
				self::$cache['GROUPS'][$id] = $item;
			}
		}

		return self::$cache['GROUPS'][$id];
	}

	private static function checkUserCanViewGroup($userId, $groupId)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		if(!isset(self::$cache['GROUP_ACCESS'][$groupId][$userId]) || !self::$cacheData)
		{
			self::$cache['GROUP_ACCESS'][$groupId][$userId] = CSocNetGroup::CanUserViewGroup($userId, $groupId);
		}

		return self::$cache['GROUP_ACCESS'][$groupId][$userId];
	}

	private static function checkUserCanViewGroupExtended($userId, $groupId)
	{
		if(!isset(self::$cache['GROUP_ACCESS_EXT'][$groupId][$userId]) || !self::$cacheData)
		{
			self::$cache['GROUP_ACCESS_EXT'][$groupId][$userId] = CTasksTools::HasUserReadAccessToGroup($userId, $groupId);
		}

		return self::$cache['GROUP_ACCESS_EXT'][$groupId][$userId];
	}

	/**
	 * @access private
	 */
	public static function getUsers(array $ids = array())
	{
		if(empty($ids))
		{
			return array();
		}

		if(!is_array(self::$cache['USER']) || !self::$cacheData)
		{
			self::$cache['USER'] = array();
		}

		$absent = array_diff($ids, array_keys(self::$cache['USER']));

		if(!empty($absent))
		{
			$res = CUser::GetList(
				$by = 'ID',
				$order = 'ASC',
				array('ID' => implode('|', $absent)),
				array('FIELDS' => array('NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL', 'ID', 'PERSONAL_GENDER', 'EXTERNAL_AUTH_ID'))
			);
			while($item = $res->fetch())
			{
				self::$cache['USER'][$item['ID']] = $item;
			}
		}

		$ids = array_flip($ids);
		foreach($ids as $userId => $void)
		{
			$ids[$userId] = self::$cache['USER'][$userId];
		}

		return $ids;
	}

	/**
	 * @access private
	 */
	public static function getUser($id)
	{
		if(!intval($id))
		{
			return false;
		}

		$users = CTaskNotifications::getUsers(array($id));
		return $users[$id];
	}

	private static function getTaskBaseByTaskId($taskId)
	{
		if(!is_array(static::$cache['TASK2GROUP']))
		{
			static::$cache['TASK2GROUP'] = array();
		}

		if(!isset(static::$cache['TASK2GROUP'][$taskId]))
		{
			$item = CTasks::getList(array(), array('ID' => $taskId), array('ID', 'GROUP_ID'), array('USER_ID' => \Bitrix\Tasks\Util\User::getAdminId()))->fetch();
			if(is_array($item) && !empty($item))
			{
				static::$cache['TASK2GROUP'][$taskId] = $item;
			}
		}

		return static::$cache['TASK2GROUP'][$taskId];
	}

	########################
	# mode togglers

	/**
	 * Enable sending messages to IM
	 */
	public static function enableInstantNotifications()
	{
		if(!self::$suppressIM) // already enabled
		{
			return false;
		}

		self::$suppressIM = false;
	}

	/**
	 * Disable sending messages to IM
	 */
	public static function disableInstantNotifications()
	{
		if(self::$suppressIM) // already disabled
		{
			return false;
		}

		self::$suppressIM = true;
	}

	private static function enableStaticCache()
	{
		if(self::$cacheData) // already enabled
		{
			return false;
		}

		self::$cacheData = true;
		self::clearStaticCache();

		return true;
	}

	private static function disableStaticCache()
	{
		if(!self::$cacheData) // already disabled
		{
			return false;
		}

		self::$cacheData = true;
		self::clearStaticCache();

		return true;
	}

	private static function clearStaticCache()
	{
		self::$cache = array();
	}

	public static function disableAutoDeliver()
	{
		if(self::$bufferize) // already disabled
		{
			return false;
		}

		self::$bufferize = true;
		self::enableStaticCache();
		return true;
	}

	public static function enableAutoDeliver($flushNow = true)
	{
		self::$bufferize = false;
		if($flushNow)
		{
			self::flushNotificationBuffer();
		}
		self::disableStaticCache();
	}

	########################
	# deprecated

	/**
	 * Sends notifications to IM.
	 *
	 * @param $fromUserID
	 * @param $arRecipientsIDs
	 * @param $message
	 * @param int $taskID
	 * @param null $message_email
	 * @param array $arEventData
	 * @return bool|null
	 *
	 * @deprecated
	 */
	public static function SendMessage($fromUserID, $arRecipientsIDs, $message,
		$taskID = 0, $message_email = null, $arEventData = array(),
		$taskAssignedTo = null
	)
	{
		$result = self::sendMessageEx($taskID, $fromUserID, $arRecipientsIDs, array(
			'INSTANT' => $message,
			'EMAIL' => $message_email
		), array(
			'EVENT_DATA' => $arEventData,
			'TASK_ASSIGNED_TO' => $taskAssignedTo
		));

		if($result === true)
		{
			return (null);
		}
		else
		{
			return $result;
		}
	}

	/**
	 * @deprecated
	 */
	private function __GetUsers($arFields)
	{
		$arUsersIDs = array_unique(
			array_filter(
				array_merge(
					array(
						$arFields["CREATED_BY"],
						$arFields["RESPONSIBLE_ID"]
					),
					(array) $arFields["ACCOMPLICES"],
					(array) $arFields["AUDITORS"],
					(array) $arFields['ADDITIONAL_RECIPIENTS']
				)
			)
		);

		return self::getUsers($arUsersIDs);
	}

	/**
	 * @deprecated
	 */
	private function __Users2String($arUserIDs, $arUsers, $nameTemplate = "")
	{
		$arUsersStrs = array();
		if (!is_array($arUserIDs))
			$arUserIDs = array($arUserIDs);

		$arUserIDs = array_unique(array_filter($arUserIDs));
		foreach ($arUserIDs as $userID)
		{
			if ($user = $arUsers[$userID])
				$arUsersStrs[] = CUser::FormatName(empty($nameTemplate) ? CSite::GetNameFormat(false) : $nameTemplate, $arUsers[$userID]);
		}

		return implode(", ", $arUsersStrs);
	}

	/**
	 * @deprecated
	 */
	function __UserIDs2Rights($arUserIDs)
	{
		$arUserIDs = array_unique(array_filter($arUserIDs));
		$arRights = array();
		foreach($arUserIDs as $userID)
			$arRights[] = "U".$userID;

		return $arRights;
	}

	/**
	 * @deprecated
	 */
	function __Fields2Names($arFields)
	{
		$arFields = array_unique(array_filter($arFields));
		$arNames = array();
		foreach($arFields as $field)
		{
			if ($field == "NEW_FILES" || $field == "DELETED_FILES")
				$field = "FILES";
			$arNames[] = GetMessage("TASKS_SONET_LOG_".$field);
		}

		return array_unique(array_filter($arNames));
	}

	/**
	 * @deprecated
	 */
	public static function GetRecipientsIDs($arFields, $bExcludeCurrent = true, $bExcludeAdditionalRecipients = false, $currentUserId = false)
	{
		if ($bExcludeAdditionalRecipients)
			$arFields['ADDITIONAL_RECIPIENTS'] = array();

		if ( ! isset($arFields['ADDITIONAL_RECIPIENTS']) )
			$arFields['ADDITIONAL_RECIPIENTS'] = array();

		if ( ! isset($arFields['IGNORE_RECIPIENTS']) || ! is_array($arFields['IGNORE_RECIPIENTS']) )
			$arFields['IGNORE_RECIPIENTS'] = array();

		$arRecipientsIDs = array_unique(
			array_filter(
				array_merge(
					array($arFields["CREATED_BY"], $arFields["RESPONSIBLE_ID"]),
					(array) $arFields["ACCOMPLICES"],
					(array) $arFields["AUDITORS"],
					(array) $arFields['ADDITIONAL_RECIPIENTS']
					)));

		if (!empty($arFields['IGNORE_RECIPIENTS']))
		{
			foreach ($arRecipientsIDs as $key => $value)
			{
				if (in_array($value, $arFields['IGNORE_RECIPIENTS']))
				{
					unset($arRecipientsIDs[$key]);
				}
			}
		}

		if ($bExcludeCurrent)
		{
			if($currentUserId !== false)
			{
				$currentUserIDFound = $currentUserId;
			}
			elseif(User::getId())
			{
				$currentUserIDFound = User::getId();
			}

			if($currentUserIDFound)
			{
				$currentUserPos = array_search($currentUserIDFound, $arRecipientsIDs);
				if ($currentUserPos !== false)
				{
					unset($arRecipientsIDs[$currentUserPos]);
				}
			}
		}

		return $arRecipientsIDs;
	}

	private static function notifyByMail(array $message, array $site)
	{
		if (
			!is_array($message)
			|| !isset($message["ENTITY_CODE"])
			|| !isset($message["FROM_USER_ID"])
			|| !isset($message["TASK_ID"])
			|| !isset($message["TO_USER_IDS"])
			|| !is_array($message["TO_USER_IDS"])
			|| empty($message["TO_USER_IDS"])
		)
		{
			return false;
		}

		if (!\Bitrix\Tasks\Integration\Mail::isInstalled())
		{
			return false;
		}

		if(!is_array($message["TO_USER_IDS"]) || empty($message["TO_USER_IDS"]))
		{
			return false;
		}

		// ids
		$authorId = intval($message["FROM_USER_ID"]);
		$taskId = intval($message["TASK_ID"]);

		// check event type
		$entityCode = trim($message["ENTITY_CODE"]);
		$entityOperation = trim($message["ENTITY_OPERATION"]);

		// site detect
		if(!is_array($site) || empty($site) || empty($site["SITE_ID"]))
		{
			$site = \Bitrix\Tasks\Util\Site::get(SITE_ID);
		}
		if(empty($site["SITE_ID"])) // no way, this cant be true
		{
			return false;
		}
		$siteId = $site["SITE_ID"];

		// event type
		$eventId = false;
		$threadMessageId = false;
		$prevFields = array();
		$commentId = 0;
		$taskTitle = '';
		$subjPrefix = '';
		if($entityCode == 'TASK')
		{
			if($entityOperation == 'ADD' || $entityOperation == 'UPDATE')
			{
				$eventId = 'TASKS_TASK_'.$entityOperation.'_EMAIL';
				$threadMessageId = \Bitrix\Tasks\Integration\Mail::formatThreadId('TASK_'.$taskId, $siteId);
			}

			if($entityOperation == 'UPDATE')
			{
				$threadMessageId = \Bitrix\Tasks\Integration\Mail::formatThreadId(
					sprintf('TASK_UPDATE_%u_%x%x', $taskId, time(), rand(0, 0xffffff)),
					$siteId
				);

				$prevFields = $message["EVENT_DATA"]['arChanges'];
				$subjPrefix = \Bitrix\Tasks\Integration\Mail::getSubjectPrefix();
			}

			if($message["EVENT_DATA"]["arFields"])
			{
				$taskTitle = trim($message["EVENT_DATA"]["arFields"]['TITLE']);
			}
		}
		elseif($entityCode == 'COMMENT')
		{
			if($entityOperation == 'ADD')
			{
				$eventId = 'TASKS_TASK_COMMENT_ADD_EMAIL';

				$commentId = $message["EVENT_DATA"]['MESSAGE_ID'];
				if(!$commentId)
				{
					// unable to identify comment id, exit
					return false;
				}

				$threadMessageId = \Bitrix\Tasks\Integration\Mail::formatThreadId('TASK_COMMENT_'.$commentId, $siteId);
				$subjPrefix = \Bitrix\Tasks\Integration\Mail::getSubjectPrefix();
			}

			if($message["ADDITIONAL_DATA"]['TASK_DATA'])
			{
				$taskTitle = trim($message["ADDITIONAL_DATA"]['TASK_DATA']['TITLE']);
			}
		}
		if($eventId === false)
		{
			return false; // unknown action
		}

		// email letter data
		$pathToTask = \Bitrix\Tasks\Integration\Mail\Task::getDefaultPublicPath($taskId);

		$users = static::getUsers(array_merge(array($authorId), $message["TO_USER_IDS"]));
		foreach($users as $i => $user)
		{
			$users[$i]['NAME_FORMATTED'] = \Bitrix\Tasks\Util\User::formatName($users[$i], $siteId);
		}

		$receiversData = \Bitrix\Tasks\Integration\Mail\User::getData($message["TO_USER_IDS"], $siteId);
		if(empty($receiversData))
		{
			return false; // nowhere to send
		}

		foreach ($receiversData as $userId => $arUser)
		{
			$email = $arUser["EMAIL"];
			$nameFormatted = str_replace(array('<', '>', '"'), '', $arUser["NAME_FORMATTED"]);

			$replyTo = \Bitrix\Tasks\Integration\Mail\Task::getReplyTo(
				$userId,
				$taskId,
				$pathToTask,
				$siteId
			);
			if ($replyTo != '')
			{
				$authorName = str_replace(array('<', '>', '"'), '', $users[$authorId]['NAME_FORMATTED']);

				$e = array(
					"=Reply-To" => $authorName.' <'.$replyTo.'>',
					"=Message-Id" => $threadMessageId,
					"EMAIL_FROM" => $authorName.' <'.\Bitrix\Tasks\Integration\Mail::getDefaultEmailFrom($siteId).'>',
					"EMAIL_TO" => (!empty($nameFormatted) ? ''.$nameFormatted.' <'.$email.'>' : $email),

					"TASK_ID" => $taskId,
					"TASK_COMMENT_ID" => $commentId,
					"TASK_TITLE" => $taskTitle,
					"TASK_PREVIOUS_FIELDS" => \Bitrix\Tasks\Util\Type::serializeArray($prevFields),

					"RECIPIENT_ID" => $userId,
					"USER_ID" => \Bitrix\Tasks\Util\User::getAdminId(),

					"URL" => $pathToTask,
					"SUBJECT" => $subjPrefix.$taskTitle
				);

				if (!('TASK' == $entityCode && 'ADD' == $entityOperation))
				{
					$e['=In-Reply-To'] = \Bitrix\Tasks\Integration\Mail::formatThreadId('TASK_'.$taskId, $siteId);
				}

				CEvent::Send(
					$eventId,
					$siteId,
					$e
				);
			}
		}
	}

	public static function enableSonetLogNotifyAuthor()
	{
		self::$sonetLogNotifyAuthor = true;
	}

	public static function disableSonetLogNotifyAuthor()
	{
		self::$sonetLogNotifyAuthor = false;
	}
}
