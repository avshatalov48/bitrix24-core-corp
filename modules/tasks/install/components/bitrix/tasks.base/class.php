<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Dispatcher;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util;

Loc::loadMessages(__FILE__);

abstract class TasksBaseComponent extends CBitrixComponent
{
	///////////////////////////////////
	// Component life cycle functions
	///////////////////////////////////

	const QUERY_TYPE_HIT = 			'hit';
	const QUERY_TYPE_AJAX = 		'ajax';

	protected $componentId = 		'';
	protected $inPageNumber =       0;

	/** @var null|Collection */
	protected $errors = 			null;
	protected $userId = 			false;
	protected $auxParams = 			array();
	protected $returnData =         null;

	protected $dispatcher =         null;
	protected $dispatcherTrigger =  null;

	/**
	 * Component life cycle: page hit entry point
	 * @return \Bitrix\Tasks\Util\Result
	 */
	public function executeComponent()
	{
		$this->inPageNumber = static::incrementComponentPageIndex();
		$this->componentId = md5($this->getSignature());

		if(static::checkTasksModule())
		{
			$this->errors = new Collection();
		}

		$this->processExecutionStart();

		if(static::checkTasksModule())
		{
			$request = static::getRequest();

			$arResult = array();
			$this->auxParams = $this->makeAuxParams();

			static::checkRequiredModules($this->arParams, $arResult, $this->errors, $this->auxParams);

			if($this->errors->checkNoFatals())
			{
				static::checkBasicParameters($this->arParams, $arResult, $this->errors, $this->auxParams);
			}

			if($this->errors->checkNoFatals())
			{
				static::checkPermissions($this->arParams, $arResult, $this->errors, $this->auxParams);
			}

			$this->auxParams['ORIGIN_ARRESULT'] = $arResult;
			$this->translateArResult($arResult);

			if($this->errors->checkNoFatals())
			{
				$this->checkParameters();
			}

            $this->doPreAction();

			if($this->errors->checkNoFatals())
			{
                if(
	                $this->arParams['USE_DISPATCHER']
	                &&
	                Dispatcher::isGloballyEnabled()
	                &&
	                ($todo = static::checkExecuteDispatcher($request, $this->errors, $this->auxParams)) !== false)
                {
                    $todo = $this->processBeforeAction($todo);

	                $plan = new Dispatcher\ToDo\Plan();
	                $plan->import($todo);
	                $this->setDispatcherTrigger($plan);

                    $this->auxParams['REQUEST'] = $request;
	                $this->auxParams['DISPATCHER'] = $this->getDispatcher();
                    $this->arResult['ACTION_RESULT'] = static::dispatch($plan, $this->errors, $this->auxParams, $this->arParams);
                    $this->processAfterAction();
                }
            }

			if($this->errors->checkNoFatals())
			{
				$this->getAllData();
			}

			$this->doPostAction();
		}
		else
		{
			$this->arResult['ERROR'] = array(array('TYPE' => 'FATAL', 'CODE' => 'TASKS_MODULE_NOT_INSTALLED', 'MESSAGE' => Loc::getMessage("TASKS_TB_TASKS_MODULE_NOT_INSTALLED")));
		}

		$this->display();
		$this->processExecutionEnd();

		if($this->returnData !== null)
		{
			$result = new Util\Result();
			$result->setData($this->returnData);

			return $result;
		}
	}

	protected function makeAuxParams()
	{
		return array(
			// you may also do an ajax hit on non-ajax urls, but in general this is not a good idea
			'QUERY_TYPE' => $this->getRequestParameter('AJAX') == '1' ? static::QUERY_TYPE_AJAX : static::QUERY_TYPE_HIT,
			'CLASS_NAME' => static::getComponentClassName(),
			'ID' => $this->getId(),
			'SIGNATURE' => $this->getSignature(),
		);
	}

	protected static function useDispatcherResultObject()
	{
		return false; // by default, for compatibility
	}

	protected function getFilter()
    {

    }

	protected function getOrder()
    {

    }

	protected function getAllData()
	{
		$this->getData();
		$this->getAuxData();
		$this->getReferenceData();
		$this->formatData();
	}

