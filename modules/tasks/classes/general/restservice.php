<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Tasks\Integration\Rest\Exception\RestException;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Util\User;

if(!CModule::IncludeModule('rest'))
{
	return;
}

/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */
final class CTaskRestService extends IRestService
{
	const SCOPE_NAME_NORMAL     = 'task';
	const SCOPE_NAME_EXTENDED   = 'tasks_extended';
	const TASKS_LIMIT_PAGE_SIZE = 50;	// Max nPageSize (NAV_PARAMS) for GetLists()
	const TASKS_LIMIT_TOP_COUNT = 50;	// Max nTopCount (NAV_PARAMS) for GetLists()

	// private const
	const delimiter = '____x____';

	// Must be lower-case list
	// base classes rotes here (actually, root-classes)
	private static $arAllowedClasses = array(
		'ctaskitem',
		'ctaskelapseditem',
		'ctaskcommentitem',
		'ctaskchecklistitem',
		'ctaskplannermaintance',
		'ctasklogitem',
		//'\bitrix\tasks\integration\bizproc\automation\rest',

		/*deprecated*/
		'ctaskcomments',
		'ctasks'
	);
	// additional classes (may be sub-classes of a base class or even specific class routes)
	private static $allowedSpecialClasses = array();

	// will be inited later:
	private static $inited = false;
	private static $arMethodsMetaInfo = array();
	private static $arAllowedMethods;
	private static $arClassAliases = array();
	private static $arManifests = array();

	const PLACEMENT_TASK_VIEW_TAB = 'TASK_VIEW_TAB';
	const PLACEMENT_TASK_VIEW_SIDEBAR = 'TASK_VIEW_SIDEBAR';
	const PLACEMENT_TASK_VIEW_TOP_PANEL = 'TASK_VIEW_TOP_PANEL';
	const PLACEMENT_TASK_VIEW_MENU_ADD = 'TASK_VIEW_MENU_ADD';

	const PLACEMENT_TASK_TOP_MENU = 'TASK_TOP_MENU';
	const PLACEMENT_TASK_LIST_CONTEXT_MENU = 'TASK_LIST_CONTEXT_MENU';
	const PLACEMENT_TASK_VIEW_MODE = 'TASK_VIEW_MODE';

