<?php

namespace Bitrix\Intranet\Controller;

use Bitrix\Intranet\MainPage\Access;
use Bitrix\Intranet\MainPage\Publisher;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;

class MainPage extends Controller
{
	public function publishAction(): AjaxJson
	{
		$errorCollection = new ErrorCollection();

		if ((new Access)->canEdit())
		{
			(new Publisher)->publish();

			return AjaxJson::createSuccess();
		}

		$errorCollection->setError(new Error('Access denied'));

		return AjaxJson::createError($errorCollection);
	}

	public function withdrawAction(): AjaxJson
	{
		$errorCollection = new ErrorCollection();

		if ((new Access)->canEdit())
		{
			(new Publisher)->withdraw();

			return AjaxJson::createSuccess();
		}

		$errorCollection->setError(new Error('Access denied'));

		return AjaxJson::createError($errorCollection);
	}
}