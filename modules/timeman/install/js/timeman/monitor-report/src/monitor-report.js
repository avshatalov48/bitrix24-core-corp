import {Type} from "main.core";
import {Loc, Tag} from "main.core";
import {BitrixVue} from "ui.vue";
import {Vuex} from "ui.vuex";
import {EntityGroup} from "timeman.const";
import {AddIntervalPopup} from "./popup/addintervalpopup/addintervalpopup"
import {SelectIntervalPopup} from "./popup/selectintervalpopup/selectIntervalpopup"
import {Group} from "./group/group";
import {Consent} from "./consent/consent";
import {Timeline} from "./timeline/timeline";
import {DateFormatter} from "timeman.dateformatter";
import {TimeFormatter} from "timeman.timeformatter";
import {Monitor} from "timeman.monitor";
import {MountingPortal} from 'ui.vue.portal';
import {PausePopup} from "./popup/pausepopup/pausepopup";
import {ConfirmPopup} from "./popup/confirmpopup/confirmpopup";
import {UI} from 'ui.notification';
import {Viewer} from "./report/viewer/viewer";
import {Time} from "./mixin/time";

import {PopupManager} from "main.popup";
import {Loader} from "main.loader";

import "ui.pinner";
import "ui.alerts";

import "ui.design-tokens";
import "./monitor-report.css";

class MonitorReport
{
	open(store)
	{
		if (this.isReportOpen)
		{
			return;
		}

		BX.SidePanel.Instance.open("timeman:pwt-report", {
			contentCallback: () => this.getAppPlaceholder(),
			animationDuration: 200,
			width: 960,
			closeByEsc: true,
			title: Loc.getMessage('TIMEMAN_PWT_REPORT_DAY'),
			events: {
				onOpen: () =>
				{
					if (Type.isFunction(BXIM.desktop.setPreventEsc))
					{
						BXIM.desktop.setPreventEsc(true);
					}
				},
				onOpenComplete: () => {
					this.isReportOpen = true;
				},
				onLoad: () => this.createEditor(store),
				onCloseComplete: () =>
				{
					this.isReportOpen = false;

					if (Monitor.shouldShowGrantingPermissionWindow())
					{
						Monitor.showGrantingPermissionLater();
					}

					if (Type.isFunction(BXIM.desktop.setPreventEsc))
					{
						BXIM.desktop.setPreventEsc(false);
					}
				},
			}
		});
	}

	createEditor(store)
	{
		this.createEditorApp(store);
	}

	openPreview(store)
	{
		if (this.isReportPreviewOpen)
		{
			return;
		}

		BX.SidePanel.Instance.open("timeman:pwt-report-preview", {
			contentCallback: () => this.getAppPlaceholder(),
			animationDuration: 200,
			width: 750,
			closeByEsc: true,
			title: Loc.getMessage('TIMEMAN_PWT_REPORT_DAY'),
			label: {
				text: Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_SLIDER_LABEL'),
			},
			events: {
				onOpenComplete: () => {
					this.isReportPreviewOpen = true;
				},
				onLoad: () => this.createPreview(store),
				onCloseComplete: () => {
					this.isReportPreviewOpen = false;
				},
			}
		});
	}

	createPreview(store)
	{
		this.createPreviewApp(store);
	}

	openViewer(event)
	{
		Viewer.open(event);
	}

	getAppPlaceholder()
	{
		return Tag.render`
					<div id="pwt">
						<div 
							class="main-ui-loader main-ui-show" 
							style="width: 110px; height: 110px;" 
							data-is-shown="true"
						>
							<svg class="main-ui-loader-svg" viewBox="25 25 50 50">
								<circle 
									class="main-ui-loader-svg-circle" 
									cx="50" 
									cy="50" 
									r="20" 
									fill="none" 
									stroke-miterlimit="10"
									/>
								</svg>
							</div>
						</div>
				`;
	}

