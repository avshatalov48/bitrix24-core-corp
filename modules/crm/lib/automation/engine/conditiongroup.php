<?php
namespace Bitrix\Crm\Automation\Engine;

use Bitrix\Crm\Automation;
use Bitrix\Crm\Automation\Target\BaseTarget;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ConditionGroup
 * @package Bitrix\Crm\Automation\Engine
 * @deprecated
 * @see \Bitrix\Bizproc\Automation\Engine\ConditionGroup
 */
class ConditionGroup
{
	const TYPE_FIELD = 'field';
	//const TYPE_VARIABLE = 'variable'; //reserved

	const JOINER_AND = 'AND';// 0
	const JOINER_OR = 'OR';// 1

	private $type;
	private $items = [];

	public function __construct(array $params = null)
	{
		$this->setType(static::TYPE_FIELD);
		if ($params)
		{
			if (isset($params['type']))
			{
				$this->setType($params['type']);
			}
			if (isset($params['items']) && is_array($params['items']))
			{
				foreach ($params['items'] as list($item, $joiner))
				{
					$condition = new Condition($item);
					$this->addItem($condition, $joiner);
				}
			}
		}
	}

	public function evaluate(BaseTarget $target)
	{
		if (empty($this->items) || !Automation\Helper::isBizprocEnabled())
		{
			return true;
		}

		$documentId = \CCrmBizProcHelper::ResolveDocumentId($target->getEntityTypeId(), $target->getEntityId());

		$runtime = \CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$documentService = $runtime->GetService("DocumentService");
		$document = $documentService->GetDocument($documentId);
		$documentFields = $documentService->GetDocumentFields($documentService->GetDocumentType($documentId));

		$result = array(0 => true);
		$i = 0;
		foreach ($this->items as $item)
		{
			/** @var Condition $condition */
			$condition = $item[0];
			$joiner = ($item[1] === static::JOINER_OR) ? static::JOINER_OR : static::JOINER_AND;

			$conditionResult = true;

			if (array_key_exists($condition->getField(), $document))
			{
				$fld = $document[$condition->getField()];
				$type = $documentFields[$condition->getField()]["BaseType"];
				if ($documentFields[$item[0]]['Type'] === 'UF:boolean')
				{
					$type = 'bool';
				}

				if (!$condition->check($fld, $type, $target))
				{
					$conditionResult = false;
				}
			}

			if ($joiner == static::JOINER_OR)
			{
				++$i;
				$result[$i] = $conditionResult;
			}
			elseif (!$conditionResult)
			{
				$result[$i] = false;
			}
		}

		return (count(array_filter($result)) > 0);
	}

	/**
	 * @param string $type
	 * @return ConditionGroup
	 */
	public function setType($type)
	{
		if ($type === static::TYPE_FIELD)
		{
			$this->type = $type;
		}
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param Condition $condition
	 * @param string $joiner
	 * @return $this
	 */
	public function addItem(Condition $condition, $joiner = self::JOINER_AND)
	{
		$this->items[] = [$condition, $joiner];
		return $this;
	}

	/**
	 * @return array Condition items.
	 */
	public function getItems()
	{
		return $this->items;
	}

	public function toArray()
	{
		$itemsArray = [];

		/** @var Condition $condition */
		foreach ($this->getItems() as list($condition, $joiner))
		{
			$itemsArray[] = [$condition->toArray(), $joiner];
		}

		return ['type' => $this->getType(), 'items' => $itemsArray];
	}

	public function createBizprocActivity(array $childActivity)
	{
		$title = Loc::getMessage('CRM_AUTOMATION_CONDITION_TITLE');
		$fieldCondition = [];

		/** @var Condition $condition */
		foreach ($this->getItems() as list($condition, $joiner))
		{
			$bizprocJoiner = ($joiner === static::JOINER_OR) ? 1 : 0;
			$fieldCondition[] = [
				$condition->getField(),
				$condition->getOperator(),
				$condition->getValue(),
				$bizprocJoiner
			];
		}

		$activity = array(
			'Type' => 'IfElseActivity',
			'Name' => Robot::generateName(),
			'Properties' => array('Title' => $title),
			'Children' => array(
				array(
					'Type' => 'IfElseBranchActivity',
					'Name' => Robot::generateName(),
					'Properties' => array(
						'Title' => $title,
						'fieldcondition' => $fieldCondition
					),
					'Children' => array($childActivity)
				),
				array(
					'Type' => 'IfElseBranchActivity',
					'Name' => Robot::generateName(),
					'Properties' => array(
						'Title' => $title,
						'truecondition' => '1',
					),
					'Children' => array()
				)
			)
		);

		return $activity;
	}

	/**
	 * @param array $activity
	 * @return false|Condition
	 */
	public static function convertBizprocActivity(array &$activity)
	{
		$conditionGroup = false;
		if (
			count($activity['Children']) === 2
			&& $activity['Children'][0]['Type'] === 'IfElseBranchActivity'
			&& $activity['Children'][1]['Type'] === 'IfElseBranchActivity'
			&& !empty($activity['Children'][0]['Properties']['fieldcondition'])
			&& !empty($activity['Children'][1]['Properties']['truecondition'])
			&& count($activity['Children'][0]['Children']) === 1
			&& count($activity['Children'][0]['Properties']['fieldcondition']) > 0
		)
		{
			$conditionGroup = new static();

			foreach ($activity['Children'][0]['Properties']['fieldcondition'] as $fieldCondition)
			{
				$conditionItem = new Condition(array(
					'field' => $fieldCondition[0],
					'operator' => $fieldCondition[1],
					'value' => $fieldCondition[2],
				));

				$joiner = (isset($fieldCondition[3]) && $fieldCondition[3] > 0) ? static::JOINER_OR : static::JOINER_AND;
				$conditionGroup->addItem($conditionItem, $joiner);
			}

			$activity = $activity['Children'][0]['Children'][0];
		}

		return $conditionGroup;
	}
}