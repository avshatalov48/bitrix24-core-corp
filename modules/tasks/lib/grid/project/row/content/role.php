<?php
namespace Bitrix\Tasks\Grid\Project\Row\Content;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Grid\Project\Row\Content;

Loc::loadMessages(__FILE__);

/**
 * Class Role
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class Role extends Content
{
	public function prepare(): string
	{
		$row = $this->getRowData();
		$parameters = $this->getParameters();

		$userId = $parameters['USER_ID'];
		$userData = [];

		if (array_key_exists($userId, $row['MEMBERS']['HEADS']))
		{
			$userData = $row['MEMBERS']['HEADS'][$userId];
		}
		elseif (array_key_exists($userId, $row['MEMBERS']['MEMBERS']))
		{
			$userData = $row['MEMBERS']['MEMBERS'][$userId];
		}

		if (empty($userData))
		{
			return $this->createRequestButton();
		}

		if ($userData['IS_ACCESS_REQUESTING'] === 'Y')
		{
			return $this->createRequestingLayout($userData);
		}

		return $this->createRoleLayout($userData);
	}

	private function createRequestButton(): string
	{
		$row = $this->getRowData();
		$text = Loc::getMessage('TASKS_GRID_PROJECT_ROW_CONTENT_ROLE_JOIN');

		if ($row['OPENED'] === 'Y')
		{
			$requestUrl = \CComponentEngine::makePathFromTemplate(
				$this->getParameters()['PATH_TO_USER_REQUEST_GROUP'],
				['group_id' => $row['ID']]
			);

			return
				"<div
					class='ui-label tasks-projects-badge-join'
					id='requestButton{$row['ID']}'
					bx-request-url='{$requestUrl}'
					onclick='event.stopPropagation(); BX.Tasks.Projects.ActionsController.sendJoinRequest(this)'
				><span class='ui-label-inner'>{$text}</span></div>"
			;
		}

		return
			"<a class='ui-label tasks-projects-badge-join' href='/workgroups/group/{$row['ID']}/user_request/'>
				<span class='ui-label-inner'>{$text}</span>
			</a>"
		;
	}

	private function createRequestingLayout(array $user): string
	{
		$requestPath = \CComponentEngine::makePathFromTemplate(
			$this->getParameters()['PATH_TO_USER_REQUESTS'],
			['user_id' => $user['ID']]
		);
		$userGroupRelationId = $this->getRowData()['USER_GROUP_ID'];

		if ($user['IS_ACCESS_REQUESTING_BY_ME'] === 'Y')
		{
			$text = Loc::getMessage('TASKS_GRID_PROJECT_ROW_CONTENT_ROLE_REQUEST_SENT');

			return
				"
				<div class='tasks-projects-badge-invite-box'>
					<div class='ui-label tasks-projects-badge-invite'>
						<span class='ui-label-inner'>{$text}</span>
					</div>
					<div class='ui-label tasks-projects-badge-cancel' onclick='event.stopPropagation(); BX.Tasks.Projects.ActionsController.sendCancelRequest({$userGroupRelationId}, \"{$requestPath}\");'>
						<span class='ui-label-inner'></span>
					</div>
				</div>
				"
			;
		}

		$text = Loc::getMessage('TASKS_GRID_PROJECT_ROW_CONTENT_ROLE_INVITED');

		return
			"
			<div class='tasks-projects-badge-invite-box'>
				<div class='ui-label tasks-projects-badge-invite-accept'>
					<span class='ui-label-inner'>{$text}</span>
				</div>
				<div class='ui-label tasks-projects-badge-accept' onclick='event.stopPropagation(); BX.Tasks.Projects.ActionsController.sendAcceptRequest({$userGroupRelationId}, \"{$requestPath}\");'>
					<span class='ui-label-inner'></span>
				</div>
				<div class='ui-label tasks-projects-badge-cancel' onclick='event.stopPropagation(); BX.Tasks.Projects.ActionsController.sendDenyRequest({$userGroupRelationId}, \"{$requestPath}\");'>
					<span class='ui-label-inner'></span>
				</div>
			</div>
			"
		;
	}

	private function createRoleLayout(array $user): string
	{
		$roles = [
			'owner' => [
				'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_CONTENT_ROLE_OWNER'),
				'color' => 'green',
			],
			'moderator' => [
				'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_CONTENT_ROLE_MODERATOR'),
				'color' => 'yellow',
			],
			'member' => [
				'text' => Loc::getMessage('TASKS_GRID_PROJECT_ROW_CONTENT_ROLE_MEMBER'),
				'color' => 'blue',
			],
		];

		$role = $roles['member'];
		if ($user['IS_OWNER'] === 'Y')
		{
			$role = $roles['owner'];
		}
		elseif ($user['IS_MODERATOR'] === 'Y')
		{
			$role = $roles['moderator'];
		}

		return
			"<div class='ui-label tasks-projects-badge-{$role['color']}' onclick='event.stopPropagation()'>"
			. "<span class='ui-label-inner'>{$role['text']}</span>"
			. "</div>"
		;
	}
}