	createEditorApp(store)
	{
		BitrixVue.createApp({
			components:
			{
				Timeline,
				Group,
				AddIntervalPopup,
				SelectIntervalPopup,
				Consent,
				MountingPortal,
				PausePopup,
				ConfirmPopup,
			},
			store,
			mixins: [Time],
			data: function() {
				return {
					newInterval: null,
					showSelectInternalPopup: false,
					popupInstance: null,
					popupId: null,
					showPlayAlert: false,
					selectedPrivateCode: null,
					selectIntervalTimeout: null,
				}
			},
			computed:
			{
				...Vuex.mapGetters('monitor',[
					'getWorkingEntities',
					'getPersonalEntities',
					'getReportComment',
					'hasActivityOtherThanBitrix24'
				]),
				EntityGroup: () => EntityGroup,
				TimeFormatter: () => TimeFormatter,
				dateLog()
				{
					return DateFormatter.toLong(new Date(this.$store.state.monitor.reportState.dateLog));
				},
				isHistorySent()
				{
					return !!this.$store.getters['monitor/isHistorySent'];
				},
				isPermissionGranted()
				{
					return this.$store.state.monitor.config.grantingPermissionDate !== null;
				},
				isPaused()
				{
					return !!this.$store.state.monitor.config.pausedUntil;
				},
				pausedUntil()
				{
					let pausedUntil = this.$store.state.monitor.config.pausedUntil;
					if (!pausedUntil)
					{
						return '';
					}

					if (pausedUntil.getDay() - new Date().getDay() !== 0)
					{
						return this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_TOMORROW');
					}

					return TimeFormatter.toShort(pausedUntil);
				},
				isWindows()
				{
					return navigator.userAgent.toLowerCase().includes('windows') || (!this.isMac && !this.isLinux);
				},
				isMac()
				{
					return navigator.userAgent.toLowerCase().includes('macintosh');
				},
				isLinux()
				{
					return navigator.userAgent.toLowerCase().includes('linux');
				},
				hasActivity()
				{
					if (this.isMac)
					{
						return this.hasActivityOtherThanBitrix24;
					}

					return true;
				},
				hasIntervalsToAdd()
				{
					return Type.isArrayFilled(this.$store.getters['monitor/getChartData']
						.filter(interval =>
							interval.type === EntityGroup.inactive.value
							&& interval.start < new Date()
						));
				},
			},
			methods:
			{
				onIntervalClick(event)
				{
					this.newInterval = event;
				},
				onAddIntervalPopupHide()
				{
					this.newInterval = null;
				},
				onAddIntervalPopupClose()
				{
					this.newInterval = null;
					this.showSelectInternalPopup = false;
				},
				onSelectIntervalClick()
				{
					this.showSelectInternalPopup = true;
				},
				onSelectIntervalPopupCloseClick()
				{
					this.showSelectInternalPopup = false;
				},
				pauseClick(event)
				{
					if (this.popupInstance != null)
					{
						this.popupInstance.destroy();
						this.popupInstance = null;
					}

					const popup = PopupManager.create({
						id: 'bx-timeman-pwt-editor-pause-popup',
						targetContainer: document.body,
						className: 'bx-timeman-pwt-pause-popup',
						bindElement: event.target,
						lightShadow : true,
						offsetTop: 0,
						offsetLeft: 10,
						autoHide: true,
						closeByEsc: true,
						angle: {},
						bindOptions: {position: 'top'},
						events: {
							onPopupClose: () => this.popupInstance.destroy(),
							onPopupDestroy: () => this.popupInstance = null
						},
					});

					this.popupIdSelector = `#bx-timeman-pwt-editor-pause-popup`;
					this.popupId = 'PausePopup';

					//little hack for correct open several popups in a row.
					this.$nextTick(() => {this.popupInstance = popup});
				},
				pause(dateTime)
				{
					Monitor.pauseUntil(dateTime);
				},
				play()
				{
					Monitor.play();

					this.showPlayAlert = true;
					setTimeout(() => this.showPlayAlert = false, 1000)
				},
				openReportPreview()
				{
					Monitor.openReportPreview();
				},
				selectInterval(privateCode)
				{
					this.selectIntervalTimeout = setTimeout(() => {
						this.selectedPrivateCode = privateCode;
					}, 500);
				},
				unselectInterval()
				{
					clearTimeout(this.selectIntervalTimeout);

					this.selectedPrivateCode = null;
				},
				openPermissionHelp()
				{
					this.openHelpdesk('13857358');
				},
				openSkipConfirm()
				{
					if (this.popupInstance != null)
					{
						this.popupInstance.destroy();
						this.popupInstance = null;
					}

					const popup = PopupManager.create({
						id: 'bx-timeman-pwt-skip-report-confirm-popup',
						targetContainer: BX('pwt-report-container-editor'),
						autoHide: false,
						closeByEsc: true,
						overlay: true,
						events: {
							onPopupDestroy: () =>
							{
								this.popupInstance = null;
							}
						},
					});

					this.popupIdSelector = `#bx-timeman-pwt-skip-report-confirm-popup`;
					this.popupId = 'SkipReportPopup';

					//little hack for correct open several popups in a row.
					this.$nextTick(() => {this.popupInstance = popup});
				},
				skipReport()
				{
					this.$store.dispatch('monitor/clearStorageBeforeDate', this.$store.state.monitor.reportState.dateLog)
						.then(() => {
							const notifyText = this.$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SKIPPED')
								.replace('#DATE#', this.dateLog);

							this.$store.dispatch('monitor/refreshDateLog').then(() => {
								UI.Notification.Center.notify({
									content: notifyText,
									autoHideDelay: 5000,
								});
							});
						});
				},
				openPwtHelp()
				{
					// if (this.isMac)
					// {
					// 	this.openHelpdesk('');
					// 	return;
					// }
					//
					// if (this.isWindows)
					// {
					// 	this.openHelpdesk('');
					// 	return;
					// }

					this.openPermissionHelp();
				},
				openHelpdesk(code)
				{
					if(top.BX.Helper)
					{
						top.BX.Helper.show('redirect=detail&code=' + code);
					}
				},
			},
			// language=Vue
			template: `
				<div id="pwt-report-container-editor" class="pwt-report-container">
					<div class="pwt-report">
						<Consent v-if="!isPermissionGranted"/>
						<template v-else>
							<div class="pwt-report-header-container">
								<div class="pwt-report-header-title">
									{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE') }}
								</div>
								<div class="pwt-report-header-buttons-container">
									<a
										class="ui-btn ui-btn-light-border"
										@click="openPwtHelp"
									>
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_HELP_BUTTON_TITLE') }}
									</a>
								</div>
							</div>

						 	<transition-group
								name="bx-timeman-pwt-report"
								tag="div"
								class="pwt-report-content-container"
							>
								<div
									:key="'reportNotSentAlert'"
									v-if="!isHistorySent"
									class="pwt-report-alert ui-alert ui-alert-md ui-alert-danger ui-alert-icon-danger"
								>
									<span class="ui-alert-message">
										{{ 
											$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ALERT_NOT_SENT')
												.replace('#DATE#', dateLog)
										}}
									</span>
								</div>
								<div 
									:key="'pauseAlert'"
									v-if="isPaused || showPlayAlert"
									:class="[
										'pwt-report-alert',
										'ui-alert',
										'ui-alert-md',
										{
											'ui-alert-warning ui-alert-icon-warning': isPaused, 
											'ui-alert-success ui-alert-icon-info' : showPlayAlert,
										}
									]"
								>
									<span v-if="isPaused" class="ui-alert-message">
										{{ 
											$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ALERT_PAUSE_UNTIL_TIME')
												.replace('#TIME#', pausedUntil)
										}}
									</span>
									<span v-if="showPlayAlert" class="ui-alert-message">
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ALERT_PLAY') }}
									</span>
									<button
										v-if="isPaused"
										@click="play"
										class="
											ui-btn 
											ui-btn-xs 
											ui-btn-success-dark
											ui-btn-round 
											ui-btn-icon-start
											bx-monitor-group-btn-right
											bx-monitor-alert-btn-right
										"
									>
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PLAY') }}
									</button>
								</div>

								<div
									:key="'activityAlert'"
									v-if="!hasActivity"
									class="pwt-report-alert ui-alert ui-alert-icon-info ui-alert-md"
								>
									<span class="ui-alert-message">
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_MAC_INACTIVE_ALERT') }}
										<a
											class="pwt-report-alert-link"
											@click="openPermissionHelp"
										>
											{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_MAC_HELP_DETAIL') }}
										</a>
									</span>
								</div>

								<div class="pwt-report-content" :key="'report-header'">
									<div class="pwt-report-content-header">
										<div class="pwt-report-content-header-title">
											{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, {{ dateLog }}
										</div>
									</div>
									<Timeline
										:selectedPrivateCode="selectedPrivateCode"
										@intervalClick="onIntervalClick"
									/>
								</div>
								<div class="pwt-report-content" :key="'report-content'">
									<div class="pwt-report-content-groups">
										<Group 
											:group="EntityGroup.working.value"
											:items="getWorkingEntities"
											:time="workingTime"
											:reportComment="getReportComment"
											:hasIntervalsToAdd="hasIntervalsToAdd"
											@selectIntervalClick="onSelectIntervalClick"
											@intervalSelected="selectInterval"
											@intervalUnselected="unselectInterval"
										/>
										<Group 
											:group="EntityGroup.personal.value"
											:items="getPersonalEntities"
											:time="personalTime"
											@intervalSelected="selectInterval"
											@intervalUnselected="unselectInterval"
										/>
									</div>
								</div>
							</transition-group>
	
							<div 
								class="
									pwt-report-button-panel-wrapper 
									ui-pinner 
									ui-pinner-bottom 
									ui-pinner-full-width" 
								style="z-index: 0"
							>
								<div class="pwt-report-button-panel">
									<button 
										class="ui-btn ui-btn-success ui-btn-icon-page"
										@click="openReportPreview"
									>
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_BUTTON') }}
									</button>
									<button
										id="timeman-pwt-button-pause"
										@click="pauseClick"
										class="
											ui-btn 
											ui-btn-light-border 
											ui-btn-dropdown 
											ui-btn-icon-pause
										"
									>
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PAUSE_BUTTON') }}
									</button>
									<button
										v-if="!isHistorySent"
										@click="openSkipConfirm"
										class="ui-btn ui-btn-danger ui-btn-icon-remove"
									>
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SKIP_BUTTON') }}
									</button>
									<mounting-portal 
										:mount-to="popupIdSelector" 
										append 
										v-if="popupInstance"
									>
										<PausePopup
											v-if="popupId === 'PausePopup'"
											:popupInstance="popupInstance" 
											@monitorPause="pause"
										/>
										<ConfirmPopup
                                            v-if="popupId === 'SkipReportPopup'"
											:popupInstance="popupInstance"
											:title="$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SKIP_POPUP_TITLE')"
											:text="$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SKIP_POPUP_TEXT')"
											:buttonOkTitle="$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SKIP_CONFIRM_BUTTON')"
											:buttonCancelTitle="$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON')"
											@okClick="skipReport"
										/>
									</mounting-portal>
								</div>
							</div>
	
							<div id="bx-timeman-pwt-popup-editor" class="bx-timeman-pwt-popup">
								<SelectIntervalPopup
									v-if="showSelectInternalPopup"
									@selectIntervalPopupCloseClick="onSelectIntervalPopupCloseClick"
									@intervalSelected="onIntervalClick"
								/>
								<AddIntervalPopup
									v-if="newInterval"
									:minStart="newInterval.start"
									:maxFinish="newInterval.finish"
									@addIntervalPopupClose="onAddIntervalPopupClose"
									@addIntervalPopupHide="onAddIntervalPopupHide"
								/>
							</div>
						</template>
					</div>
				</div>
			`,
		}).mount('#pwt');
	}

