<?php

namespace Bitrix\Crm\Agent\Security;

final class AssignAccessRightsToObserversAgent extends  \Bitrix\Crm\Agent\AgentBase
{
	public const STATE_STORY_KEY = '~CRM_MOVE_OBSERVERS_TO_ACCESS_ATTR__STATE';

	public const AGENT_WORK_IN_PROGRESS = 'CRM_MOVE_OBSERVERS_TO_ACCESS_ATTR_IN_WORK';

	public static function doRun()
	{
		return false;
	}
}