<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Tasksmobile\Dto\GroupDto;
use Bitrix\TasksMobile\Provider\GroupProvider;
use Bitrix\Socialnetwork\Collab\Provider\CollabDefaultProvider;

class Collab extends Base
{
	protected function getQueryActionNames(): array
	{
		return [
			'getDefaultCollab',
		];
	}

	public function getDefaultCollabAction(): ?GroupDto
	{
		$collabId = (new CollabDefaultProvider())->getCollab($this->getCurrentUser()->getId())?->getId();

		return ($collabId ? GroupProvider::loadByIds([$collabId])[0] : null);
	}
}
