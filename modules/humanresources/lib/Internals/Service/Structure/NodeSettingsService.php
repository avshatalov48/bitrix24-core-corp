<?php

namespace Bitrix\HumanResources\Internals\Service\Structure;

use Bitrix\HumanResources\Item\Collection\NodeSettingsCollection;
use Bitrix\HumanResources\Item\NodeSettings;
use Bitrix\HumanResources\Repository\NodeSettingsRepository;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\NodeSettingsType;
use Bitrix\Main\Result;

class NodeSettingsService
{
	private NodeSettingsRepository $nodeSettingsRepository;

	public function __construct(?NodeSettingsRepository $nodeSettingsRepository = null)
	{
		$this->nodeSettingsRepository = $nodeSettingsRepository ?? Container::getNodeSettingsRepository();
	}

	public function save(int $nodeId, array $settingsMap): Result
	{
		foreach ($settingsMap as $type => $settings)
		{
			$settingsCollection = new NodeSettingsCollection();

			if ($settings['replace'] ?? false)
			{
				$this->nodeSettingsRepository->removeByTypeAndNodeId(
					$nodeId,
					NodeSettingsType::from($type),
				);
			}

			foreach ($settings['values'] as $value)
			{
				$settingsCollection->add(
					new NodeSettings(
						$nodeId,
						NodeSettingsType::from($type),
						$value,
					),
				);
			}

			$this->nodeSettingsRepository->createByCollection($settingsCollection);
		}

		return new Result();
	}
}