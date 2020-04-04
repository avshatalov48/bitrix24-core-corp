<?php

namespace Bitrix\Disk\Internals\Engine\ActionFilter;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\Event;
use Bitrix\Main\HttpResponse;

class HumanReadableError extends ActionFilter\Base
{
	const NAME_GET_PARAMETER = 'humanRE';

	public function onAfterAction(Event $event)
	{
		$enabled = Context::getCurrent()->getRequest()->get(self::NAME_GET_PARAMETER);
		if ($enabled)
		{
			$errors = [];
			$result = $event->getParameter('result');
			if ($result instanceof Errorable && $result->getErrors())
			{
				$errors = $result->getErrors();
			}

			$controller = $event->getParameter('controller');
			if ($controller instanceof Errorable && $controller->getErrors())
			{
				$errors = array_merge($errors, $controller->getErrors());
			}

			if ($errors)
			{
				$errorText = null;
				foreach ($errors as $error)
				{
					/** @var Error $error */
					$errorText .= $error->getMessage() . "\n<br>";
				}

				$event->setParameter('result', (new HttpResponse())->setContent($errorText));
			}
		}

		parent::onAfterAction($event);
	}
}