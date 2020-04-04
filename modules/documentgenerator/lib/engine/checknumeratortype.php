<?php

namespace Bitrix\DocumentGenerator\Engine;

use Bitrix\DocumentGenerator\Driver;
use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Numerator\Numerator;

class CheckNumeratorType extends Base
{
	/**
	 * @param Event $event
	 * @return EventResult|null
	 * @throws \Bitrix\Main\SystemException
	 */
	public function onBeforeAction(Event $event)
	{
		$numerator = false;
		foreach($this->action->getArguments() as $name => $argument)
		{
			if($argument instanceof Numerator)
			{
				$numerator = $argument;
				break;
			}
		}

		if($numerator && $numerator->getConfig()[Numerator::getType()]['type'] !== Driver::NUMERATOR_TYPE)
		{
			$this->errorCollection[] = new Error('Access denied', \Bitrix\DocumentGenerator\Controller\Base::ERROR_ACCESS_DENIED);
			return new EventResult(EventResult::ERROR, null, null, $this);
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function listAllowedScopes()
	{
		return [
			Controller::SCOPE_REST,
		];
	}
}