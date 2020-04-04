<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Error,
	\Bitrix\Main\Loader,
	\Bitrix\Main\ErrorCollection,
	\Bitrix\Main\Localization\Loc;

use \Bitrix\ImOpenlines\Model,
	\Bitrix\ImOpenLines\Tools\Correction,
	\Bitrix\ImOpenlines\Security\Permissions;

Loc::loadMessages(__FILE__);

/**
 * Class CImOpenLinesCorrectionComponent
 */
class CImOpenLinesCorrectionComponent extends CBitrixComponent
{
	/** @var ErrorCollection */
	protected $errors;
	protected $sessionsThatNotShown = false;
	protected $statusClosedSessions = false;
	protected $correctSessionsDataClose = false;
	protected $repairBrokenSessions = false;

	/**
	 * CImOpenLinesCorrectionComponent constructor.
	 * @param $component
	 */
	public function __construct($component)
	{
		@set_time_limit(0);

		parent::__construct($component);

		$this->errors = new ErrorCollection();

		$this->arResult['STATUS_CLOSED_SESSIONS_CORRECTION'] = true;
		$this->arResult['CORRECT_SESSIONS_DATA_CLOSE_CORRECTION'] = true;
		$this->arResult['REPAIR_BROKEN_SESSIONS_CORRECTION'] = true;

		if(check_bitrix_sessid())
		{
			if(!empty($this->request['sessions_that_not_shown']) || !empty($this->request['correction_run_all']))
			{
				$this->sessionsThatNotShown = true;
			}
			if(!empty($this->request['status_closed_sessions']) || !empty($this->request['correction_run_all']))
			{
				$this->statusClosedSessions = true;
			}
			if(!empty($this->request['correct_sessions_data_close']) || !empty($this->request['correction_run_all']))
			{
				$this->correctSessionsDataClose = true;
			}
			if(!empty($this->request['repair_broken_sessions']) || !empty($this->request['correction_run_all']))
			{
				$this->repairBrokenSessions = true;
			}

			if(empty($this->request['status_closed_sessions_correction']) || $this->request['status_closed_sessions_correction'] != 'Y')
			{
				$this->arResult['STATUS_CLOSED_SESSIONS_CORRECTION'] = false;
			}
			if(empty($this->request['correct_sessions_data_close_correction']) || $this->request['correct_sessions_data_close_correction'] != 'Y')
			{
				$this->arResult['CORRECT_SESSIONS_DATA_CLOSE_CORRECTION'] = false;
			}
			if(!empty($this->request['correct_sessions_data_close_select']))
			{
				$this->arResult['CORRECT_SESSIONS_DATA_CLOSE_SELECT'] = false;

				switch (intval($this->request['correct_sessions_data_close_select']))
				{
					case 30:
					case 40:
					case 50:
					case 60:
					case 70:
					case 80:
					case 90:
					case 100:
					$this->arResult['CORRECT_SESSIONS_DATA_CLOSE_SELECT'] = intval($this->request['correct_sessions_data_close_select']);
						break;
				}
			}

			if(empty($this->request['repair_broken_sessions_correction']) || $this->request['repair_broken_sessions_correction'] != 'Y')
			{
				$this->arResult['REPAIR_BROKEN_SESSIONS_CORRECTION'] = false;
			}
			if(!empty($this->request['repair_broken_sessions_correction_select']))
			{
				$this->arResult['REPAIR_BROKEN_SESSIONS_CORRECTION_SELECT'] = false;

				switch (intval($this->request['repair_broken_sessions_correction_select']))
				{
					case 30:
					case 40:
					case 50:
					case 60:
					case 70:
					case 80:
					case 90:
					case 100:
						$this->arResult['REPAIR_BROKEN_SESSIONS_CORRECTION_SELECT'] = intval($this->request['repair_broken_sessions_correction_select']);
						break;
				}
			}
		}
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function checkModules()
	{
		$result = false;

		if(Loader::includeModule('imopenlines'))
		{
			$result = true;
		}
		else
		{
			$this->errors[] = new Error(Loc::getMessage('OL_COMPONENT_MODULE_NOT_INSTALLED'));
		}


		return $result;
	}

	protected function showErrors()
	{
		if(!$this->errors->isEmpty())
		{
			foreach($this->getErrors() as $error)
			{
				ShowError($error->getMessage());
			}
		}
	}

	/**
	 * Returns an array of Error objects.
	 *
	 * @return Error[]
	 */
	protected function getErrors()
	{
		return $this->errors->toArray();
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public static function setSessionsThatNotShown()
	{
		$result = [];

		$raw = Correction::setSessionsThatNotShown();

		foreach ($raw as $item)
		{
			$result[] = $item['SESSION_ID'];
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function setStatusClosedSessions()
	{
		return Correction::setStatusClosedSessions($this->arResult['STATUS_CLOSED_SESSIONS_CORRECTION']);
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function correctSessionsDataClose()
	{
		return Correction::closeOldSession($this->arResult['CORRECT_SESSIONS_DATA_CLOSE_CORRECTION'], $this->arResult['CORRECT_SESSIONS_DATA_CLOSE_SELECT']);
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function repairBrokenSessions()
	{
		return Correction::repairBrokenSessions($this->arResult['REPAIR_BROKEN_SESSIONS_CORRECTION'], $this->arResult['REPAIR_BROKEN_SESSIONS_CORRECTION_SELECT']);
	}

	/**
	 * @return array|bool|mixed
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function executeComponent()
	{
		$result = false;

		if($this->checkModules())
		{
			$permissions = Permissions::createWithCurrentUser();
			if($permissions->canPerform(Permissions::ENTITY_SETTINGS, Permissions::ACTION_MODIFY))
			{
				if($this->sessionsThatNotShown)
				{
					$this->arResult['SESSIONS_THAT_NOT_SHOWN'] = $this->setSessionsThatNotShown();
				}
				if($this->statusClosedSessions)
				{
					$this->arResult['STATUS_CLOSED_SESSIONS'] = $this->setStatusClosedSessions();
				}
				if($this->correctSessionsDataClose)
				{
					$this->arResult['CORRECT_SESSIONS_DATA_CLOSE'] = $this->correctSessionsDataClose();
				}
				if($this->repairBrokenSessions)
				{
					$this->arResult['REPAIR_BROKEN_SESSIONS'] = $this->repairBrokenSessions();
				}

				$this->includeComponentTemplate();

				$result = $this->arResult;
			}
			else
			{
				$this->errors[] = new Error(Loc::getMessage('IMOL_CORRECTION_ACCESS_DENIED'));
			}
		}

		$this->showErrors();

		return $result;
	}
}