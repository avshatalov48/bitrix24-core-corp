<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SendEvent\WithCancel;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action\Compatible;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class Update extends Compatible\SendEvent
{
	protected $canceledMessage;

	public function __construct(string $eventName, ?string $canceledMessage = null)
	{
		parent::__construct($eventName);

		$this->canceledMessage = $canceledMessage ?? static::DEFAULT_CANCELED_MESSAGE_CODE;
	}

	protected function executeEvent(array $event, Item $item): Result
	{
		$result = new Result();

		$fields = $item->getCompatibleData();
		$eventResult = ExecuteModuleEventEx($event, [&$fields]);
		if($eventResult === false)
		{
			$message = $fields['RESULT_MESSAGE'] ?? Loc::getMessage($this->canceledMessage, [
				'#EVENT#' => $this->eventName,
				'#NAME#' => $event['TO_NAME'],
			]);
			$result->addError(new Error(
				$message,
			static::ERROR_CODE_TERMINATED_BY_EVENT_COMPATIBLE
			));
		}
		else
		{
			$item->setFromCompatibleData($fields);
		}

		return $result;
	}
}
