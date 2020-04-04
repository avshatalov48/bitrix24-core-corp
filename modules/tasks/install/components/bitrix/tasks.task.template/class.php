<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\Util\Type\ArrayOption;
use Bitrix\Tasks\Util\Type\StructureChecker;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Item\Converter\Task\Template\ToTemplate;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskTemplateComponent extends TasksBaseComponent
{
	/** @var null|Template */
	protected $template = 		null;
	protected $users2Get = 		array();
	protected $groups2Get = 	array();
	protected $tasks2Get = 		array();
	protected $templates2Get =  array();
	/** @var bool|mixed[] */
	protected $formData = 		false;

	protected $hitState =       null;
	protected $state =          null;

	private $success =          false;

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return bool
	 */
	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);

		if($errors->checkNoFatals())
		{
			// check task access
			$id = intval($arParams[static::getParameterAlias('ID')]);
			$arResult['ITEM'] = new Template($id, $arResult['USER_ID']);
			if(!$arResult['ITEM']->canRead(null))
			{
				$errors->add('ACCESS_DENIED', Loc::getMessage('TASKS_TTTC_NOT_FOUND_OR_NOT_ACCESSIBLE'));
			}
		}

		return $errors->checkNoFatals();
	}

	/**
	 * Function checks and prepares only the basic parameters passed
	 * @return bool
	 */
	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		static::tryParseIntegerParameter($arParams[static::getParameterAlias('ID')], 0, true); // parameter keeps currently chosen template ID

		return $errors->checkNoFatals();
	}

	protected static function useDispatcherResultObject()
	{
		return true;
	}

	protected function checkParameters()
	{
		parent::checkParameters();
		if($this->arParams['USER_ID'])
		{
			$this->users2Get[] = $this->arParams['USER_ID'];
		}

		static::tryParseArrayParameter($this->arParams['AUX_DATA_SELECT']);
		static::tryParseBooleanParameter($this->arParams['REDIRECT_ON_SUCCESS'], true);
		static::tryParseURIParameter($this->arParams['BACKURL']);

		return $this->errors->checkNoFatals();
	}

	/**
	 * Allows to decide which data should be passed to $this->arResult, and which should not
	 * @param mixed[] $arResult
	 */
	protected function translateArResult($arResult)
	{
		$this->template = $arResult['ITEM']; // a short-cut to the currently selected template instance

		parent::translateArResult($arResult); // all other will merge to $this->arResult
	}

	protected function getDataDefaults()
	{
		if(!$this->template->isAttached())
		{
			$this->template['CREATED_BY'] = $this->userId;
			$this->template['RESPONSIBLES'] = array($this->userId);

			$state = $this->getStateInstanceCached()->get();
			foreach($state['FLAGS'] as $flag => $value)
			{
				$this->template[$flag] = $value;
			}
		}
	}

	protected function getDataRequest()
	{
		$request = $this->request->toArray();

		// base template
		$baseTemplateId = intval($request['BASE_TEMPLATE']);
		if($baseTemplateId)
		{
			$this->template['BASE_TEMPLATE_ID'] = $baseTemplateId;
		}
	}

	protected function getData()
	{
		//$this->getFlagStateInstance()->remove();

		$formSubmitted = $this->formData !== false;
		$id = $this->template->getId();

		if(!$id) // get from other sources: default data or other templates`s data
		{
			$this->getDataDefaults();
		}

		if($formSubmitted)
		{
			// translate members
			$this->formData['RESPONSIBLES'] = static::extractMemberIds($this->formData['RESPONSIBLES']);
			$this->formData['AUDITORS'] = static::extractMemberIds($this->formData['AUDITORS']);
			$this->formData['ACCOMPLICES'] = static::extractMemberIds($this->formData['ACCOMPLICES']);

			// applying form data on top, what changed
			$this->template->setData($this->formData, array('ACTUALIZE_COLLECTIONS' => true));
		}
		elseif(!$id)
		{
			if(intval($this->request['COPY'])) // copy from another template?
			{
				$sourceTemplate = new Template(
					intval($this->request['COPY']),
					$this->arResult['USER_ID']
				);

				/**
				 * todo: @see \Bitrix\Tasks\Item\Converter::convert for todo remark
				 */
				$transformResult = $sourceTemplate->transform(new ToTemplate());
				if($transformResult->isSuccess())
				{
					$this->template = $this->arResult['ITEM'] = $transformResult->getInstance();
				}
				else
				{
					$transformErrors = $transformResult->getErrors()->transform(array('TYPE' => Util\Error::TYPE_WARNING));
					$this->errors->load($transformErrors);
				}
			}
			else // get some from request
			{
				$this->getDataRequest();
			}
		}

		$this->collectTaskMembers();
		$this->collectProjects();
		$this->collectTasks();
		$this->collectTemplates();
	}

	protected function getAuxData()
	{
		$this->arResult['AUX_DATA'] = array(
			'COMPANY_WORKTIME' => static::getCompanyWorkTime(),
			'HINT_STATE' => \Bitrix\Tasks\UI::getHintState(),
		);

		parent::getAuxData();
	}

	/**
	 * Fetch common data aggregated with getData(): users, gropus from different sources, etc
	 */
	protected function getReferenceData()
	{
		$this->arResult['DATA']['TASK'] = $this->getTasksData($this->tasks2Get);
		$this->arResult['DATA']['TEMPLATE'] = $this->getTaskTemplateData($this->templates2Get);

		$this->arResult['DATA']['GROUP'] = Group::getData($this->groups2Get);
		$this->arResult['DATA']['USER'] = User::getData($this->users2Get);

		// "new" user
		$this->arResult['DATA']['USER'][0] = array(
			'ID' => 0,
			'IS_EXTRANET_USER' => false,
			'IS_CRM_EMAIL_USER' => false,
			'IS_EMAIL_USER' => false,
			'NAME' => Loc::getMessage('TASKS_COMMON_NEW_USER'),
			'LOGIN' => 'nu', // :)
			'WORK_POSITION' => '',
		);
	}

	protected function collectTaskMembers()
	{
		$accomplices = $this->template['ACCOMPLICES'];
		$accomplices = is_object($accomplices) ? $accomplices->toArray() : array();

		$auditors = $this->template['AUDITORS'];
		$auditors = is_object($auditors) ? $auditors->toArray() : array();

		$responsibles = $this->template['RESPONSIBLES'];
		$responsibles = is_object($responsibles) ? $responsibles->toArray() : array();

		$this->users2Get = array_merge(array(
			$this->userId,
			$this->template['CHANGED_BY'],
			$this->template['CREATED_BY'],
		), $responsibles, $accomplices, $auditors);
	}

	protected function collectProjects()
	{
		$this->groups2Get[] = $this->template['GROUP_ID'];
	}

	protected function collectTasks()
	{
		if(is_object($this->template['DEPENDS_ON']))
		{
			$this->tasks2Get = $this->template['DEPENDS_ON']->toArray();
		}

		if(intval($this->template['PARENT_ID']))
		{
			$this->tasks2Get[] = $this->template['PARENT_ID'];
		}
	}

	protected function collectTemplates()
	{
		$this->templates2Get[] = $this->template['BASE_TEMPLATE_ID'];
	}

	protected function getStateInstanceCached()
	{
		if($this->state === null)
		{
			$this->state = static::getStateInstance(); // todo: object pool here?
		}

		return $this->state;
	}

	protected function doPreAction()
	{
		parent::doPreAction();

		$this->arResult['COMPONENT_DATA']['BACKURL'] = $this->getBackUrl();
	}

	protected function processAfterAction()
	{
		/** @var \Bitrix\Tasks\Dispatcher\ToDo\Plan $plan */
		$plan = $this->arResult['ACTION_RESULT'];
		$op = $plan->getOperationByCode('task_template_action');

		if($op && $op->isProcessed())
		{
			$this->success = $op->isSuccess();
			$this->formData = false;

			if($this->success)
			{
				if($this->arParams['REDIRECT_ON_SUCCESS'])
				{
					LocalRedirect($this->makeRedirectUrl($op));
				}
			}
			else
			{
				$errors = $op->getResult()->getErrors();

				// merge errors
				if(count($errors))
				{
					$this->errors->load($errors->transform(array(
						'CODE' => 'SAVE_ERROR.#CODE#',
						'TYPE' => Util\Error::TYPE_WARNING,
					)));
				}

				$this->formData = $op['ARGUMENTS']['data'];
			}

			$this->arResult['COMPONENT_DATA']['ACTION'] = array(
				'SUCCESS' => $this->success,
			);
		}
	}

	private function makeRedirectUrl($operation)
	{
		$backUrl = $this->getBackUrl();
		$url = $backUrl != '' ? Util::secureBackUrl($backUrl) : $GLOBALS["APPLICATION"]->GetCurPageParam('');
		$action = 'view'; // having default backurl after success edit we go to view ...

		return UI\Task\Template::makeActionUrl($url, static::getOperationTaskId($operation), $action);
	}

	private function getBackUrl()
	{
		if((string) $this->request['BACKURL'] != '')
		{
			return $this->request['BACKURL'];
		}
		elseif(array_key_exists('BACKURL', $this->arParams))
		{
			return $this->arParams['BACKURL'];
		}
		// or else backurl will be defined somewhere like result_modifer, see below

		return false;
	}

	/**
	 * @param \Bitrix\Tasks\Dispatcher\ToDo $operation
	 * @return int
	 */
	private static function getOperationTaskId($operation)
	{
		$data = $operation->getResult()->getData();

		return intval($data['ID']); // task.add and task.update always return TASK_ID on success
	}

	protected static function getStateInstance()
	{
		return new ArrayOption(
			'task_template_edit_form_state',
			array(
				'FLAGS' => array('VALUE' => array(

					'ALLOW_TIME_TRACKING' => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array('Y', 'N'), 'DEFAULT' => 'N'),
					'TASK_CONTROL' => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array('Y', 'N'), 'DEFAULT' => 'N'),
					'ALLOW_CHANGE_DEADLINE' => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array('Y', 'N'), 'DEFAULT' => 'N'),
					'MATCH_WORK_TIME' => array('VALUE' => StructureChecker::TYPE_ENUM, 'VALUES' => array('Y', 'N'), 'DEFAULT' => 'N'),

				), 'DEFAULT' => array())
			),
			ArrayOption::TYPE_GLOBAL
		);
	}

	protected function getTasksData(array $taskIds)
	{
		$tasks = array();

		if(!empty($taskIds))
		{
			$taskIds = array_unique($taskIds);
			$parsed = array();
			foreach($taskIds as $taskId)
			{
				if(intval($taskId))
				{
					$parsed[] = $taskId;
				}
			}

			if(!empty($parsed))
			{
				$select = array("ID", "TITLE", "START_DATE_PLAN", "END_DATE_PLAN", "DEADLINE", "RESPONSIBLE_ID");

				list($list, $res) = CTaskItem::fetchList(
					$this->userId,
					array("ID" => "ASC"),
					array("ID" => $parsed),
					array(),
					$select
				);
				$select = array_flip($select);

				/** @var CTaskItem $item */
				foreach($list as $item)
				{
					$data = $item->getData(false);
					$tasks[$data['ID']] = array_intersect_key($data, $select);

					$this->users2Get[] = $data['RESPONSIBLE_ID']; // get also these users
				}
			}
		}

		return $tasks;
	}

	protected function getTaskTemplateData(array $ids)
	{
		$items = array();

		if(!empty($ids))
		{
			$ids = array_unique(array_filter($ids, 'intval'));

			if(!empty($ids))
			{
				$items = \Bitrix\Tasks\Item\Task\Template::find(
					array(
						'select' => array('ID', 'TITLE'),
						'filter' => array('ID' => $ids),
					),
					array('USER_ID' => $this->userId)
				);
			}
		}

		return $items;
	}

	private static function extractMemberIds($members)
	{
		$ids = array();
		if(is_array($members))
		{
			foreach($members as $member)
			{
				if(is_array($member))
				{
					$memId = $member['ID'];
					if($memId)
					{
						$ids[] = $memId;
					}
				}
			}
		}

		return $ids;
	}
}