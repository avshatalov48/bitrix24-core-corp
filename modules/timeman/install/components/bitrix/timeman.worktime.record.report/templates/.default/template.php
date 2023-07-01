<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'sidepanel',
	'ui.buttons',
	'ui.alerts',
	'ui.hint',
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/timeman.worktime.grid/templates/.default/violation-style.css');

/** @var \Bitrix\Timeman\Form\Worktime\WorktimeRecordForm $recordForm */
$recordForm = $arResult['recordForm'];
/** @var \Bitrix\Timeman\Helper\UserHelper $userHelper */
$userHelper = $arResult['userHelper'] ?? null;

?>
<div class="<?= $arResult['isSlider'] ? 'timeman-report-slider' : ''; ?>">

	<? if (defined('SITE_TEMPLATE_ID') && SITE_TEMPLATE_ID == 'bitrix24'): ?>
		<? $this->SetViewTarget('inside_pagetitle'); ?>
		<div class="timeman-report-nav">
			<? if (isset($arResult['RECORD_PREV_HREF'])): ?>
				<a href="#" class="timeman-report-nav-arrow timeman-report-nav-arrow-prev"
						data-role="navigation-record"
						data-url="<?= $arResult['RECORD_PREV_HREF']; ?>"
				></a>
			<? endif; ?>
			<span class="timeman-report-nav-current"><?= htmlspecialcharsbx($arResult['REPORT_FORMATTED_DATE']) ?></span>
			<? if (isset($arResult['RECORD_NEXT_HREF'])): ?>
				<a href="#" class="timeman-report-nav-arrow timeman-report-nav-arrow-next"
						data-role="navigation-record"
						data-url="<?= $arResult['RECORD_NEXT_HREF']; ?>"
				></a>
			<? endif; ?>
		</div>
		<? $this->EndViewTarget(); ?>
	<? endif; ?>
	<div class="timeman-report-wrap">
		<div class="timeman-report-inner"><?

			require __DIR__ . '/users_header.php';

			?>
			<div class="timeman-report-time">
				<div data-role="timeman-record-report-errors-block"></div>
				<div class="ui-alert ui-alert-danger timeman-hide" data-role="timeman-error-msg"></div>

				<div class="timeman-report-title">
					<div class="timeman-report-title-text"><?= htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_WORKTIME')) ?></div>
					<? if ($arResult['worktimeInfoHint']): ?>
						<span
							class="timeman-report-title-info-icon"
							data-hint-html
							data-hint="<?php echo htmlspecialcharsbx($arResult['worktimeInfoHint']); ?>"
						></span>
					<? endif; ?>
					<? if ($arResult['canUpdateWorktime']): ?>
						<div class="timeman-report-title-change" data-role="edit-worktime-btn">
							<?= htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_EDIT')) ?>
						</div>
					<? endif; ?>
					<? if ($arResult['canChangeWorktime']): ?>
						<div class="timeman-report-title-change" data-role="change-worktime-btn">
							<?= htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_EDIT')) ?>
						</div>
					<? endif; ?>
				</div>
				<?


				require __DIR__ . '/record_form.php';


				?>
				<div class="timeman-report-decs <?= empty($arResult['WORKTIME_REPORT']['REPORT']) ? 'timeman-hide' : '' ?>">
					<div class="timeman-report-title">
						<div class="timeman-report-title-text"><?= htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_REPORT')); ?></div>
					</div>
					<div class="timeman-report-decs-inner">
						<?= $arResult['WORKTIME_REPORT']['REPORT'] ?>
					</div>
				</div>
				<div class="timeman-report-activity-block
				<? if (empty($arResult['WORKTIME_REPORT']['EVENTS']) && empty($arResult['WORKTIME_REPORT']['TASKS'])): ?>
				    timeman-hide
				<? endif; ?>">
					<?php $maxToShow = 5; ?>
					<?php $maxToHide = $maxToShow - 1; ?>
					<div class="timeman-report-activity timeman-report-activity-tasks <?php echo empty($arResult['WORKTIME_REPORT']['TASKS']) ? 'timeman-hide' : ''; ?>">
						<div class="timeman-report-title">
							<div class="timeman-report-title-text"><?php
								echo htmlspecialcharsbx(Loc::getMessage('TM_RECORD_REPORT_TASKS_TITLE')); ?>
							</div>
						</div>
						<ol class="timeman-report-activity-list">
							<? foreach ($arResult['WORKTIME_REPORT']['TASKS'] as $index => $task) : ?>
								<li class="<?php echo $index > $maxToHide ? 'timeman-hide' : ''; ?>"
									<? if ($index > $maxToHide): ?>
										data-role="task-more"
									<? endif; ?>
								>
									<a href="<?php echo $task['URL']; ?>" class="timeman-report-activity-item"><?php
										echo htmlspecialcharsbx($task['TITLE']);
										?>
										<span><?php
											echo htmlspecialcharsbx($task['TIME_FORMATTED']);
											?></span>
									</a>
								</li>
							<? endforeach; ?>
						</ol>
						<? if (count($arResult['WORKTIME_REPORT']['TASKS']) > $maxToShow): ?>
							<span class="timeman-report-activity-more"
									data-role="show-more"
									data-show-id="task-more"><?php
								echo htmlspecialcharsbx(
									Loc::getMessage('TM_RECORD_REPORT_MORE_TASKS_TITLE', [
										'#COUNT#' => count($arResult['WORKTIME_REPORT']['TASKS']) - $maxToShow,
									])
								); ?></span>
						<? endif; ?>
					</div>
					<div class="timeman-report-activity <?php
					echo empty($arResult['WORKTIME_REPORT']['EVENTS']) ? ' timeman-hide ' : ''; ?><?php
					echo !empty($arResult['WORKTIME_REPORT']['TASKS']) ? ' timeman-report-activity-events ' : ''; ?>">
						<div class="timeman-report-title">
							<div class="timeman-report-title-text"><?php echo htmlspecialcharsbx(Loc::getMessage('TM_RECORD_REPORT_EVENTS_TITLE')); ?></div>
						</div>
						<div class="timeman-report-activity-list">
							<? foreach ($arResult['WORKTIME_REPORT']['EVENTS'] as $index => $event) : ?>
								<a href="<?php echo $event['URL']; ?>" target="_blank"
										class="timeman-report-activity-item <?php echo $index > $maxToHide ? 'timeman-hide' : ''; ?>"
									<? if ($index > $maxToHide): ?>
										data-role="event-more"
									<? endif; ?>>
									<span class="timeman-report-activity-time"><?php
										echo htmlspecialcharsbx($event['TIME_FROM']); ?> - <?php echo htmlspecialcharsbx($event['TIME_TO']);
										?></span>
									<?php echo htmlspecialcharsbx($event['NAME']); ?>
								</a>
							<? endforeach; ?>
						</div>
						<? if (count($arResult['WORKTIME_REPORT']['EVENTS']) > $maxToShow): ?>
							<span class="timeman-report-activity-more"
									data-role="show-more"
									data-show-id="event-more"><?php
								echo htmlspecialcharsbx(
									Loc::getMessage('TM_RECORD_REPORT_MORE_EVENTS_TITLE', [
										'#COUNT#' => count($arResult['WORKTIME_REPORT']['EVENTS']) - $maxToShow,
									])
								); ?></span>
						<? endif; ?>
					</div>
				</div>
				<div class="timeman-report-comment">
					<div class="timeman-report-title">
						<div class="timeman-report-title-text"><?= htmlspecialcharsbx(Loc::getMessage('JS_CORE_TMR_COMMENTS')); ?></div>
					</div>
					<div class="timeman-report-comment-inner">
						<?
						if ($arResult['COMMENT_FORUM_ID'] >= 0)
						{
							$APPLICATION->IncludeComponent(
								'bitrix:forum.comments',
								'bitrix24',
								[
									'FORUM_ID' => $arResult['COMMENT_FORUM_ID'],
									'ENTITY_TYPE' => 'TM',
									'ENTITY_ID' => $arResult['record']['ID'],
									'ENTITY_XML_ID' => 'TIMEMAN_ENTRY_' . $arResult['record']['ID'],
									'IMAGE_HTML_SIZE' => 400,
									'PAGE_NAVIGATION_TEMPLATE' => 'arrows',
									'DATE_TIME_FORMAT' => '',
									'EDITOR_CODE_DEFAULT' => 'N',
									'SHOW_MODERATION' => 'Y',
									'URL_TEMPLATES_PROFILE_VIEW' => $arResult['URL_TEMPLATES_PROFILE_VIEW'],
									'SHOW_AVATAR' => 'Y',
									'SHOW_MINIMIZED' => 'N',
									'USE_CAPTCHA' => 'N',
									'PREORDER' => 'N',
									'SHOW_LINK_TO_FORUM' => 'N',
									"SHOW_LINK_TO_MESSAGE" => "N",
									'SHOW_SUBSCRIBE' => 'N',
									'FILES_COUNT' => 10,
									'SHOW_WYSIWYG_EDITOR' => 'Y',
									'BIND_VIEWER' => 'N',
									'AUTOSAVE' => true,
									'SHOW_RATING' => true,
									'PERMISSION' => 'M',
									'MESSAGE_COUNT' => 3,
								],
								($component->__parent ? $component->__parent : $component),
								['HIDE_ICONS' => 'Y']
							);
						}
						?>
					</div>
				</div>
				<?
				if (!$arResult['IS_RECORD_APPROVED'])
				{
					$btnText = Loc::getMessage('TM_APPROVE_FORM_ACCEPT_LABEL');
					$btnClass = 'ui-btn-success';
					$btnAction = 'approve';
				}
				else
				{
					$btnText = Loc::getMessage('TIMEMAN_BTN_SAVE_TITLE');
					$btnClass = 'ui-btn-disabled ui-btn-default';
					$btnAction = 'save';
				}
				?>
				<div class="timeman-report-buttons">
					<div class="timeman-report-buttons-inner">
						<? if ($arResult['canUpdateWorktime']): ?>
							<button class="ui-btn ui-btn-md <?= $btnClass ?>"
									data-role="tm-record-btn-save"
									data-action="<?php echo $btnAction; ?>"><?=
								htmlspecialcharsbx($btnText)
								?></button>
						<? endif; ?>
						<? if ($arResult['canChangeWorktime']): ?>
							<button
								class="ui-btn ui-btn-md <?= $btnClass ?>"
								data-role="tm-record-btn-change"
								data-action="<?php echo $btnAction; ?>"
							><?= htmlspecialcharsbx($btnText) ?></button>
						<? endif; ?>
						<button class="ui-btn ui-btn-md ui-btn-light" data-role="tm-record-btn-cancel"><?=
							htmlspecialcharsbx(Loc::getMessage('TIMEMAN_BTN_CANCEL_TITLE'))
							?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
		BX.ready(function ()
		{
			BX.message({
				TIMEMAN_POPUP_WORK_TIME_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_POPUP_WORK_TIME_TITLE')) ?>',
				TIMEMAN_BTN_SAVE_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_BTN_SAVE_TITLE')) ?>',
				TIMEMAN_BTN_CANCEL_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TIMEMAN_BTN_CANCEL_TITLE')) ?>',
				TM_RECORD_REPORT_ROLL_UP_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('TM_RECORD_REPORT_ROLL_UP_TITLE')) ?>'
			});

			new BX.Timeman.Component.Worktime.Record.Report({
				containerSelector: '#workarea-content',
				startTimeFormHiddenInputName: "<?= CUtil::JSEscape($startTimeInputName)?>",
				endTimeFormHiddenInputName: "<?= CUtil::JSEscape($endTimeInputName)?>",
				breakLengthTimeFormHiddenInputName: "<?= CUtil::JSEscape($breakLengthInputName)?>",
				isSlider: <?= CUtil::PhpToJSObject($arResult['isSlider'])?>,
				useEmployeesTimezone: <?= CUtil::PhpToJSObject($arResult['useEmployeesTimezone'])?>,
				isShiftplan: <?= CUtil::PhpToJSObject($arResult['isShiftplan'])?>
			});
		});

	</script>