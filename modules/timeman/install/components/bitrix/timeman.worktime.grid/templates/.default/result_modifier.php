<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
if ($arResult['PARTIAL_ITEM'] === 'shiftCell')
{
	return;
}
/** @var \Bitrix\Timeman\Model\User\UserCollection $usersCollection */
$usersCollection = $arResult['usersCollection'];

use Bitrix\Timeman\Component\WorktimeGrid\TemplateParams;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Helper\UserHelper;

foreach ($arResult['DEPARTMENT_USERS_DATA'] as $departmentData)
{
	$departmentId = $departmentData['ID'];
	if ($arResult['DRAW_DEPARTMENT_SEPARATOR'])
	{
		$hintChain = '';
		foreach ($departmentData['CHAIN'] as $chainIndex => $chainDepartment)
		{
			if ($chainIndex > 0)
			{
				$hintChain .= '<span class="tm-departments-delimiter"> &mdash; </span>';
			}
			$hintChain .= htmlspecialcharsbx($chainDepartment['NAME']);
		}
		$url = !empty($departmentData['URL']) ? $departmentData['URL'] : '#';
		$depName = '<a data-hint-no-icon ' . ($arResult['isSlider'] ? ' target="_blank" ' : '') . '
				data-hint-html data-hint="' . htmlspecialcharsbx($hintChain) . '"
				href="' . $url . '">'
			. htmlspecialcharsbx($departmentData['NAME'])
			. '</a>';

		$departmentSeparatorHtml = '<span class="tm-department-name">' . $depName . '</span>';
		if ($arResult['canReadSettings'] && $arResult['showUserWorktimeSettings'])
		{
			$departmentSeparatorHtml .= '<span class="timeman-grid-settings-icon timeman-grid-settings-icon-time"
					data-entity-code="' . \Bitrix\Timeman\Helper\EntityCodesHelper::buildDepartmentCode($departmentData['ID']) . '"
					data-role="timeman-settings-toggle"
					data-id="' . htmlspecialcharsbx($departmentData['ID']) . '"
					data-type="department"></span>';
		}

		$arResult['ROWS'][] = [
			'columns' => [
				'USER_NAME' => $departmentSeparatorHtml,
			],
		];
	}
	foreach ($departmentData['USERS'] as $userData)
	{
		$user = $usersCollection->getByPrimary($userData['ID']);
		/** @var \Bitrix\Timeman\Model\User\User $user */

		$data = [
			'FORMATTED_NAME' => $user->buildFormattedName(),
			'WORK_POSITION' => $user->getWorkPosition(),
			'SHOW_DELETE_USER_BTN' => $arResult['SHOW_DELETE_USER_BTN'],
			'USER_ID' => $user->getId(),
			'PHOTO_SRC' => UserHelper::getInstance()->getPhotoPath($user['PERSONAL_PHOTO']) ?: '',
			'USER_PROFILE_PATH' => UserHelper::getInstance()->getProfilePath($user->getId()),
		];
		$columns = [];
		$columnClasses = [];

		foreach ($arResult['HEADERS'] as $worktimeCellDataIndex => $worktimeCellData)
		{
			if ($worktimeCellData['id'] === 'USER_NAME')
			{
				ob_start();
				require __DIR__ . '/_column-name.php';
				$columns['USER_NAME'] = ob_get_clean();
				continue;
			}

			##############
			$templateParamsList = (array) (
				$departmentData['USERS_DATA_BY_DATES'][$user->getId()][$worktimeCellData['id']] ?? null
			);
			ob_start();
			require __DIR__ . '/day-cell.php';
			$cellHtml = ob_get_clean();
			$date = array_key_exists('date', $worktimeCellData) ? $worktimeCellData['date'] : $worktimeCellData['id'];
			$columnClasses[$worktimeCellData['id']] = 'js-' . TemplateParams::getDayCellIdByData($user->getId(), $date);
			if (
				!empty($arResult['HOLIDAYS'][$user->getId()][$worktimeCellData['id']])
				&& $arResult['HOLIDAYS'][$user->getId()][$worktimeCellData['id']] === true
			)
			{
				$columnClasses[$worktimeCellData['id']] .= ' tm-worktime-list-holiday-cell';
			}
			$columns[$worktimeCellData['id']] = $cellHtml;
		}
		if ($arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'])
		{
			$userStats = $arResult['WORKTIME_STATISTICS'][$user->getId()];
			$workedDays = $userStats['TOTAL_WORKDAYS'];
			$workedDaysHtml = '';
			if ($userStats['TOTAL_NOT_APPROVED_WORKDAYS'] > 0)
			{
				$workedDaysHtml = "<span style=\"color:red;font-size: 12px;\">("
					. htmlspecialcharsbx($userStats['TOTAL_NOT_APPROVED_WORKDAYS'])
					. ")</span>";
			}

			$percentagePersonal = ($workedDays > 0 ? round(($userStats['TOTAL_VIOLATIONS']['PERSONAL'] / $workedDays) * 100) : 0);
			$percentageCommon = ($workedDays > 0 ? round(($userStats['TOTAL_VIOLATIONS']['COMMON'] / $workedDays) * 100) : 0);

			$workedDaysValue = $workedDays . $workedDaysHtml;
			$workedHoursValue = $userStats['TOTAL_WORKED_SECONDS'] >= 0 ?
				preg_replace(
					'#([0-9]+)([\D ]+)#',
					'\\1<span>\\2</span>',
					TimeHelper::getInstance()->convertSecondsToHoursMinutesLocal($userStats['TOTAL_WORKED_SECONDS']))
				: '0';
			$totalViolationPercent = '<span>'
				. '<span data-role="violation-percentage-stat" data-type="individual">' . ($percentagePersonal . '<span>%</span></span>')
				. '<span data-role="violation-percentage-stat" data-type="common">' . ($percentageCommon . '<span>%</span></span>')
				. '</span>';
			$columns['WORKED_DAYS'] = '<span class="timeman-grid-stat">' . $workedDaysValue . '</span>';
			$columns['WORKED_HOURS'] = '<span class="timeman-grid-stat">' . $workedHoursValue . '</span>';
			$columns['PERCENTAGE_OF_VIOLATIONS'] = '<span class="timeman-grid-stat">' . $totalViolationPercent . '</span>';
		}
		$row = [
			'data' => [],
			'columns' => $columns,
			'columnClasses' => $columnClasses,
		];

		$arResult['ROWS'][] = $row;
	}
}