	/**
	 * Component life cycle: ajax hit entry point
	 * @return mixed[]
	 */
	public static function executeComponentAjax(array $arParams = array(), array $behavior = array('DISPLAY' => true))
	{
		$assetHtml = '';
		$behavior = array_merge(array('DISPLAY' => true), $behavior);

		if(static::checkTasksModule())
		{
			global $APPLICATION;

			$errors = new Collection();
			$request = static::getRequestUnescaped();

			$arParams = array_merge(static::extractParamsFromRequest($request), $arParams);
			$arResult = array();
			$auxParams = array(
				'QUERY_TYPE' => static::QUERY_TYPE_AJAX,
				'CLASS_NAME' => static::getComponentClassName(),
			);

			static::checkSiteId($request, $errors); // SITE_ID should be present in request
			if($errors->checkNoFatals())
			{
				static::checkRequiredModules($arParams, $arResult, $errors, $auxParams);
			}
			if($errors->checkNoFatals())
			{
				static::checkBasicParameters($arParams, $arResult, $errors, $auxParams);
			}
			if($errors->checkNoFatals())
			{
				static::checkPermissions($arParams, $arResult, $errors, $auxParams);
			}

			$auxParams['ORIGIN_ARRESULT'] = $arResult;

			$result = array();
			if($errors->checkNoFatals())
			{
				if(($todo = static::checkExecuteDispatcher($request, $errors, $auxParams)) !== false)
				{
					$APPLICATION->showAjaxHead(false, false, false, false); // reset asset list

					if(array_key_exists('RUNTIME_ACTIONS', $behavior) && is_array($behavior['RUNTIME_ACTIONS']))
					{
						$auxParams['RUNTIME_ACTIONS'] = $behavior['RUNTIME_ACTIONS'];
					}

					$plan = new Dispatcher\ToDo\Plan();
					$plan->import($todo);

					$auxParams['REQUEST'] = $request;
					$result = static::dispatch($plan, $errors, $auxParams, $arParams);
					if(is_object($plan))
					{
						$result = $plan ->exportResult();
					}

					if($errors->checkNoFatals())
					{
						$assetHtml = static::getApplicationResources(); // fetch asset list, to see new items appeared
					}
				}
			}

			$errorsArray = $errors->getAll(true);
		}
		else
		{
			$errorsArray = array(array('TYPE' => 'FATAL', 'CODE' => 'TASKS_MODULE_NOT_INSTALLED', 'MESSAGE' => Loc::getMessage("TASKS_TB_TASKS_MODULE_NOT_INSTALLED")));
			$result = array();
		}

		if($behavior['DISPLAY'])
		{
			static::displayAjax($result, $errorsArray, $assetHtml);
		}

		return array($result, $errorsArray);
	}

	public function getDispatcher()
	{
		if(!$this->dispatcher)
		{
			$this->dispatcher = new Dispatcher();
		}

		return $this->dispatcher;
	}

	public function setDispatcherTrigger($trigger)
	{
		$this->dispatcherTrigger = $trigger;
	}

	public function getDispatcherTrigger()
	{
		return $this->dispatcherTrigger;
	}

	protected function getInPageNumber()
	{
		return $this->inPageNumber;
	}

	public function getId()
	{
		return $this->componentId;
	}

	public function getSignature()
	{
		return preg_replace('#[^a-zA-Z0-9]#', '_', ToLower($this->getName().$this->getTemplateName())).'_'.$this->getInPageNumber();
	}

	protected function processExecutionStart()
	{
	}

	protected function processExecutionEnd()
	{
	}

	protected function processBeforeAction($trigger = array())
	{
		return $trigger;
	}

	protected function processAfterAction()
	{
	}

	/**
	 * Allows to pass some of arParams through ajax request, according to the white-list
	 */
	protected static function extractParamsFromRequest($request)
	{
		return array(); // DO NOT simply pass $request to the result, its unsafe
	}

	protected function doPreAction()
	{
		return true;
	}

