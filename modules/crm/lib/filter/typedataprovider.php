<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Field;
use Bitrix\Main\Localization\Loc;

class TypeDataProvider extends EntityDataProvider
{
	protected $settings;

	public function __construct(TypeSettings $settings)
	{
		$this->settings = $settings;
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function getSettings(): TypeSettings
	{
		return $this->settings;
	}

	protected function getFieldName($fieldID): string
	{
		$result = null;

		$entity = $this->settings->getEntity();
		if($entity->hasField($fieldID))
		{
			$result = $entity->getField($fieldID)->getTitle();
		}

		if(!is_string($result))
		{
			$result = $fieldID;
		}

		return $result;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 */
	public function prepareFields(): array
	{
		$result = [];

		$fields = [
			'ID' => [
				'options' => [
					'type' => 'number',
					'default' => false,
				]
			],
			'ENTITY_TYPE_ID' => [
				'options' => [
					'default' => true,
					'type' => 'number',
				]
			],
			'TITLE' => [
				'options' => [
					'default' => true,
				],
			],
			'CREATED_BY' => [
				'options' => [
					'default' => true,
					'type' => 'dest_selector',
					'partial' => true,
				]
			],
		];

		foreach($fields as $name => $field)
		{
			$result[$name] = $this->createField($name, (!empty($field['options']) ? $field['options'] : []));
		}

		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 */
	public function prepareFieldData($fieldID): ?array
	{
		$result = null;

		if ($fieldID === 'CREATED_BY')
		{
			$result = [
				'params' => [
					'apiVersion' => 3,
					'context' => 'CRM_TYPE_FILTER_CREATED_BY',
					'multiple' => 'N',
					'contextCode' => 'U',
					'enableDepartments' => 'N',
					'departmentFlatEnable' => 'N',
					'enableAll' => 'N',
					'enableUsers' => 'Y',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'N',
					'isNumeric' => 'N',
				]
			];
		}

		return $result;
	}

	public function getGridColumns(): array
	{
		return [
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
				'sort' => 'ID',
			],
			[
				'id' => 'ENTITY_TYPE_ID',
				'name' => $this->getFieldName('ENTITY_TYPE_ID'),
				'default' => true,
				'sort' => 'ENTITY_TYPE_ID',
			],
			[
				'id' => 'TITLE',
				'name' => $this->getFieldName('TITLE'),
				'default' => true,
				'sort' => 'TITLE',
			],
			[
				'id' => 'CREATED_BY',
				'name' => $this->getFieldName('CREATED_BY'),
				'default' => true,
				'sort' => 'CREATED_BY',
			],
		];
	}

	public function prepareListFilter(array &$filter, array $requestFilter)
	{
		if(isset($requestFilter['ID_from']) && $requestFilter['ID_from'] > 0)
		{
			$filter['>=ID'] = (int) $requestFilter['ID_from'];
		}
		if(isset($requestFilter['ID_to']) && $requestFilter['ID_to'] > 0)
		{
			$filter['<=ID'] = (int) $requestFilter['ID_to'];
		}
		if(isset($requestFilter['ENTITY_TYPE_ID_from']) && $requestFilter['ENTITY_TYPE_ID_from'] > 0)
		{
			$filter['>=ENTITY_TYPE_ID'] = (int) $requestFilter['ENTITY_TYPE_ID_from'];
		}
		if(isset($requestFilter['ENTITY_TYPE_ID_to']) && $requestFilter['ENTITY_TYPE_ID_to'] > 0)
		{
			$filter['<=ENTITY_TYPE_ID'] = (int) $requestFilter['ENTITY_TYPE_ID_to'];
		}
		$titleSearch = null;
		if(isset($requestFilter['TITLE']) && !empty($requestFilter['TITLE']))
		{
			$titleSearch = $requestFilter['TITLE'];
		}
		elseif(isset($requestFilter['FIND']) && !empty($requestFilter['FIND']))
		{
			$titleSearch = $requestFilter['FIND'];
		}
		if($titleSearch)
		{
			$filter['TITLE'] = '%'.$titleSearch.'%';
		}
		if(isset($requestFilter['CREATED_BY']) && !empty($requestFilter['CREATED_BY']))
		{
			$userId = (int)substr($requestFilter['CREATED_BY'], 1);
			if($userId > 0)
			{
				$filter['=CREATED_BY'] = $userId;
			}
		}
	}
}