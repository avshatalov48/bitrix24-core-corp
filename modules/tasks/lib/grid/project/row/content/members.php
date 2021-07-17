<?php
namespace Bitrix\Tasks\Grid\Project\Row\Content;

use Bitrix\Tasks\Grid\Project\Row\Content;

/**
 * Class Members
 *
 * @package Bitrix\Tasks\Grid\Project\Row\Content
 */
class Members extends Content
{
	public function prepare(): string
	{
		$row = $this->getRowData();
		$visibleMembersCount = 3;

		$headsLayout = '';
		$heads = $this->fillUsersLayout(($row['MEMBERS']['HEADS'] ?? []));
		if (count($heads) > 0)
		{
			$innerLayouts = [];
			foreach ($heads as $head)
			{
				if ($head['IS_GROUP_OWNER'] === 'Y')
				{
					array_unshift($innerLayouts, $head['LAYOUT']);
					continue;
				}
				$innerLayouts[] = $head['LAYOUT'];
			}
			if (count($innerLayouts) > $visibleMembersCount)
			{
				$innerLayouts = array_slice($innerLayouts, 0, $visibleMembersCount);
			}
			$innerLayouts =	implode("\n", $innerLayouts);

			$headsLayout =
				"<div style='display: inline-block'>"
					. "<div class='tasks-projects-user-list tasks-projects-user-list--green'>"
						. $innerLayouts
						. $this->makeOtherHeadsCounterLayout(((int)$row['NUMBER_OF_MODERATORS'] - $visibleMembersCount))
					. "</div>"
				. "</div>"
			;
		}

		$usersLayout = '';
		$users = $this->fillUsersLayout(($row['MEMBERS']['MEMBERS'] ?? []));
		$users = array_filter(
			$users,
			static function ($user) {
				return $user['IS_GROUP_ACCESS_REQUESTING'] !== 'Y';
			}
		);
		if (count($users) > 0)
		{
			$innerLayouts = [];
			foreach ($users as $user)
			{
				$innerLayouts[] = $user['LAYOUT'];
				if (count($innerLayouts) >= $visibleMembersCount)
				{
					break;
				}
			}
			$innerLayouts = implode("\n", $innerLayouts);

			$usersLayout =
				"<div style='display: inline-block'>"
					. "<div class='tasks-projects-user-list'>"
						. $innerLayouts
						. $this->makeOtherUsersCounterLayout(
							((int)$row['NUMBER_OF_MEMBERS'] - (int)$row['NUMBER_OF_MODERATORS'] - $visibleMembersCount)
						)
					. "</div>"
				. "</div>"
			;
		}

		return $headsLayout.$usersLayout;
	}

	private function fillUsersLayout(array $users): array
	{
		foreach ($users as $id => $user)
		{
			$style = ($user['PHOTO'] ? "style='background-image: url(\"{$user['PHOTO']}\")'" : '');
			$users[$id]['LAYOUT'] =
				"<a class='tasks-projects-user-item' {$style} href='{$user['HREF']}' title='{$user['FORMATTED_NAME']}'>"
					. "<div class='tasks-projects-user-crown'></div>"
				. "</a>"
			;
		}

		return $users;
	}

	private function makeOtherHeadsCounterLayout(int $otherHeadsCount): string
	{
		if ($otherHeadsCount > 0)
		{
			$groupId = (int)$this->getRowData()['ID'];

			return "<div
						class='tasks-projects-user-count'
						onclick='BX.Tasks.ProjectsInstance.getMembersPopup().show({$groupId}, \"heads\", this); event.stopPropagation();'
					><span class='tasks-projects-user-plus'>+</span>{$otherHeadsCount}</div>"
			;
		}

		return '';
	}

	private function makeOtherUsersCounterLayout(int $otherUsersCount): string
	{
		if ($otherUsersCount > 0)
		{
			$groupId = (int)$this->getRowData()['ID'];

			return "<div
						class='tasks-projects-user-count'
						onclick='BX.Tasks.ProjectsInstance.getMembersPopup().show({$groupId}, \"members\", this); event.stopPropagation();'
					><span class='tasks-projects-user-plus'>+</span>{$otherUsersCount}</div>"
			;
		}

		return '';
	}
}