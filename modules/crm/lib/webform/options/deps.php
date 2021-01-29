<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2019 Bitrix
 */

namespace Bitrix\Crm\WebForm\Options;

use Bitrix\Crm\WebForm;

/**
 * Class Deps
 * @package Bitrix\Crm\WebForm\Options
 */
final class Deps
{
	private $form;

	/**
	 * Deps constructor.
	 * @param WebForm\Form $form
	 */
	public function __construct(WebForm\Form $form)
	{
		$this->form = $form;
	}

	/**
	 * Convert to array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$groupRows = $this->form->get()['DEP_GROUPS'];
		$deps = $this->form->get()['DEPENDENCIES'];
		if (empty($groupRows))
		{
			return [];
		}

		$groups = [];
		foreach ($groupRows as $group)
		{
			$groupId = (string) $group['ID'];
			$groupTypeId = (int) $group['TYPE_ID'];
			switch ($groupTypeId)
			{
				case WebForm\Internals\FieldDepGroupTable::TYPE_AND:
					$groupLogic = 'and';
					break;

				default:
					$groupLogic = 'or';
			}
			$groups[$groupId] = [
				'id' => $groupId,
				'typeId' => $groupTypeId,
				'logic' => $groupLogic,
				'list' => [],
			];
		}

		$fieldsBySection = [];
		$currentSection = false;
		foreach ($this->form->getFields() as $field)
		{
			if ($field['TYPE'] === 'section')
			{
				$currentSection = $field['NAME'];
			}
			elseif ($field['TYPE'] === 'page')
			{
				$currentSection = null;
			}

			if($currentSection)
			{
				$fieldsBySection[$currentSection][] = $field['NAME'];
			}
		}

		foreach ($deps as $dep)
		{
			$condition = [
				'target' => $dep['IF_FIELD_CODE'],
				'event' => $dep['IF_ACTION'],
				'value' => $dep['IF_VALUE'],
				'operation' => $dep['IF_VALUE_OPERATION'],
			];

			if (!empty($fieldsBySection[$dep['DO_FIELD_CODE']]))
			{
				$fieldNames = $fieldsBySection[$dep['DO_FIELD_CODE']];
			}
			else
			{
				$fieldNames = [$dep['DO_FIELD_CODE']];
			}

			foreach ($fieldNames as $fieldName)
			{
				$action = [
					'target' => $fieldName,
					'type' => $dep['DO_ACTION'],
					'value' => $dep['DO_VALUE'],
				];

				$groupId = (string) $dep['GROUP_ID'];
				if (!isset($groups[$groupId]))
				{
					if (count($groups) === 1)
					{
						$groupId = (string) current($groups)['id'];
					}
				}

				$groups[$groupId]['list'][] = [
					'condition' => $condition,
					'action' => $action,
				];
			}
		}

		return array_values($groups);
	}

	/**
	 * Set data.
	 *
	 * @param array $data Data.
	 * @return $this
	 */
	public function setData(array $data)
	{
		$deps = [];
		$groups = [];
		foreach ($data as $index => $group)
		{
			if (empty($group['id']))
			{
				$group['id'] = "n-$index";
			}
			if (empty($group['typeId']))
			{
				$group['typeId'] = WebForm\Internals\FieldDepGroupTable::TYPE_DEF;
			}

			$list = array_map(
				function ($dep) use ($group)
				{
					return [
						'GROUP_ID' => $group['id'],
						'IF_FIELD_CODE' => $dep['condition']['target'],
						'IF_ACTION' => $dep['condition']['event'],
						'IF_VALUE' => $dep['condition']['value'],
						'IF_VALUE_OPERATION' => $dep['condition']['operation'],
						'DO_FIELD_CODE' => $dep['action']['target'],
						'DO_ACTION' => $dep['action']['type'],
						'DO_VALUE' => $dep['action']['value'],
					];
				},
				self::filterDependencies($group['list'] ?? [])
			);
			$deps = array_merge($deps, $list);

			if (!$list)
			{
				continue;
			}

			$groups[] = [
				'ID' => $group['id'],
				'TYPE_ID' => $group['typeId'],
			];
		}

		$this->form->merge([
			'DEPENDENCIES' => $deps,
			'DEP_GROUPS' => $groups,
		]);

		return $this;
	}


	private static function filterDependencies(array $deps)
	{
		$dict = Dictionary::instance()->getDeps();
		$actionTypes = array_column($dict['action']['types'], 'id');
		$conditionEvents = array_column($dict['condition']['events'], 'id');
		$conditionOperations = array_column($dict['condition']['operations'], 'id');
		$conditionOperations[] = '<>';

		$result = [];
		foreach ($deps as $dep)
		{
			if (!is_array($dep))
			{
				continue;
			}

			$condition = $dep['condition'] ?? null;
			$action = $dep['action'] ?? null;
			if (!$condition || !$action)
			{
				continue;
			}

			// TODO: $condition['target'] check existed in fields
			$condition['event'] = $condition['event'] ?? null;
			if (!$condition['event'] || !in_array($condition['event'], $conditionEvents))
			{
				$condition['event'] = $conditionEvents[0];
			}
			$condition['operation'] = $condition['operation'] ?? null;
			if (!$condition['operation'] || !in_array($condition['operation'], $conditionOperations))
			{
				$condition['operation'] = $conditionOperations[0];
			}
			$condition['value'] = $condition['value'] ?? null;

			// TODO: $action['target'] check existed in fields
			$action['type'] = $action['type'] ?? null;
			if (!$action['type'] || !in_array($action['type'], $actionTypes))
			{
				$action['type'] = $actionTypes[0];
			}
			$action['value'] = $action['value'] ?? null;

			$result[] = [
				'condition' => $condition,
				'action' => $action,
			];
		}

		return $result;
	}
}
