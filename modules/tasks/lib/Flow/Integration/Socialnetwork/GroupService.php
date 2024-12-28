<?php

namespace Bitrix\Tasks\Flow\Integration\Socialnetwork;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Component\WorkgroupForm;
use Bitrix\Socialnetwork\Helper\Workgroup;
use Bitrix\Socialnetwork\Item\Workgroup\Type;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Tasks\InvalidCommandException;
use Bitrix\Tasks\Flow\Integration\Socialnetwork\Exception\AutoCreationException;
use Bitrix\Tasks\Flow\Kanban\KanbanService;
use CApplicationException;
use CSocNetFeatures;
use CSocNetGroup;
use CSocNetGroupSubject;
use CSocNetUserToGroup;

class GroupService
{
	protected AddGroupCommand|UpdateGroupCommand $command;
	protected KanbanService $kanbanService;

	public function __construct()
	{
		$this->init();
	}

	/**
	 * @throws LoaderException
	 */
	public static function getDefaultSubjectId(): int
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			throw new LoaderException('Socialnetwork is not loaded');
		}

		$subject = CSocNetGroupSubject::GetList(
			['SORT' => 'ASC', 'NAME' => 'ASC'],
			['SITE_ID' => SITE_ID],
			false,
			false,
			['ID', 'NAME'],
		)->fetch();

		return (int)($subject['ID'] ?? 0);
	}

	/**
	 * @throws LoaderException
	 */
	public static function getDefaultAvatar(): string
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			throw new LoaderException('Socialnetwork is not loaded');
		}

		return array_key_first(Workgroup::getDefaultAvatarTypes());
	}

	/**
	 * @throws AutoCreationException
	 * @throws InvalidCommandException
	 * @throws LoaderException
	 */
	public function add(AddGroupCommand $command): int
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			throw new LoaderException('Socialnetwork is not loaded');
		}

		$this->command = $command;

		$command->validateAdd();

		$this->saveGroup();

		$this->saveMembers();

		$this->saveFeatures();

		return $this->command->id;
	}

	/**
	 * @throws InvalidCommandException
	 * @throws LoaderException
	 */
	public function update(UpdateGroupCommand $command): int
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			throw new LoaderException('Socialnetwork is not loaded');
		}

		$this->command = $command;

		$command->validateUpdate();

		CSocNetUserToGroup::addUniqueUsersToGroup(
			$this->command->id,
			$command->members,
		);

		return $this->command->id;
	}

	/**
	 * @throws AutoCreationException
	 * @throws LoaderException
	 */
	protected function saveGroup(): void
	{
		$groupId = CSocNetGroup::createGroup($this->command->ownerId, [
			'NAME' => $this->command->name,
			'SITE_ID' => SITE_ID,
			'SUBJECT_ID' => static::getDefaultSubjectId(),
			'INITIATE_PERMS' => UserToGroupTable::ROLE_USER,
			'AVATAR_TYPE' => static::getDefaultAvatar(),
			'TYPE' => Type::Group,
		]);

		if ($groupId === false)
		{
			global $APPLICATION;
			$exception = $APPLICATION->GetException();

			if ($exception instanceof CApplicationException && is_string($exception->GetID()))
			{
				// ERROR_GROUP_NAME_EXISTS etc.
				// that's because we need a DIFFERENT phrase. that's horrible, I'm so sorry
				$message = Loc::getMessage('TASKS_FLOW_GROUP_SERVICE_' . $exception->GetID());
			}

			$message ??= Loc::getMessage('TASKS_FLOW_GROUP_SERVICE_CANNOT_AUTO_CREATE_GROUP');

			throw new AutoCreationException($message);
		}

		$this->command->id = $groupId;
	}

	protected function saveMembers(): void
	{
		$userIds = array_filter($this->command->members, fn(int $userId): bool => $userId !== $this->command->ownerId);
		$userIds = array_map('intval', $userIds);
		$userIds = array_unique($userIds);

		CSocNetUserToGroup::AddUsersToGroup(
			$this->command->id,
			$userIds
		);
	}

	/**
	 * @throws AutoCreationException
	 */
	protected function saveFeatures(): void
	{
		$features = [];
		WorkgroupForm::processWorkgroupFeatures(0, $features);

		foreach ($features as $featureName => $featureData)
		{
			$result = CSocNetFeatures::setFeature(
				SONET_ENTITY_GROUP,
				$this->command->id,
				$featureName,
				$featureData['Active'],
			);

			if ($result === false)
			{
				global $APPLICATION;
				$message = $APPLICATION->GetException();
				if ($message)
				{
					throw new AutoCreationException($message->getString());
				}
			}
		}
	}

	protected function init(): void
	{
		$this->kanbanService = new KanbanService();
	}
}
