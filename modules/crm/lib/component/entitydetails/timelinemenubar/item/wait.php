<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Service\Container;

class Wait extends Item
{
	public function getId(): string
	{
		return 'wait';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_WAIT');
	}

	public function isAvailable(): bool
	{
		if (!\Bitrix\Crm\Settings\ActivitySettings::areOutdatedCalendarActivitiesEnabled())
		{
			return false;
		}

		return (
			in_array($this->getEntityTypeId(), [
				\CCrmOwnerType::Lead,
				\CCrmOwnerType::Deal,
				\CCrmOwnerType::Order,
			])
			|| \CCrmOwnerType::isUseDynamicTypeBasedApproach($this->getEntityTypeId())
		);
	}


	public function prepareSettings(): array
	{
		$optionName = mb_strtolower($this->context->getGuid());
		$editorConfig = (array)\CUserOptions::GetOption('crm.timeline.wait', $optionName, []);
		$editorWaitTargetDates = $this->getWaitTargetDates();

		return [
			'config' => $editorConfig,
			'targetDates' => $editorWaitTargetDates,
			'serviceUrl' => '/bitrix/components/bitrix/crm.timeline/ajax.php?&site='.SITE_ID.'&'.bitrix_sessid_get(),
			'optionName' => $optionName,
		];
	}

	private function getWaitTargetDates(): array
	{
		return array_merge(
			$this->getWaitTargetDatesFromStandardFields(),
			$this->getWaitTargetDatesFromUserFields(),
		);
	}

	private function getWaitTargetDatesFromStandardFields(): array
	{
		if ($this->getEntityTypeId() === \CCrmOwnerType::Deal)
		{
			return [
					[
						'name' => 'BEGINDATE',
						'caption' => \CCrmDeal::GetFieldCaption('BEGINDATE'),
					],
					[
						'name' => 'CLOSEDATE',
						'caption' => \CCrmDeal::GetFieldCaption('CLOSEDATE'),
					],
				];
		}

		return [];
	}

	private function getWaitTargetDatesFromUserFields(): array
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		if (!$factory)
		{
			return [];
		}
		$result = [];
		$userFields = $factory->getUserFields();
		foreach($userFields as $userField)
		{
			if($userField['USER_TYPE_ID'] === 'date' && $userField['MULTIPLE'] !== 'Y')
			{
				$result[] = [
					'name' => $userField['FIELD_NAME'],
					'caption' => $userField['EDIT_FORM_LABEL'] ?? $userField['FIELD_NAME'],
				];
			}
		}

		return $result;
	}
}
