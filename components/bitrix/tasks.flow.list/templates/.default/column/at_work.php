<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

require_once __DIR__.'/users_avatar.php';

if (!function_exists('renderAtWorkColumn'))
{
	function renderAtWorkColumn(array $data, array $arResult, bool $isActive): string
	{
		/** @var \Bitrix\Tasks\Flow\Flow $flow */
		$flow = $data['flow'];
		$users = $data['users'];
		$flowId = $flow->getId();
		$membersClass = '--at-work';
		$members = renderUsersAvatar($users, $membersClass);
		$total = count($users);
		$subsequence = implode(',', array_slice(array_column($users, 'id'), 0, 3));

		$isEmpty = !$users;
		$days = $isEmpty ? Loc::getMessage('TASKS_FLOW_LIST_NO_TASKS') : $data['date'];
		$linkClass = $isEmpty ? '' : '--link';
		$disableClass = $isActive ? '' : '--disable';

		$onclick = $isEmpty ? '' : "BX.Tasks.Flow.Grid.showTaskQueue('{$flowId}', 'AT_WORK', this)";

		return <<<HTML
			<div class="tasks-flow__list-cell --middle $disableClass">
				<div class="tasks-flow__list-members_wrapper $linkClass" onclick="{$onclick}">
					<div class="tasks-flow__list-cell_line --middle">
						<div
							class="tasks-flow__list-members"
							data-total="{$total}"
							data-subsequence="{$subsequence}"
						>
							$members
						</div>
					</div>
					<div class="tasks-flow__list-members_info $linkClass">
						$days
					</div>
				</div>
			</div>
		HTML;
	}
}
