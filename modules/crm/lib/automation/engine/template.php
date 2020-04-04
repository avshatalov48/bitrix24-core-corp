<?php
namespace Bitrix\Crm\Automation\Engine;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Result;
use Bitrix\Crm\Automation;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Template
 * @package Bitrix\Crm\Automation\Engine
 * @deprecated
 * @see \Bitrix\Bizproc\Automation\Engine\Template
 */
class Template
{
	protected static $parallelActivityType = 'ParallelActivity';
	protected static $sequenceActivityType = 'SequenceActivity';
	protected static $delayActivityType = 'DelayActivity';
	protected static $conditionActivityType = 'IfElseActivity';

	protected static $availableActivities = array();
	protected static $availableActivityClasses = array();

	protected $template;
	protected $bizprocTemplate;
	/** @var  null|Robot[] */
	protected $robots;
	protected $isExternalModified;

	/**
	 * Template constructor.
	 * @param array|null $template Template fields.
	 */
	public function __construct(array $template = null)
	{
		if (is_array($template))
		{
			$this->template = $template;
			if (count($template) === 2 && isset($template['ENTITY_TYPE_ID']) && isset($template['ENTITY_STATUS']))
			{
				$row = Entity\TemplateTable::getList(array(
					'filter' => array(
						'=ENTITY_TYPE_ID' => $template['ENTITY_TYPE_ID'],
						'=ENTITY_STATUS' => $template['ENTITY_STATUS']
					)
				))->fetch();
				if ($row)
				{
					$this->template = $row;
				}
			}
		}
		else
		{
			$this->template = array();
		}
	}

	public function getRobotSettingsDialog(array $robot, $request = null)
	{
		$documentType = array(
			'crm',
			\CCrmBizProcHelper::ResolveDocumentName($this->template['ENTITY_TYPE_ID']),
			\CCrmOwnerType::ResolveName($this->template['ENTITY_TYPE_ID'])
		);

		if (isset($robot['Properties']) && is_array($robot['Properties']))
		{
			$robot['Properties'] = $this->convertRobotProperties($robot['Properties'], $documentType);
		}

		$this->setRobots(array($robot));
		$raw = $this->getBizprocTemplate();

		return \CBPActivity::CallStaticMethod(
			$robot['Type'],
			"GetPropertiesDialog",
			array(
				$documentType,
				$robot['Name'],
				$raw['TEMPLATE'],
				array(),
				array(),
				null,
				$request,
				null,
				SITE_ID
			)
		);
	}

	public function getRobotAjaxResponse(array $robot, array $request)
	{
		return \CBPActivity::CallStaticMethod(
			$robot['Type'],
			"getAjaxResponse",
			array(
				$request
			)
		);
	}

	public function saveRobotSettings(array $robot, array $request)
	{
		$saveResult = new Result();
		$documentType = array(
			'crm',
			\CCrmBizProcHelper::ResolveDocumentName($this->template['ENTITY_TYPE_ID']),
			\CCrmOwnerType::ResolveName($this->template['ENTITY_TYPE_ID'])
		);

		if (isset($robot['Properties']) && is_array($robot['Properties']))
		{
			$robot['Properties'] = $this->unConvertRobotProperties($robot['Properties'], $documentType);
		}

		if (is_array($request))
		{
			$request = $this->unConvertRobotProperties($request, $documentType);
		}

		$this->setRobots(array($robot));
		$raw = $this->getBizprocTemplate();

		$robotErrors = $v = $p = array();
		$result = \CBPActivity::CallStaticMethod(
			$robot['Type'],
			"GetPropertiesDialogValues",
			array(
				$documentType,
				$robot['Name'],
				&$raw['TEMPLATE'],
				&$v,
				&$p,
				$request,
				&$robotErrors
			)
		);

		if ($result)
		{
			$templateActivity = \CBPWorkflowTemplateLoader::FindActivityByName($raw['TEMPLATE'], $robot['Name']);

			if ($robot['Type'] === 'CrmSendEmailActivity') //Fix for WAF
			{
				$templateActivity['Properties'] = $this->unConvertRobotProperties($templateActivity['Properties'], $documentType);
			}

			$robotTitle = $robot['Properties']['Title'];
			$robot['Properties'] = $templateActivity['Properties'];
			$robot['Properties']['Title'] = $robotTitle;

			$saveResult->setData(array('robot' => $robot));
		}
		else
		{
			foreach ($robotErrors as $i => $error)
			{
				$saveResult->addError(new Error($error['message']));
			}
		}

		return $saveResult;
	}

