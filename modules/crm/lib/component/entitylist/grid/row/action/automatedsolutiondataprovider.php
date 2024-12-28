<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Row\Action;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\Component\EntityList\Grid\Row\Action\AutomatedSolution\DeleteAction;
use Bitrix\Crm\Component\EntityList\Grid\Row\Action\AutomatedSolution\EditAction;
use Bitrix\Crm\Service\Router;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Grid\Row\Action\DataProvider;
use Bitrix\Main\Grid\Settings;

final class AutomatedSolutionDataProvider extends DataProvider
{
	public function __construct(
		Settings $settings,
		private readonly AutomatedSolutionManager $automatedSolutionManager,
		private readonly UserPermissions $userPermissions,
		private readonly Router $router,
	)
	{
		parent::__construct($settings);
	}

	public function prepareActions(): array
	{
		$actions = [];
		if ($this->userPermissions->canEditAutomatedSolutions())
		{
			$actions[] = new EditAction($this->router);
			$actions[] = new DeleteAction($this->getSettings(), $this->automatedSolutionManager, $this->userPermissions);
		}

		return $actions;
	}
}