	/**
	 * Use it if you need to modify arResult in ancestor or do smth else before template show
	 */
	protected function doPostAction()
	{
		$this->arResult['ERROR'] = $this->errors->getAll(true);
		$this->arResult['COMPONENT_DATA']['ID'] = $this->auxParams['ID']; // for queries
		$this->arResult['COMPONENT_DATA']['SIGNATURE'] = $this->auxParams['SIGNATURE']; // for human-readable js
		$this->arResult['COMPONENT_DATA']['QUERY_TYPE'] = $this->auxParams['QUERY_TYPE'];
		$this->arResult['COMPONENT_DATA']['CLASS_NAME'] = $this->auxParams['CLASS_NAME'];

		return true;
	}

	protected function display()
	{
		$this->includeComponentTemplate();
	}

	protected static function displayAjax($data, $errors, $html)
	{
		$result = array(
			'SUCCESS' => empty($errors),
			'ERROR' => $errors,
			'DATA' => $data,
			'ASSET' => $html,
		);

		static::outputJSONResponce($result);
	}

	protected static function outputJSONResponce($result)
	{
		header('Content-Type: application/json');
		print(\Bitrix\Tasks\UI::toJSON($result));
	}

	public static function doFinalActions()
	{
		CMain::FinalActions();
		die();
	}

	protected static function checkExecuteDispatcher($request, Collection $errors, array $auxParams = array())
	{
		if($errors->checkNoFatals())
		{
			$trigger = static::detectDispatchTrigger($request);

			if($trigger && static::checkCSRF($request, $errors))
			{
				if(array_key_exists('ID', $auxParams) && $request['EMITTER'] != '' && $auxParams['ID'] != $request['EMITTER'])
				{
					return false;
				}

				return $trigger;
			}
		}

		return false;
	}

	/**
	 * Fetch all component data here
	 * You can add cached parts here
	 * @return void
	 */
	protected function getData()
	{
	}

	protected function getAuxData()
	{
		$this->arResult['AUX_DATA']['USER'] = array(
			'IS_SUPER' => \Bitrix\Tasks\Util\User::isSuper($this->userId)
		);
	}

	/**
	 * Fetch common data aggregated with getData(): users, gropus from different sources, etc
	 */
	protected function getReferenceData()
	{
	}

	/**
	 * Reformat result data if required
	 */
	protected function formatData()
	{
	}

	protected static function dispatch($batch, Collection $errors, array $auxParams = array(), array $arParams = array())
	{
		$useObject = static::useDispatcherResultObject();

		if(!is_array($batch) && !Dispatcher\ToDo\Plan::isA($batch))
		{
			return $useObject ? new Dispatcher\ToDo\Plan() : array();
		}

		if(Dispatcher::isA($auxParams['DISPATCHER']))
		{
			$dispatcher = $auxParams['DISPATCHER'];
		}
		else
		{
			$dispatcher = new Dispatcher();
		}
		$dispatcher->addRuntimeActions($auxParams['RUNTIME_ACTIONS']);

		$className = ToLower(static::getComponentClassName());

		if(is_array($batch))
		{
			$batchArray = $batch;

			$batch = new Dispatcher\ToDo\Plan();
			$batch->import($batchArray);
		}

		$batch->replaceThis($className); // scan batch for "this.***" operations

		$result = $dispatcher->run($batch); // run all but exclude template runtimes

		if(!$result->isSuccess() || $result->getErrors()->checkHasErrorOfType(Dispatcher::ERROR_TYPE_PARSE))
		{
			$errors->load($result->getErrors()->transform(array('TYPE' => Util\Error::TYPE_WARNING)));
			if($useObject)
			{
				return $batch;
			}
			else
			{
				return array();
			}
		}
		else
		{
			if($useObject)
			{
				return $batch;
			}
			else
			{
				return $batch->exportResult();
			}
		}
	}

	protected static function checkTasksModule()
	{
		return Loader::includeModule('tasks');
	}

	protected static function checkSiteId($request, Collection $errors)
	{
		$siteId = static::extractSiteId($request);

		if((string) $siteId == '')
		{
			$errors->add('NO_SITE_ID', 'SITE_ID was not provided. There may be troubles with server-side API', Collection::TYPE_WARNING);
			return true;
		}
		$siteId = trim($siteId);

		if(!preg_match('#^[a-zA-Z0-9]{2}$#', $siteId))
		{
			$errors->add('SITE_ID_INVALID', 'SITE_ID is not valid');
			return false;
		}

		return true;
	}