	public function save(array $robots, $userId)
	{
		$userId = (int)$userId;
		$result = new Result();
		$bizprocTemplateId = $this->getBizprocTemplateId();

		if ($bizprocTemplateId > 0 && $this->isExternalModified() && empty($this->template['UNSET_EXTERNAL_MODIFIED']))
			return $result; //ignore

		$this->setRobots($robots);

		if ($bizprocTemplateId)
			$bizprocResult = $this->updateBizprocTemplate($bizprocTemplateId, $userId);
		else
			$bizprocResult = $this->addBizprocTemplate($userId);

		if ($bizprocResult->isSuccess())
		{
			$resultData = $bizprocResult->getData();
			if (isset($resultData['ID']))
				$this->setBizprocTemplateId($resultData['ID']);
		}
		else
		{
			$result->addErrors($bizprocResult->getErrors());
		}

		if ($result->isSuccess())
		{
			Entity\TemplateTable::upsert($this->template);
		}

		return $result;
	}

	public function setRobots(array $robots)
	{
		$this->robots = array();
		$this->isExternalModified = null;
		foreach ($robots as $robot)
		{
			if (is_array($robot))
				$robot = new Robot($robot);

			if (!($robot instanceof Robot))
				throw new ArgumentException('Robots array is incorrect', 'robots');

			$this->robots[] = $robot;
		}

		$this->unConvertTemplate();// make bizproc template

		return $this;
	}

	/**
	 * Convert instance data to array.
	 * @return array
	 */
	public function toArray()
	{
		$result = $this->template;

		$result['IS_EXTERNAL_MODIFIED'] = $this->isExternalModified();
		$result['ROBOTS'] = array();

		foreach ($this->getRobots() as $robot)
		{
			$result['ROBOTS'][] = $robot->toArray();
		}

		$bizprocTemplate = $this->getBizprocTemplate();
		$result['TEMPLATE_ID'] = $bizprocTemplate? (int)$bizprocTemplate['ID'] : 0;

		return $result;
	}

	public static function getAvailableRobots($entityTypeId)
	{
		if (!Automation\Helper::isBizprocEnabled())
			throw new NotSupportedException('Module bizproc is not available.');

		$entityTypeId = (int)$entityTypeId;
		if (!isset(static::$availableActivities[$entityTypeId]))
		{
			$documentType = $entityTypeId ? array(
				'crm',
				\CCrmBizProcHelper::ResolveDocumentName($entityTypeId),
				\CCrmOwnerType::ResolveName($entityTypeId)
			) : null;

			$runtime = \CBPRuntime::GetRuntime();
			static::$availableActivities[$entityTypeId] = $runtime->SearchActivitiesByType('robot_activity', $documentType);
		}
		return static::$availableActivities[$entityTypeId];
	}

	protected function getBizprocTemplate()
	{
		if ($this->bizprocTemplate === null)
		{
			$bizprocTemplateId = $this->getBizprocTemplateId();
			if ($bizprocTemplateId > 0)
			{
				$this->loadBizprocTemplateById($bizprocTemplateId);
			}
			else
				$this->bizprocTemplate = false;
		}

		return $this->bizprocTemplate;
	}

	protected static function getAvailableRobotClasses($entityTypeId)
	{
		$entityTypeId = (int)$entityTypeId;
		if (!isset(static::$availableActivityClasses[$entityTypeId]))
		{
			static::$availableActivityClasses[$entityTypeId] = array();
			$activities = static::getAvailableRobots($entityTypeId);
			foreach ($activities as $activity)
			{
				static::$availableActivityClasses[$entityTypeId][] = $activity['CLASS'];
			}
		}
		return static::$availableActivityClasses[$entityTypeId];
	}

