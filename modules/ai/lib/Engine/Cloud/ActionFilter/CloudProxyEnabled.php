<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Cloud\ActionFilter;

use Bitrix\AI\Cloud\Configuration;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * Class CloudProxyEnabled
 */
class CloudProxyEnabled extends ActionFilter\Base
{
	/**
	 * Checks if cloud AI handler is enabled.
	 * @param Event $event Event.
	 * @return EventResult|null
	 */
	public function onBeforeAction(Event $event): ?EventResult
	{
		$configuration = new Configuration();
		if (!$configuration->hasCloudRegistration())
		{
			$this->addError(new Error('Cloud AI handler is not configured and not enabled.'));

			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}
}