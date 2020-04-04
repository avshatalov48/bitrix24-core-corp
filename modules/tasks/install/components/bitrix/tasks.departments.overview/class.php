<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Main\Web\PostDecodeFilter;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksDepartmentsOverviewComponent extends TasksBaseComponent
{
	protected static function checkRequiredModules(array &$arParams, array &$arResult, Error\Collection $errors,
												   array $auxParams = array())
	{
		if (!Loader::includeModule('intranet'))
		{
			$errors->add('INTRANET_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_INTRANET_MODULE_NOT_INSTALLED"));
		}

		return $errors->checkNoFatals();
	}

	protected function checkParameters()
	{
		// todo

		/*
		// sample:
		static::tryParseEnumerationParameter($this->arParams['ENTITY_CODE'], array('TASK', 'TASK_TEMPLATE'), false);
		if(!$this->arParams['ENTITY_CODE'])
		{
			$this->errors->add('INVALID_PARAMETER.ENTITY_CODE', 'Unknown entity code');
		}
		static::tryParseArrayParameter($this->arParams['EXCLUDE']);
		*/

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$departmentIds = $this->getDepartmentsIds();
		$departmentsData = \CIntranetUtils::GetDepartmentsData($departmentIds);



		// todo
		// $this->arResult['COMPONENT_DATA'] // some data related to the component mechanics
		// $this->arResult['AUX_DATA'] // some reference data, data from other modules, etc ...
		// $this->arResult['DATA'] // component primary arResult data
	}

	protected function getDepartmentsIds()
	{
		$request = static::getRequest();
		$departamentId = (int)$request->get('DEP_ID');

		// Start from given department or from user-managed
		if ($departamentId)
		{
			$startFromDepartmentsDraft = array($departamentId);
		}
		else
		{
			// Departments where given user is head
			$startFromDepartmentsDraft = array_unique(
				array_filter(
					array_map(
						'intval',
						Integration\Intranet\Department::getSubordinateIds(
							$this->arParams['USER_ID']
						)
					)
				)
			);
		}

		if (User::isSuper())
		{
			// access to any departments
			$startFromDepartments = $startFromDepartmentsDraft;
		}
		else	// Filter unaccessible departments
		{
			$allAccessibleDepartments = array_unique(
				array_filter(
					array_map(
						'intval',
						Integration\Intranet\Department::getSubordinateIds(
							User::getId(),
							true
						)
					)
				)
			);

			$startFromDepartments = array();
			foreach ($startFromDepartmentsDraft as $departmentId)
			{
				if (in_array($departmentId, $allAccessibleDepartments, true))
				{
					$startFromDepartments[] = $departmentId;
				}
			}
		}

		return $startFromDepartments;
	}


	protected static function getRequest($unEscape = false)
	{
		$request = Context::getCurrent()->getRequest();

		if($unEscape)
		{
			$request->addFilter(new PostDecodeFilter);
		}

		return $request->getQueryList();
	}
}