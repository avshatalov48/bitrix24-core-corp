<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Errorable;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Tasks\Internals\Project\Pull\PullDictionary;
use Bitrix\Tasks\Internals\Registry\UserRegistry;
use Bitrix\Tasks\Util\User;

class TasksInterfaceCountersComponent extends \CBitrixComponent
	implements Controllerable, Errorable
{
	const ERROR_UNKNOWN_SYSTEM_ERROR = 'TASKS_TIC_01';

	private $errorCollection;

	/**
	 * TasksInterfaceCountersComponent constructor.
	 * @param null $component
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new ErrorCollection();
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @param string $code
	 * @return \Bitrix\Main\Error|null
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @return array|\Bitrix\Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 *
	 */
	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	/**
	 * @param $params
	 * @return array
	 */
	public function onPrepareComponentParams($params)
	{
		$params['GROUP_ID'] = (isset($params['GROUP_ID']) && is_numeric($params['GROUP_ID'])) ? (int)$params['GROUP_ID'] : 0;
		$params['TARGET_USER_ID'] = (isset($params['USER_ID']) && is_numeric($params['USER_ID'])) ? (int)$params['USER_ID'] : 0;
		$params['COUNTERS'] = (isset($params['COUNTERS']) && is_array($params['COUNTERS'])) ? $params['COUNTERS'] : [];
		$params['ROLE'] = isset($params['ROLE']) ? $params['ROLE'] : null;

		return $params;
	}

	/**
	 * @return mixed|void|null
	 */
	public function executeComponent()
	{
		try
		{
			$this->checkModules();
			$this->init();
			$this->loadData();
			$this->doPostAction();

			$this->includeComponentTemplate('toolbar');
		}
		catch (SystemException $exception)
		{
			$this->includeErrorTemplate($exception->getMessage());
		}
	}

	protected function listKeysSignedParameters()
	{
		return [
			'FILTER_FIELD',
		];
	}

	/**
	 *
	 */
	public function getCountersAction()
	{
		try
		{
			$this->checkModules();
			$this->initFromRequest();
			$this->init();
			$this->loadData();
		}
		catch (SystemException $exception)
		{
			$this->setError(Loc::getMessage('TASKS_COUNTERS_SYSTEM_ERROR'), [], $exception);
		}

		return $this->arResult['COUNTERS'];
	}

	/**
	 *
	 */
	private function initFromRequest()
	{
		$request = $this->request->toArray();

		if (array_key_exists('groupId', $request))
		{
			$this->arParams['GROUP_ID'] = (int)$request['groupId'];
		}
		if (array_key_exists('counters', $request) && is_array($request['counters']))
		{
			$this->arParams['COUNTERS'] = $request['counters'];
		}
		if (array_key_exists('role', $request) && is_scalar($request['role']))
		{
			$this->arParams['ROLE'] = (string)$request['role'];
		}
	}

	/**
	 * @param string $inputMessage
	 * @param Error[] $errors
	 * @param Exception|null $exception
	 */
	private function setError(string $inputMessage, array $errors = [], Exception $exception = null): void
	{
		$this->errorCollection->setError(new \Bitrix\Main\Error($inputMessage, $this->getFirstErrorCode($errors)));
	}

	/**
	 * @param Error[] $errors
	 */
	private function getFirstErrorCode(array $errors): string
	{
		foreach ($errors as $error)
		{
			return (string) $error->getCode();
		}
		return self::ERROR_UNKNOWN_SYSTEM_ERROR;
	}

	private function isMyTaskList(): bool
	{
		return ($this->arParams['USER_ID'] === $this->arParams['TARGET_USER_ID']);
	}

	private function isUserTaskList(): bool
	{
		$foreignCounters = [
			CounterDictionary::COUNTER_SONET_FOREIGN_EXPIRED,
			CounterDictionary::COUNTER_SONET_FOREIGN_COMMENTS,
			CounterDictionary::COUNTER_SCRUM_FOREIGN_COMMENTS,
		];
		$currentCounters = array_keys($this->arResult['COUNTERS']);

		return (count(array_intersect($currentCounters, $foreignCounters)) === 0);
	}

	private function isProjectsTaskList(): bool
	{
		return ($this->arParams['GROUP_ID'] > 0);
	}

	private function isProjectList(): bool
	{
		return (!$this->isUserTaskList() && !$this->isProjectsTaskList());
	}

	/**
	 * @throws SystemException
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	private function loadData()
	{
		$counterProvider = Counter::getInstance($this->arParams['TARGET_USER_ID']);

		$this->arResult['COUNTERS'] = [];
		foreach ($this->arParams['COUNTERS'] as $counter)
		{
			$value = 0;
			$roleCounter = $this->getCounterByRole($counter, $this->arParams['ROLE']);
			if ($roleCounter)
			{
				$value = $counterProvider->get($roleCounter, $this->arParams['GROUP_ID']);
			}
			$this->arResult['COUNTERS'][$counter] = [
				'VALUE' => $value,
				'FILTER_FIELD' => $this->arParams['FILTER_FIELD'],
				'FILTER_VALUE' => $this->getFilterValue($counter),
				'STYLE' => $this->getCounterStyle($counter, $value)
			];
		}
	}

	/**
	 * @param string $counter
	 * @param int $value
	 * @return string
	 */
	private function getCounterStyle(string $counter, int $value): string
	{
		if (in_array($counter, [
			CounterDictionary::COUNTER_EXPIRED,
			CounterDictionary::COUNTER_MY_EXPIRED,
			CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED,
			CounterDictionary::COUNTER_ORIGINATOR_EXPIRED,
			CounterDictionary::COUNTER_AUDITOR_EXPIRED,
			CounterDictionary::COUNTER_PROJECTS_TOTAL_EXPIRED,
			CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED,
			CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED,
			CounterDictionary::COUNTER_FLOW_TOTAL_EXPIRED,
		]))
		{
			return Counter\Template\CounterStyle::STYLE_RED;
		}

		if (in_array($counter, [
			CounterDictionary::COUNTER_NEW_COMMENTS,
			CounterDictionary::COUNTER_MY_NEW_COMMENTS,
			CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS,
			CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS,
			CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS,
			CounterDictionary::COUNTER_PROJECTS_TOTAL_COMMENTS,
			CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS,
			CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS,
			CounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS,
			CounterDictionary::COUNTER_FLOW_TOTAL_COMMENTS,
		]))
		{
			return Counter\Template\CounterStyle::STYLE_GREEN;
		}

		if (
			$value
			&& $counter === CounterDictionary::COUNTER_GROUP_EXPIRED
			&& UserRegistry::getInstance((int)$this->arParams['TARGET_USER_ID'])->isGroupAdmin((int)$this->arParams['GROUP_ID'])
		)
		{
			return Counter\Template\CounterStyle::STYLE_RED;
		}

		return Counter\Template\CounterStyle::STYLE_GRAY;
	}

	private function getFilterValue(string $counter): string
	{
		$map = [
			// my task's list
			CounterDictionary::COUNTER_EXPIRED => Counter\Type::TYPE_EXPIRED,
			CounterDictionary::COUNTER_MY_EXPIRED => Counter\Type::TYPE_EXPIRED,
			CounterDictionary::COUNTER_ORIGINATOR_EXPIRED => Counter\Type::TYPE_EXPIRED,
			CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED => Counter\Type::TYPE_EXPIRED,
			CounterDictionary::COUNTER_AUDITOR_EXPIRED => Counter\Type::TYPE_EXPIRED,
			CounterDictionary::COUNTER_NEW_COMMENTS => Counter\Type::TYPE_NEW_COMMENTS,
			CounterDictionary::COUNTER_MY_NEW_COMMENTS => Counter\Type::TYPE_NEW_COMMENTS,
			CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS => Counter\Type::TYPE_NEW_COMMENTS,
			CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS => Counter\Type::TYPE_NEW_COMMENTS,
			CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS => Counter\Type::TYPE_NEW_COMMENTS,
			// group task's list
			CounterDictionary::COUNTER_GROUP_EXPIRED => Counter\Type::TYPE_PROJECT_EXPIRED,
			CounterDictionary::COUNTER_GROUP_COMMENTS => Counter\Type::TYPE_PROJECT_NEW_COMMENTS,
			// project's list
			CounterDictionary::COUNTER_PROJECTS_TOTAL_EXPIRED => 'EXPIRED',
			CounterDictionary::COUNTER_PROJECTS_TOTAL_COMMENTS => 'NEW_COMMENTS',
			CounterDictionary::COUNTER_PROJECTS_FOREIGN_EXPIRED => 'PROJECT_EXPIRED',
			CounterDictionary::COUNTER_PROJECTS_FOREIGN_COMMENTS => 'PROJECT_NEW_COMMENTS',
			CounterDictionary::COUNTER_GROUPS_TOTAL_EXPIRED => 'EXPIRED',
			CounterDictionary::COUNTER_GROUPS_TOTAL_COMMENTS => 'NEW_COMMENTS',
			CounterDictionary::COUNTER_GROUPS_FOREIGN_EXPIRED => 'PROJECT_EXPIRED',
			CounterDictionary::COUNTER_GROUPS_FOREIGN_COMMENTS => 'PROJECT_NEW_COMMENTS',
			CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED => 'EXPIRED',
			CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS => 'NEW_COMMENTS',
			CounterDictionary::COUNTER_SONET_FOREIGN_EXPIRED => 'PROJECT_EXPIRED',
			CounterDictionary::COUNTER_SONET_FOREIGN_COMMENTS => 'PROJECT_NEW_COMMENTS',
			// scrum's list
			CounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS => 'NEW_COMMENTS',
			CounterDictionary::COUNTER_SCRUM_FOREIGN_COMMENTS => 'PROJECT_NEW_COMMENTS',
			// flow
			CounterDictionary::COUNTER_FLOW_TOTAL_COMMENTS => Counter\Type::TYPE_NEW_COMMENTS,
			CounterDictionary::COUNTER_FLOW_TOTAL_EXPIRED => Counter\Type::TYPE_EXPIRED,
		];

		return (array_key_exists($counter, $map) ? (string)$map[$counter] : '');
	}

	/**
	 * @return array
	 */
	private function getDefaultCounters(): array
	{
		$defaultCounters = [
			CounterDictionary::COUNTER_EXPIRED,
			CounterDictionary::COUNTER_NEW_COMMENTS,
			CounterDictionary::COUNTER_MUTED_NEW_COMMENTS,
		];

		if ($this->arParams['GROUP_ID'] > 0 && Counter::isSonetEnable())
		{
			$defaultCounters[] = CounterDictionary::COUNTER_GROUP_EXPIRED;
			$defaultCounters[] = CounterDictionary::COUNTER_GROUP_COMMENTS;
		}

		return $defaultCounters;
	}

	/**
	 * @throws SystemException
	 */
	private function checkModules()
	{
		try
		{
			if (!Loader::includeModule('tasks'))
			{
				throw new SystemException(Loc::getMessage('TASKS_COUNTERS_SYSTEM_ERROR_INCLUDE_MODULE'));
			}
		}
		catch (LoaderException $exception)
		{
			throw new SystemException(Loc::getMessage('TASKS_COUNTERS_SYSTEM_ERROR_INCLUDE_MODULE'));
		}
	}

	/**
	 * @param string $errorMessage
	 * @param string $code
	 */
	private function includeErrorTemplate(string $errorMessage, string $code = ''): void
	{
		$this->arResult['ERROR'] = $errorMessage;
		$this->arResult['ERROR_CODE'] = ($code ?: self::ERROR_UNKNOWN_SYSTEM_ERROR);

		$this->includeComponentTemplate('error');
	}

	/**
	 *
	 */
	private function init()
	{
		$this->arParams['USER_ID'] = User::getId();

		if (!$this->arParams['TARGET_USER_ID'])
		{
			$this->arParams['TARGET_USER_ID'] = $this->arParams['USER_ID'];
		}
		if (!$this->arParams['TARGET_USER_ID'])
		{
			throw new SystemException(Loc::getMessage('TASKS_COUNTERS_SYSTEM_ERROR'));
		}

		if (empty($this->arParams['COUNTERS']))
		{
			$this->arParams['COUNTERS'] = $this->getDefaultCounters();
		}

		if (!is_array($this->arParams['COUNTERS']))
		{
			throw new SystemException(Loc::getMessage('TASKS_COUNTERS_SYSTEM_ERROR'));
		}

		$this->arParams['COUNTERS'] = $this->prepareCounters();

		if (is_null($this->arParams['ROLE']))
		{
			$this->arParams['ROLE'] = Counter\Role::ALL;
		}
	}

	private function prepareCounters()
	{
		$counters = $this->arParams['COUNTERS'];

		if (!Counter::isSonetEnable())
		{
			$counters = array_diff($counters, CounterDictionary::MAP_SONET_OTHER);
		}

		return $counters;
	}

	/**
	 * @param string $counter
	 * @param string $role
	 * @return string
	 */
	private function getCounterByRole(string $counter, string $role): ?string
	{
		if ($role === Counter\Role::ALL)
		{
			return $counter;
		}

		$nonRolesCounters = [
			CounterDictionary::COUNTER_GROUP_EXPIRED,
			CounterDictionary::COUNTER_GROUP_COMMENTS
		];

		if (in_array($counter, $nonRolesCounters))
		{
			return null;
		}

		$map = [
			CounterDictionary::COUNTER_EXPIRED => [
				Counter\Role::RESPONSIBLE => CounterDictionary::COUNTER_MY_EXPIRED,
				Counter\Role::ACCOMPLICE => CounterDictionary::COUNTER_ACCOMPLICES_EXPIRED,
				Counter\Role::ORIGINATOR => CounterDictionary::COUNTER_ORIGINATOR_EXPIRED,
				Counter\Role::AUDITOR => CounterDictionary::COUNTER_AUDITOR_EXPIRED,
			],
			CounterDictionary::COUNTER_NEW_COMMENTS => [
				Counter\Role::RESPONSIBLE => CounterDictionary::COUNTER_MY_NEW_COMMENTS,
				Counter\Role::ACCOMPLICE => CounterDictionary::COUNTER_ACCOMPLICES_NEW_COMMENTS,
				Counter\Role::ORIGINATOR => CounterDictionary::COUNTER_ORIGINATOR_NEW_COMMENTS,
				Counter\Role::AUDITOR => CounterDictionary::COUNTER_AUDITOR_NEW_COMMENTS,
			],
			CounterDictionary::COUNTER_MUTED_NEW_COMMENTS => [
				Counter\Role::RESPONSIBLE => CounterDictionary::COUNTER_MY_MUTED_NEW_COMMENTS,
				Counter\Role::ACCOMPLICE => CounterDictionary::COUNTER_ACCOMPLICES_MUTED_NEW_COMMENTS,
				Counter\Role::ORIGINATOR => CounterDictionary::COUNTER_ORIGINATOR_MUTED_NEW_COMMENTS,
				Counter\Role::AUDITOR => CounterDictionary::COUNTER_AUDITOR_MUTED_NEW_COMMENTS,
			],
		];

		if (
			!isset($map[$counter])
			|| !isset($map[$counter][$role])
		)
		{
			return $counter;
		}

		return $map[$counter][$role];
	}

	private function doPostAction(): void
	{
		if (!Loader::includeModule('pull'))
		{
			return;
		}
		if (!$this->request->isAjaxRequest() && ($this->isProjectsTaskList() || $this->isProjectList()))
		{
			$tag = PullDictionary::PULL_PROJECTS_TAG;

			if ($this->isProjectsTaskList())
			{
				$tag .= "_{$this->arParams['GROUP_ID']}";
			}

			\CPullWatch::Add($this->arParams['USER_ID'], $tag, true);
		}
	}
}