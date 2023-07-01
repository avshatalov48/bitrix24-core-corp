<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Access\Model\UserModel;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListConverterHelper;
use Bitrix\Tasks\CheckList\Template\TemplateCheckListFacade;
use Bitrix\Tasks\Provider\TemplateProvider;
use Bitrix\Tasks\Util\Error\Collection;
use Bitrix\Tasks\Item\Task\Template;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TaskLimit;
use Bitrix\Tasks\Util\Type\ArrayOption;
use Bitrix\Tasks\Util\Type\StructureChecker;
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\UI;
use Bitrix\Tasks\Item\Converter\Task\Template\ToTemplate;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit\TemplateSubtaskLimit;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskTemplateComponent extends TasksBaseComponent
	implements \Bitrix\Main\Errorable, \Bitrix\Main\Engine\Contract\Controllerable
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

	protected $errorCollection;

	public function configureActions()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return [];
		}

		return [
			'saveChecklist' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setPriority' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'setTags' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
			'delete' => [
				'+prefilters' => [
					new \Bitrix\Tasks\Action\Filter\BooleanFilter(),
				],
			],
		];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->init();
	}

	protected function init()
	{
		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		$this->setUserId();
		$this->errorCollection = new \Bitrix\Tasks\Util\Error\Collection();
	}

	protected function setUserId()
	{
		$this->userId = (int) \Bitrix\Tasks\Util\User::getId();
	}

	public function getErrorByCode($code)
	{
		// TODO: Implement getErrorByCode() method.
	}

	public function getErrors()
	{
		if (!empty($this->componentId))
		{
			return parent::getErrors();
		}
		return $this->errorCollection->toArray();
	}

	public function deleteAction($templateId)
	{
		$templateId = (int) $templateId;
		if (!$templateId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		if (!\Bitrix\Tasks\Access\TemplateAccessController::can($this->userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_REMOVE, $templateId))
		{
			$this->addForbiddenError();
			return [];
		}

		\CTaskTemplates::Delete($templateId);

		return [];
	}

	public function setPriorityAction($templateId, $priority)
	{
		$templateId = (int) $templateId;
		if (!$templateId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		if (!\Bitrix\Tasks\Access\TemplateAccessController::can($this->userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_EDIT, $templateId))
		{
			$this->addForbiddenError();
			return [];
		}

		$template = new Template($templateId);
		$template->setData([
			'PRIORITY' => $priority
		]);
		$template->save();

		return [];
	}

	public function setTagsAction($templateId, array $tags = [])
	{
		$templateId = (int) $templateId;
		if (!$templateId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		if (!\Bitrix\Tasks\Access\TemplateAccessController::can($this->userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_EDIT, $templateId))
		{
			$this->addForbiddenError();
			return [];
		}

		$template = new Template($templateId);
		$template->setData([
			'SE_TAG' => $tags
		]);
		$template->save();

		return [];
	}

	public function saveCheckListAction($templateId, $items = [], $params = [])
	{
		if (!is_array($items))
		{
			$items = [];
		}

		$templateId = (int) $templateId;
		if (!$templateId)
		{
			return null;
		}

		if (!\Bitrix\Main\Loader::includeModule('tasks'))
		{
			return null;
		}

		if (!\Bitrix\Tasks\Access\TemplateAccessController::can($this->userId, \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_EDIT, $templateId))
		{
			$this->addForbiddenError();
			return [];
		}

		foreach ($items as $id => $item)
		{
			$item['ID'] = isset($item['ID']) ? (int)$item['ID'] : null;
			$item['IS_COMPLETE'] = isset($item['IS_COMPLETE']) && (int)$item['IS_COMPLETE'] > 0;
			$item['IS_IMPORTANT'] = isset($item['IS_IMPORTANT']) && (int)$item['IS_IMPORTANT'] > 0;

			if (isset($item['MEMBERS']) && is_array($item['MEMBERS']))
			{
				$members = [];

				foreach ($item['MEMBERS'] as $number => $member)
				{
					$members[key($member)] = current($member);
				}

				$item['MEMBERS'] = $members;
			}

			$items[$item['NODE_ID']] = $item;
			unset($items[$id]);
		}

		return TemplateCheckListFacade::merge($templateId, $this->userId, $items, $params);
	}

	protected function addForbiddenError()
	{
		$this->errorCollection->add('ACTION_NOT_ALLOWED.RESTRICTED', Loc::getMessage('TASKS_ACTION_NOT_ALLOWED'));
	}

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

			if (!\Bitrix\Tasks\Access\TemplateAccessController::can((int)$arResult['USER_ID'], \Bitrix\Tasks\Access\ActionDictionary::ACTION_TEMPLATE_READ, $id))
			{
				$errors->add('ACCESS_DENIED', Loc::getMessage('TASKS_TTTC_NOT_FOUND_OR_NOT_ACCESSIBLE'));
			}

			$arResult['ITEM'] = new Template($id, $arResult['USER_ID']);
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
		$baseTemplateId = (int)($request['BASE_TEMPLATE'] ?? null);
		if($baseTemplateId && !TaskLimit::isLimitExceeded())
		{
			$this->template['BASE_TEMPLATE_ID'] = $baseTemplateId;
		}
	}

	protected function getData()
	{
		//$this->getFlagStateInstance()->remove();

		$formSubmitted = $this->formData !== false;
		$id = $this->template->getId();

		if (!array_key_exists('USER_ID', $this->arResult))
		{
			$this->arResult['USER_ID'] = $this->userId;
		}

		if ($id)
		{
			$template = \Bitrix\Tasks\Internals\Task\TemplateTable::getByPrimary($id)->fetch();
			if (!$template)
			{
				$this->errors->add('ACCESS_DENIED', Loc::getMessage('TASKS_TTTC_NOT_FOUND_OR_NOT_ACCESSIBLE'));
				return;
			}
		}
		else
		{
			$this->getDataDefaults();
		}

		$this->arResult['CHECKLIST_CONVERTED'] = ($id? TemplateCheckListConverterHelper::checkEntityConverted($id) : true);

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
			if((int)$this->request['COPY']) // copy from another template?
			{
				$copiedFrom = (int)$this->request['COPY'];
				$sourceTemplate = new Template($copiedFrom, $this->arResult['USER_ID']);

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

				$this->arResult['COPIED_FROM'] = $copiedFrom;
				$this->arResult['CHECKLIST_CONVERTED'] = TemplateCheckListConverterHelper::checkEntityConverted($copiedFrom);
			}
			else // get some from request
			{
				$this->getDataRequest();
			}
		}

		// scenario
		$scenario = $this->request->get('SCENARIO');
		if ($scenario && \Bitrix\Tasks\Internals\Task\Template\ScenarioTable::isValidScenario($scenario))
		{
			$this->arResult['TEMPLATE_DATA']['SCENARIO'] = $scenario;
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

		$this->arResult['AUX_DATA']['TASK_LIMIT_EXCEEDED'] = TaskLimit::isLimitExceeded();
		$this->arResult['AUX_DATA']['TEMPLATE_SUBTASK_LIMIT_EXCEEDED'] = TemplateSubtaskLimit::isLimitExceeded();
		$this->arResult['AUX_DATA']['TASK_RECURRENT_RESTRICT'] = Util\Restriction\Bitrix24Restriction\Limit\RecurringLimit::isLimitExceeded();

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
				$templates = Util::getOption('propagate_to_sub_templates');
				if ($templates)
				{
					$templates = unserialize($templates, ['allowed_classes' => false]);
					$templateId = $op->getResult()->getData()['ID'];
					$propagateToSubTemplates = $op->getArguments()['data']['PROPAGATE_TO_SUB_TEMPLATES'];

					if (in_array($propagateToSubTemplates, ['Y', '1']) && !in_array($templateId, $templates))
					{
						$templates[] = $templateId;
					}
					else if (!in_array($propagateToSubTemplates, ['Y', '1']) && in_array($templateId, $templates))
					{
						unset($templates[array_search($templateId, $templates)]);
					}

					Util::setOption('propagate_to_sub_templates', serialize($templates));
				}

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

        $isIframe = $_REQUEST['IFRAME'] && $_REQUEST['IFRAME']=='Y';
        if($isIframe)
        {
            $url .= '?IFRAME=Y';
        }

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
					try
					{
						$data = $item->getData(false);
					}
					catch (TasksException $e)
					{
						continue;
					}

					$tasks[$data['ID']] = array_intersect_key($data, $select);

					$this->users2Get[] = $data['RESPONSIBLE_ID']; // get also these users
				}
			}
		}

		return $tasks;
	}

	protected function getTaskTemplateData(array $ids): array
	{
		$items = [];

		if(!empty($ids))
		{
			$ids = array_unique(array_filter($ids, 'intval'));

			if(!empty($ids))
			{
				$user = UserModel::createFromId($this->userId);

				global $DB, $USER_FIELD_MANAGER;
				$provider = new TemplateProvider($DB, $USER_FIELD_MANAGER);
				$rows = $provider->getList(
					[],
					['ID' => $ids],
					['ID', 'TITLE'],
					[
						'USER_ID' => $this->userId,
						'USER_IS_ADMIN' => $user->isAdmin(),
					],
					[]
				);
				while ($row  = $rows->Fetch())
				{
					$items[] = [
						'ID' => $row['ID'],
						'TITLE' => $row['TITLE'],
					];
				}
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