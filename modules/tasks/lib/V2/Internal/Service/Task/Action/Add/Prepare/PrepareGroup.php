<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\SocialNetwork\Collab\Provider\CollabDefaultProvider;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Util\User;

class PrepareGroup implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		if (!isset($fields['GROUP_ID']))
		{
			$fields['GROUP_ID'] = 0;
		}
		else
		{
			$fields['GROUP_ID'] = (int)$fields['GROUP_ID'];
		}

		// todo
		$fields = $this->prepareCollab($fields);

		$isSuper = User::isSuper($this->config->getUserId());
		$isExtranet = \Bitrix\Tasks\Integration\Extranet\User::isExtranet($this->config->getUserId());

		if (
			!$isSuper
			&& $isExtranet
			&& $fields['GROUP_ID'] === 0
		)
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_GROUP'));
		}

		return $fields;
	}

	private function prepareCollab(array $fields): array
	{
		$isGroupUpdateOnCorrect = isset($fields['GROUP_ID']) && (int)$fields['GROUP_ID'] !== 0;
		if ($isGroupUpdateOnCorrect)
		{
			return $fields;
		}

		$isCollaber = \Bitrix\Tasks\Integration\Extranet\User::isCollaber($this->config->getUserId());
		if (!$isCollaber)
		{
			return $fields;
		}

		$defaultCollab = CollabDefaultProvider::getInstance()?->getCollab($this->config->getUserId());
		$defaultCollabId = $defaultCollab?->getId();
		if ($defaultCollabId === null)
		{
			return $fields;
		}

		// todo: why is this here??
		if (Group::can($defaultCollabId, Group::ACTION_CREATE_TASKS, $this->config->getUserId()))
		{
			$fields['GROUP_ID'] = $defaultCollabId;
		}

		return $fields;
	}
}
