<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Activity\FastSearch\Sync\ActivitySearchData;
use Bitrix\Crm\Activity\Provider\Base;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Filter\Field;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;

class ActivityFastSearchDataProvider extends EntityDataProvider
{

	private ActivityFastSearchSettings $settings;

	public function __construct(ActivityFastSearchSettings $settings)
	{
		$this->settings = $settings;
	}

	public function getSettings(): Settings
	{
		return $this->settings;
	}

	/**
	 * @return array|Main\Filter\Field[]
	 */
	public function prepareFields(): array
	{
		if (Option::get('crm', 'enable_act_fastsearch_filter', 'Y') === 'N')
		{
			return [];
		}

		$result = [
			'ACTIVITY_FASTSEARCH_CREATED' => $this->createField(
				'ACTIVITY_FASTSEARCH_CREATED',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_CREATED'),
					'type' => 'list',
					'partial' => true,
				]
			),

			'ACTIVITY_FASTSEARCH_DEADLINE' => $this->createField(
				'ACTIVITY_FASTSEARCH_DEADLINE',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_DEADLINE'),
					'type' => 'date',
					'partial' => true,
				]
			),

			'ACTIVITY_FASTSEARCH_RESPONSIBLE_ID' => $this->createField(
				'ACTIVITY_FASTSEARCH_RESPONSIBLE_ID',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_RESPONSIBLE_ID'),
					'type' => 'entity_selector',
					'partial' => true,
				]
			),

			'ACTIVITY_FASTSEARCH_AUTHOR_ID' => $this->createField(
				'ACTIVITY_FASTSEARCH_AUTHOR_ID',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_AUTHOR_ID'),
					'type' => 'entity_selector',
					'partial' => true,
				]
			),

			'ACTIVITY_FASTSEARCH_COMPLETED' => $this->createField(
				'ACTIVITY_FASTSEARCH_COMPLETED',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_COMPLETED'),
					'type' => 'list',
					'partial' => true,
				]
			),

			'ACTIVITY_FASTSEARCH_ACTIVITY_TYPE' => $this->createField(
				'ACTIVITY_FASTSEARCH_ACTIVITY_TYPE',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_ACTIVITY_TYPE'),
					'type' => 'list',
					'partial' => true,
				]
			),

			'ACTIVITY_FASTSEARCH_ACTIVITY_KIND' => $this->createField(
				'ACTIVITY_FASTSEARCH_ACTIVITY_KIND',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_ACTIVITY_KIND'),
					'type' => 'list',
					'partial' => true,
				]
			),
		];

		/** @var Field $field */
		foreach ($result as $field)
		{
			$field->setSectionId('ACTIVITY_FASTSEARCH');
			$field->setIconParams([
				'url' => '/bitrix/images/crm/grid_icons/activity.svg',
				'title' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_ACTIVITY'),
			]);
		}

		return $result;
	}

	public function prepareFieldData($fieldID)
	{
		if ($fieldID === 'ACTIVITY_FASTSEARCH_ACTIVITY_KIND')
		{
			return [
				'params' => ['multiple' => 'N'],
				'items' => [
					ActivitySearchData::KIND_COMMON => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_TYPE_COMMON'),
					ActivitySearchData::KIND_INCOMING => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_TYPE_INCOMING'),
				]
			];
		}
		elseif ($fieldID === 'ACTIVITY_FASTSEARCH_ACTIVITY_TYPE')
		{
			return [
				'params' => ['multiple' => 'Y'],
				'items' => Base::makeTypeCodeNameList()
			];
		}
		elseif (in_array($fieldID, ['ACTIVITY_FASTSEARCH_RESPONSIBLE_ID', 'ACTIVITY_FASTSEARCH_AUTHOR_ID']))
		{
			return $this->getUserEntitySelectorParams(
				EntitySelector::CONTEXT,
				[
					'fieldName' => $fieldID,
					'referenceClass' => null,
					'isEnableAllUsers' => false,
					'isEnableOtherUsers' => false,
				]
			);
		}
		elseif ($fieldID === 'ACTIVITY_FASTSEARCH_CREATED')
		{
			return [
				'params' => ['multiple' => 'N'],
				'items' => [
					365 => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_CREATED_YEAR_AGO'),
					180 => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_CREATED_6_MONTH_AGO'),
					90 => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_CREATED_3_MONTH_AGO'),
					30 => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_CREATED_MONTH_AGO'),
					7 => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_CREATED_WEEK_AGO'),
				]
			];
		}
		elseif ($fieldID === 'ACTIVITY_FASTSEARCH_COMPLETED')
		{
			return [
				'params' => ['multiple' => 'N'],
				'items' => [
					'N' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_COMPLETED_VALUE_OPEN'),
					'Y' => Loc::getMessage('CRM_ACTIVITY_FASTSEARCH_FILTER_COMPLETED_VALUE_CLOSE'),
				]
			];
		}
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$parentEntityDataProvider = $this->settings->getParentEntityDataProvider();
		$parentEntityTypeId = $this->settings->getParentFilterEntityTypeId();

		$parentEntityDataProvider->applyActivityFastSearchFilter(
			$parentEntityTypeId,
			$rawFilterValue,
		);

		return $rawFilterValue;
	}

}