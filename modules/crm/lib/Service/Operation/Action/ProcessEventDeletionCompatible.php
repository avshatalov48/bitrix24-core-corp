<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\Item;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ProcessEventDeletionCompatible extends SendEventCompatible
{
	public function process(Item $item): Result
	{
		global $APPLICATION;
		$APPLICATION->ResetException();

		return parent::process($item);
	}

	protected function executeEvent(array $event, Item $item): Result
	{
		global $APPLICATION;
		$result = new Result();

		$eventResult = ExecuteModuleEventEx($event, [$item->getId()]);
		if ($eventResult === false)
		{
			$message = $fields['RESULT_MESSAGE'] ?? Loc::getMessage(static::DEFAULT_CANCELED_MESSAGE_CODE, [
				'#NAME#' => $event['TO_NAME'],
				'#EVENT#' => $this->eventName,
			]);
			if ($exception = $APPLICATION->GetException())
			{
				$message .= ': ' . $exception->GetString();
			}
			$APPLICATION->throwException($message);

			$result->addError(new Error($message, static::ERROR_CODE_TERMINATED_BY_EVENT_COMPATIBLE));
		}

		return $result;
	}
}