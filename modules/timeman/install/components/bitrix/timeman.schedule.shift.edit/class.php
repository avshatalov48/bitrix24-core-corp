<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
if (!\Bitrix\Main\Loader::includeModule('timeman'))
{
	return;
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Component\BaseComponent;
use Bitrix\Timeman\Form\Schedule\ShiftForm;
use Bitrix\Timeman\Repository\Schedule\ShiftRepository;
use Bitrix\Timeman\Repository\Schedule\ScheduleRepository;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Service\Schedule\ShiftService;

Loc::loadMessages(__FILE__);

/**
 * Class MainNumeratorEdit
 */
class TimemanScheduleShiftComponent extends BaseComponent
{
	/** @var ShiftRepository $shiftRepository */
	private $shiftRepository;
	/** @var ShiftService $shiftService */
	private $shiftService;
	/** @var ScheduleRepository $scheduleRepository */
	private $scheduleRepository;
	/** @var \Bitrix\Timeman\Security\UserPermissionsManager */
	private $userPermissionsManager;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->shiftRepository = DependencyManager::getInstance()->getShiftRepository();
		$this->scheduleRepository = DependencyManager::getInstance()->getScheduleRepository();
		$this->shiftService = DependencyManager::getInstance()->getShiftService();
		global $USER;
		$this->userPermissionsManager = DependencyManager::getInstance()->getUserPermissionsManager($USER);
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->arResult['SCHEDULE_ID'] = $this->getFromParamsOrRequest($arParams, 'SCHEDULE_ID', 'int');
		$this->arResult['SHIFT_ID'] = $this->getFromParamsOrRequest($arParams, 'SHIFT_ID', 'int');

		return $arParams;
	}

	/** @inheritdoc */
	public function executeComponent()
	{
		$shift = null;
		if (!$this->arResult['SCHEDULE_ID'])
		{
			return $this->showError(Loc::getMessage('TIMEMAN_SHIFT_EDIT_ERROR_SCHEDULE_ID_IS_REQUIRED'));
		}
		if (!$this->userPermissionsManager->canUpdateSchedule($this->arResult['SCHEDULE_ID']))
		{
			return $this->showError(Loc::getMessage('TIMEMAN_SHIFT_EDIT_ERROR_ACCESS_DENIED'));
		}
		if (!$this->arResult['SHIFT_ID'])
		{
			# trying to create
			if (!$this->scheduleRepository->findById($this->arResult['SCHEDULE_ID']))
			{
				return $this->showError(Loc::getMessage('TIMEMAN_ERROR_SCHEDULE_NOT_FOUND'));
			}
			$this->arResult['shiftForm'] = new ShiftForm();
			/** @noinspection PhpVoidFunctionResultUsedInspection */
			return $this->includeComponentTemplate();
		}
		else
		{
			# trying to edit
			$shift = $this->shiftRepository->findByIdAndScheduleId($this->arResult['SHIFT_ID'], $this->arResult['SCHEDULE_ID']);
			if (!$shift)
			{
				return $this->showError(Loc::getMessage('TIMEMAN_SHIFT_EDIT_ERROR_SHIFT_NOT_FOUND'));
			}
		}

		$shiftForm = new ShiftForm($shift);

		$this->arResult['shiftForm'] = $shiftForm;

		$this->includeComponentTemplate();
	}

	private function addError($errorMessage)
	{
		$this->arResult['errorMessages'][] = $errorMessage;
	}

	private function showError($errorMessage)
	{
		$this->addError($errorMessage);
		$this->includeComponentTemplate('error');
	}
}