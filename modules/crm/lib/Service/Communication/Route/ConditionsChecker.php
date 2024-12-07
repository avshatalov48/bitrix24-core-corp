<?php

namespace Bitrix\Crm\Service\Communication\Route;

use Bitrix\Crm\Service\Communication\Channel\Event\ChannelEventPropertiesCollection;
use Bitrix\Crm\Service\Communication\Channel\Property\Common\BaseType;
use Bitrix\Crm\Service\Communication\Channel\Property\PropertiesCollection;
use Bitrix\Crm\Service\Communication\Channel\Property\PropertiesManager;

final class ConditionsChecker
{
	private PropertiesManager $propertiesManager;
	private PropertiesCollection $commonProperties;
	private array $bindings = [];

	/**
	 * @var BaseType[] $commonPropertyInstances
	 */
	private array $commonPropertyInstances;

	public function __construct()
	{
		$this->propertiesManager = PropertiesManager::getInstance();
		$this->commonProperties = $this->propertiesManager->getCommonProperties();
	}

	public function isSuitableCondition(
		array $channelConditions,
		ChannelEventPropertiesCollection $eventPropertiesCollection
	): bool
	{
		$resultExpressionParts = $this->getExpressionParts($channelConditions, $eventPropertiesCollection);

		$value = false;

		$wasUseExpression = false;
		foreach ($resultExpressionParts as $index => $item)
		{
			if ($wasUseExpression)
			{
				$wasUseExpression = false;

				continue;
			}

			if (is_bool($item))
			{
				$value = $item;
			}
			else
			{
				if ($item === '&&')
				{
					$value = $value && $resultExpressionParts[$index + 1] ?? false;
				}
				else
				{
					$value = $value || $resultExpressionParts[$index + 1] ?? false;
				}

				$wasUseExpression = true;
			}
		}

		return $value;
	}

	private function getExpressionParts(
		array $channelConditions,
		ChannelEventPropertiesCollection $eventPropertiesCollection
	): array
	{
		$resultExpressionParts = [];

		foreach ($channelConditions as $channelCondition)
		{
			if ($this->isCommonProperty($channelCondition['code']))
			{
				$commonProperty = $this->getCommonPropertyInstance($channelCondition['code']);

				if ($commonProperty === null)
				{
					continue;
				}

				$resultExpressionParts[] = in_array($commonProperty->getValue(), $channelCondition['values']);
			}
			else
			{
				$eventParam = $eventPropertiesCollection->getByCode($channelCondition['code']);
				if ($eventParam === null)
				{
					continue;
				}

				$resultExpressionParts[] = in_array($eventParam->getValue(), $channelCondition['values']);
			}

			if ($channelCondition['logic'] === 'AND')
			{
				$resultExpressionParts[] = '&&';
			}
			else
			{
				$resultExpressionParts[] = '||';
			}
		}

		array_pop($resultExpressionParts);

		return $resultExpressionParts;
	}

	private function isCommonProperty(string $propertyCode): bool
	{
		return $this->commonProperties->hasProperty($propertyCode);
	}

	private function getCommonPropertyInstance(string $code): ?BaseType
	{
		if (isset($this->commonPropertyInstances[$code]))
		{
			return $this->commonPropertyInstances[$code];
		}

		$this->commonPropertyInstances[$code] = $this->propertiesManager::getCommonPropertyInstance($code, $this->bindings);

		return $this->commonPropertyInstances[$code];
	}
}