	/**
	 * @return array
	 */
	public static function onRestServiceBuildDescription()
	{
		if (!self::$inited)
		{
			self::_init();
		}

		$arFunctionsMap = [
			CRestUtil::EVENTS     => [
				'OnTaskAdd'           => ['tasks', 'OnTaskAdd', ['CTaskRestService', 'onEventFilter']],
				'OnTaskUpdate'        => ['tasks', 'OnTaskUpdate', ['CTaskRestService', 'onEventFilter']],
				'OnTaskDelete'        => ['tasks', 'OnTaskDelete', ['CTaskRestService', 'onEventFilter']],

				// task comments
				'OnTaskCommentAdd'    => ['tasks', 'OnAfterCommentAdd', ['CTaskCommentItem', 'onEventFilter']],
				'OnTaskCommentUpdate' => ['tasks', 'OnAfterCommentUpdate', ['CTaskCommentItem', 'onEventFilter']],
				'OnTaskCommentDelete' => ['tasks', 'OnAfterCommentDelete', ['CTaskCommentItem', 'onEventFilter']],
			],
			CRestUtil::PLACEMENTS => [
				self::PLACEMENT_TASK_TOP_MENU          => [],
				self::PLACEMENT_TASK_LIST_CONTEXT_MENU => [],
				self::PLACEMENT_TASK_VIEW_MODE         => [],
				self::PLACEMENT_TASK_VIEW_TAB          => [
					'options' => [
						'groupId' => 'string',
					]
				],
				self::PLACEMENT_TASK_VIEW_SIDEBAR      => [
					'options' => [
						'groupId' => 'string',
					]
				],
				self::PLACEMENT_TASK_VIEW_TOP_PANEL	   => [],
				self::PLACEMENT_TASK_VIEW_MENU_ADD	   => [],

			]
		];

		foreach (self::$arAllowedMethods as $className => $arMethods)
		{
			$aliasClassName = null;
			if (isset(self::$arClassAliases[$className]))
			{
				$aliasClassName = self::$arClassAliases[$className];
			}

			foreach ($arMethods as $methodName)
			{
				$aliasMethodName = null;
				if (isset(self::$arMethodsMetaInfo[$className][$methodName]['alias']))
				{
					$aliasMethodName = self::$arMethodsMetaInfo[$className][$methodName]['alias'];
				}

				$transitMethodName = $className.self::delimiter.$methodName;

				$arPublicNames = ['task.'.$className.'.'.$methodName];

				if ($aliasMethodName !== null)
				{
					$arPublicNames[] = 'task.'.$className.'.'.$aliasMethodName;
				}

				// Is class alias exists?
				if ($aliasClassName !== null)
				{
					$arPublicNames[] = 'task.'.$aliasClassName.'.'.$methodName;

					if ($aliasMethodName !== null)
					{
						$arPublicNames[] = 'task.'.$aliasClassName.'.'.$aliasMethodName;
					}
				}

				foreach ($arPublicNames as $publicMethodName)
				{
					$arFunctionsMap[$publicMethodName] = ['CTaskRestService', $transitMethodName];
				}
			}
		}

		// additional routing rules, for "subclasses"
		foreach (self::$allowedSpecialClasses as $className => $subclass)
		{
			$methods = self::$arMethodsMetaInfo[$className];

			if (is_array($methods))
			{
				foreach ($methods as $methodName => $methodDesc)
				{
					// query like "task.item.userfield.add"
					$publicMethodName = implode(
						'.',
						[
							'task',
							$subclass['publicParentClassName'],
							$subclass['publicClassName'],
							$methodName
						]
					);
					// goes to "\Bitrix\Tasks\Rest\Item\UserField::add()"
					$transitMethodName = implode(self::delimiter, [$className, $methodName]);

					$arFunctionsMap[$publicMethodName] = ['CTaskRestService', $transitMethodName];
				}
			}
		}

		return [
			self::SCOPE_NAME_NORMAL   => $arFunctionsMap,
			self::SCOPE_NAME_EXTENDED => [
				'tasks_extended.meta.setAnyStatus'  => ['CTaskRestService', 'tasks_extended_meta_setAnyStatus'],
				'tasks_extended.meta.occurInLogsAs' => ['CTaskRestService', 'tasks_extended_meta_occurInLogsAs']
			]
		];
	}


	public static function tasks_extended_meta_occurInLogsAs($args)
	{
		$arMessages  = array();
		$parsedReturnValue = null;
		$withoutExceptions = false;

		try
		{
			if ( ! (
				CTasksTools::IsAdmin()
				|| CTasksTools::IsPortalB24Admin()
			))
			{
				throw new TasksException('Only root can do this', TasksException::TE_ACCESS_DENIED);
			}

			CTaskAssert::assert(
				is_array($args)
				&& (count($args) == 1)
			);

			$userId = array_pop($args);

			CTasksTools::setOccurAsUserId($userId);

			$parsedReturnValue = CTasksTools::getOccurAsUserId();
			$withoutExceptions = true;
		}
		catch (CTaskAssertException $e)
		{
			$arMessages[] = array(
				'id'   => 'TASKS_ERROR_ASSERT_EXCEPTION',
				'text' => 'TASKS_ERROR_ASSERT_EXCEPTION'
			);
		}
		catch (TasksException $e)
		{
			$errCode = $e->getCode();
			$errMsg  = $e->getMessage();

			if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
				$arMessages = unserialize($errMsg, ['allowed_classes' => false]);
			else
			{
				$arMessages[] = array(
					'id'   => 'TASKS_ERROR_EXCEPTION_#' . $errCode,
					'text' => 'TASKS_ERROR_EXCEPTION_#' . $errCode
						. '; ' . $errMsg
						. '; ' . TasksException::renderErrorCode($e)
				);
			}
		}
		catch (Exception $e)
		{
			$errMsg = $e->getMessage();
			if ($errMsg !== '')
				$arMessages[] = array('text' => $errMsg, 'id' => 'TASKS_ERROR');
		}

		if ($withoutExceptions)
			return ($parsedReturnValue);
		else
		{
			self::_emitError($arMessages);
			throw new Exception();
		}
	}


