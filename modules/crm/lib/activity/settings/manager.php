<?php

namespace Bitrix\Crm\Activity\Settings;

use Bitrix\Crm\Activity\Settings\Section\Calendar;
use Bitrix\Crm\Activity\Settings\Section\Ping;

class Manager
{
	private OptionallyConfigurable $entity;

	public static function createFromEntity(OptionallyConfigurable $entity): self
	{
		return new self($entity);
	}

	private function __construct(OptionallyConfigurable $entity)
	{
		$this->entity = $entity;
	}

	public function saveAll(array $settings): void
	{
		$activityData = $this->getActivityData();

		foreach ($settings as $data)
		{
			$settingsSectionInstance = Factory::getInstance($data['id'], $data, $activityData);
			$settingsSectionInstance->apply();
		}
	}

/*
	@todo may be needed in the future for other settings blocks
	public function updateSectionSettings(array $settings): void
	{
		$activityData = $this->getActivityData();

		$settingsSectionInstance = Factory::getInstance($settings['id'], $settings, $activityData);
		$settingsSectionInstance->update($this->entity);
	}

	public function removeSectionSettings(string $sectionName): void
	{
		$activityData = $this->getActivityData();

		$settingsSectionInstance = Factory::getInstance($sectionName, [], $activityData);
		$settingsSectionInstance->remove($this->entity);
	}*/

	public function getPreparedEntity(array $settings = [], $skipActiveSectionCheck = false): OptionallyConfigurable
	{
		$activityData = $this->getActivityData();

		if (empty($settings))
		{
			$settings = $this->getAllSettingsSections();
		}

		foreach ($settings as $data)
		{
			$settingsSectionInstance = Factory::getInstance($data['id'], $data, $activityData);
			$settingsSectionInstance->prepareEntity($this->entity, $skipActiveSectionCheck);
		}

		return $this->entity;
	}

	public function getEntityOptions(array $settings): array
	{
		$activityData = $this->getActivityData();

		$options = [];

		foreach ($settings as $data)
		{
			$settingsSectionInstance = Factory::getInstance($data['id'], $data, $activityData);
			$options = array_merge($options, $settingsSectionInstance->getOptions($this->entity));
		}

		return $options;
	}

	public function fetch(): array
	{
		$result = [];

		foreach ($this->getAllSettingsSections() as $section)
		{
			$result[] = Factory::getInstance($section['id'], [], $this->getActivityData())->fetchSettings();
		}

		return $result;
	}

	private function getAllSettingsSections(): array
	{
		return [
			['id' => Calendar::TYPE_NAME],
			['id' => Ping::TYPE_NAME],
		];
	}

	private function getActivityData(): array
	{
		$entity = $this->entity;

		return [
			'id' => $entity->getId(),
			'providerId' => $entity->getProviderId(),
			'ownerTypeId' => $entity->getOwner()->getEntityTypeId(),
			'ownerId' => $entity->getOwner()->getEntityId(),
			'name' => $entity->getDescription(),
			'calendarEventId' => $entity->getCalendarEventId(),
			'deadline' => $entity->getDeadline(),
		];
	}
}
