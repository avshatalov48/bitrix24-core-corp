<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Crm\Integration;
/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponent $component */
/** @global CMain $APPLICATION */

/** @var \Bitrix\Crm\Activity\Provider\Base $provider */
$provider = $arResult['PROVIDER'];
$activity = $arResult['ACTIVITY'];

$storageValues = array();
$storageProps = array();

$pageAsset = \Bitrix\Main\Page\Asset::getInstance();

switch ($activity['STORAGE_TYPE_ID'])
{
	case Integration\StorageType::WebDav:

		$pageAsset->addCss('/bitrix/components/bitrix/webdav/templates/.default/style.css');
		$pageAsset->addCss('/bitrix/components/bitrix/webdav.user.field/templates/.default/style.css');
		$pageAsset->addCss('/bitrix/js/webdav/css/file_dialog.css');

		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/core/core_dd.js');
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/main/file_upload_agent.js');
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/webdav/file_dialog.js');
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/webdav_uploader.js');

		$paths = CCrmWebDavHelper::GetPaths();
		$storageProps['WEBDAV_SELECT_URL'] = isset($paths['PATH_TO_FILES']) ? $paths['PATH_TO_FILES'] : '';
		$storageProps['WEBDAV_UPLOAD_URL'] = isset($paths['ELEMENT_UPLOAD_URL']) ? $paths['ELEMENT_UPLOAD_URL'] : '';
		$storageProps['WEBDAV_SHOW_URL'] = isset($paths['ELEMENT_SHOW_INLINE_URL']) ? $paths['ELEMENT_SHOW_INLINE_URL'] : '';

		$storageValues = $activity['WEBDAV_ELEMENTS'];
		break;

	case Integration\StorageType::Disk:
		CJSCore::Init(array('uploader', 'file_dialog'));
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/disk_uploader.js');
		$pageAsset->addCss('/bitrix/js/disk/css/legacy_uf_common.css');
		$storageValues = $activity['DISK_FILES'];
		break;

	default:
		$storageValues = $activity['FILES'];
		$pageAsset->addCss('/bitrix/js/disk/css/disk.css');
		break;
}
$storageValues = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($storageValues));
$storageProps = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($storageProps));
$destinationEntities = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($arResult['DESTINATION_ENTITIES']));
$communicationsData = htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($arResult['COMMUNICATIONS_DATA']));

$APPLICATION->restartBuffer();

\CJSCore::init(array('ui.design-tokens', 'crm_activity_planner', 'ui.fonts.opensans'));
$pageAsset->addCss('/bitrix/js/crm/css/crm.css');
$pageAsset->addCss('/bitrix/themes/.default/crm-entity-show.css');
$pageAsset->addJs('/bitrix/js/calendar/planner.js');
$pageAsset->addCss('/bitrix/js/calendar/planner-style.css');
?><!DOCTYPE html>
<html>
<head><? $APPLICATION->showHead(); ?></head>
<body style="background: #eef2f4 !important; ">