	/**
	 * Function checks if required modules installed. If not, throws an exception
	 * @throws Exception
	 * @return bool
	 */
	protected static function checkRequiredModules(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		return $errors->checkNoFatals();
	}

	/**
	 * Function checks and prepares only the basic parameters passed
	 */
	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		return $errors->checkNoFatals();
	}

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return boolean
	 */
	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		$userId = static::getEffectiveUserId($arParams);

		if(!$userId)
		{
			$errors->add('USER_NOT_DEFINED', 'Can not identify current user');
		}
		else
		{
			$arResult['USER_ID'] = static::checkUserRestrictions($userId, $errors);
		}

		if (!CBXFeatures::IsFeatureEnabled('Tasks'))
		{
			$errors->add('TASKS_MODULE_NOT_AVAILABLE', Loc::getMessage("TASKS_TB_TASKS_MODULE_NOT_AVAILABLE"));
		}

		$arResult['COMPONENT_DATA']['MODULES']['bitrix24'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');
		$arResult['COMPONENT_DATA']['MODULES']['mail'] = (
			\Bitrix\Main\ModuleManager::isModuleInstalled('mail')
			&& (
				!Loader::includeModule('bitrix24')
				|| \CBitrix24::isEmailConfirmed()
			)
		);

		return $errors->checkNoFatals();
	}

	protected static function getEffectiveUserId($arParams)
	{
		return \Bitrix\Tasks\Util\User::getId();
	}

	protected static function checkUserRestrictions($userId, Collection $errors)
	{
		if($userId == \Bitrix\Tasks\Util\User::getId()) // if the effective user equals to the current user, check if authorized
		{
			if(!\Bitrix\Tasks\Util\User::get()->isAuthorized())
			{
				$errors->add('USER_NOT_AUTHORIZED', Loc::getMessage("TASKS_TB_USER_NOT_AUTHORIZED"));
				return false;
			}
		}

		return $userId;
	}

	/**
	 * Function checks and prepares all the parameters passed
	 */
	protected function checkParameters()
	{
		static::tryParseIntegerParameter($this->arParams['USER_ID'], $this->userId);
		static::tryParseBooleanParameter($this->arParams['USE_DISPATCHER'], true);

		return $this->errors->checkNoFatals();
	}

	/**
	 * Allows to decide which data shoult pass to $this->arResult, and which should not
	 * @param mixed[] $arResult
	 */
	protected function translateArResult($arResult)
	{
		$this->userId = $arResult['USER_ID']; // a short-cut to current user`s ID
		unset($arResult['USER_ID']);

		static::tryParseIntegerParameter($this->arParams['USER_ID'], $this->userId);

		$this->arResult = array_merge($this->arResult, $arResult); // default action: merge to $this->arResult
	}

	/**
	 * Check conditions on which the component starts to show interest to the current request. 
	 * There could be some general conditions besides the main dispatching process.
	 * @param mixed[] $request
	 * @return boolean
	 */
	protected static function detectDispatchTrigger($request)
	{
		$method = Context::getCurrent()->getServer()->getRequestMethod();

		if($method == 'POST' && !empty($request['ACTION']) && is_array($request['ACTION']))
		{
			return $request['ACTION'];
		}

		return false;
	}

	protected static function extractCSRF($request)
	{
		return $request['sessid'];
	}

	protected static function extractSiteId($request)
	{
		return $request['SITE_ID'];
	}

	protected static function checkCSRF($request, Collection $errors)
	{
		$csrf = static::extractCSRF($request);

		if((string) $csrf == '')
		{
			$errors->add('CSRF_ABSENT', 'CSRF token was not provided');
			return false;
		}
		elseif(\bitrix_sessid() != $csrf)
		{
			$errors->add('CSRF_FAIL', 'CSRF token is not valid');
			return false;
		}

		return true;
	}

	protected function getParameter($name)
	{
		return $this->arParams[static::getParameterAlias($name)];
	}

	protected static function getParameterAlias($name)
	{
		return $name;
	}

	protected static function getRequest($unEscape = false)
	{
		$request = Context::getCurrent()->getRequest();

		if($unEscape)
		{
			$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);
		}

		return $request->getPostList();
	}

	public static function getComponentClassName()
	{
		return get_called_class();
	}

	////////////////////////////////////////////////////
	// Auxiliary data getters, can be included on-demand
	////////////////////////////////////////////////////

	protected static function getCompanyWorkTime($default = false)
	{
		return $default ? Calendar::getDefaultSettings() : Calendar::getSettings();
	}

	protected static function getUserFields($entityId = 0, $entityName = 'TASKS_TASK')
	{
		return $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($entityName, $entityId, LANGUAGE_ID);
	}

	/**
	 * @param array $groupIds
	 * @return array
	 * @deprecated
	 */
	protected static function getGroupsData(array $groupIds)
	{
		return \Bitrix\Tasks\Integration\SocialNetwork\Group::getData($groupIds);
	}

	////////////////////////////
	// Parameter parse functions
	////////////////////////////

	public function findParameterValue($parameter)
	{
		$parameter = trim((string) $parameter);

		if($parameter != '')
		{
			$c = $this;
			while($c)
			{
				if(is_array($c->arParams) && array_key_exists($parameter, $c->arParams))
				{
					return $c->arParams[$parameter];
				}

				$c = $c->__parent;
			}
		}

		return null;
	}

	/**
	 * Function forces 'Y'/'N' value to boolean
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseBooleanParameter(&$fld, $default = false)
	{
		// Y or N
		if($fld === 'Y' || $fld === 'N')
		{
			$fld = $fld === 'Y';
			return $fld;
		}

		// true or false
		if($fld === true || $fld === false)
		{
			return $fld;
		}

		// 1 or 0
		if($fld === '1' || $fld === '0')
		{
			$fld = $fld === '1';
			return $fld;
		}

		// numeric 1 or 0
		if($fld === 1 || $fld === 0)
		{
			$fld = $fld === 1;
			return $fld;
		}

		$fld = $default;
		return $fld;
	}

	/**
	 * Function processes parameter value by white list, if gets null, passes the first value in white list
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseListParameter(&$fld, $list = array())
	{
		if(!in_array($fld, $list))
		{
			$fld = current($list);
		}

		return $fld;
	}

	/**
	 * Function reduces input value to integer type, and, if gets null, passes the default value
	 * @param mixed $fld Field value
	 * @param int $default Default value
	 * @param int $allowZero Allows zero-value of the parameter
	 * @return int Parsed value
	 */
	public static function tryParseIntegerParameter(&$fld, $default = false, $allowZero = false)
	{
		$fld = intval($fld);
		if(!$allowZero && !$fld && $default !== false)
		{
			$fld = $default;
		}
			
		return $fld;
	}

	public static function tryParseNonNegativeIntegerParameter(&$fld, $default = false)
	{
		$fld = isset($fld) ? abs(intval($fld)) : ($default !== false ? $default : 0);

		return $fld;
	}

	/**
	 * Function processes string value and, if gets null, passes the default value to it
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseStringParameter(&$fld, $default = false)
	{
		$fld = trim((string)$fld);
		if((string) $fld == '' && $default !== false)
		{
			$fld = $default;
		}

		$fld = htmlspecialcharsbx($fld);

		return $fld;
	}

	/**
	 * Function processes string value and, if gets null, passes the default value to it
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseURIParameter(&$fld, $default = false, $secure = true, $escape = true)
	{
		$fld = trim((string)$fld);
		if((string) $fld == '' && $default !== false)
		{
			$fld = $default;
		}

		if(!$fld)
		{
			return $fld;
		}

		if($escape)
		{
			$fld = htmlspecialcharsbx($fld);
		}

		return Util::secureBackUrl($fld);
	}

	/**
	 * Function processes string value and, if gets null, passes the default value to it
	 * @param mixed $fld Field value
	 * @param string $default Default value
	 * @return string parsed value
	 */
	public static function tryParseStringParameterStrict(&$fld, $default = false)
	{
		$fld = trim((string)$fld);
		if((string) $fld == '' && $default !== false)
		{
			$fld = $default;
		}

		$fld = preg_replace('#[^a-z0-9_-]#i', '', $fld);

		return $fld;
	}

	public static function tryParseArrayParameter(&$fld, $default = array())
	{
		if(!is_array($fld))
		{
			$fld = $default;
		}

		return $fld;
	}

	/**
	* When not a part of enumeration assign default.
	*/
	public static function tryParseEnumerationParameter(&$fld, array $enum, $default = false)
	{
		if(!in_array($fld, $enum))
		{
			$fld = $default;
		}

		return $fld;
	}

	////////////////////////////
	// Helper functions
	////////////////////////////

	private static function incrementComponentPageIndex()
	{
		static $pageIndexes = 0;

		$pageIndexes++;

		return $pageIndexes;
	}

	protected function getRequestParameter($name)
	{
		$value = false;
		if($this->request['EMITTER'] == $this->getId())
		{
			return isset($this->request[$name]) ? $this->request[$name] : false;
		}

		return $value;
	}

	protected static function cleanTaskData(&$data)
	{
		//unset($data['CREATED_BY_NAME']);
		//unset($data['CREATED_BY_LAST_NAME']);
		//unset($data['CREATED_BY_SECOND_NAME']);
		//unset($data['CREATED_BY_LOGIN']);
		unset($data['CREATED_BY_WORK_POSITION']);
		unset($data['CREATED_BY_PHOTO']);

		//unset($data['RESPONSIBLE_NAME']);
		//unset($data['RESPONSIBLE_LAST_NAME']);
		//unset($data['RESPONSIBLE_SECOND_NAME']);
		//unset($data['RESPONSIBLE_LOGIN']);
		unset($data['RESPONSIBLE_WORK_POSITION']);
		unset($data['RESPONSIBLE_PHOTO']);
	}

	protected static function getRequestUnescaped()
	{
		CUtil::JSPostUnescape();

		return static::getRequest(true);
	}

	public static function getAllowedMethods()
	{
		return array();
	}

	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Returns a list of browser assets that are in the application list currently
	 *
	 * @return array
	 */
	private static function getApplicationResources()
	{
		global $APPLICATION;

		$result = static::parseResourceString($APPLICATION->getHeadScripts());
		$result = array_merge($result, static::parseResourceString($APPLICATION->getCSS()));
		$result = array_merge($result, static::parseResourceString($APPLICATION->getHeadScripts()));

		return array_unique($result);
	}

	/**
	 * Parse a resource asset bunch
	 *
	 * @param $string
	 * @return array
	 */
	private static function parseResourceString($string)
	{
		$result = array();
		$string = preg_split("/\\r\\n|\\r|\\n/", $string);
		foreach($string as $v)
		{
			$v = trim((string) $v);
			if($v !== '')
			{
				$result[] = $v;
			}
		}

		return array_unique($result);
	}

	/**
	 * @return null
	 *
	 * @deprecated Bad name
	 */
	public function getErrorCollection()
	{
		return $this->errors;
	}

    /**
     * Redefined if need in components
     *
     * @return array
     */
	public function getDefaultPathsPatterns()
    {
        return array(
            'TASKS' => '/company/personal/user/#user_id#/tasks/',
            'TASK_ACTION' => '/company/personal/user/#user_id#/tasks/task/#action#/#task_id#/',

            'TASK_GROUP' => '/workgroups/group/#group_id#/tasks/',
            'TASK_GROUP_ACTION' => '/workgroups/group/#group_id#/tasks/task/#action#/#task_id#/',

            'TEMPLATES' => '/company/personal/user/#user_id#/tasks/templates/',
            'TEMPLATE_ACTION' => '/company/personal/user/#user_id#/tasks/templates/',

            'WIDGET' => '',
            'REPORT' =>  '',
            'PROJECTS' => '/company/personal/user/#user_id#/tasks/projects/',
            'PLAN' => '',

            'VIEW_LIST' =>  '',
            'VIEW_KANBAN' =>  '',
            'VIEW_GANTT' =>  '',
        );
    }
}