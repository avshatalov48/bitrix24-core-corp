<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Row\Action\AutomatedSolution;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Router;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class EditAction extends BaseAction
{
	public function __construct(
		private readonly Router $router,
	)
	{
	}

	public static function getId(): ?string
	{
		return 'edit';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		return null;
	}

	protected function getText(): string
	{
		Container::getInstance()->getLocalization()->loadMessages();

		return Loc::getMessage('CRM_COMMON_ACTION_EDIT');
	}

	public function getControl(array $rawFields): ?array
	{
		$id = $rawFields['ID'] ?? null;
		if ($id <= 0)
		{
			return null;
		}

		$this->href = $this->router->getAutomatedSolutionDetailUrl((int)$id);

		return parent::getControl($rawFields);
	}
}