<div class="crm-activity-planner-slider-wrap" data-role="wrapper-container">
	<div class="crm-activity-planner-slider-container">
		<?if ((int)$activity['TYPE_ID'] === \CCrmActivityType::Email):?>

			<div class="crm-activity-planner-slider-header crm-activity-planner-slider-header-icon crm-activity-planner-slider-header-icon-<?=$arResult['TYPE_ICON']?>">
				<div class="crm-activity-planner-slider-header-title"><?=htmlspecialcharsbx($provider::getTypeName($activity['PROVIDER_TYPE_ID'], ($activity['DIRECTION'] ?? null))) ?></div>
				<div class="crm-activity-planner-slider-header-control-block">
					<? $APPLICATION->showViewContent('planner_slider_header') ?>
					<div class="crm-activity-planner-slider-header-control-item crm-activity-planner-slider-header-control-important crm-activity-planner-slider-header-icon-flame-active">
							<input class="crm-activity-planner-slider-header-control-checkbox" id="crm_act_planner_edit_important" name="__crm_activity_planner[important]" value="1" type="checkbox">
							<label class="crm-activity-planner-slider-header-control-text crm-activity-planner-slider-header-control-label" for="crm_act_planner_edit_important"><?=getMessage('CRM_ACTIVITY_PLANNER_IMPORTANT_SLIDER') ?></label>
							<div class="crm-activity-planner-slider-header-control-icon"></div>
					</div>
				</div>
			</div><!--crm-task-list-header-->
			<?=$provider::renderEdit($activity) ?>

		<?else:?>
		<form data-role="form">
			<div class="crm-activity-planner-slider-header crm-activity-planner-slider-header-icon crm-activity-planner-slider-header-icon-<?=$arResult['TYPE_ICON']?>">
				<div class="crm-activity-planner-slider-header-title"><?=htmlspecialcharsbx($provider::getTypeName($activity['PROVIDER_TYPE_ID'], $activity['DIRECTION'] ?? null)) ?></div>
				<div class="crm-activity-planner-slider-header-control-block">
					<? $APPLICATION->showViewContent('planner_slider_header') ?>
					<div class="crm-activity-planner-slider-header-control-item crm-activity-planner-slider-header-control-important crm-activity-planner-slider-header-icon-flame-active">
						<div class="crm-activity-popup-timeline-checkbox-container">
							<label class="crm-activity-popup-timeline-checkbox-block">
								<input type="checkbox" name="completed" value="Y" class="crm-activity-popup-timeline-checkbox" <?if (($activity['COMPLETED'] ?? 'N') == 'Y'):?>checked<?endif?>>
								<span class="crm-activity-popup-timeline-checkbox-text"><?=GetMessage('CRM_ACTIVITY_PLANNER_COMPLETED_SLIDER')?></span>
							</label>
							<label class="crm-activity-popup-timeline-checkbox-block">
								<input type="checkbox" name="important" value="Y" class="crm-activity-popup-timeline-checkbox" data-role="priority-switcher" <?if (($activity['PRIORITY'] ?? null) == CCrmActivityPriority::High):?>checked<?endif?>>
								<span class="crm-activity-popup-timeline-checkbox-text"><?=GetMessage('CRM_ACTIVITY_PLANNER_IMPORTANT_SLIDER')?></span>
								<svg class="crm-activity-popup-timeline-checkbox-flame" viewBox="0 0 12 17" version="1.1">
									<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
										<g transform="translate(-489.000000, -341.000000)">
											<g transform="translate(489.000000, 341.000000)">
												<path class="crm-activity-flame <?if (($activity['PRIORITY'] ?? null) == CCrmActivityPriority::High):?>crm-activity-popup-container-open<?endif?>" d="M6.99737834,5.68434189e-14 C4.48779883,1.21921125 -1.13686838e-13,5.48917695 -1.13686838e-13,10.0533128 C-1.13686838e-13,14.6177213 4.28414257,16.0853005 4.28414257,16.0853005 L7.71167473,16.0853005 C7.71167473,16.0853005 11.9958173,14.7055089 11.9958173,10.8076861 C11.9958173,5.31551023 6.52190776,4.21298556 6.99737834,5.68434189e-14" fill="#A6ACB3" data-role="priority-flame"></path>
												<path d="M3,12.9755379 C3,15.605875 5.33680504,16.4515986 5.33680504,16.4515986 L7.20636804,16.4515986 C7.20636804,16.4515986 8.99790865,15.6564644 8.99790865,13.4102615 C8.99790865,10.2452788 6.54431789,10.4536899 5.99895433,8 C4.63009278,8.70259631 3,10.3453579 3,12.9755379 Z" fill="#EEF2F4"></path>
											</g>
										</g>
									</g>
								</svg>
							</label>
						</div><!--crm-activity-popup-timeline-checkbox-container-->
					</div>
				</div>
			</div><!--crm-task-list-header-->

			<div data-role="wrapper-container" data-title="<?=htmlspecialcharsbx($provider::getPlannerTitle($activity))?>">

					<input type="hidden" name="id" value="<?=(int)($activity['ID'] ?? 0)?>" data-role="field-id">
					<input type="hidden" name="type" value="<?=(int)$activity['TYPE_ID']?>" data-role="field-type-id">
					<input type="hidden" name="providerId" value="<?=htmlspecialcharsbx($activity['PROVIDER_ID'])?>" data-role="field-provider-id">
					<input type="hidden" name="providerTypeId" value="<?=htmlspecialcharsbx($activity['PROVIDER_TYPE_ID'])?>" data-role="field-provider-type-id">
					<input type="hidden" name="direction" value="<?=(int)($activity['DIRECTION'] ?? 0)?>" data-role="field-custom-type-id">
					<input type="hidden" name="startTime" value="<?=htmlspecialcharsbx($activity['START_TIME'] ?? '')?>" data-role="field-start-time">
					<input type="hidden" name="endTime" value="<?=htmlspecialcharsbx($activity['END_TIME'] ?? '')?>" data-role="field-end-time">
					<input type="hidden" name="notifyValue" value="<?=(int)$activity['NOTIFY_VALUE']?>" data-role="field-notify-value">
					<input type="hidden" name="notifyType" value="<?=(int)$activity['NOTIFY_TYPE']?>" data-role="field-notify-type">
					<input type="hidden" name="ownerType" value="<?=CCrmOwnerType::ResolveName($activity['OWNER_TYPE_ID'])?>" data-role="field-owner-type">
					<input type="hidden" name="ownerId" value="<?=(int)$activity['OWNER_ID']?>" data-role="field-owner-id">
					<input type="hidden" value="<?=$destinationEntities?>" data-role="destination-entities">
					<input type="hidden" value="<?=$communicationsData?>" data-role="communications-data">
					<div class="crm-activity-slider-container" data-role="main-container">
						<div class="crm-activity-popup-recall-container">
							<div class="crm-activity-popup-recall-select-container" data-role="day-switcher">
								<span class="crm-activity-popup-recall-select-date" data-day="0"><?=GetMessage('CRM_ACTIVITY_PLANNER_TODAY')?></span>
								<span class="crm-activity-popup-recall-select-date" data-day="1"><?=GetMessage('CRM_ACTIVITY_PLANNER_TOMORROW')?></span>
								<span class="crm-activity-popup-recall-select-date" data-day="2"><?=GetMessage('CRM_ACTIVITY_PLANNER_2_DAYS')?></span>
								<span class="crm-activity-popup-recall-select-date" data-day="3"><?=GetMessage('CRM_ACTIVITY_PLANNER_3_DAYS')?></span>
							</div><!--crm-activity-popup-recall-select-container-->
							<div class="crm-activity-popup-recall-remind-container">
								<label class="crm-activity-popup-recall-remind-block">
									<input type="checkbox" class="crm-activity-popup-recall-remind-checkbox" data-role="notify-activator" <?if ($activity['NOTIFY_VALUE']):?>checked<?endif?>>
									<span class="crm-activity-popup-recall-remind-text" data-role="notify-activator-label" data-label-y="<?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_SWITCHER_2')?>" data-label-n="<?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_SWITCHER')?>"><?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_SWITCHER'.($activity['NOTIFY_VALUE'] ? '_2' : ''))?></span>
								</label>
								<span class="crm-activity-popup-recall-remind-link crm-activity-popup-container-open" data-role="notify-switcher"></span>
							</div><!--crm-activity-popup-recall-remind-container-->
						</div><!--crm-activity-popup-recall-container-->
						<div class="crm-activity-popup-timeline-container">

							<div class="crm-activity-popup-calendar-planner-wrap" id="calendar-planner-outer<?=htmlspecialcharsbx($arResult['PLANNER_ID'])?>" data-role="calendar-planner-container" style="min-height: 104px">
								<?
								\Bitrix\Main\UI\Extension::load("ui.alerts");

								Bitrix\Main\Loader::includeModule('calendar');
								\Bitrix\Main\UI\Extension::load("calendar.planner");

								// CCalendarPlanner::Init(array(
								// 	'id' => 'calendar_planner_'.htmlspecialcharsbx($arResult['PLANNER_ID']),
								// 	'scaleLimitOffsetLeft' => 2,
								// 	'scaleLimitOffsetRight' => 2,
								// 	'maxTimelineSize' => 30
								// ));
								?>
								<div class="calendar-planner-wrapper"></div>
							</div>

							<div class="crm-activity-popup-timeline-detail-info-container" data-role="detail-container">
				<span class="crm-activity-popup-timeline-detail-info-date">
					<label class="crm-activity-popup-timeline-detail-info-date-name"><?=GetMessage('CRM_ACTIVITY_PLANNER_START_DAY')?>:</label>
					<span class="crm-activity-popup-timeline-detail-info-date-calendar-container">
						<input type="text" class="crm-activity-popup-timeline-detail-info-date-calendar" data-role="calendar-start-time" readonly>
					</span>
					<span class="crm-activity-popup-timeline-detail-info-date-time-container">
						<input type="text" class="crm-activity-popup-timeline-detail-info-date-time" data-role="clock-start-time" readonly>
					</span>
				</span><!--crm-activity-popup-timeline-detail-info-date-->
								<span class="crm-activity-popup-timeline-detail-info-duration">
					<label class="crm-activity-popup-timeline-detail-info-duration-name"><?=GetMessage('CRM_ACTIVITY_PLANNER_DURATION')?>:</label>
					<input type="text" name="durationValue" value="<?=(int)$arResult['DURATION_VALUE']?>" placeholder="---" class="crm-activity-popup-timeline-detail-info-duration-number" data-role="duration-value">
					<select name="durationType" class="crm-activity-popup-timeline-detail-info-duration-input" data-role="duration-type">
						<option value="<?=CCrmActivityNotifyType::Min?>" <?if ($arResult['DURATION_TYPE'] === CCrmActivityNotifyType::Min):?> selected<?endif?>><?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_TYPE_M')?></option>
						<option value="<?=CCrmActivityNotifyType::Hour?>" <?if ($arResult['DURATION_TYPE'] === CCrmActivityNotifyType::Hour):?> selected<?endif?>><?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_TYPE_H')?></option>
						<option value="<?=CCrmActivityNotifyType::Day?>" <?if ($arResult['DURATION_TYPE'] === CCrmActivityNotifyType::Day):?> selected<?endif?>><?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_TYPE_D')?></option>
					</select>
				</span><!--crm-activity-popup-timeline-detail-info-duration-->
								<span class="crm-activity-popup-timeline-detail-info-date">
					<label class="crm-activity-popup-timeline-detail-info-date-name"><?=GetMessage('CRM_ACTIVITY_PLANNER_END_DAY')?>:</label>
					<span class="crm-activity-popup-timeline-detail-info-date-calendar-container">
						<input type="text" value="" class="crm-activity-popup-timeline-detail-info-date-calendar" data-role="calendar-end-time" readonly>
					</span>
					<span class="crm-activity-popup-timeline-detail-info-date-time-container">
						<input type="text" value="" class="crm-activity-popup-timeline-detail-info-date-time" data-role="clock-end-time" readonly>
					</span>
				</span><!--crm-activity-popup-timeline-detail-info-date-->
							</div><!--crm-activity-popup-timeline-detail-info-container-->

							<div class="crm-activity-popup-timeline-detail">
					<span class="crm-activity-popup-timeline-detail-link"
						  data-state="<?=$arResult['DETAIL_MODE']? 'open' : ''?>"
						  data-role="view-mode-switcher"
						  data-label-short="<?=GetMessage('CRM_ACTIVITY_PLANNER_SHORT_1')?>"
						  data-label-detail="<?=GetMessage('CRM_ACTIVITY_PLANNER_DETAIL_1')?>"
						  data-animation-duration="300">
						<?=GetMessage('CRM_ACTIVITY_PLANNER_DETAIL_1')?>
					</span>
							</div><!--crm-activity-popup-timeline-detail-->
						</div><!--crm-activity-popup-timeline-container-->
						<div class="crm-activity-popup-info">
							<? foreach ($provider::getFieldsForEdit($activity) as $field):
								$name = isset($field['NAME']) ? $field['NAME'] : '';
								if($name === '')
								{
									$name = $field['TYPE'];
								}
								switch ($field['TYPE'])
								{
									case 'SUBJECT':
									case 'LOCATION':
									case 'TEXT':?>
										<div class="crm-activity-popup-info-location-container">
										<span class="crm-activity-popup-info-location-text"><?=htmlspecialcharsbx($field['LABEL'])?>:</span>
										<input type="text" name="<?= mb_strtolower($name)?>" value="<?=\CModule::IncludeModule('calendar')
											? htmlspecialcharsbx(CCalendar::GetTextLocation($field['VALUE']))
											: htmlspecialcharsbx($field['VALUE'])?>" class="crm-activity-popup-info-location" <?if(($field['PLACEHOLDER'] ?? '') != ''):?>placeholder="<?=htmlspecialcharsbx($field['PLACEHOLDER'])?>"<?endif?> data-role="focus-on-show">
										</div><?
										break;
									case 'TEXTAREA':?>
										<div class="crm-activity-popup-info-person-detail-description">
										<label class="crm-activity-popup-info-person-detail-description-name"><?=htmlspecialcharsbx($field['LABEL'])?>:</label>
										<textarea name="<?= mb_strtolower($name)?>" class="crm-activity-popup-info-person-detail-description-input" <?if($field['PLACEHOLDER'] != ''):?>placeholder="<?=htmlspecialcharsbx($field['PLACEHOLDER'])?>"<?endif?>><?=htmlspecialcharsbx($field['VALUE'])?></textarea>
										</div><?
										break;
									case 'COMMUNICATIONS': ?>
										<div class="crm-activity-popup-info-person-container">
										<span class="crm-activity-popup-info-person-text"><?=htmlspecialcharsbx($field['LABEL'])?>:</span>
										<div class="crm-activity-popup-info-person-block" data-role="communications-container" data-communication-type="<?=$provider::getCommunicationType($activity['PROVIDER_TYPE_ID'])?>"></div><!--crm-activity-popup-info-person-block-->
										</div><?
									default:
										if (isset($field['HTML']))
											echo $field['HTML'];
								}
							endforeach;
							?>
							<div class="crm-activity-popup-info-additional-container">
								<div class="crm-activity-popup-info-person-link-container">
									<span class="crm-activity-popup-info-person-link-triangle <?if ($arResult['ADDITIONAL_MODE']):?>crm-activity-popup-info-person-link-triangle-up<?endif;?>" data-role="additional-mode-switcher">
										<?=GetMessage('CRM_ACTIVITY_PLANNER_ADDITIONAL')?>
									</span>
								</div><!--crm-activity-popup-info-person-link-container-->
								<div class="crm-activity-popup-info-person-detail-container <?if ($arResult['ADDITIONAL_MODE']):?>crm-activity-person-detail-open<?endif;?>" data-role="additional-container">
									<? foreach ($provider::getAdditionalFieldsForEdit($activity) as $field):
										switch ($field['TYPE'])
										{
											case 'DESCRIPTION': ?>
												<div class="crm-activity-popup-info-person-detail-description">
												<label class="crm-activity-popup-info-person-detail-description-name"><?=GetMessage('CRM_ACTIVITY_PLANNER_DESCRIPTION')?>:</label>
												<textarea name="description" class="crm-activity-popup-info-person-detail-description-input"><?=htmlspecialcharsbx($activity['DESCRIPTION'] ?? '')?></textarea>
												</div><?
												break;
											case 'PROVIDER_TYPE':
												$directions = $provider::getTypeDirections($activity['PROVIDER_TYPE_ID']);
												if ($directions)
												{ ?>
													<div class="crm-activity-popup-info-person-detail-calendar">
													<label class="crm-activity-popup-info-person-detail-calendar-name"><?=GetMessage('CRM_ACTIVITY_PLANNER_DIRECTIONS')?>:</label>
													<select name="direction" class="crm-activity-popup-info-person-detail-calendar-input" data-role="field-direction">
														<?foreach ($directions as $dir => $label):?>
															<option value="<?=htmlspecialcharsbx($dir)?>" <?if($activity['DIRECTION'] == $dir):?>selected<?endif;?>><?=htmlspecialcharsbx($label)?></option>
														<?endforeach;?>
													</select>
													</div><?
												}
												break;
											case 'FILE': ?>
												<div class="crm-activity-popup-info-person-detail-file">
													<div class="crm-activity-popup-info-person-detail-file-name" data-role="storage-switcher" data-storage-type="<?=(int)$activity['STORAGE_TYPE_ID']?>" data-values="<?=$storageValues?>" data-props="<?=$storageProps?>"><?=GetMessage('CRM_ACTIVITY_PLANNER_FILES')?>:</div>
													<div data-role="storage-container"></div>
												</div> <?
												break;
											case 'DEAL':
												if ($activity['OWNER_TYPE_ID'] !== CCrmOwnerType::Order)
												{
													?>
													<div class="crm-activity-popup-info-person-detail-deal-container">
														<span class="crm-activity-popup-info-person-text"><?=GetMessage('CRM_ACTIVITY_PLANNER_DEAL')?>:</span>
														<div class="crm-activity-popup-info-person-detail-deal" data-role="deal-container"></div><!--crm-activity-popup-info-person-detail-deal-->
													</div>
													<?
												}
												break;
											case 'ORDER':
												if ($activity['OWNER_TYPE_ID'] == CCrmOwnerType::Order)
												{
													?>
													<div class="crm-activity-popup-info-person-detail-deal-container">
														<span class="crm-activity-popup-info-person-text"><?=GetMessage('CRM_ACTIVITY_PLANNER_ORDER')?>:</span>
														<div class="crm-activity-popup-info-person-detail-deal" data-role="order-container"></div><!--crm-activity-popup-info-person-detail-deal-->
													</div>
													<?
												}
												break;
											case 'RESPONSIBLE': ?>
												<div class="crm-activity-popup-info-person-detail-responsible">
												<label class="crm-activity-popup-info-person-detail-responsible-name"><?=GetMessage('CRM_ACTIVITY_PLANNER_RESPONSIBLE_USER')?>:</label>
												<div class="crm-activity-popup-info-person-detail-responsible-person-container" data-role="responsible-container" style="margin-bottom: 20px"></div><!--crm-activity-popup-info-person-detail-responsible-->
												</div><?
												break;
											default:
												if (isset($field['HTML']))
													echo $field['HTML'];
										}
									endforeach; ?>
								</div><!--crm-activity-popup-info-person-detail-container-->
							</div><!--crm-activity-popup-info-person-container-->
						</div><!--crm-activity-popup-info-->
					</div><!--crm-activity-popup-container-->

				<div class="crm-activity-planner-section-control-active" style="height: 35px; ">
					<div class="crm-activity-planner-section crm-activity-planner-section-control">
						<button class="webform-small-button webform-small-button-accept" data-role="button-save">
							<span class="webform-small-button-text"><?=GetMessage('CRM_ACTIVITY_PLANNER_SAVE')?></span>
						</button>
						<a href="#" class="webform-button-link" data-role="button-cancel"><?=GetMessage('CRM_ACTIVITY_PLANNER_CANCEL')?></a>
						<div class="crm-activity-planner-section-control-error-block" data-role="error-block" style="height: 0; opacity: 0;">
							<div class="crm-activity-planner-section-control-error-text" data-role="error-block-text"></div>
						</div>
					</div>
				</div>

			</div><!--crm-popup-wrapper-->
		</form>

		<!-- Js templates -->
		<div style="display: none" hidden>
			<div class="crm-activity-inner-remind-popup-container" data-role="template-notify">
				<span class="crm-activity-inner-remind-popup-text"><?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_MENU_TITLE')?>:</span>
				<div class="crm-activity-inner-remind-popup-block">
					<input type="text" value="15" class="crm-activity-inner-remind-popup-input-number" data-role="notify-value">
					<select name="" class="crm-activity-inner-remind-popup-input-select" data-role="notify-value-type">
						<option value="<?=CCrmActivityNotifyType::Min?>"><?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_TYPE_M')?></option>
						<option value="<?=CCrmActivityNotifyType::Hour?>"><?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_TYPE_H')?></option>
						<option value="<?=CCrmActivityNotifyType::Day?>"><?=GetMessage('CRM_ACTIVITY_PLANNER_NOTIFY_TYPE_D')?></option>
					</select>
				</div><!--crm-activity-inner-remind-popup-block-->
				<div class="crm-activity-inner-remind-popup-button-container">
					<a class="webform-small-button webform-small-button-blue crm-activity-inner-remind-popup-button" data-role="notify-menu-save"><?=GetMessage('CRM_ACTIVITY_PLANNER_SELECT')?></a>
				</div>
			</div><!--crm activity-inner-remind-popup-container-->

			<div class="feed-add-post-destination-wrap" data-role="template-destination-container">
				<span data-role="destination-items"></span>
				<span class="feed-add-destination-input-box" data-role="destination-input-box">
					<input type="text" value="" class="feed-add-destination-inp" data-role="destination-input">
				</span>
				<a href="#" class="feed-add-destination-link" data-role="destination-tag"></a>
			</div>

			<span class="feed-event-destination" data-role="template-destination-item" data-class-prefix="feed-event-destination-">
				<span class="feed-event-destination-text" data-role="text"></span>
				<span class="feed-event-del-but" data-role="delete" data-hover-class="feed-event-destination-hover"></span>
				<input type="hidden" data-role="value">
			</span>
		</div>
		<?endif;?>
	</div><!--crm-task-list-container-->
</div><!--crm-task-list-wrapper-->

</body></html>