<?php

namespace Bitrix\Tasks\Flow\Option\FlowUserOption;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\FlowUserOptionTable;

class FlowUserOptionService
{
	use FlowUserOptionValidatorTrait;

	private static self $instance;
	private FlowUserOptionRepository $flowUserOptionRepository;

	public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct()
	{
		$this->flowUserOptionRepository = FlowUserOptionRepository::getInstance();
	}

	public function save(int $flowId, string $code, int $userId, string $value): void
	{
		$this->validateCode($code);

		$insertFields = [
			'FLOW_ID' => $flowId,
			'USER_ID' => $userId,
			'NAME' => $code,
			'VALUE' => $value,
		];

		$updateFields = [
			'VALUE' => $value,
		];

		$uniqueFields = ['FLOW_ID', 'NAME', 'USER_ID'];

		FlowUserOptionTable::merge($insertFields, $updateFields, $uniqueFields);
	}

	/**
	 * @throws ArgumentException
	 */
	public function deleteAllForFlow(int $flowId): void
	{
		FlowUserOptionTable::deleteByFilter(['FLOW_ID' => $flowId]);
	}

	/**
	 * @throws ArgumentException
	 */
	public static function deleteAllForUser(int $userId): void
	{
		FlowUserOptionTable::deleteByFilter(['USER_ID' => $userId]);
	}

	/**
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function changePinOption(int $flowId, int $userId): FlowUserOption
	{
		$option = $this->flowUserOptionRepository->getOptionForUser(
			$flowId,
			$userId,
			FlowUserOptionDictionary::FLOW_PINNED_FOR_USER->value
		);

		$value = 'Y';
		if (null !== $option)
		{
			$value = $option->getValue() === 'Y' ? 'N' : 'Y';
		}

		$this->save($flowId, FlowUserOptionDictionary::FLOW_PINNED_FOR_USER->value, $userId, $value);

		return new FlowUserOption($flowId, $userId, FlowUserOptionDictionary::FLOW_PINNED_FOR_USER->value, $value);
	}
}