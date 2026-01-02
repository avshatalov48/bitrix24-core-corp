<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\SocialNetwork\Collab\Provider\CollabDefaultProvider;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Util\User;

class PrepareGroup implements PrepareFieldInterface
{
	use ConfigTrait;
	
	public function __invoke(array $fields, array $fullTaskData): array
	{
		if (isset($fields['GROUP_ID']))
		{
			$fields['GROUP_ID'] = (int)$fields['GROUP_ID'];
		}

		$fields = $this->prepareCollab($fields, $fullTaskData);

		$this->checkEmptyGroupForExtranet($fields, $fullTaskData);

		$this->checkChangeToEmptyGroupForExtranet($fields, $fullTaskData);

		if (!$this->isGroupChanged($fields, $fullTaskData))
		{
			return $fields;
		}

		$fields = $this->changeStageId($fields);

		return $this->changeFlowId($fields, $fullTaskData);
	}

	private function changeFlowId(array $fields, array $fullTaskData): array
	{
		if (
			isset($fullTaskData['FLOW_ID'])
			&& !isset($fields['FLOW_ID'])
		)
		{
			$fields['FLOW_ID'] = 0;
		}

		return $fields;
	}

	private function changeStageId(array $fields): array
	{
		if ($fields['GROUP_ID'])
		{
			$fields['STAGE_ID'] = 0;
		}

		return $fields;
	}

	private function isGroupChanged(array $fields, array $fullTaskData): bool
	{
		if (!isset($fields['GROUP_ID']))
		{
			return false;
		}

		return (int)$fields['GROUP_ID'] !== (int)$fullTaskData['GROUP_ID'];
	}

	private function checkChangeToEmptyGroupForExtranet(array $fields, array $fullTaskData): void
	{
		if (!isset($fields['GROUP_ID']))
		{
			return;
		}

		if ((int)$fields['GROUP_ID'] !== 0)
		{
			return;
		}

		if (!isset($fullTaskData['GROUP_ID'])) {
			return;
		}

		if ((int)$fullTaskData['GROUP_ID'] === 0) {
			return;
		}

		if (User::isSuper($this->config->getUserId())) {
			return;
		}

		if (!\Bitrix\Tasks\Integration\Extranet\User::isExtranet($this->config->getUserId())) {
			return;
		}

		throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_GROUP'));
	}

	private function checkEmptyGroupForExtranet(array $fields, array $fullTaskData): void
	{
		if (!isset($fields['GROUP_ID']))
		{
			return;
		}

		if ((int)$fields['GROUP_ID'] !== 0)
		{
			return;
		}

		if (isset($fullTaskData['GROUP_ID']))
		{
			return;
		}

		if (User::isSuper($this->config->getUserId()))
		{
			return;
		}

		if (!\Bitrix\Tasks\Integration\Extranet\User::isExtranet($this->config->getUserId()))
		{
			return;
		}

		throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_GROUP'));
	}

	private function prepareCollab(array $fields, array $fullTaskData): array
	{
		$isCollaber = \Bitrix\Tasks\Integration\Extranet\User::isCollaber($this->config->getUserId());
		if (!$isCollaber)
		{
			return $fields;
		}

		$isGroupAlreadyFilled = isset($fullTaskData['GROUP_ID']) && (int)$fullTaskData['GROUP_ID'] !== 0;
		$isGroupUpdateOnEmpty = isset($fields['GROUP_ID']) && (int)$fields['GROUP_ID'] === 0;
		$isGroupUpdateOnCorrect = isset($fields['GROUP_ID']) && (int)$fields['GROUP_ID'] !== 0;

		if (
			($isGroupAlreadyFilled && !$isGroupUpdateOnEmpty)
			|| $isGroupUpdateOnCorrect
		)
		{
			return $fields;
		}

		$defaultCollab = CollabDefaultProvider::getInstance()?->getCollab($this->config->getUserId());
		$defaultCollabId = $defaultCollab?->getId();
		if ($defaultCollabId === null)
		{
			return $fields;
		}

		if (Group::can($defaultCollabId, Group::ACTION_CREATE_TASKS, $this->config->getUserId()))
		{
			$fields['GROUP_ID'] = $defaultCollabId;
		}

		return $fields;
	}
}