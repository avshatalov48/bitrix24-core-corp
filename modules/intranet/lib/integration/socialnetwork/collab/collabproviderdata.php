<?php

namespace Bitrix\Intranet\Integration\Socialnetwork\Collab;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\CollabCollection;
use Bitrix\Socialnetwork\Collab\CollabFeature;
use Bitrix\Socialnetwork\Collab\Control\Option\Type\AllowGuestsInvitationField;
use Bitrix\Socialnetwork\Collab\Provider\CollabProvider;
use Bitrix\Socialnetwork\Collab\Requirement;

final class CollabProviderData
{
	protected bool $available = false;
	protected bool $isEnabled= false;

	public function __construct()
	{
		if (Loader::includeModule('socialnetwork'))
		{
			$this->isEnabled = true;
			$this->available = CollabFeature::isOn()
				&& CollabFeature::isFeatureEnabled()
				&& Requirement::check()->isSuccess();
		}
	}

	public function isAvailable(): bool
	{
		return $this->available;
	}

	public function getUserCollabCollection($user): CollabCollection
	{
		$collection = new CollabCollection();
		if (!$this->isEnabled)
		{
			return $collection;
		}

		$provider = CollabProvider::getInstance();
		$filter = \Bitrix\Main\ORM\Query\Query::filter()
			->where('MEMBERS.USER_ID', $user->getId());
		$query = (new \Bitrix\Socialnetwork\Collab\Provider\CollabQuery($user->getId()))
			->setWhere($filter)
			->setSelect(['ID']);
		$collabs = $provider->getList($query);

		foreach ($collabs as $collabData)
		{
			$collab = $provider->getCollab($collabData->getId());
			$collection[$collab->getId()] = $collab;
		}

		return $collection;
	}

	public function isAllowedGuestsInvitation(int $collabId): bool
	{
		if (!$this->isEnabled || !$this->available)
		{
			return false;
		}

		$collab = CollabProvider::getInstance()->getCollab($collabId);
		if (!$collab)
		{
			return false;
		}

		if (!class_exists(AllowGuestsInvitationField::class))
		{
			return true;
		}

		$allowGuestsInvitation = $collab->getOptionValue(AllowGuestsInvitationField::DB_NAME);

		if (!$allowGuestsInvitation)
		{
			return AllowGuestsInvitationField::DEFAULT_VALUE;
		}

		return $allowGuestsInvitation === 'Y';
	}
}