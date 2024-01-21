<?php

namespace Bitrix\Crm\Component\EntityList;

use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;

final class ActionManager
{
	private Snippet $snippet;

	public function __construct(private string $gridManagerId)
	{
		$this->snippet = new Snippet();
	}

	public function getEditButton(): array
	{
		$editAction = $this->snippet->getEditButton();
		foreach ($editAction['ONCHANGE'] as &$onChangeAction)
		{
			if ($onChangeAction['ACTION'] === Actions::CALLBACK)
			{
				$onChangeAction['DATA'] = [
					[
						'JS' => "BX.CrmUIGridExtension.applyAction('{$this->gridManagerId}', 'edit')",
					],
				];
			}
		}
		unset($onChangeAction);

		return $editAction;
	}

	public function getEditAction(): array
	{
		$button = $this->snippet->getEditAction();
		$this->snippet->setButtonActions(
			$button,
			[
				[
					'ACTION' => Actions::CALLBACK,
					'CONFIRM' => false,
					'DATA' => [
						['JS' => "BX.CrmUIGridExtension.applyAction('{$this->gridManagerId}', 'edit')"],
					],
				],
			]
		);

		return $button;
	}
}
