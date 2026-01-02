<?php

namespace Bitrix\Tasks\Flow\Option\FlowUserOption;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Flow\Internal\FlowUserOptionTable;
use Bitrix\Tasks\Integration\Pull\PushService;

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

	public function save(FlowUserOption $option): void
	{
		$this->validateName($option->getName());

		$insertFields = [
			'FLOW_ID' => $option->getFlowId(),
			'USER_ID' => $option->getUserId(),
			'NAME' => $option->getName(),
			'VALUE' => $option->getValue(),
		];

		$updateFields = [
			'VALUE' => $option->getValue(),
		];

		$uniqueFields = ['FLOW_ID', 'NAME', 'USER_ID'];

		FlowUserOptionTable::merge($insertFields, $updateFields, $uniqueFields);

		$this->onOptionChanged($option);
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

		$newOption = new FlowUserOption(
			$flowId,
			$userId,
			FlowUserOptionDictionary::FLOW_PINNED_FOR_USER->value,
			$value,
		);
		$this->save($newOption);

		return $newOption;
	}

	private function onOptionChanged(FlowUserOption $option): void
	{
		if ($this->isSendPushNeeded($option))
		{
			$this->addPushEvent($option);
		}
	}

	private function addPushEvent(FlowUserOption $option): void
	{
		$command = $option->getName();
		$userId = $option->getUserId();

		$params = [
			'FLOW_ID' => $option->getFlowId(),
			'VALUE' => $option->getValue(),
		];

		PushService::addEvent([$userId], [
			'module_id' => 'tasks',
			'command' => $command,
			'params' => $params,
		]);
	}

	private function isSendPushNeeded(FlowUserOption $option): bool
	{
		$name = FlowUserOptionDictionary::tryFrom($option->getName());

		return in_array($name, FlowUserOptionDictionary::NOTIFIABLE_OPTIONS, true);
	}
}