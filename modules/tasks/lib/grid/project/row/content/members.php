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
				if ($head['IS_OWNER'] === 'Y')
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
						. $this->makeOtherCounterLayout(((int)$row['NUMBER_OF_MODERATORS'] - $visibleMembersCount))
					. "</div>"
				. "</div>"
			;
		}

		$usersLayout = '';
		$users = $this->fillUsersLayout(($row['MEMBERS']['MEMBERS'] ?? []));
		$users = array_filter(
			$users,
			static function ($user) {
				return $user['IS_ACCESS_REQUESTING'] !== 'Y';
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
						. $this->makeOtherCounterLayout(
							((int)$row['NUMBER_OF_MEMBERS'] - (int)$row['NUMBER_OF_MODERATORS'] - $visibleMembersCount)
						)
					. "</div>"
				. "</div>"
			;
		}

		return
			"<div class='tasks-projects-user-list-container' onclick='{$this->getMembersPopupShowFunction()}'>"
				. $headsLayout
				. $usersLayout
			. "</div>"
		;
	}

	private function fillUsersLayout(array $users): array
	{
		foreach ($users as $id => $user)
		{
			$style = ($user['PHOTO'] ? "style='background-image: url(\"{$user['PHOTO']}\")'" : '');
			$users[$id]['LAYOUT'] =
				"<a class='tasks-projects-user-item' {$style}>"
					. "<div class='tasks-projects-user-crown'></div>"
				. "</a>"
			;
		}

		return $users;
	}

	private function makeOtherCounterLayout(int $otherCount): string
	{
		if ($otherCount <= 0)
		{
			return "";
		}

		return "<div class='tasks-projects-user-count'><span class='tasks-projects-user-plus'>+</span>{$otherCount}</div>";
	}

	private function getMembersPopupShowFunction(): string
	{
		$row = $this->getRowData();

		return "BX.Tasks.ProjectsInstance.getMembersPopup().show({$row['ID']}, this); event.stopPropagation();";
	}
}