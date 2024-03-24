<?php
namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Activity\Provider\Base;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main\Localization\Loc;
use CCrmActivityPriority;

Loc::loadMessages(__FILE__);

class ActivityDataProvider extends EntityDataProvider implements FactoryOptionable
{
	use ForceUseFactoryTrait;

	protected ?ActivitySettings $settings = null;

	function __construct(ActivitySettings $settings)
	{
		$this->settings = $settings;
	}

	public function getSettings(): ?ActivitySettings
	{
		return $this->settings;
	}

	protected function getFieldName($fieldID): ?string
	{
		return Loc::getMessage("CRM_ACTIVITY_FILTER_{$fieldID}");
	}

	public function prepareFields(): array
	{
		$result = [
			'ID' => $this->createField('ID'),
			'RESPONSIBLE_ID' => $this->createField(
				'RESPONSIBLE_ID',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_RESPONSIBLE_ID'),
					'type' => 'entity_selector',
					'partial' => true,
				]
			),
			'AUTHOR_ID' => $this->createField(
				'AUTHOR_ID',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_AUTHOR_ID'),
					'type' => 'entity_selector',
					'partial' => true,
				]
			),
			'BINDING_OWNER_TYPE_ID' => $this->createField(
				'BINDING_OWNER_TYPE_ID',
				[
					'type' => 'list',
					'default' => false,
					'partial' => true,
				]
			),
			'STATUS_SEMANTIC_ID' => $this->createField(
				'STATUS_SEMANTIC_ID',
				[
					'type' => 'list',
					'partial' => true
				]
			),
			'CREATED' => $this->createField(
				'CREATED',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_CREATED'),
					'type' => 'list',
					'partial' => true,
				]
			),
			'DEADLINE' => $this->createField(
				'DEADLINE',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_DEADLINE'),
					'type' => 'date',
					'partial' => true,
				]
			),
			'TYPE_ID' => $this->createField(
				'TYPE_ID',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_TYPE'),
					'type' => 'list',
					'partial' => true,
				]
			),
			'IS_INCOMING_CHANNEL' => $this->createField(
				'IS_INCOMING_CHANNEL',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_KIND'),
					'type' => 'list',
					'partial' => true,
				]
			),
			'ACTIVITY_COUNTER' => $this->createField(
				'ACTIVITY_COUNTER',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_COUNTER'),
					'type' => 'list',
					'partial' => true,
				]
			),
			'PRIORITY' => $this->createField(
				'PRIORITY',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_PRIORITY'),
					'type' => 'list',
					'partial' => true,
				]
			),
			'START_TIME' => $this->createField(
				'START_TIME',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_START'),
					'type' => 'date',
				]
			),
			'END_TIME' => $this->createField(
				'END_TIME',
				[
					'name' => Loc::getMessage('CRM_ACTIVITY_FILTER_END'),
					'type' => 'date',
				]
			),
		];

		return $result;
	}

	public function prepareFieldData($fieldID): ?array
	{
		if (in_array($fieldID, ['RESPONSIBLE_ID', 'AUTHOR_ID']))
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

		if ($fieldID === 'BINDING_OWNER_TYPE_ID')
		{
			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => [
					\CCrmOwnerType::Lead => Loc::getMessage('CRM_ACTIVITY_FILTER_OWNER_TYPE_LIST_LEAD'),
					\CCrmOwnerType::Deal => Loc::getMessage('CRM_ACTIVITY_FILTER_OWNER_TYPE_LIST_DEAL'),
				],
			];
		}

		if ($fieldID === 'STATUS_SEMANTIC_ID')
		{
			return PhaseSemantics::getListFilterInfo(
				\CCrmOwnerType::Activity,
				[
					'params' => [
						'multiple' => 'Y',
					],
				]
			);
		}

		if ($fieldID === 'CREATED')
		{
			return [
				'type' => 'date',
				'partial' => true,
			];
		}

		if ($fieldID === 'IS_INCOMING_CHANNEL')
		{
			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => [
//					ActivitySearchData::KIND_COMMON => Loc::getMessage('CRM_ACTIVITY_FILTER_KIND_COMMON'),
//					ActivitySearchData::KIND_INCOMING => Loc::getMessage('CRM_ACTIVITY_FILTER_KIND_INCOMING'),
					'N' => Loc::getMessage('CRM_ACTIVITY_FILTER_KIND_COMMON'),
					'Y' => Loc::getMessage('CRM_ACTIVITY_FILTER_KIND_INCOMING'),
				],
			];
		}

		if ($fieldID === 'TYPE_ID')
		{
			return [
				'params' => [
					'multiple' => 'Y',
				],
				'items' => Base::makeTypeCodeNameList(),
			];
		}

		if ($fieldID === 'ACTIVITY_COUNTER')
		{
			return EntityCounterType::getListFilterInfo(
				[
					'params' => [
						'multiple' => 'Y',
					],
				],
				[
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Activity,
				]
			);
		}

		if ($fieldID === 'PRIORITY')
		{
			return [
				'params' => [
					'multiple' => 'Y',
				],
				'items' => CCrmActivityPriority::PrepareFilterItems(),
			];
		}

		return null;
	}
}
