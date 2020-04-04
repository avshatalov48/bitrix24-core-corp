<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Helper\UserHelper;

foreach ($arResult['DEPARTMENTS_USERS_DATA']['DEPARTMENTS'] as $departmentId => $departmentData)
{
	$draw = false;
	foreach ($departmentData['USERS'] as $user)
	{
		$columns = [];
		if ($arResult['DRAW_DEPARTMENT_SEPARATOR'] && !$draw)
		{
			$hintChain = '';
			if (empty($departmentData['CHAIN']) && !empty($arResult['DEPARTMENTS'][$departmentId]['CHAIN']))
			{
				$departmentData['CHAIN'] = &$arResult['DEPARTMENTS'][$departmentId]['CHAIN'];
			}
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
				data-hint="' . htmlspecialcharsbx($hintChain) . '"
				href="' . $url . '">'
					   . htmlspecialcharsbx($departmentData['NAME'])
					   . '</a>';

			$depNameHtml = '<span class="tm-department-name">' . $depName . '</span>';
			if ($arResult['canReadSettings'])
			{
				$depNameHtml .= '<span class="timeman-grid-settings-icon timeman-grid-settings-icon-time"
					data-role="timeman-settings-toggle"
					data-id="' . htmlspecialcharsbx($departmentData['ID']) . '"
					data-type="department"></span>';
			}
			$arResult['ROWS'][] = [
				'columns' => [
					'USER_NAME' => $depNameHtml,
				],
			];
			$draw = true;
		}

		$datesCellData = $arResult['USER_GRID_DATA'][$user['ID']];
		if (empty($datesCellData))
		{
			continue;
		}
		$columnClasses = [];
		$data = [
			'FORMATTED_NAME' => $user['NAME'],
			'WORK_POSITION' => $user['WORK_POSITION'],
			'SHOW_DELETE_USER_BTN' => $arResult['SHOW_DELETE_USER_BTN'],
			'USER_ID' => $user['ID'],
			'PHOTO_SRC' => UserHelper::getInstance()->getPhotoPath($user['PERSONAL_PHOTO']) ?: '',
			'USER_PROFILE_PATH' => UserHelper::getInstance()->getProfilePath($user['ID']),
		];
		foreach ($datesCellData as $dateFormatted => $worktimeCellData)
		{
			ob_start();
			require __DIR__ . '/parts/column-name.php';
			$usernameCell = ob_get_clean();

			##############
			$cellHtml = '';
			foreach ($worktimeCellData as $recordShiftplanData)
			{
				if (!empty($recordShiftplanData['ABSENCE']))
				{
					$columnClasses[$dateFormatted] = 'timeman-grid-cell-absence';
				}
				$itemHtml = '';
				if ($recordShiftplanData['ABSENCE'])
				{
					ob_start(); ?>
				<div class="timeman-grid-worktime timeman-grid-worktime-absence-block timeman-grid-cell-absence-<?= htmlspecialcharsbx($recordShiftplanData['ABSENCE_PART']) ?>">
					<?
					if (empty($recordShiftplanData['WORKTIME_RECORD'])):?>
						<div class="timeman-grid-worktime-inner" <? if ($recordShiftplanData['ABSENCE_HINT']): ?>
							data-hint-no-icon data-hint="<?php echo htmlspecialcharsbx($recordShiftplanData['ABSENCE_HINT']) ?>"
						<? endif; ?>
						>
								<span class="timeman-grid-worktime-absence-desc"><?=
									htmlspecialcharsbx(isset($recordShiftplanData['ABSENCE_TITLE']) ? $recordShiftplanData['ABSENCE_TITLE'] : '')
									?></span>
						</div>
						</div><? // end of absence-block
						?>
					<? endif;
					$itemHtml .= ob_get_clean();
				}

				ob_start();
				require __DIR__ . '/fixed-flex-cell.php';
				$itemHtml .= ob_get_clean();
				if ($recordShiftplanData['WORKTIME_RECORD'] && $recordShiftplanData['ABSENCE'])
				{
					$itemHtml .= '</div>'; // end of absence-block
				}

				$cellHtml .= $itemHtml;
			}
			$columns[$dateFormatted] = $cellHtml;
		}
		if ($arResult['GRID_OPTIONS']['SHOW_STATS_COLUMNS'])
		{
			$userStats = $arResult['WORKTIME_STATISTICS'][$user['ID']];
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
									 . '<span data-role="violation-percentage-stat" data-type="personal">' . ($percentagePersonal . '<span>%</span></span>')
									 . '<span data-role="violation-percentage-stat" data-type="common">' . ($percentageCommon . '<span>%</span></span>')
									 . '</span>';
			$columns = array_merge(
				[
					'WORKED_DAYS' => '<span class="timeman-grid-stat">' . $workedDaysValue . '</span>',
					'WORKED_HOURS' => '<span class="timeman-grid-stat">' . $workedHoursValue . '</span>',
					'PERCENTAGE_OF_VIOLATIONS' => '<span class="timeman-grid-stat">' . $totalViolationPercent . '</span>',
				],
				$columns
			);
		}

		$row = [
			'actions' => [],
			'data' => [],
			'columns' => array_merge(
				[
					'USER_NAME' => $usernameCell,
				],
				$columns
			),
			'columnClasses' => $columnClasses,
		];

		$arResult['ROWS'][] = $row;
	}
}