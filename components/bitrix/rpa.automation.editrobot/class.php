<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa;
use Bitrix\Bizproc;

class RpaAutomationEditRobotComponent extends Rpa\Components\Base
{
	public function onPrepareComponentParams($arParams)
	{
		$arParams["typeId"] = (int)$arParams["typeId"];
		$this->fillParameterFromRequest('stage', $arParams);
		$this->fillParameterFromRequest('robotType', $arParams);
		$this->fillParameterFromRequest('robotName', $arParams);
		$arParams["SET_TITLE"] = (isset($arParams["SET_TITLE"]) && $arParams["SET_TITLE"] === "N" ? "N" : "Y");

		return $arParams;
	}

	public function executeComponent()
	{
		if (!Bitrix\Rpa\Integration\Bizproc\Automation\Factory::canUseAutomation())
		{
			$this->showError('Service is unavailable');
			return;
		}
		parent::init();

		$this->arResult['DOCUMENT_TYPE'] = Rpa\Integration\Bizproc\Document\Item::makeComplexType(
			$this->arParams['typeId']
		);

		if (!$this->checkPermissions($this->arResult['DOCUMENT_TYPE']))
		{
			$this->showError(Loc::getMessage('RPA_MODIFY_TYPE_ACCESS_DENIED'));
			return;
		}

		$this->arResult['DOCUMENT_STATUS'] = $this->arParams['stage'];

		$template = new \Bitrix\Bizproc\Automation\Engine\Template($this->arResult['DOCUMENT_TYPE'], $this->arResult['DOCUMENT_STATUS']);
		$robotData = $this->getRobotData($template);

		if (!$robotData)
		{
			$this->showError("Empty robot data.");
			return;
		}

		$robotName = $this->getRobotName();
		$request = $_POST;

		if ($request)
		{
			$this->saveRobot($template, $robotName, $robotData, $request);
		}

		$dialog = $template->getRobotSettingsDialog($robotData, $request ?: null);

		if ($dialog === '')
		{
			return;
		}

		if (!($dialog instanceof \Bitrix\Bizproc\Activity\PropertiesDialog))
		{
			$this->showError('Robot dialog not supported in current context.');
			return;
		}

		if (isset($this->arParams['~CONTEXT']) && is_array($this->arParams['~CONTEXT']))
		{
			$dialog->setContext($this->arParams['~CONTEXT']);
		}

		$dialog->setDialogFileName('robot_properties_dialog');
		$this->arResult['dialog'] = $dialog;

		$this->prepareScenarios($robotData);
		$this->setTitle();

		$this->includeComponentTemplate();
	}

	private function saveRobot(Bizproc\Automation\Engine\Template $template, $robotName, $robotData, $request)
	{
		if (empty($robotData['Name']))
		{
			$robotData['Name'] = Bizproc\Automation\Engine\Robot::generateName();
		}

		$saveResult = $template->saveRobotSettings($robotData, $request);

		if ($saveResult->isSuccess())
		{
			$data = $saveResult->getData();
			$robotData = $data['robot'];

			$robots = $template->getRobots();
			if ($robots === null)
			{
				$robots = [];
			}

			$isChanged = false;
			if ($robotName && $robots)
			{
				foreach ($robots as $i => $robot)
				{
					if ($robotName === $robot->getName())
					{
						$robots[$i] = $robotData;
						$isChanged = !$this->isPropertiesEqual($robotData['Properties'], $robot->getProperties());
					}
				}
			}
			else
			{
				$robots[] = $robotData;
			}

			$tplUser = new \CBPWorkflowTemplateUser(\CBPWorkflowTemplateUser::CurrentUser);
			$result = $template->save($robots, $tplUser->getId());

			if ($isChanged && $result->isSuccess())
			{
				Rpa\Driver::getInstance()->getTaskManager()->onTaskPropertiesChanged(
					$template->getDocumentType(),
					$template->getId(),
					$robotData
				);
			}
		}
		else
		{
			$this->errorCollection->add($saveResult->getErrors());
		}
	}

	private function isPropertiesEqual(array $prop1, array $prop2): bool
	{
		//@TODO: choose another algo
		$s1 = Main\Web\Json::encode($prop1);
		$s2 = Main\Web\Json::encode($prop2);

		return ($s1 === $s2);
	}

	protected function getRobotName()
	{
		return $this->arParams['robotName'] ?? null;
	}

	protected function getRobotData(Bizproc\Automation\Engine\Template $template)
	{
		$name = $this->getRobotName();
		if ($name)
		{
			$robot = $template->getRobotByName($name);

			return $robot ? $robot->toArray() : null;
		}

		return [
			'Type' => $this->arParams['robotType'],
		];
	}

	protected function checkPermissions(array $documentType)
	{
		$tplUser = new \CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);

		return (
			$tplUser->isAdmin()
			||
			CBPDocument::CanUserOperateDocumentType(
				\CBPCanUserOperateOperation::CreateAutomation,
				$tplUser->getId(),
				$documentType
			)
		);
	}

	private function setTitle()
	{
		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			$this->getApplication()->SetTitle(
				!empty($this->arParams['robotName']) ?
					GetMessage("RPA_AUTOMATION_EDITROBOT_TITLE_EDIT") : GetMessage("RPA_AUTOMATION_EDITROBOT_TITLE")
			);
		}

	}

	private function prepareScenarios(array $robotData)
	{
		$urlMan = Rpa\Driver::getInstance()->getUrlManager();
		$stageId = $this->arParams['stage'];
		$this->arResult['AVAILABLE_ROBOTS'] = [];
		$activities = \CBPRuntime::getRuntime()->searchActivitiesByType('rpa_activity', $this->arResult['DOCUMENT_TYPE']);

		foreach ($activities as $activity)
		{
			$editRobotUrl = $urlMan->getAutomationEditRobotUrl($this->arParams["typeId"]);
			$this->arResult['AVAILABLE_ROBOTS'][] = [
				'name' => $activity['NAME'],
				'url' => $editRobotUrl->addParams(['robotType' => $activity['CLASS'], 'stage' => $stageId]),
			];

			if ($activity['CLASS'] === $robotData['Type'])
			{
				$this->arResult['AVAILABLE_ROBOT_CURRENT_NAME'] = $activity['NAME'];
			}
		}
	}

	private function showError($message)
	{
		echo <<<HTML
			<div class="ui-alert ui-alert-danger ui-alert-icon-danger">
				<span class="ui-alert-message">{$message}</span>
			</div>
HTML;

		return;
	}
}
