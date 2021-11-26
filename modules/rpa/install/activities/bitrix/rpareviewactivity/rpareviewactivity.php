<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime()->IncludeActivityFile('RpaApproveActivity');

class CBPRpaReviewActivity extends CBPRpaApproveActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = [
			"Title" => "",
			"Name" => null,
			"Description" => null,
			"Responsible" => null,
			"Actions" => [],
			'FieldsToShow' => [],

			"TaskId" => 0,
			"LastReviewer" => null,
		];

		$this->SetPropertiesTypes([
			'TaskId' => ['Type' => 'int'],
			'LastReviewer' => ['Type' => 'user'],
		]);
	}

	public function OnExternalEvent($arEventParameters = array())
	{
		if ($this->executionStatus == CBPActivityExecutionStatus::Closed)
			return;

		if (!array_key_exists("USER_ID", $arEventParameters) || intval($arEventParameters["USER_ID"]) <= 0)
			return;

		if (isset($arEventParameters['onStageUpdate']) && isset($arEventParameters['stageId']))
		{
			if ($this->Actions[0]['stageId'] == $arEventParameters['stageId'])
			{
				$arEventParameters['REVIEWED'] = true;
			}
		}

		if (!array_key_exists("REVIEWED", $arEventParameters))
			return;

		if (empty($arEventParameters["REAL_USER_ID"]))
		{
			$arEventParameters["REAL_USER_ID"] = $arEventParameters["USER_ID"];
		}

		$arEventParameters["USER_ID"] = intval($arEventParameters["USER_ID"]);
		$arEventParameters["REAL_USER_ID"] = intval($arEventParameters["REAL_USER_ID"]);
		if (!in_array($arEventParameters["REAL_USER_ID"], \CBPTaskService::getTaskUserIds($this->taskId)))
		{
			return;
		}

		$this->LastReviewer = "user_".$arEventParameters["REAL_USER_ID"];

		$taskService = $this->workflow->GetService("TaskService");
		$taskService->MarkCompleted($this->taskId, $arEventParameters["REAL_USER_ID"], CBPTaskUserStatus::Ok);

		$this->taskStatus = CBPTaskStatus::CompleteOk;

		$taskId = $this->taskId;
		$this->Unsubscribe($this);
		$this->ExecuteAction($this->Actions[0], $taskId, $this->LastReviewer);
	}

	public static function getTaskControls($arTask)
	{
		$actions = $arTask['PARAMETERS']['ACTIONS'];
		return array(
			'BUTTONS' => array(
				array(
					'TYPE'  => 'submit',
					'TARGET_USER_STATUS' => CBPTaskUserStatus::Ok,
					'NAME'  => 'review',
					'VALUE' => 'Y',
					'TEXT'  => $actions[0]['label'],
					'COLOR' => $actions[0]['color']
				)
			)
		);
	}

	public static function PostTaskForm($arTask, $userId, $arRequest, &$arErrors, $userName = "", $realUserId = null)
	{
		$arErrors = array();

		try
		{
			$userId = intval($userId);
			if ($userId <= 0)
			{
				throw new CBPArgumentNullException("userId");
			}

			$eventParameters = [
				"USER_ID" => $userId,
				"REAL_USER_ID" => $realUserId,
				"USER_NAME" => $userName,
				"REVIEWED" => true
			];

			CBPRuntime::SendExternalEvent($arTask["WORKFLOW_ID"], $arTask["ACTIVITY_NAME"], $eventParameters);

			return true;
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]",
			);
		}

		return false;
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$dialog = parent::GetPropertiesDialog(...func_get_args());
		$map = $dialog->getMap();

		array_pop($map['Actions']['Default']);
		$map['Actions']['Default'][0]['label'] = GetMessage('RPA_BP_REV_BTN_OK_TEXT');

		$dialog->setMap([
			'Name' => $map['Name'],
			'Description' => $map['Description'],
			'Responsible' => $map['Responsible'],
			'Actions' => $map['Actions'],
			'FieldsToShow' => $map['FieldsToShow'],
		]);

		return $dialog;
	}
}