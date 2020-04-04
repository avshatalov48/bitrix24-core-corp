<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPDelayActivity
	extends CBPActivity
	implements IBPEventActivity, IBPActivityExternalEventListener, IBPEventDrivenActivity
{
	private $subscriptionId = 0;
	private $isInEventActivityMode = false;

	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"Title"               => "",
			"TimeoutDuration"     => null,
			"TimeoutDurationType" => "s",
			"TimeoutTime"         => null,
			"TimeoutTimeIsLocal"  => 'N'
		);
	}

	public function Cancel()
	{
		if (!$this->isInEventActivityMode && $this->subscriptionId > 0)
		{
			$this->Unsubscribe($this);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public function Execute()
	{
		if ($this->isInEventActivityMode)
		{
			return CBPActivityExecutionStatus::Closed;
		}

		$result = $this->Subscribe($this);
		$this->isInEventActivityMode = false;

		return $result ? CBPActivityExecutionStatus::Executing : CBPActivityExecutionStatus::Closed;
	}

	public function Subscribe(IBPActivityExternalEventListener $eventHandler)
	{
		$this->isInEventActivityMode = true;

		$timeoutDuration = $this->TimeoutDuration;
		$timeoutDurationValue = 0;
		$timeoutTime = $this->TimeoutTime;
		$isLocalTime = ($this->TimeoutTimeIsLocal === 'Y');

		if ($timeoutDuration != null)
		{
			$timeoutDurationValue = $this->CalculateTimeoutDuration();
			$expiresAt = time() + $timeoutDurationValue;
		}
		elseif ($timeoutTime != null)
		{
			if ($timeoutTime instanceof \Bitrix\Bizproc\BaseType\Value\Date)
			{
				$timeoutTime = $timeoutTime->getTimestamp();
			}
			else
			{
				if (intval($timeoutTime)."|" != $timeoutTime."|")
				{
					$timeoutTime = MakeTimeStamp($timeoutTime);
				}

				if ($isLocalTime)
				{
					$timeoutTime -= \CTimeZone::GetOffset();
				}
			}

			$expiresAt = $timeoutTime;
		}
		else
		{
			$expiresAt = time();
		}

		if ($timeoutTime != null && $eventHandler === $this && $expiresAt <= time() + 1) //now + 1 second
		{
			$this->WriteToTrackingService(GetMessage("BPDA_TRACK3"));
			return false;
		}

		$schedulerService = $this->workflow->GetService("SchedulerService");
		$this->subscriptionId = $schedulerService->SubscribeOnTime($this->workflow->GetInstanceId(), $this->name, $expiresAt);

		$this->workflow->AddEventHandler($this->name, $eventHandler);

		if ($timeoutDuration != null)
		{
			$timeoutDurationValue = max($timeoutDurationValue, CBPSchedulerService::getDelayMinLimit());
			$timestamp = time() + $timeoutDurationValue;

			$this->WriteToTrackingService(
				GetMessage('BPDA_TRACK4', [
					'#PERIOD1#' => trim(CBPHelper::FormatTimePeriod($timeoutDurationValue)),
					'#PERIOD2#' => sprintf(
							'%s (%s)',
							ConvertTimeStamp($timestamp, "FULL"),
							date('P', $timestamp)
						),
					]
				)
			);
		}
		elseif ($timeoutTime != null)
		{
			$timestamp = max($timeoutTime, time() + CBPSchedulerService::getDelayMinLimit());
			$this->WriteToTrackingService(GetMessage("BPDA_TRACK1", [
				'#PERIOD#' => sprintf('%s (%s)', ConvertTimeStamp($timestamp, "FULL"), date('P', $timestamp))
			]));
		}
		else
		{
			$this->WriteToTrackingService(GetMessage("BPDA_TRACK2"));
		}

		return true;
	}

	public function Unsubscribe(IBPActivityExternalEventListener $eventHandler)
	{
		$schedulerService = $this->workflow->GetService("SchedulerService");
		$schedulerService->UnSubscribeOnTime($this->subscriptionId);
		$this->workflow->RemoveEventHandler($this->name, $eventHandler);
		$this->subscriptionId = 0;
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if ($this->executionStatus != CBPActivityExecutionStatus::Closed)
		{
			$this->Unsubscribe($this);
			$this->workflow->CloseActivity($this);
		}
	}

	public function HandleFault(Exception $exception)
	{
		$status = $this->Cancel();
		if ($status == CBPActivityExecutionStatus::Canceling)
		{
			return CBPActivityExecutionStatus::Faulting;
		}

		return $status;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$errors = [];

		if (
			(!array_key_exists("TimeoutDuration", $arTestProperties)
				|| (intval($arTestProperties["TimeoutDuration"]) <= 0 && !CBPActivity::isExpression($arTestProperties["TimeoutDuration"])))
			&&
			(!array_key_exists("TimeoutTime", $arTestProperties)
				|| (intval($arTestProperties["TimeoutTime"]) <= 0 && !CBPActivity::isExpression($arTestProperties["TimeoutTime"])))
		)
		{
			$errors[] = array("code" => "NotExist", "parameter" => "TimeoutDuration", "message" => GetMessage("BPDA_EMPTY_PROP"));
		}

		return array_merge($errors, parent::ValidateProperties($arTestProperties, $user));
	}

	private function CalculateTimeoutDuration()
	{
		$timeoutDuration = ($this->IsPropertyExists("TimeoutDuration") ? $this->TimeoutDuration : 0);

		$timeoutDurationType = ($this->IsPropertyExists("TimeoutDurationType") ? $this->TimeoutDurationType : "s");
		$timeoutDurationType = strtolower($timeoutDurationType);
		if (!in_array($timeoutDurationType, array("s", "d", "h", "m")))
		{
			$timeoutDurationType = "s";
		}

		$timeoutDuration = intval($timeoutDuration);
		switch ($timeoutDurationType)
		{
			case 'd':
				$timeoutDuration *= 3600 * 24;
				break;
			case 'h':
				$timeoutDuration *= 3600;
				break;
			case 'm':
				$timeoutDuration *= 60;
				break;
			default:
				break;
		}

		return min($timeoutDuration, 3600 * 24 * 365 * 5);
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);

			if (is_array($arCurrentActivity["Properties"]))
			{
				if (array_key_exists("TimeoutDuration", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["TimeoutDuration"]))
					$arCurrentValues["delay_time"] = $arCurrentActivity["Properties"]["TimeoutDuration"];
				if (array_key_exists("TimeoutDurationType", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["TimeoutDurationType"]))
					$arCurrentValues["delay_type"] = $arCurrentActivity["Properties"]["TimeoutDurationType"];
				if (array_key_exists("TimeoutTime", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["TimeoutTime"]))
				{
					$arCurrentValues["delay_date"] = $arCurrentActivity["Properties"]["TimeoutTime"];
					if (!CBPActivity::isExpression($arCurrentValues["delay_date"]))
						$arCurrentValues["delay_date"] = ConvertTimeStamp($arCurrentValues["delay_date"], "FULL");
				}

				if (array_key_exists("TimeoutTimeIsLocal", $arCurrentActivity["Properties"]) && !is_null($arCurrentActivity["Properties"]["TimeoutTimeIsLocal"]))
				{
					$arCurrentValues["delay_date_is_local"] = $arCurrentActivity["Properties"]["TimeoutTimeIsLocal"];
				}
			}

			if (is_array($arCurrentValues)
				&& array_key_exists("delay_time", $arCurrentValues)
				&& (intval($arCurrentValues["delay_time"]) > 0)
				&& !array_key_exists("delay_type", $arCurrentValues))
			{
				$arCurrentValues["delay_time"] = intval($arCurrentValues["delay_time"]);

				$arCurrentValues["delay_type"] = "s";
				if ($arCurrentValues["delay_time"] % (3600 * 24) == 0)
				{
					$arCurrentValues["delay_time"] = $arCurrentValues["delay_time"] / (3600 * 24);
					$arCurrentValues["delay_type"] = "d";
				}
				elseif ($arCurrentValues["delay_time"] % 3600 == 0)
				{
					$arCurrentValues["delay_time"] = $arCurrentValues["delay_time"] / 3600;
					$arCurrentValues["delay_type"] = "h";
				}
				elseif ($arCurrentValues["delay_time"] % 60 == 0)
				{
					$arCurrentValues["delay_time"] = $arCurrentValues["delay_time"] / 60;
					$arCurrentValues["delay_type"] = "m";
				}
			}
		}

		if (!is_array($arCurrentValues) || !array_key_exists("delay_type", $arCurrentValues))
			$arCurrentValues["delay_type"] = "s";
		if (!is_array($arCurrentValues) || !array_key_exists("delay_time", $arCurrentValues) && !array_key_exists("delay_date", $arCurrentValues))
		{
			$arCurrentValues["delay_time"] = 1;
			$arCurrentValues["delay_type"] = "h";
		}

		if (!is_array($arCurrentValues) || !array_key_exists("delay_date_is_local", $arCurrentValues))
		{
			$arCurrentValues["delay_date_is_local"] = "N";
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"formName"        => $formName
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$errors)
	{
		$errors = [];
		$properties = [];

		if ($arCurrentValues["time_type_selector"] == "time")
		{
			if (CBPDocument::IsExpression($arCurrentValues["delay_date"]))
			{
				$arCurrentValues["delay_date_x"] = $arCurrentValues["delay_date"];
				$arCurrentValues["delay_date"] = '';
			}

			if (strlen($arCurrentValues["delay_date"]) > 0 && $d = MakeTimeStamp($arCurrentValues["delay_date"]))
			{
				$properties["TimeoutTime"] = $d;
			}
			elseif (
				strlen($arCurrentValues["delay_date_x"]) > 0 &&
				CBPActivity::isExpression($arCurrentValues["delay_date_x"])
			)
			{
				$properties["TimeoutTime"] = $arCurrentValues["delay_date_x"];
			}

			$properties['TimeoutTimeIsLocal'] = ($arCurrentValues["delay_date_is_local"] === 'Y') ? 'Y' : 'N';
		}
		else
		{
			$properties["TimeoutDuration"] = $arCurrentValues["delay_time"];
			$properties["TimeoutDurationType"] = $arCurrentValues["delay_type"];
		}

		$errors = self::ValidateProperties($properties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($errors) > 0)
		{
			return false;
		}

		$currentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$currentActivity["Properties"] = $properties;

		return true;
	}
}