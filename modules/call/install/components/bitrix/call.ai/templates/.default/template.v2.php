<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Call\Integration\AI;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
/**
 * @global \CMain $APPLICATION
 * @var array $arResult
 * @var array $arParams
 */
global $APPLICATION;

\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/components/bitrix/call.ai/templates/.default/style-v2.css");

Loc::loadMessages(__DIR__. '/template.php');
Loc::loadMessages(__FILE__);


$overview = $arResult['OVERVIEW'] instanceof AI\Outcome\Overview ? $arResult['OVERVIEW'] : null;
$insights = $arResult['INSIGHTS'] instanceof AI\Outcome\Insights ? $arResult['INSIGHTS'] : null;
$summary = $arResult['SUMMARY'] instanceof AI\Outcome\Summary ? $arResult['SUMMARY'] : null;
$transcribe = $arResult['TRANSCRIBE'] instanceof AI\Outcome\Transcription ? $arResult['TRANSCRIBE'] : null;
$track = !empty($arResult['RECORD']) ? $arResult['RECORD'] : null;

\Bitrix\Main\UI\Extension::load([
	'ui.tooltip',
	'call.component.user-list',
	'call.component.elements.audioplayer',
	'call.lib.analytics',
	'call.component.elements.action-popup',
]);

CJSCore::Init(array('amcharts', 'amcharts4_core', 'amcharts_pie'));

$APPLICATION->SetTitle('');

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty(
	'BodyClass',
	($bodyClass ? $bodyClass . ' ' : '') . 'no-all-paddings no-background bx-call-component-call-ai-page bitrix24-light-theme'
);

$userCount = Loc::getMessagePlural('CALL_COMPONENT_USER', $arResult['USER_COUNT'], ['#USER_COUNT#' => $arResult['USER_COUNT']]);

Toolbar::deleteFavoriteStar();

if (!empty($arResult['FEEDBACK_URL']))
{
	$feedbackButton = new Button([
		'color' => Color::LIGHT_BORDER,
		'text' => Loc::getMessage('CALL_COMPONENT_FEEDBACK'),
		'classList' => [],
		'link' => $arResult['FEEDBACK_URL'],
	]);

	Toolbar::addButton($feedbackButton, ButtonLocation::RIGHT);
}

$jsMessages = [];
$jsMessagesCodes = [
	'CALL_COMPONENT_DELETE_FOLLOWUP_TITLE',
	'CALL_COMPONENT_DELETE_FOLLOWUP_DESCRIPTION',
	'CALL_COMPONENT_DELETE_FOLLOWUP_OK_BTN',
];

if(count($jsMessagesCodes) >0 )
{
	foreach ($jsMessagesCodes as $code)
	{
		$jsMessages[$code] = Loc::getMessage($code);
	}
}
?>

