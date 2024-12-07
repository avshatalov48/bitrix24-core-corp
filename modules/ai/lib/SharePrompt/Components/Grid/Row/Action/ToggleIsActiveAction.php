<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Action;

use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\AI\SharePrompt\Events\ActivateAnalyticEvent;
use Bitrix\AI\SharePrompt\Events\DeactivateAnalyticEvent;
use Bitrix\AI\SharePrompt\Events\Enums\ShareType;
use Bitrix\AI\SharePrompt\Request\ChangeActivityRequest;
use CUtil;
use Bitrix\AI\SharePrompt\Service\PromptService;
use Bitrix\AI\Container;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;

class ToggleIsActiveAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	protected function getText(): string
	{
		return Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_ACTIVATE');
	}

	/**
	 * @inheritDoc
	 */
	public static function getId(): ?string
	{
		return 'toggle-active';
	}

	/**
	 * @inheritDoc
	 */
	public function processRequestAction(HttpRequest $request): ?Result
	{
		$requestDTO = $this->getRequest()->getData($request, $this->getCurrentUser());

		$this->fillEvent($requestDTO->needActivate, $requestDTO->shareType);

		$this->getService()->changeActivatePrompt(
			$requestDTO->promptId,
			$requestDTO->needActivate,
			$requestDTO->userId
		);

		$result = new Result();
		$result->setData([
			'id' => $requestDTO->promptCode,
			'IS_ACTIVE' => $requestDTO->needActivate,
		]);

		return $result;
	}

	protected function fillEvent(bool $needActivate, ShareType $shareType)
	{
		if ($needActivate)
		{
			$this->event = new ActivateAnalyticEvent(Category::LIST, $shareType);

			return;
		}

		$this->event = new DeactivateAnalyticEvent(Category::LIST, $shareType);
	}

	protected function getService(): PromptService
	{
		return Container::init()->getItem(PromptService::class);
	}

	protected function getRequest(): ChangeActivityRequest
	{
		return Container::init()->getItem(ChangeActivityRequest::class);
	}

	public function getControl(array $rawFields): array
	{
		$this->default = true;
		$promptCode = $rawFields['ID'];

		$isActive = $rawFields['IS_ACTIVE'];
		$name = CUtil::JSEscape($rawFields['DATA']['NAME']);

		if ($isActive)
		{
			$this->onclick = "BX.AI.SharePrompt.Library.Controller.handleClickOnDeactivatePromptMenuItem(event, '$promptCode', '$name')";
			$text = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_DEACTIVATE');
		}
		else
		{
			$text = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_ACTIVATE');
			$this->onclick = "BX.AI.SharePrompt.Library.Controller.handleClickOnActivatePromptMenuItem(event, '$promptCode', '$name')";
		}

		$rawFields['id'] = $promptCode;

		return [
			...parent::getControl($rawFields),
			'TEXT' => $text,
		];
	}
}
