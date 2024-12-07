<?php

namespace Bitrix\Crm\Activity\Provider\ToDo;

use Bitrix\Crm\Activity\Provider\ToDo\Block\Address;
use Bitrix\Crm\Activity\Provider\ToDo\Block\Calendar;
use Bitrix\Crm\Activity\Provider\ToDo\Block\Client;
use Bitrix\Crm\Activity\Provider\ToDo\Block\File;
use Bitrix\Crm\Activity\Provider\ToDo\Block\Link;

final class BlocksManager
{
	private OptionallyConfigurable $entity;
	private bool $needPreSave = false;

	public static function createFromEntity(OptionallyConfigurable $entity): self
	{
		return new self($entity);
	}

	private function __construct(OptionallyConfigurable $entity)
	{
		$this->entity = $entity;
	}

	public function preEnrichEntity(?array $blocks = []): SaveConfig
	{
		$blocksData = $this->getPreparedBlocks($blocks);
		$activityData = $this->getActivityData();

		$needSave = false;
		foreach ($blocksData as $blockData)
		{
			$blockInstance = BlocksFactory::getInstance($blockData['id'], $blockData, $activityData);

			$saveConfig = $blockInstance?->prepareEntityBefore($this->entity);
			if ($saveConfig->isNeedSave())
			{
				$needSave = true;
			}
		}

		return new SaveConfig($needSave);
	}

	public function enrichEntityWithBlocks(
		?array $blocks = [],
		bool $skipActiveSectionCheck = false,
		bool $preEnrichEntity = true
	): OptionallyConfigurable
	{
		$blocksData = $this->getPreparedBlocks($blocks);
		$activityData = $this->getActivityData();

		foreach ($blocksData as $blockData)
		{
			$blockInstance = BlocksFactory::getInstance($blockData['id'], $blockData, $activityData);

			if ($preEnrichEntity)
			{
				$blockInstance?->prepareEntityBefore($this->entity);
			}
			$blockInstance?->prepareEntity($this->entity, $skipActiveSectionCheck);
		}

		$this->prepareEnrichedEntity();

		return $this->entity;
	}

	protected function getPreparedBlocks(?array $blocks = []): array
	{
		if ($blocks === null)
		{
			$blocks = $this->fetch();
			foreach ($blocks as &$block)
			{
				$block = [
					...$block['data'],
					'id' => $block['id'],
				];
			}
			unset($block);
		}
		elseif (empty($blocks))
		{
			$blocks = self::getBlocks();
		}

		return $blocks;
	}

	protected function prepareEnrichedEntity(): void
	{
		$additionalFields = $this->entity->getAdditionalFields();
		$calendarAdditionalDescriptionData = $additionalFields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA'] ?? null;
		$settings = $additionalFields['SETTINGS'] ?? null;

		if (!$calendarAdditionalDescriptionData)
		{
			return;
		}

		if (
			empty($settings['ADDRESS_FORMATTED'])
			&& isset($calendarAdditionalDescriptionData['CALENDAR_LOCATION'])
		)
		{
			unset($additionalFields['CALENDAR_ADDITIONAL_DESCRIPTION_DATA']['CALENDAR_LOCATION']);
			$this->entity->setAdditionalFields($additionalFields);
		}
	}

	public function getEntityOptions(array $blocks): array
	{
		$activityData = $this->getActivityData();

		$options = [];

		foreach ($blocks as $block)
		{
			$blockInstance = BlocksFactory::getInstance($block['id'], $block, $activityData);
			if ($blockInstance)
			{
				$options = array_merge($options, $blockInstance->getOptions($this->entity));
			}
		}

		return $options;
	}

	public function fetchAsPlainArray(): array
	{
		$blocks = $this->fetch();

		$result = [];

		foreach ($blocks as $block)
		{
			$result[] = [
				...$block['data'],
				'id' => $block['id'],
			];
		}

		return $result;
	}

	public function fetch(): array
	{
		$result = [];

		foreach (self::getBlocks() as $section)
		{
			$data = BlocksFactory::getInstance($section['id'], [], $this->getActivityData())->fetchSettings();
			if (empty($data))
			{
				continue;
			}

			$result[] = [
				'id' => $section['id'],
				'data' => $data,
			];
		}

		return $result;
	}

	public static function getBlocks(): array
	{
		return [
			[
				'id' => Calendar::TYPE_NAME,
				'sort' => 100,
			],
			[
				'id' => Client::TYPE_NAME,
				'sort' => 200,
			],
			[
				'id' => Address::TYPE_NAME,
				'sort' => 300,
			],
			[
				'id' => Link::TYPE_NAME,
				'sort' => 400,
			],
			[
				'id' => File::TYPE_NAME,
				'sort' => 500,
			],
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
			'settings' => $entity->getSettings(),
			'storageElementIds' => $entity->getStorageElementIds(),
		];
	}
}