<div class="bx-call-component-call-ai" data-call-id="<?= $arResult['CALL_ID'] ?>">
	<div class="bx-call-component-call-ai__resume-container">
		<div class="bx-call-component-call-ai__resume-wrapper">
			<h2 class="bx-call-component-call-ai__slider-title">
				<?= Loc::getMessage('CALL_COMPONENT_COPILOT_DETAIL_TITLE') ?>
			</h2>
			<h3 class="bx-call-component-call-ai__resume-title"><?= $overview?->topic ?></h3>

			<p class="bx-call-component-call-ai__resume-description">
				<?
				if ($overview?->agenda)
				{
					echo $overview->agenda?->explanation . '<br>';
					echo $overview->agenda?->quote;
				}
				?>
			</p>

			<div class="bx-call-component-call-ai__call-timing-container">
				<div class="bx-call-component-call-ai__call-user-list-wrapper">
					<div class="bx-call-component-call-ai-page__title-users-container" data-call-id="<?= $arResult['CALL_ID'] ?>"></div>
					<div class="bx-call-component-call-ai__time-container">
						<div class="bx-call-component-call-ai__time-icon --calendar"></div>
						<div class="bx-call-component-call-ai__time-value"><?= $arResult['CALL_DATE'] ?></div>
					</div>
				</div>
				<div class="bx-call-component-call-ai__time-container">
					<div class="bx-call-component-call-ai__time-icon --clock"></div>
					<div class="bx-call-component-call-ai__time-value"><?= $arResult['CALL_START_TIME'] ?>
					<?
					if ($arResult['CALL_END_TIME'])
					{
						echo ' - '. $arResult['CALL_END_TIME'];
					}
					?>
					</div>
					<?
					if ($arResult['CALL_DURATION'])
					{
						?>
						<div class="bx-call-component-call-ai__time-divider"></div>
						<div class="bx-call-component-call-ai__time-icon --timer"></div>
						<div class="bx-call-component-call-ai__time-value"><?= $arResult['CALL_DURATION'] ?></div>
						<?
					}
					?>
				</div>
			</div>

			<div class="bx-call-component-call-ai__disclaimer">
				<?= Loc::getMessage('CALL_COMPONENT_COPILOT_DISCLAIMER', [
					'#LINK_START#' => '<span class="bx-call-component-call-ai__disclaimer-link">',
					'#LINK_END#' => '</span>'
				]) ?>
			</div>
			<?
			if ($arResult['CAN_DELETE'])
			{
				?>
				<div class="bx-call-component-call-ai__delete"><?= Loc::getMessage('CALL_COMPONENT_DELETE_FOLLOWUP') ?></div>
				<div class="bx-call-component-call-ai__delete-popup"></div>
				<?
			}
			?>
		</div>
	</div>

	<div class="bx-call-component-call-ai__tabs-container">
		<div class="bx-call-component-call-ai__tabs-header">
			<!--	data-tab-id must be the same as the corresponding id bx-call-component-call-ai__tab-content		-->
			<div class="bx-call-component-call-ai__tab-title --active" data-tab-id="TabGrade" data-tab-name="grade">
				<?= Loc::getMessage('CALL_COMPONENT_GRADE') ?>
				<span class="bx-call-component-call-ai__grade-tab-value"><?= $overview->efficiencyValue ?>%</span>
			</div>
			<div class="bx-call-component-call-ai__tab-title" data-tab-id="TabAgreements" data-tab-name="notes"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS') ?></div>
			<div class="bx-call-component-call-ai__tab-title" data-tab-id="TabRecommendations" data-tab-name="ai_call_quality"><?= Loc::getMessage('CALL_COMPONENT_INSIGHTS_V2') ?></div>
			<div class="bx-call-component-call-ai__tab-title" data-tab-id="TabSummary" data-tab-name="followup"><?= Loc::getMessage('CALL_COMPONENT_SUMMARY') ?></div>
			<div class="bx-call-component-call-ai__tab-title" data-tab-id="TabTranscriptions" data-tab-name="transcript"><?= Loc::getMessage('CALL_COMPONENT_TRANSCRIPTIONS') ?></div>
		</div>

		<div class="bx-call-component-call-ai__tab-content-container">
			<div class="bx-call-component-call-ai__tab-content-wrapper">
				<!--	id from data-tab-id	-->
				<div id="TabGrade" class="bx-call-component-call-ai__tab-details --grade">
				<?
				if ($overview->efficiencyValue >= 0)
				{
					// use --success when >75% or --failure
					$state = $overview->efficiencyValue > 75 ? '--success' : '--failure';
					$stateShort = match ($overview->efficiencyValue)
					{
						50 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_50'),
						75 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_75'),
						100 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_100'),
						default => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_0'),
					};
					if ($overview->isExceptionMeeting)
					{
						$recommendation = Loc::getMessage('CALL_COMPONENT_EXCEPTION_MEETING', [
							'#MEETING_TYPE#' => $overview->meetingDetails?->type ?? Loc::getMessage('CALL_COMPONENT_EXCEPTION_MEETING_DAILY')
						]);
					}
					else
					{
						$recommendation = match ($overview->efficiencyValue)
						{
							75 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS_75'),
							100 => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS_100'),
							default => Loc::getMessage('CALL_COMPONENT_EFFICIENCY_RECOMMENDATIONS_0'),
						};
					}
					?>

					<div class="bx-call-component-call-ai__grade-stats">
						<h4 class="bx-call-component-call-ai__tab-details-title"><?= Loc::getMessage('CALL_COMPONENT_GRADE') ?></h4>

						<div class="bx-call-component-call-ai__grade-value-wrapper" data-efficiency-value="<?= $overview->efficiencyValue ?>">
							<div id="bx-call-component-call-ai__grade-chart" class="bx-call-component-call-ai__grade-chart-wrapper"></div>
							<p class="bx-call-component-call-ai__grade-value">
								<?= $overview->efficiencyValue ?><span class="bx-call-component-call-ai__grade-value-symbol">%</span>
							</p>
						</div>
					</div>

					<div class="bx-call-component-call-ai__grade-description">
						<span class="bx-call-component-call-ai__resume-comment"><?= $recommendation ?></span>

						<ul class="bx-call-component-call-ai__list">

							<?
							// #1
							$disable = '';
							if ($overview->isExceptionMeeting)
							{
								//$disable = (bool)$overview->efficiency?->agendaClearlyStated?->value ? '' : '--disable';
								$disable = '--disable';
							}
							$state = (bool)$overview->efficiency?->agendaClearlyStated?->value ? '--success' : '--failure';
							?>
							<li class="bx-call-component-call-ai__list-item <?= $disable?>">
								<span class="bx-call-component-call-ai__list-item-icon <?= $state ?>"></span>
								<span class="bx-call-component-call-ai__list-item-text <?= $state ?>">
									<?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_AGENDA_CLEARLY') ?>
								</span>
							</li>

							<?
							// #2
							$state = (bool)$overview->efficiency?->agendaItemsCovered?->value ? '--success' : '--failure';
							?>
							<li class="bx-call-component-call-ai__list-item">
								<span class="bx-call-component-call-ai__list-item-icon <?= $state ?>"></span>
								<span class="bx-call-component-call-ai__list-item-text <?= $state ?>">
									<?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_AGENDA_COVERED_V2') ?>
								</span>
							</li>

							<?
							// #3
							$disable = '';
							if ($overview->isExceptionMeeting)
							{
								//$disable = (bool)$overview->efficiency?->conclusionsAndActionsOutlined?->value ? '' : '--disable';
								$disable = '--disable';
							}
							$state = (bool)$overview->efficiency?->conclusionsAndActionsOutlined?->value ? '--success' : '--failure';
							?>
							<li class="bx-call-component-call-ai__list-item <?= $disable?>">
								<span class="bx-call-component-call-ai__list-item-icon <?= $state ?>"></span>
								<span class="bx-call-component-call-ai__list-item-text <?= $state ?>">
									<?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_AGENDA_CONCLUSIONS') ?>
								</span>
							</li>

							<?
							// #4
							$state = '--success';
							if ($overview?->calendar)
							{
								$state = $overview->calendar->overhead ? '--failure' : '--success';
							}
							?>
							<li class="bx-call-component-call-ai__list-item">
								<span class="bx-call-component-call-ai__list-item-icon <?= $state ?>"></span>
								<span class="bx-call-component-call-ai__list-item-text <?= $state ?>">
									<?= Loc::getMessage('CALL_COMPONENT_EFFICIENCY_AGENDA_TIME_EXCEED') ?>
								</span>
							</li>
						</ul>
					</div>
				<?
				}
				?>
				</div>

				<div id="TabAgreements" class="bx-call-component-call-ai__tab-details --agreements">
					<h4 class="bx-call-component-call-ai__tab-details-title"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS') ?></h4>

					<?
					if ($overview?->agreements || $overview?->meetings || $overview?->tasks || $overview?->actionItems)
					{
						if ($overview?->agreements)
						{
							?>
							<div class="bx-call-component-call-ai__recommendations-container --result">
								<div class="bx-call-component-call-ai__recommendations__title"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS_COMMON') ?></div>

								<ol class="bx-call-component-call-ai__result-list">
									<?
									foreach ($overview->agreements as $row)
									{
										?>
										<li class="bx-call-component-call-ai__result-list-item">
											<?= $row->agreement ?>
										</li>
										<?
									}
									?>
								</ol>
							</div>
							<?
						}
						if ($overview?->actionItems)
						{
							?>
							<div class="bx-call-component-call-ai__recommendations-container --task">
								<div class="bx-call-component-call-ai__recommendations__title"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS_TASKS') ?></div>

								<ol class="bx-call-component-call-ai__result-list">
									<?
									foreach ($overview->actionItems as $row)
									{
										?>
										<li class="bx-call-component-call-ai__result-list-item">
											<div class="bx-call-component-call-ai__result-list-item-container">
												<p class="bx-call-component-call-ai__task-description">
													<?= $row->actionItem ?>
												</p>
												<!--		use data for parameters		-->
												<span
													class="bx-call-component-call-ai__task-button"
													data-user-id="<?= $arResult['CURRENT_USER_ID'] ?>"
													data-description="<?= htmlspecialcharsbx($row->actionItemMentionLess) ?>"
													data-auditors=""
													title="<?= Loc::getMessage('CALL_COMPONENT_TASK_CREATE') ?>"
												></span>
											</div>
										</li>
										<?
									}
									?>
								</ol>
							</div>
							<?
						}
						if ($overview?->tasks)
						{
							?>
							<div class="bx-call-component-call-ai__recommendations-container --task">
								<div class="bx-call-component-call-ai__recommendations__title"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS_TASKS') ?></div>

								<ol class="bx-call-component-call-ai__result-list">
									<?
									foreach ($overview->tasks as $row)
									{
										?>
										<li class="bx-call-component-call-ai__result-list-item">
											<div class="bx-call-component-call-ai__result-list-item-container">
												<p class="bx-call-component-call-ai__task-description">
													<?= $row->task ?>
												</p>
												<!--		use data for parameters		-->
												<span
													class="bx-call-component-call-ai__task-button"
													data-user-id="<?= $arResult['CURRENT_USER_ID'] ?>"
													data-description="<?= htmlspecialcharsbx($row->taskMentionLess) ?>"
													data-auditors=""
													title="<?= Loc::getMessage('CALL_COMPONENT_TASK_CREATE') ?>"
												></span>
											</div>
										</li>
										<?
									}
									?>
								</ol>
							</div>
							<?
						}
						if ($overview?->meetings)
						{
							?>
							<div class="bx-call-component-call-ai__recommendations-container --meetings">
								<div class="bx-call-component-call-ai__recommendations__title"><?= Loc::getMessage('CALL_COMPONENT_AGREEMENTS_MEETINGS') ?></div>

								<ol class="bx-call-component-call-ai__result-list">
									<?
									foreach ($overview->meetings as $row)
									{
										?>
										<li class="bx-call-component-call-ai__result-list-item">
											<div class="bx-call-component-call-ai__result-list-item-container">
												<p class="bx-call-component-call-ai__meetings-description">
													<?= $row->meeting ?>
												</p>
												<!--		use data for parameters		-->
												<span
													class="bx-call-component-call-ai__meetings-button"
													data-meeting-id=""
													data-meeting-type=""
													data-meeting-description="<?= htmlspecialcharsbx($row->meetingMentionLess) ?>"
													title="<?= Loc::getMessage('CALL_COMPONENT_MEETING_CREATE') ?>"
												></span>
											</div>
										</li>
										<?
									}
									?>
								</ol>
							</div>
							<?
						}
					}
					else
					{
						?><div class="bx-call-component-call-ai__result-empty-state"><?= Loc::getMessage('CALL_COMPONENT_EMPTY_AGREEMENTS'); ?></div><?
					}

					?>
				</div>

				<div id="TabRecommendations" class="bx-call-component-call-ai__tab-details --insights">
					<h4 class="bx-call-component-call-ai__tab-details-title"><?= Loc::getMessage('CALL_COMPONENT_INSIGHTS_V2') ?></h4>

					<div class="bx-call-component-call-ai__details-content-wrapper">
					<?

					if (
						$insights?->insights
						|| $insights?->meetingStrengths
						|| $insights?->meetingWeaknesses
						|| $insights?->speechStyleInfluence
						|| $insights?->engagementLevel
						|| $insights?->areasOfResponsibility
						|| $insights?->finalRecommendations
					)
					{
						if ($insights?->insights)
						{
							foreach ($insights->insights as $row)
							{
								?>
								<p class="bx-call-component-call-ai__recommendations">
									<?= $row->detailedInsight ?>
								</p>
								<?
							}
						}

						if ($insights?->meetingStrengths)
						{
							?>
							<div class="bx-call-component-call-ai-resume-block">
								<div class="bx-call-component-call-ai-resume-block__header">
									<?= Loc::getMessage('CALL_COMPONENT_INSIGHTS_STRENGTH') ?>
								</div>
								<?
								foreach ($insights->meetingStrengths as $row)
								{
									?>
									<p class="bx-call-component-call-ai__recommendations">
										<?= $row->strengthTitle ?>. <?= $row->strengthExplanation ?>
									</p>
									<?
								}
								?>
							</div>
							<?
						}

						if (
							$insights?->meetingWeaknesses
							|| $insights?->speechStyleInfluence
							|| $insights?->engagementLevel
							|| $insights?->areasOfResponsibility
						)
						{
							?>
							<div class="bx-call-component-call-ai-resume-block">
								<div class="bx-call-component-call-ai-resume-block__header">
									<?= Loc::getMessage('CALL_COMPONENT_INSIGHTS_WEAKNESS') ?>
								</div>
								<?
								foreach ($insights->meetingWeaknesses as $row)
								{
									?>
									<p class="bx-call-component-call-ai__recommendations">
										<?= $row->weaknessTitle ?>. <?= $row->weaknessExplanation ?>
									</p>
									<?
								}
								foreach (['speechStyleInfluence', 'engagementLevel', 'areasOfResponsibility'] as $field)
								{
									if ($insights?->{$field})
									{
										?>
										<p class="bx-call-component-call-ai__recommendations">
											<?= $insights->{$field} ?>
										</p>
										<?
									}
								}
								?>
							</div>
							<?
						}

						if ($insights?->finalRecommendations)
						{
							?>
							<div class="bx-call-component-call-ai-resume-block">
								<div class="bx-call-component-call-ai-resume-block__header">
									<?= Loc::getMessage('CALL_COMPONENT_INSIGHTS_FINAL_RECOMMENDATIONS') ?>
								</div>
								<p class="bx-call-component-call-ai__recommendations">
									<?= $insights->finalRecommendations ?>
								</p>
							</div>
							<?
						}
					}
					else
					{
						?><div class="bx-call-component-call-ai__result-empty-state"><?= Loc::getMessage('CALL_COMPONENT_EMPTY_INSIGHTS'); ?></div><?
					}
					?>
					</div>
				</div>

				<div id="TabSummary" class="bx-call-component-call-ai__tab-details --summary">
					<h4 class="bx-call-component-call-ai__tab-details-title"><?= Loc::getMessage('CALL_COMPONENT_SUMMARY') ?></h4>

					<div class="bx-call-component-call-ai__details-content-wrapper">
					<?

					if ($summary?->summary || $overview?->detailedTakeaways)
					{
						if (!empty($track['REL_URL']))
						{
							?>
							<div class="bx-call-component-call-ai__call-audio-wrapper">
								<div
									id="bx-call-component-call-ai__call-audio-record-TabSummary"
									class="bx-call-component-call-ai__call-audio-record"
									data-audio-id="TabSummary" data-audio-src="<?= $track['REL_URL'] ?>"
								></div>
							</div>
							<?
						}

						if ($overview?->detailedTakeaways)
						{
							?>
							<div class="bx-call-component-call-ai-resume-block">
								<p class="bx-call-component-call-ai-resume-block__description">
									<?= $overview->detailedTakeaways ?>
								</p>
							</div>
							<?
						}
						?>
						<div class="bx-call-component-call-ai__resume-block-wrapper">
							<?
							foreach ($summary->summary as $row)
							{
							?>
								<div class="bx-call-component-call-ai-resume-block">
									<div class="bx-call-component-call-ai-resume-block__title">
										<span class="bx-call-component-call-ai-resume-block__time bx-call-component-call-ai__time-code"
											  data-audio-id="TabSummary"
										>
											<?= $row->start ?>—<?= $row->end ?>
										</span>
										<span class="bx-call-component-call-ai-resume-block__name"><?= $row->title ?></span>
									</div>
									<p class="bx-call-component-call-ai-resume-block__description">
										<?= $row->summary ?>
									</p>
								</div>
							<?
							}
							?>
						</div>
						<?
					}
					else
					{
						?><div class="bx-call-component-call-ai__result-empty-state"><?= Loc::getMessage('CALL_COMPONENT_EMPTY_SUMMARY'); ?></div><?
					}

					?>
					</div>
				</div>

				<div id="TabTranscriptions" class="bx-call-component-call-ai__tab-details --transcriptions">
					<h4 class="bx-call-component-call-ai__tab-details-title"><?= Loc::getMessage('CALL_COMPONENT_TRANSCRIPTIONS') ?></h4>

					<div class="bx-call-component-call-ai__details-content-wrapper">
					<?

					if ($transcribe?->transcriptions)
					{
						if (!empty($track['REL_URL']))
						{
							?>
							<div class="bx-call-component-call-ai__call-audio-wrapper">
								<div
									id="bx-call-component-call-ai__call-audio-record-TabTranscriptions"
									class="bx-call-component-call-ai__call-audio-record"
									data-audio-id="TabTranscriptions" data-audio-src="<?= $track['REL_URL'] ?>"
								></div>

								<div class="bx-call-component-call-ai__audio-disclaimer-wrapper">
									<div class="bx-call-component-call-ai__audio-disclaimer-icon"></div>
									<span class="bx-call-component-call-ai__call-audio-disclaimer"><?= Loc::getMessage('CALL_COMPONENT_AUDIO_DISCLAIMER') ?></span>
								</div>
							</div>
							<?
						}
						foreach ($transcribe->transcriptions as $row)
						{
							?>
							<div class="bx-call-component-call-ai-decryption-block">
								<p class="bx-call-component-call-ai-decryption-block__description">
									<span
										class="bx-call-component-call-ai-decryption-block__time bx-call-component-call-ai__time-code"
										data-audio-id="TabTranscriptions"
									>
										<?= $row->start ?>—<?= $row->end ?>
									</span>
									<span class="bx-call-component-call-ai-decryption-block__name"><?= $row->user ?>:</span>
									<?= $row->text ?>
								</p>
							</div>
							<?
						}
					}
					else
					{
						?><div class="bx-call-component-call-ai__result-empty-state"><?= Loc::getMessage('CALL_COMPONENT_EMPTY_TRANSCRIPTIONS'); ?></div><?
					}

					?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	BX.message(<?=\Bitrix\Main\Web\Json::encode($jsMessages)?>);
</script>