	public static function tasks_extended_meta_setAnyStatus($args)
	{
		$arMessages  = array();
		$parsedReturnValue = null;
		$withoutExceptions = false;

		try
		{
			CTaskAssert::assert(
				is_array($args)
				&& (count($args) == 2)
			);

			$statusId = array_pop($args);
			$taskId   = array_pop($args);

			CTaskAssert::assertLaxIntegers($statusId, $taskId);

			$taskId   = (int) $taskId;
			$statusId = (int) $statusId;

			if ( ! in_array(
				$statusId,
				[
					Status::PENDING,
					Status::IN_PROGRESS,
					Status::SUPPOSEDLY_COMPLETED,
					Status::COMPLETED,
					Status::DEFERRED,
				],
				true	// forbid type casting
			))
			{
				throw new TasksException('Invalid status given', TasksException::TE_WRONG_ARGUMENTS);
			}

			$oTask = CTaskItem::getInstance($taskId, CTasksTools::getCommanderInChief());	// act as Admin
			$oTask->update(array('STATUS' => $statusId));

			$parsedReturnValue = null;

			$withoutExceptions = true;
		}
		catch (CTaskAssertException $e)
		{
			$arMessages[] = array(
				'id'   => 'TASKS_ERROR_ASSERT_EXCEPTION',
				'text' => 'TASKS_ERROR_ASSERT_EXCEPTION'
			);
		}
		catch (TasksException $e)
		{
			$errCode = $e->getCode();
			$errMsg  = $e->getMessage();

			if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
				$arMessages = unserialize($errMsg, ['allowed_classes' => false]);
			else
			{
				$arMessages[] = array(
					'id'   => 'TASKS_ERROR_EXCEPTION_#' . $errCode,
					'text' => 'TASKS_ERROR_EXCEPTION_#' . $errCode
						. '; ' . $errMsg
						. '; ' . TasksException::renderErrorCode($e)
				);
			}
		}
		catch (Exception $e)
		{
			$errMsg = $e->getMessage();
			if ($errMsg !== '')
				$arMessages[] = array('text' => $errMsg, 'id' => 'TASKS_ERROR');
		}

		if ($withoutExceptions)
			return ($parsedReturnValue);
		else
		{
			self::_emitError($arMessages);
			throw new Exception();
		}
	}


	public static function onEventFilter($arParams, $arHandler)
	{
		if ( ! isset($arHandler['EVENT_NAME']) )
			$arHandler['EVENT_NAME'] = '$arHandler[\'EVENT_NAME\'] is not set';

		$arEventFields = array(
			'FIELDS_BEFORE'        => 'undefined',
			'FIELDS_AFTER'         => 'undefined',
			'IS_ACCESSIBLE_BEFORE' => 'undefined',
			'IS_ACCESSIBLE_AFTER'  => 'undefined'
		);

		$taskId = (int) $arParams[0];

		CTaskAssert::assert($taskId >= 1);

		switch(mb_strtolower($arHandler['EVENT_NAME']))
		{
			case 'ontaskadd':
				$arEventFields['FIELDS_BEFORE'] = 'undefined';
				$arEventFields['FIELDS_AFTER'] = array('ID' => $taskId);
				$arEventFields['IS_ACCESSIBLE_BEFORE'] = 'N';
				$arEventFields['IS_ACCESSIBLE_AFTER'] = 'undefined';
				break;

			case 'ontaskupdate':
				$arEventFields['FIELDS_BEFORE'] = array('ID' => $taskId);
				$arEventFields['FIELDS_AFTER'] = array('ID' => $taskId);
				$arEventFields['IS_ACCESSIBLE_BEFORE'] = 'undefined';
				$arEventFields['IS_ACCESSIBLE_AFTER'] = 'undefined';
				break;

			case 'ontaskdelete':
				$arEventFields['FIELDS_BEFORE'] = array('ID' => $taskId);
				$arEventFields['FIELDS_AFTER'] = 'undefined';
				$arEventFields['IS_ACCESSIBLE_BEFORE'] = 'undefined';
				$arEventFields['IS_ACCESSIBLE_AFTER'] = 'N';
				break;

			default:
				throw new Exception(
					'tasks\' RPC event handler: onEventFilter: '
					.'not allowed $arHandler[\'EVENT_NAME\']: '
					.$arHandler['EVENT_NAME']
				);
				break;
		}

		return ($arEventFields);
	}

