<?php
namespace Bitrix\Tasks\Grid\Project\Row;

use Bitrix\Main\Localization\Loc;

/**
 * Class Action
 *
 * @package Bitrix\Tasks\Grid\Project\Row
 */
class Action
{
	protected $rowData = [];
	protected $parameters = [];

	/**
	 * Action constructor.
	 *
	 * @param array $rowData
	 * @param array $parameters
	 */
	public function __construct(array $rowData = [], array $parameters = [])
	{
		$this->rowData = $rowData;
		$this->parameters = $parameters;
	}

	/**
	 * Prepares actions for project row.
	 *
	 * @return array[]
	 */
	public function prepare(): array
	{
		$groupId = (int)$this->rowData['ID'];
		$groupIdReplace = ['group_id' => $groupId];

		$userId = (int)$this->parameters['USER_ID'];
		$user = [];
		if (array_key_exists($userId, $this->rowData['MEMBERS']['HEADS']))
		{
			$user = $this->rowData['MEMBERS']['HEADS'][$userId];
		}
		elseif (array_key_exists($userId, $this->rowData['MEMBERS']['MEMBERS']))
		{
			$user = $this->rowData['MEMBERS']['MEMBERS'][$userId];
		}

		$actions = [];

		if ($this->rowData['IS_PINNED'] === 'N')
		{
			$actions[] = [
				'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_PIN'),
				'onclick' => "BX.Tasks.Projects.ActionsController.doAction('pin', {$groupId})",
			];
		}
		else
		{
			$actions[] = [
				'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_UNPIN'),
				'onclick' => "BX.Tasks.Projects.ActionsController.doAction('unpin', {$groupId})",
			];
		}

		$actions[] = [
			'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_OPEN'),
			'href' => $this->rowData['PATH'],
		];

		if ($user['IS_OWNER'] === 'Y')
		{
			$actions[] = [
				'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_EDIT'),
				'href' => \CComponentEngine::makePathFromTemplate(
					$this->parameters['PATH_TO_GROUP_EDIT'],
					$groupIdReplace
				),
			];

			if ($this->rowData['CLOSED'] === 'N')
			{
				$actions[] = [
					'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_ADD_TO_ARCHIVE'),
					'onclick' => "BX.Tasks.Projects.ActionsController.doAction('addToArchive', {$groupId})",
				];
			}
			else
			{
				$actions[] = [
					'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_REMOVE_FROM_ARCHIVE'),
					'onclick' => "BX.Tasks.Projects.ActionsController.doAction('removeFromArchive', {$groupId})",
				];
			}

			$actions[] = [
				'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_DELETE'),
				'href' => \CComponentEngine::makePathFromTemplate(
					$this->parameters['PATH_TO_GROUP_DELETE'],
					$groupIdReplace
				),
			];
		}

		$isMember = !empty($user);
		if (!$isMember)
		{
			$actionRequest = ['text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_REQUEST')];
			if ($this->rowData['OPENED'] === 'Y')
			{
				$actionRequest['onclick'] = "BX.Tasks.Projects.ActionsController.sendJoinRequest(document.querySelector('#requestButton{$groupId}'))";
			}
			else
			{
				$actionRequest['href'] = \CComponentEngine::makePathFromTemplate(
					$this->parameters['PATH_TO_USER_REQUEST_GROUP'],
					$groupIdReplace
				);
			}

			$actions[] = $actionRequest;
		}

		if (
			$isMember
			&& $user['IS_OWNER'] === 'N'
			&& $user['IS_AUTO_MEMBER'] === 'N'
		)
		{
			if ($user['IS_ACCESS_REQUESTING'] === 'Y')
			{
				$requestPath = \CComponentEngine::makePathFromTemplate(
					$this->parameters['PATH_TO_USER_REQUESTS'],
					['user_id' => $user['ID']]
				);
				$userGroupRelationId = $this->rowData['USER_GROUP_ID'];

				if ($user['IS_ACCESS_REQUESTING_BY_ME'] === 'Y')
				{
					$actions[] = [
						'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_CANCEL_REQUEST'),
						'onclick' => "BX.Tasks.Projects.ActionsController.sendCancelRequest({$userGroupRelationId}, '{$requestPath}')",
					];
				}
				else
				{
					$actions[] = [
						'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_ACCEPT_REQUEST'),
						'onclick' => "BX.Tasks.Projects.ActionsController.sendAcceptRequest({$userGroupRelationId}, '{$requestPath}')",
					];
					$actions[] = [
						'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_DENY_REQUEST'),
						'onclick' => "BX.Tasks.Projects.ActionsController.sendDenyRequest({$userGroupRelationId}, '{$requestPath}')",
					];
				}
			}
			else
			{
				$actions[] = [
					'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_ACTION_LEAVE'),
					'href' => \CComponentEngine::makePathFromTemplate(
						$this->parameters['PATH_TO_USER_LEAVE_GROUP'],
						$groupIdReplace
					),
				];
			}
		}

		return $actions;
	}
}