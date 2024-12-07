<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\User\User;

if (!function_exists('renderOwnerIdColumn'))
{
	function renderOwnerIdColumn(array $data, array $arResult, bool $isActive): string
	{
		/** @var Flow $flow */
		$flow = $data['flow'];
		/** @var User $user */
		$user = $data['user'];

		$userData = $user?->toArray() ?? [];

		$name = HtmlFilter::encode($userData['name'] ?? '');
		$photoSrc = Uri::urnEncode($userData['photo']['src'] ?? '');
		$photoStyle = $photoSrc ? 'background-image: url(\''.$photoSrc.'\');' : '';
		$pathToProfile = $userData['pathToProfile'] ?? '';

		$disableClass = $isActive ? '' : '--disable';

		if ($flow->isDemo())
		{
			$label = Loc::getMessage('TASKS_FLOW_LIST_COLUMN_DEMO_LABEL');
			$owner = <<<HTML
				<div class="tasks-flow__list-owner --demo">
					<div class="tasks-flow__list-members-icon_element --icon">
						<div
							class="ui-icon-set --person"
							style="--ui-icon-set__icon-color: var(--ui-color-base-50);"
						></div>
					</div>
					<div>$label</div>
				</div>
			HTML;
		}
		else
		{
			$owner = <<<HTML
				<a href="$pathToProfile" class="tasks-flow__list-owner">
					<span class="tasks-flow__list-owner-photo ui-icon ui-icon-common-user">
						<i style="$photoStyle"></i>
					</span>
					$name
				</a>
			HTML;
		}

		return <<<HTML
			<div class="tasks-flow__list-cell $disableClass">
				<div class="tasks-flow__list-owner-wrapper">
					$owner
				</div>
			</div>
		HTML;
	}
}