	createPreviewApp(store)
	{
		BitrixVue.createApp({
			components:
			{
				Timeline,
				Group,
				MountingPortal,
			},
			mixins: [Time],
			data: function() {
				return {
					popupIdSelector: false,
					popupInstance: null,
				}
			},
			store,
			computed:
			{
				...Vuex.mapGetters('monitor',[
					'getWorkingEntities',
					'getReportComment',
				]),
				EntityGroup: () => EntityGroup,
				dateLog()
				{
					return DateFormatter.toLong(new Date(this.$store.state.monitor.reportState.dateLog));
				},
			},
			methods:
			{
				sendReport()
				{
					Monitor.send();
				},
				close()
				{
					BX.SidePanel.Instance.close();
				},
			},
			// language=Vue
			template: `
				<div id="pwt-report-container-preview" class="pwt-report-container">
					<div class="pwt-report">
						<div class="pwt-report-content">
							<div class="pwt-report-content-header" style="margin-bottom: 0">
								<div class="pwt-report-content-header-title">
									{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_SLIDER_TITLE') }}
								</div>
							</div>
						</div>
						<div class="pwt-report-content-container">
							<div class="pwt-report-content">
								<div class="pwt-report-content-header">
									<div class="pwt-report-content-header-title">
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, {{ dateLog }}
									</div>
								</div>
								<Timeline
									:readOnly="true"
								/>
							</div>
							<div class="pwt-report-content">
								<div class="pwt-report-content-groups">
									<Group 
										:group="EntityGroup.working.value"
										:items="this.getWorkingEntities"
										:time="this.workingTime"
										:reportComment="this.getReportComment"
										:readOnly="true"
									/>
								</div>
							</div>
						</div>
						<div class="pwt-report-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width" style="z-index: 0">
							<div class="pwt-report-button-panel">
								<button
									@click="sendReport"
									class="ui-btn ui-btn-success ui-btn-icon-share"
								>
									{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SEND_BUTTON') }}
								</button>
								<button
									@click="close"
									class="ui-btn ui-btn-light-border"
								>
									{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}
								</button>
							</div>
						</div>
						<div id="bx-timeman-pwt-popup-preview" class="bx-timeman-pwt-popup"/>
					</div>
				</div>
			`,
		}).mount('#pwt');
	}
}

const monitorReport = new MonitorReport();

export {monitorReport as MonitorReport};