	/**
	 * This is an entry point for running any task.* rest method
	 *
	 * @throws RestException
	 * @throws Exception
	 */
	public static function __callStatic($transitMethodName, $args)
	{
		global $APPLICATION;
		$APPLICATION->resetException();

		if (!self::$inited)
		{
			self::_init();
		}

		$arFuncNameParts = explode(self::delimiter, $transitMethodName, 2);
		$className = $arFuncNameParts[0];
		$methodName = $arFuncNameParts[1];

		$returnValue = null;
		$errors  = [];
		$parsedReturnValue = null;
		$withoutExceptions = false;

		try
		{
			if (
				!isset(self::$allowedSpecialClasses[$className])
				&& !in_array($className, self::$arAllowedClasses, true)
			)
			{
				throw new Exception('Unknown REST-method signature given');
			}

			$methodArgs = [];
			foreach ($args[0] as $value)
			{
				$methodArgs[] = $value;
			}

			[$returnValue, $dbResult] = $className::runRestMethod(
				User::getId(),
				$methodName,
				$methodArgs,
				self::getNavData($args[1]),
				$args[2] //instance of CRestServer
			);

			$parsedReturnValue = self::_parseReturnValue($className, $methodName, $returnValue, ['SERVER' => $args[2]]);
			if ($dbResult !== null)
			{
				$parsedReturnValue = self::setNavData($parsedReturnValue, $dbResult);
			}

			$withoutExceptions = true;
		}
		catch (CTaskAssertException $e)
		{
			$errors[] = [
				'id' => 'TASKS_ERROR_ASSERT_EXCEPTION',
				'text' => 'TASKS_ERROR_ASSERT_EXCEPTION',
			];
		}
		catch (TasksException $e)
		{
			$code = $e->getCode();
			$message = $e->getMessage();

			if ($e->GetCode() & TasksException::TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE)
			{
				$errors = unserialize($message, ['allowed_classes' => false]);
				if (!$errors)
				{
					$errors = [['text' => $message]];
				}
				elseif (!empty($errors) && empty(array_column($errors, 'text')))
				{
					$errors = array_map(fn (string $error): array => ['text' => $error], $errors);
				}
			}
			else
			{
				$errors[] = [
					'id' => "TASKS_ERROR_EXCEPTION_#{$code}",
					'text' => "TASKS_ERROR_EXCEPTION_#{$code}; {$message}; " . TasksException::renderErrorCode($e),
				];
			}
		}
		catch (Exception $e)
		{
			if ($e->getMessage() !== '')
			{
				$errors[] = [
					'id' => 'TASKS_ERROR',
					'text' => $e->getMessage(),
				];
			}
		}

		if ($withoutExceptions)
		{
			return $parsedReturnValue;
		}

		self::_emitError($errors);
		throw RestException::getException($errors[0]['text']);
	}


	protected static function getNavData($start, $bORM = false)
	{
		return array(
			'nPageSize' => self::TASKS_LIMIT_PAGE_SIZE,
			'iNumPage'  => intval($start / self::TASKS_LIMIT_PAGE_SIZE) + 1
		);
	}


	private static function _init()
	{
		self::$arAllowedMethods = array();

		foreach (self::$arAllowedClasses as $className)
		{
			$arManifest = $className::getManifest();
			self::$arAllowedMethods[$className] = array_map(
				'strtolower',
				array_keys($arManifest['REST: available methods'])
			);
			self::$arMethodsMetaInfo[$className] = $arManifest['REST: available methods'];

			if (isset($arManifest['REST: shortname alias to class']))
			{
				$aliasClassName = $arManifest['REST: shortname alias to class'];
				self::$arClassAliases[$className] = $aliasClassName;
			}

			self::$arManifests[$className] = $arManifest;

			// refactor this translation later when add userfields to other entites
			$classNameCut = $namespace = $className;
			if($className == 'ctaskitem')
			{
				$classNameCut = 'item';
				$namespace = 'task';
			}

			$specialClasses = array(
				'\\Bitrix\\Tasks\\Integration\\Rest\\'.ucfirst($namespace).'\\UserField', // user field support
			);

			foreach($specialClasses as $sClassName)
			{
				if(class_exists($sClassName))
				{
					self::$allowedSpecialClasses[$sClassName] = array(

						'publicClassName' => $sClassName::getClassName(), // i.e. userfield (also might be smth compound, like "subentity.submethod")
						'publicParentClassName' => $classNameCut // i.e. item
					);

					foreach($sClassName::getAvailableMethods() as $method)
					{
						self::$arMethodsMetaInfo[$sClassName][$method] = array();
					}
				}
			}
		}

		self::$inited = true;
	}


