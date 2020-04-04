<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Main\EventResult;

class Event extends \Bitrix\Main\Event
{
	/**
	 * Sends event.
	 *
	 * If sender has method with name of event type, than the method will be executed as event handler.
	 *
	 * @param null $sender Sender.
	 * @return void
	 */
	public function send($sender = null)
	{
		if($sender && method_exists($sender, $this->getEventType()))
		{
			$binder = new Engine\Binder($sender, $this->getEventType(), $this->getParameters());
			$result = $binder->invoke();

			if($result !== null && !$result instanceof EventResult)
			{
				$result = new EventResult(EventResult::SUCCESS);
			}
			if($result !== null)
			{
				$this->addResult($result);
			}
		}

		parent::send($sender);
	}
}