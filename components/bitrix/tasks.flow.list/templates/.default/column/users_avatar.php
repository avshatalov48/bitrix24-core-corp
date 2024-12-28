<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Flow\User\User;

if (!function_exists('renderUsersAvatar'))
{
	function renderUsersAvatar (array $users, string $class = null): string
	{
		$maxVisibleNumberAvatars = 99;
		$members = '';
		$amount = '';
		$visibleAmount = 3;
		$invisibleAmount = count($users) - $visibleAmount;
		$membersClass = $class ?? '';

		if($invisibleAmount > 0)
		{
			$visibleAmount--;
			$invisibleAmount++;
			$invisibleAmount = min($invisibleAmount, $maxVisibleNumberAvatars);

			$amount = <<<HTML
				<div class="tasks-flow__list-members-icon_element --count $membersClass">
					<span class="tasks-flow__warning-icon_element-plus">+</span>
					<span class="tasks-flow__warning-icon_element-number">$invisibleAmount</span>
				</div>
			HTML;

			$users = array_slice($users, 0, $visibleAmount);
		}

		if (!$users)
		{
			$members = <<<HTML
					<div class="tasks-flow__list-members-icon_element --icon">
						<div class="ui-icon-set --person"
						style="--ui-icon-set__icon-color: var(--ui-color-base-50);"
						></div>
					</div>
					<div class="tasks-flow__list-members-icon_element --icon">
						<div class="ui-icon-set --person"
						style="--ui-icon-set__icon-color: var(--ui-color-base-50);"
						></div>
					</div>
					<div class="tasks-flow__list-members-icon_element --icon">
						<div class="ui-icon-set --person"
						style="--ui-icon-set__icon-color: var(--ui-color-base-50);"
						></div>
					</div>
				HTML;
		}
		else
		{
			/** @var User[] $users */
			foreach ($users as $user)
			{
				$userData = $user->toArray();

				$photoSrc= Uri::urnEncode($userData['photo']['src'] ?? '');
				$photoStyle = $photoSrc ? 'background-image: url(\''.$photoSrc.'\');' : '';

				if ($photoStyle)
				{
					$members .= <<<HTML
						<div
							class="tasks-flow__list-members-icon_element"
							style="$photoStyle"
						></div>
					HTML;
				}
				else
				{
					$members .= <<<HTML
						<div class="tasks-flow__list-members-icon_element ui-icon ui-icon-common-user ui-icon-xs">
							<i></i>
						</div>
					HTML;
				}
			}
		}

		return <<<HTML
			$members
			$amount
		HTML;
	}
}