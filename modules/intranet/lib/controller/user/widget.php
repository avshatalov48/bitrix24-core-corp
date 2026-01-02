<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Controller\User;

use Bitrix\Intranet\User;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Error;
use Bitrix\Intranet\ActionFilter;

class Widget extends Controller
{
	protected function getDefaultPreFilters(): array
	{
		$preFilters = parent::getDefaultPreFilters();
		$preFilters[] = new ActionFilter\UserType([
			'employee',
			'extranet',
		]);

		return $preFilters;
	}

	public function getContentAction(): AjaxJson
	{
		try
		{
			return AjaxJson::createSuccess((new User\Widget())->getContentCollection());
		}
		catch (ArgumentException $e)
		{
			$this->errorCollection->add([new Error($e->getMessage())]);

			return AjaxJson::createError($this->errorCollection);
		}
	}

	public function getUserStatComponentAction(): Component
	{
		return new Component('bitrix:intranet.ustat.status', 'lite', []);
	}
}