	protected function addBizprocTemplate($userId)
	{
		$userId = (int)$userId;
		$documentType = array(
			'crm',
			\CCrmBizProcHelper::ResolveDocumentName($this->template['ENTITY_TYPE_ID']),
			\CCrmOwnerType::ResolveName($this->template['ENTITY_TYPE_ID'])
		);

		$raw = $this->getBizprocTemplate();
		$raw['DOCUMENT_TYPE'] = $documentType;
		$raw['NAME'] = Loc::getMessage('CRM_AUTOMATION_TEMPLATE_NAME', array(
			'#STATUS#' => $this->template['ENTITY_STATUS']
		));
		$raw['USER_ID'] = $userId;
		$raw['MODIFIER_USER'] = new \CBPWorkflowTemplateUser($userId);

		$result = new Result();
		try
		{
			$raw['ID'] = \CBPWorkflowTemplateLoader::Add($raw, $userId === 1);
			$result->setData(array('ID' => $raw['ID']));

			$this->bizprocTemplate = $raw;
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	protected function updateBizprocTemplate($id, $userId)
	{
		$raw = $this->getBizprocTemplate();
		$result = new Result();

		try
		{
			\CBPWorkflowTemplateLoader::Update($id, array(
				'TEMPLATE'      => $raw['TEMPLATE'],
				'PARAMETERS'    => array(),
				'VARIABLES'     => array(),
				'CONSTANTS'     => array(),
				'USER_ID' 		=> $userId,
				'MODIFIER_USER' => new \CBPWorkflowTemplateUser($userId),
			));
		}
		catch (\Exception $e)
		{
			$result->addError(new Error($e->getMessage()));
		}

		return $result;
	}

	protected function getId()
	{
		return isset($this->template['ID']) ? (int)$this->template['ID'] : 0;
	}

	protected function getBizprocTemplateId()
	{
		return isset($this->template['TEMPLATE_ID']) ? (int)$this->template['TEMPLATE_ID'] : 0;
	}

	protected function setBizprocTemplateId($id)
	{
		$this->template['TEMPLATE_ID'] = $id;
		$this->bizprocTemplate['ID'] = $id;

		return $this;
	}

	/**
	 * @param int $id Bizproc template id.
	 * @return bool Loading result.
	 * @throws NotSupportedException
	 */
	protected function loadBizprocTemplateById($id)
	{
		if (!Automation\Helper::isBizprocEnabled())
			throw new NotSupportedException('Module bizproc is not available.');

		$this->bizprocTemplate = \CBPWorkflowTemplateLoader::getList(
			array(),
			array('ID' => (int)$id, 'AUTO_EXECUTE' => \CBPDocumentEventType::Automation)
		)->fetch();

		return ($this->bizprocTemplate !== false);
	}

	protected function convertTemplate()
	{
		$this->robots = array();

		$raw = $this->getBizprocTemplate();
		if (!is_array($raw) || !isset($raw['TEMPLATE']))
		{
			return false; // BP template is lost.
		}

		if (!empty($raw['PARAMETERS']) || !empty($raw['VARIABLES']) || !empty($raw['CONSTANTS']))
		{
			$this->isExternalModified = true;
			return false; // modified or incorrect.
		}

		if (empty($raw['TEMPLATE'][0]['Children']) || !is_array($raw['TEMPLATE'][0]['Children']))
			return true;

		if (count($raw['TEMPLATE'][0]['Children']) > 1)
		{
			$this->isExternalModified = true;
			return false; // modified or incorrect.
		}

		$parallelActivity = $raw['TEMPLATE'][0]['Children'][0];
		if (!$parallelActivity || $parallelActivity['Type'] !== static::$parallelActivityType)
		{
			$this->isExternalModified = true;
			return false; // modified or incorrect.
		}

		foreach ($parallelActivity['Children'] as $sequence)
		{
			$delay = $condition = null;
			$robotsCnt = 0;
			foreach ($sequence['Children'] as $activity)
			{
				if ($activity['Type'] === static::$delayActivityType)
				{
					$delay = $activity;
					continue;
				}

				if ($activity['Type'] === static::$conditionActivityType)
				{
					$condition = Condition::convertBizprocActivity($activity);
					if ($condition === false)
					{
						$this->isExternalModified = true;
						$this->robots = array();
						return false; // modified or incorrect.
					}
				}

				if (!$this->isRobot($activity))
				{
					$this->isExternalModified = true;
					$this->robots = array();
					return false; // modified or incorrect.
				}

				$robotActivity = new Robot($activity);
				if ($delay !== null)
				{
					$delayInterval = DelayInterval::createFromActivityProperties($delay['Properties']);
					$robotActivity->setDelayInterval($delayInterval);
					$robotActivity->setDelayName($delay['Name']);
					$delay = null;
				}

				if ($condition !== null)
				{
					$robotActivity->setCondition($condition);
					$condition = null;
				}

				if ($robotsCnt > 0)
				{
					$robotActivity->setExecuteAfterPrevious();
				}

				++$robotsCnt;
				$this->robots[] = $robotActivity;
			}
		}

		return $this->robots;
	}

	protected function unConvertTemplate()
	{
		$this->bizprocTemplate = array(
			'ID' => isset($this->template['TEMPLATE_ID']) ? $this->template['TEMPLATE_ID'] : 0,
			'AUTO_EXECUTE' => \CBPDocumentEventType::Automation,
			'TEMPLATE'     => array(array(
				'Type' => 'SequentialWorkflowActivity',
				'Name' => 'Template',
				'Properties' => array('Title' => 'CRM Automation template'),
				'Children' => array()
			)),
			'SYSTEM_CODE'  => 'bitrix_crm_automation'
		);

		if ($this->robots)
		{
			$parallelActivity = $this->createParallelActivity();
			$sequence = $this->createSequenceActivity();

			foreach ($this->robots as $i => $robot)
			{
				if ($i !== 0 && !$robot->isExecuteAfterPrevious())
				{
					$parallelActivity['Children'][] = $sequence;
					$sequence = $this->createSequenceActivity();
				}

				$delayInterval = $robot->getDelayInterval();
				if ($delayInterval && !$delayInterval->isNow())
				{
					$delayName = $robot->getDelayName();
					if (!$delayName)
					{
						$delayName = Robot::generateName();
						$robot->setDelayName($delayName);
					}

					$sequence['Children'][] = $this->createDelayActivity(
						$delayInterval->toActivityProperties(),
						$delayName
					);
				}

				$activity = $robot->getBizprocActivity();
				$condition = $robot->getCondition();

				if ($condition && count($condition->getItems()) > 0)
				{
					$activity = $condition->createBizprocActivity($activity);
				}

				$sequence['Children'][] = $activity;
			}

			$parallelActivity['Children'][] = $sequence;

			if (count($parallelActivity['Children']) < 2)
			{
				$parallelActivity['Children'][] = $this->createSequenceActivity();
			}

			$this->bizprocTemplate['TEMPLATE'][0]['Children'][] = $parallelActivity;
		}
	}

	protected function isRobot(array $activity)
	{
		if (!in_array($activity['Type'], static::getAvailableRobotClasses($this->template['ENTITY_TYPE_ID'])))
			return false;

		if (!empty($activity['Children']))
			return false;
		return true;
	}

	/**
	 * @return null|Robot[] Robot activities.
	 */
	public function getRobots()
	{
		if ($this->robots === null)
			$this->convertTemplate();

		return $this->robots;
	}

	/**
	 * Checks is template was modified by external editor.
	 * @return bool
	 */
	public function isExternalModified()
	{
		if ($this->isExternalModified === null)
			$this->getRobots();

		return ($this->isExternalModified === true);
	}

	private function createSequenceActivity()
	{
		return array(
			'Type' => static::$sequenceActivityType,
			'Name' => Robot::generateName(),
			'Properties' => array(
				'Title' => 'Automation sequence'
			),
			'Children' => array()
		);
	}

	private function createParallelActivity()
	{
		return array(
			'Type' => static::$parallelActivityType,
			'Name' => Robot::generateName(),
			'Properties' => array(
				'Title' => Loc::getMessage('CRM_AUTOMATION_PARALLEL_ACTIVITY'),
			),
			'Children' => array()
		);
	}

	private function createDelayActivity(array $delayProperties, $delayName)
	{
		if (!isset($delayProperties['Title']))
			$delayProperties['Title'] = Loc::getMessage('CRM_AUTOMATION_DELAY_ACTIVITY');

		return array(
			'Type' => static::$delayActivityType,
			'Name' => $delayName,
			'Properties' => $delayProperties,
			'Children' => array()
		);
	}

	private function convertRobotProperties(array $properties, array $documentType)
	{
		foreach ($properties as $code => $property)
		{
			if (is_scalar($property))
				$property = Automation\Helper::convertExpressions($property, $documentType);
			elseif (is_array($property))
			{
				foreach ($property as $key => $value)
				{
					if (is_scalar($value))
						$value = Automation\Helper::convertExpressions($value, $documentType);
					$property[$key] = $value;
				}
			}
			$properties[$code] = $property;
		}
		return $properties;
	}

	private function unConvertRobotProperties(array $properties, array $documentType)
	{
		foreach ($properties as $code => $property)
		{
			if (is_array($property))
			{
				$properties[$code] = self::unConvertRobotProperties($property, $documentType);
			}
			else
			{
				$properties[$code] = Automation\Helper::unConvertExpressions($property, $documentType);
			}
		}
		return $properties;
	}
}