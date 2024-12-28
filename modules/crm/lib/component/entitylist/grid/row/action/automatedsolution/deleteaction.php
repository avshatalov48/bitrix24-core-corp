<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Row\Action\AutomatedSolution;

use Bitrix\Crm\AutomatedSolution\AutomatedSolutionManager;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Integration\Analytics\Builder\Automation\AutomatedSolution\DeleteEvent;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Grid\Row\Action\BaseAction;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

final class DeleteAction extends BaseAction
{
	public function __construct(
		private readonly Settings $settings,
		private readonly AutomatedSolutionManager $automatedSolutionManager,
		private readonly UserPermissions $userPermissions,
	)
	{
	}

	public static function getId(): ?string
	{
		return 'delete';
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		$id = (int)$request->getPost('id');
		if ($id <= 0)
		{
			return null;
		}

		$analyticsBuilder =
			(new DeleteEvent())
				->setSection(Dictionary::SECTION_AUTOMATION)
				->setElement(Dictionary::ELEMENT_GRID_ROW_CONTEXT_MENU)
				->setId($id)
		;

		$analyticsBuilder
			->setStatus(Dictionary::STATUS_ATTEMPT)
			->buildEvent()
			->send()
		;

		$result = $this->delete($id);
		if ($result->isSuccess())
		{
			$analyticsBuilder
				->setStatus(Dictionary::STATUS_SUCCESS)
				->buildEvent()
				->send()
			;
		}
		else
		{
			$analyticsBuilder
				->setStatus(Dictionary::STATUS_ERROR)
				->buildEvent()
				->send()
			;
		}

		return $result;
	}

	private function delete(int $automatedSolutionId): Result
	{
		if (!$this->userPermissions->canEditAutomatedSolutions())
		{
			return (new Result())->addError(ErrorCode::getAccessDeniedError());
		}

		return $this->automatedSolutionManager->deleteAutomatedSolution($automatedSolutionId);
	}

	protected function getText(): string
	{
		Container::getInstance()->getLocalization()->loadMessages();

		return Loc::getMessage('CRM_COMMON_ACTION_DELETE');
	}

	public function getControl(array $rawFields): ?array
	{
		$id = (int)($rawFields['ID'] ?? null);
		if ($id <= 0)
		{
			return null;
		}

		$safeGridId = \CUtil::JSEscape($this->settings->getID());
		$this->onclick = "BX.Main.gridManager.getInstanceById('{$safeGridId}').sendRowAction('delete', { id: {$id} });";

		return parent::getControl($rawFields);
	}
}
