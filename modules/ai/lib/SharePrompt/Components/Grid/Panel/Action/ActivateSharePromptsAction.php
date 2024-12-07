<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Panel\Action;

use Bitrix\AI\Container;
use Bitrix\AI\SharePrompt\Request\ChangeActivityByCodesRequest;
use Bitrix\AI\SharePrompt\Service\PromptService;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Result;
use Bitrix\AI\SharePrompt\Events\MultiActivateAnalyticEvent;
use Bitrix\AI\SharePrompt\Enums\Category;

class ActivateSharePromptsAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): string
	{
		return 'multiple-activate';
	}

	/**
	 * @inheritDoc
	 */
	public function processRequestAction(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): Result
	{
		$dto = $this->getRequest()->getData($request, $this->getCurrentUser());

		return $this->getService()->changeActivatePrompts($dto->promptIds, true, $dto->userId);
	}

	protected function fillEvent(): void
	{
		$this->event = new MultiActivateAnalyticEvent(Category::LIST);
	}

	protected function getRequest(): ChangeActivityByCodesRequest
	{
		return Container::init()->getItem(ChangeActivityByCodesRequest::class);
	}

	protected function getService(): PromptService
	{
		return Container::init()->getItem(PromptService::class);
	}
}
