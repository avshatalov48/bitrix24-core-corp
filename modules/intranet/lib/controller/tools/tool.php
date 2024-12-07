<?php

namespace Bitrix\Intranet\Controller\Tools;

use Bitrix\Intranet\Integration\Im\HeadChat;
use Bitrix\Intranet\Integration\Im\HeadChatConfiguration;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;

class Tool extends Controller
{
	public function createHeadChatAction(string $toolId): AjaxJson
	{
		if (!Loader::includeModule('im'))
		{
			$this->errorCollection->setError(new Error("'im' module not installed"));

			return AjaxJson::createError($this->errorCollection);
		}

		$configuration = new HeadChatConfiguration\ToolEnable($toolId);

		if ($configuration->isValid())
		{
			$result = (new HeadChat($configuration))->create();

			if ($result->isSuccess())
			{
				return AjaxJson::createSuccess($result->getData());
			}

			return AjaxJson::createError($result->getErrorCollection());
		}

		$this->errorCollection->setError(new Error('Invalid configuration for create chat'));

		return AjaxJson::createError($this->errorCollection);
	}
}