	private static function _emitError($arMessages = array())
	{
		global $APPLICATION;

		$e = new CAdminException(self::getFormattedMessages($arMessages));
		$APPLICATION->throwException($e);
	}

	private static function getAllowedAggregateCombos(array $functions, array $columns)
	{
		$result = array();

		if(is_array($functions) && !empty($functions) && is_array($columns) && !empty($columns))
		{
			$functions = array_unique($functions);
			$columns = array_unique($columns);

			foreach($functions as $func)
			{
				foreach($columns as $col)
				{
					$result[] = mb_strtoupper($col).'_'.mb_strtoupper($func);
				}
			}
		}

		return $result;
	}

	/**
	 * This function is for internal use only, not a part of public API
	 *
	 * @access private
	 */
	private static function _parseReturnValue($className, $methodName, $returnValue, $params)
	{
		if(is_callable($className.'::postProcessRestRequest'))
		{
			$returnValue = $className::postProcessRestRequest($methodName, $returnValue, $params);
		}

		CTaskAssert::assert(isset(self::$arMethodsMetaInfo[$className][$methodName]));
		$parsedValue = null;

		$arDateFields = array();
		if (isset(self::$arManifests[$className]['REST: date fields']))
			$arDateFields = self::$arManifests[$className]['REST: date fields'];

		$arMethodMetaInfo = self::$arMethodsMetaInfo[$className][$methodName];

		// Function returns an array of file ids?
		if (
			isset($arMethodMetaInfo['fileIdsReturnValue'])
			&& ($arMethodMetaInfo['fileIdsReturnValue'] === true)
			&& is_array($returnValue)
		)
		{
			$parsedValue = array();
			foreach ($returnValue as &$fileId)
				$parsedValue[] = '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=' . (int) $fileId;
			unset($fileId);
		}
		else if (is_array($returnValue) && isset($arMethodMetaInfo['allowedKeysInReturnValue']))
		{
			// add legal aggregated columns in result array
			if(is_array($arMethodMetaInfo['allowedAggregations'] ?? null))
			{
				if(is_array($arMethodMetaInfo['allowedValuesInAggregation'] ?? null))
					$fields = $arMethodMetaInfo['allowedValuesInAggregation'];
				else
					$fields = $arMethodMetaInfo['allowedKeysInReturnValue'];

				if(is_array($fields))
				{
					$aggrCombos = static::getAllowedAggregateCombos($arMethodMetaInfo['allowedAggregations'], $fields);
					$arMethodMetaInfo['allowedKeysInReturnValue'] = array_merge($arMethodMetaInfo['allowedKeysInReturnValue'], $aggrCombos);
				}
			}

			if (
				isset($arMethodMetaInfo['collectionInReturnValue'])
				&& ($arMethodMetaInfo['collectionInReturnValue'] === true)
			)
			{
				$parsedValue = array();
				foreach ($returnValue as $valueToBeFiltered)
				{
					$parsedValue[] = self::_filterArrayByAllowedKeys(	// Additionally converts datetime fields
						$valueToBeFiltered,
						$arMethodMetaInfo['allowedKeysInReturnValue'],
						$arDateFields
					);
				}

			}
			else
			{
				$parsedValue = self::_filterArrayByAllowedKeys(	// Additionally converts datetime fields
					$returnValue,
					$arMethodMetaInfo['allowedKeysInReturnValue'],
					$arDateFields
				);
			}
		}
		else
		{
			$parsedValue = $returnValue;
		}

		return ($parsedValue);
	}


