<?php

namespace Bitrix\Iblock\Grid\Panel\UI\Actions\Item\ElementGroup;

use Bitrix\Iblock\Grid\ActionType;
use Bitrix\Iblock\Grid\Panel\UI\Actions\Helpers\ChangeActiveHandler;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

/**
 * @see \Bitrix\Iblock\Grid\Panel\UI\Actions\Item\ElementGroupActionsItem lang messages are loaded from there.
 */
final class DeactivateGroupChild extends BaseGroupChild
{
	use ChangeActiveHandler;

	public static function getId(): string
	{
		return ActionType::DEACTIVATE;
	}

	public function getName(): string
	{
		return Loc::getMessage('IBLOCK_GRID_PANEL_UI_ACTIONS_ELEMENT_GROUP_DEACTIVATE_NAME');
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows): ?Result
	{
		return $this->processSetActive($request, $isSelectedAllRows, false);
	}

	protected function getOnchange(): Onchange
	{
		return new Onchange([
			[
				'ACTION' => Actions::RESET_CONTROLS,
			],
			[
				'ACTION' => Actions::CREATE,
				'DATA' => [
					(new Snippet)->getSendSelectedButton(),
				],
			],
		]);
	}
}
