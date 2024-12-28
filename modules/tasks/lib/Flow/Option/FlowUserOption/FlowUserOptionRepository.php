<?php

namespace Bitrix\Tasks\Flow\Option\FlowUserOption;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\FlowUserOptionTable;

class FlowUserOptionRepository
{
	use FlowUserOptionValidatorTrait;
	private static self $instance;

	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getOptionForUser(int $flowId, int $userId, string $code): ?FlowUserOption
	{
		return $this->getOptions(['FLOW_ID' => $flowId, 'USER_ID' => $userId, 'CODE' => $code])[0] ?? null;
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getOptions(array $filter): array
	{
		$query = FlowUserOptionTable::query();

		if (isset($filter['FLOW_ID']))
		{
			$query->addFilter('FLOW_ID', $filter['FLOW_ID']);
		}

		if (isset($filter['NAME']))
		{
			$this->validateCode($filter['NAME']);

			$query->addFilter('NAME', $filter['NAME']);
		}

		if (isset($filter['USER_ID']))
		{
			$query->addFilter('USER_ID', $filter['USER_ID']);
		}

		if (isset($filter['VALUE']))
		{
			$query->addFilter('VALUE', $filter['VALUE']);
		}

		$optionCollection = $query->fetchCollection();

		$result = [];

		foreach ($optionCollection as $option)
		{
			$result[] = new FlowUserOption($option->getFlowId(), $option->getUserId(), $option->getName(), $option->getValue());
		}

		return $result;
	}
}