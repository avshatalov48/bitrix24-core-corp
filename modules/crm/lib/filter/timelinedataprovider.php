<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class TimelineDataProvider extends Main\Filter\DataProvider
{
	/** @var TimelineSettings|null */
	protected $settings = null;

	function __construct(TimelineSettings $settings)
	{
		$this->settings = $settings;
	}

	/**
	 * Get Settings
	 * @return TimelineSettings
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Prepare field list.
	 * @return Field[]
	 * @throws Main\ArgumentException
	 */
	public function prepareFields()
	{
		$result = array(
			'ENTRY_CATEGORY_ID' => $this->createField(
				'ENTRY_CATEGORY_ID',
				array(
					'name' => Loc::getMessage('CRM_TIMELINE_FILTER_ENTRY_CATEGORY'),
					'type' => 'list',
					'default' => true,
					'partial' => true
				)
			),
			'CREATED' => $this->createField(
				'CREATED',
				array(
					'name' => Loc::getMessage('CRM_TIMELINE_FILTER_CREATED'),
					'type' => 'date',
					'default' => true
				)
			),
			'AUTHOR_ID' => $this->createField(
				'AUTHOR_ID',
				array(
					'name' => Loc::getMessage('CRM_TIMELINE_FILTER_AUTHOR'),
					'type' => 'dest_selector',
					'default' => true,
					'partial' => true
				)
			),
			'CLIENT' => $this->createField(
				'CLIENT',
				array(
					'name' => Loc::getMessage('CRM_TIMELINE_FILTER_CLIENT'),
					'type' => 'dest_selector',
					'default' => false,
					'partial' => true
				)
			),
			'ACTIVITY' => $this->createField(
				'ACTIVITY',
				[
					'name' => Loc::getMessage('CRM_TIMELINE_FILTER_ACTIVITY'),
					'type' => 'entity_selector',
					'partial' => true
				]
			)
		);

		return $result;
	}

	/**
	 * Prepare complete field data for specified field.
	 * @param string $fieldID Field ID.
	 * @return array|null
	 */
	public function prepareFieldData($fieldID)
	{
		if($fieldID === 'ENTRY_CATEGORY_ID')
		{
			return array(
				'params' => array('multiple' => 'Y'),
				'items' => TimelineEntryCategory::getDescriptions()
			);
		}
		elseif($fieldID === 'AUTHOR_ID')
		{
			return array(
				'params' => array(
					'context' => 'CRM_TIMELINE_FILTER_AUTHOR_ID',
					'multiple' => 'Y',
					'contextCode' => 'U',
					'enableAll' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'isNumeric' => 'Y',
					'prefix' => 'U'
				)
			);
		}
		elseif($fieldID === 'CLIENT')
		{

			return array(
				'params' => array(
					'apiVersion' => 3,
					'context' => 'CRM_TIMELINE_FILTER_CLIENT',
					'contextCode' => 'CRM',
					'useClientDatabase' => 'N',
					'enableAll' => 'N',
					'enableDepartments' => 'N',
					'enableUsers' => 'N',
					'enableSonetgroups' => 'N',
					'allowEmailInvitation' => 'N',
					'allowSearchEmailUsers' => 'N',
					'departmentSelectDisable' => 'Y',
					'enableCrm' => 'Y',
					'enableCrmContacts' => 'Y',
					'enableCrmCompanies' => 'Y',
					'addTabCrmContacts' => 'Y',
					'addTabCrmCompanies' => 'Y',
					'convertJson' => 'Y',
					'multiple' => 'Y'
				)
			);
		}
		elseif ($fieldID === 'ACTIVITY')
		{
			return [
				'params' => [
					'contextCode' => 'CRM',
					'multiple' => 'N',
					'dialogOptions' => [
						'context' => 'CRM_TIMELINE_FILTER_ACTIVITY',
						'height' => 200,
						'entities' => [
							[
								'id' => 'activity',
								'dynamicLoad' => true,
								'dynamicSearch' => true,
								'options' => [
									'entityId' => $this->getSettings()->getOwnerId(),
									'entityTypeId' => $this->getSettings()->getOwnerTypeId(),
								],
							]
						],
						'dropdownMode' => false,
					],
				],
			];
		}

		return null;
	}

	/**
	 * @param Main\Entity\Query $query
	 * @param array $filter
	 */
	public static function prepareQuery($query, $filter)
	{

		//region Search Content
		$searchContentBuilder = new Crm\Search\TimelineSearchContentBuilder();
		$searchContentBuilder->convertEntityFilterValues($filter);
		$searchContentFilter = $searchContentBuilder->prepareEntityFilter($filter);

		if(!empty($searchContentFilter))
		{
			$searchContentQuery = new Main\Entity\Query(Crm\Timeline\Entity\TimelineSearchTable::getEntity());
			foreach($searchContentFilter as $k => $v)
			{
				$searchContentQuery->addFilter($k, $v);
			}

			$searchContentQuery->addSelect('OWNER_ID');
			$query->registerRuntimeField('',
				new Main\Entity\ReferenceField('search',
					Main\Entity\Base::getInstanceByQuery($searchContentQuery),
					array('=this.ID' => 'ref.OWNER_ID'),
					array('join_type' => 'INNER')
				)
			);
		}
		//endregion

		TimelineEntryCategory::prepareQuery($query, $filter);

		$createdDateFilter = Main\Entity\Query::filter();
		$createdDateFilter->logic('and');
		foreach(Crm\UI\Filter\EntityHandler::findAllFieldOperations('CREATED', $filter) as $operationInfo)
		{
			$date = $operationInfo['CONDITION'] instanceof Main\Type\DateTime
				? $operationInfo['CONDITION'] : Main\Type\DateTime::tryParse($operationInfo['CONDITION']);

			if($date !== null)
			{
				$createdDateFilter->where('CREATED', $operationInfo['OPERATION'], $date);
			}
		}

		if($createdDateFilter->hasConditions())
		{
			$query->where($createdDateFilter);
		}

		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('AUTHOR_ID', $filter);
		if(is_array($operationInfo))
		{
			if(is_array($operationInfo['CONDITION']) && $operationInfo['OPERATION'] === '=')
			{
				$query->whereIn('AUTHOR_ID', $operationInfo['CONDITION']);
			}
			else
			{
				$query->where('AUTHOR_ID', $operationInfo['OPERATION'], $operationInfo['CONDITION']);
			}
		}

		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('CLIENT', $filter);
		if(is_array($operationInfo))
		{
			$clientFilter = Main\Entity\Query::filter();
			$clientFilter->logic('or');
			foreach($operationInfo['CONDITION'] as $condition)
			{
				$entityInfo = \CCrmOwnerType::ParseEntitySlug($condition);
				if(is_array($entityInfo))
				{
					$clientFilter->where(
						Main\Entity\Query::filter()
							->where('ENTITY_TYPE_ID', '=', $entityInfo['ENTITY_TYPE_ID'])
							->where('ENTITY_ID', '=', $entityInfo['ENTITY_ID'])
					);
				}
			}

			if($clientFilter->hasConditions())
			{
				$clientBindingQuery = new Main\Entity\Query(Crm\Timeline\Entity\TimelineBindingTable::getEntity());
				$clientBindingQuery->addSelect('OWNER_ID');
				$clientBindingQuery->where($clientFilter);

				$query->registerRuntimeField('',
					new Main\Entity\ReferenceField('client_bind',
						Main\Entity\Base::getInstanceByQuery($clientBindingQuery),
						array('=this.ID' => 'ref.OWNER_ID'),
						array('join_type' => 'INNER')
					)
				);
			}
		}

		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('ACTIVITY', $filter);
		if (is_array($operationInfo))
		{
			$query->where('ASSOCIATED_ENTITY_ID', (int)($filter['ACTIVITY'] ?? 0));
			$query->where('ASSOCIATED_ENTITY_TYPE_ID', \CCrmOwnerType::Activity);
		}
	}
}