	private static function _filterArrayByAllowedKeys($arData, $arAllowedKeys, $arDateFields = array())
	{
		$filteredData = array();

		foreach ($arAllowedKeys as $allowedKey)
		{
			if (array_key_exists($allowedKey, $arData))
			{
				// Additionally convert datetime fields
				if (in_array((string) $allowedKey, $arDateFields, true))
					$filteredData[$allowedKey] = CRestUtil::convertDateTime($arData[$allowedKey]);
				else
					$filteredData[$allowedKey] = $arData[$allowedKey];
			}
		}

		return ($filteredData);
	}


	/**
	 * This function is for internal use only, not a part of public API
	 *
	 * @access private
	 */
	public static function _parseRestParams($className, $methodName, $inArgs)
	{
		CTaskAssert::assert(is_array($inArgs) && isset(self::$arMethodsMetaInfo[$className][$methodName]));

		$arMethodMetaInfo     = self::$arMethodsMetaInfo[$className][$methodName];
		$arAllowedParams      = $arMethodMetaInfo['params'];
		$mandatoryParamsCount = $arMethodMetaInfo['mandatoryParamsCount'] ?? null;

		$arDateFields = array();
		if (isset(self::$arManifests[$className]['REST: date fields']))
		{
			$arDateFields = self::$arManifests[$className]['REST: date fields'];
		}

		if ($className == 'ctaskelapseditem' &&
			$methodName == 'getlist' &&
			is_array($inArgs[0]))
		{
			array_unshift($inArgs, 0);
		}

		$outArgs = array();
		foreach ($arAllowedParams as $paramIndex => $paramMetaInfo)
		{
			// No more params given?
			if ( ! array_key_exists($paramIndex, $inArgs) )
			{
				// Set default value, if need
				if (array_key_exists('defaultValue', $paramMetaInfo))
					$inArgs[$paramIndex] = $paramMetaInfo['defaultValue'];
				elseif ($paramIndex < $mandatoryParamsCount)	// Expected mandatory param?
				{
					throw new TasksException(
						'Param #' . $paramIndex . ' (' . $paramMetaInfo['description'] . ')'
						. ' expected by method ' . $className . '::' . $methodName . '(), but not given.',
						TasksException::TE_WRONG_ARGUMENTS
					);
				}
				else
					break;		// no more params to be processed
			}

			// for "galvanic isolation" of input/output
			$paramValue = $inArgs[$paramIndex];

			// Check param type
			/** @noinspection PhpUnusedLocalVariableInspection */
			$isCorrectValue = false;
			switch ($paramMetaInfo['type'])
			{
				case 'boolean':
					if (($paramValue === '0') || ($paramValue === 0))
						$paramValue = false;
					elseif (($paramValue === '1') || ($paramValue === 1))
						$paramValue = true;

					$isCorrectValue = is_bool($paramValue);
				break;

				case 'array':
					$isCorrectValue = is_array($paramValue);
				break;

				case 'string':
					$isCorrectValue = is_string($paramValue);
				break;

				case 'integer':
					$isCorrectValue = CTaskAssert::isLaxIntegers($paramValue);
				break;

				default:
					throw new TasksException(
						'Internal error: unknown param type: ' . $paramMetaInfo['type'],
						TasksException::TE_UNKNOWN_ERROR
					);
				break;
			}

			if ( ! $isCorrectValue )
			{
				throw new TasksException(
					'Param #' . $paramIndex . ' (' . $paramMetaInfo['description'] . ')'
					. ' for method ' . $className . '::' . $methodName . '()'
					. ' expected to be of type "' . $paramMetaInfo['type'] . '",'
					. ' but given something else.',
					TasksException::TE_WRONG_ARGUMENTS
				);
			}

			// add legal aggregated columns in keys & values array
			if(isset($paramMetaInfo['allowedAggregations']) && is_array($paramMetaInfo['allowedAggregations']))
			{
				// for keys
				if(is_array($paramMetaInfo['allowedKeysInAggregation']))
					$fields = $paramMetaInfo['allowedKeysInAggregation'];
				else
					$fields = $paramMetaInfo['allowedKeys'];

				if(is_array($fields))
				{
					$aggrCombos = static::getAllowedAggregateCombos($paramMetaInfo['allowedAggregations'], $fields);
					$paramMetaInfo['allowedKeys'] = array_merge($paramMetaInfo['allowedKeys'], $aggrCombos);
				}

				// for values
				if(is_array($paramMetaInfo['allowedValuesInAggregation'] ?? null))
					$fields = $paramMetaInfo['allowedValuesInAggregation'];
				else
					$fields = $paramMetaInfo['allowedValues'];

				if(is_array($fields))
				{
					$aggrCombos = static::getAllowedAggregateCombos($paramMetaInfo['allowedAggregations'], $fields);
					$paramMetaInfo['allowedValues'] = array_merge($paramMetaInfo['allowedValues'], $aggrCombos);
				}
			}

			if (isset($paramMetaInfo['allowedKeys']))
			{
				CTaskAssert::assert(is_array($paramValue));	// ensure that $paramValue is array
				/** @var $paramValue array */

				foreach (array_keys($paramValue) as $key)
				{
					// a little fix to be able to pass an empty array in order to "skip" argument
					if((string) $key == '0' && $paramValue[$key] == '')
					{
						unset($paramValue[$key]);
						continue;
					}

					if (isset($paramMetaInfo['allowedKeyPrefixes']))
						$keyWoPrefix = str_replace($paramMetaInfo['allowedKeyPrefixes'], '', $key);
					else
						$keyWoPrefix = $key;

					if ( ! in_array((string) $keyWoPrefix, $paramMetaInfo['allowedKeys'], true) )
					{
						throw new TasksException(
							'Param #' . $paramIndex . ' (' . $paramMetaInfo['description'] . ')'
							. ' for method ' . $className . '::' . $methodName . '()'
							. ' must not contain key "' . $key . '".',
							TasksException::TE_WRONG_ARGUMENTS
						);
					}

					// Additionally convert datetime fields from ISO 8601
					if (in_array((string) $keyWoPrefix, $arDateFields, true) && !in_array($paramValue[$key], array('asc', 'desc'))/*it could be sorting*/)
					{
						$paramValue[$key] = (string) CRestUtil::unConvertDateTime($paramValue[$key], true);
					}
				}
			}

			if (isset($paramMetaInfo['allowedValues']))
			{
				CTaskAssert::assert(is_array($paramValue));

				foreach ($paramValue as $value)
				{
					if ( ($value !== null) && ( ! is_bool($value) ) )
						$value = (string) $value;

					if ( ! in_array($value, $paramMetaInfo['allowedValues'], true) )
					{
						throw new TasksException(
							'Param #' . $paramIndex . ' (' . $paramMetaInfo['description'] . ')'
							. ' for method ' . $className . '::' . $methodName . '()'
							. ' must not contain value "' . $value . '".',
							TasksException::TE_WRONG_ARGUMENTS
						);
					}
				}
			}

			// "galvanic isolation" of input/output
			$outArgs[] = $paramValue;
		}

		// $inArgsCount = count($inArgs);
		// $allowedArgsCount = count($arAllowedParams);
		//
		// if ($inArgsCount > $allowedArgsCount)
		// {
		// 	throw new TasksException(
		// 		"Too much params({$inArgsCount}) given for method {$className}::{$methodName}(), but expected not more than {$allowedArgsCount}.",
		// 		TasksException::TE_WRONG_ARGUMENTS
		// 	);
		// }

		return $outArgs;
	}

	private static function getFormattedMessages(array $messages = []): array
	{
		$formattedMessages = [];

		if (empty($messages))
		{
			$formattedMessages[] = [
				'id' => 'TASKS_ERROR_UNKNOWN',
				'text' => 'TASKS_ERROR_UNKNOWN',
			];

			return $formattedMessages;
		}


		foreach ($messages as $id => $message)
		{
			if (is_string($message))
			{
				$formattedMessages[$id] = [
					'id' => $message,
					'text' => $message,
				];

				continue;
			}

			if (is_array($message))
			{
				if (isset($message['text']))
				{
					$formattedMessages[$id] = [
						'id' => $message['id'] ?? $message['text'],
						'text' => $message['text'],
					];
				}
				else
				{
					$formattedMessages[$id] = [
						'id' => $message['id'] ?? 'TASKS_ERROR_UNKNOWN',
						'text' => 'TASKS_ERROR_UNKNOWN',
					];
				}
			}
		}

		return $formattedMessages;
	}
}
