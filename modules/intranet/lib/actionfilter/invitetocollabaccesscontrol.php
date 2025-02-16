<?php

namespace Bitrix\Intranet\ActionFilter;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Collab;
use Bitrix\Socialnetwork\Internals\Registry\GroupRegistry;

class InviteToCollabAccessControl extends ActionFilter\Base
{
	public function onBeforeAction(Event $event): ?EventResult
	{
		if (
			Loader::includeModule('socialnetwork')
			&& !Collab\CollabFeature::isOn()
			&& !Collab\CollabFeature::isFeatureEnabled()
			&& !Collab\Requirement::check()->isSuccess()
		)
		{
			$this->addError(new Error(
				Loc::getMessage('INTRANET_COLLAB_ACCESS_CONTROL_PORTAL_ACCESS_DENIED')
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		$collabId = $this->getAction()->getArguments()['collabId']
			?? $this->getAction()->getBinder()->getMethodParams()['collabId']
			?? null;

		if ($collabId)
		{
			$group = GroupRegistry::getInstance()->get($collabId);

			if (!($group instanceof Collab\Collab))
			{
				$this->addError(new Error(
					Loc::getMessage('INTRANET_COLLAB_ACCESS_CONTROL_COLLAB_NOT_FOUND')
				));

				return new EventResult(EventResult::ERROR, null, null, $this);
			}

			$canInviteCurrentUser = Collab\Access\CollabAccessController::can(
				$this->getAction()->getCurrentUser()?->getId(),
				Collab\Access\CollabDictionary::INVITE,
				$collabId
			);

			if (!$canInviteCurrentUser)
			{
				$this->addError(new Error(
					Loc::getMessage('INTRANET_COLLAB_ACCESS_CONTROL_USER_ACCESS_DENIED')
				));

				return new EventResult(EventResult::ERROR, null, null, $this);
			}
		}
		else
		{
			$this->addError(new Error(
				'Collab ID is not specified.'
			));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}