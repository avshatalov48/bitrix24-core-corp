import {Type} from 'main.core';
import {Loc, Runtime, Tag} from 'main.core';
import {BitrixVue} from 'ui.vue';
import {EntityGroup} from 'timeman.const';
import {Control} from "./control/control";
import {AddIntervalPopup} from "./popup/addintervalpopup/addintervalpopup"
import {SelectIntervalPopup} from "./popup/selectintervalpopup/selectIntervalpopup"
import {Group} from "./group/group";
import {Consent} from "./consent/consent";
import {Timeline} from "./timeline/timeline";
import {MonitorModel} from "../model/monitor";
import {DateFormatter} from "timeman.dateformatter";
import {Loader} from 'main.loader';

import './report.css';

class Report
{
	loadComponents()
	{
		return Runtime.loadExtension([
			'ui.pinner',
			'ui.alerts',
			'timeman.component.day-control',
		]);
	}

	open(store)
	{
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
				onLoad: () => this.createEditor(store),
				onClose: () =>
				{
					if (Type.isFunction(BXIM.desktop.setPreventEsc))
					{
						BXIM.desktop.setPreventEsc(false);
					}
				},
				onDestroy: () =>
				{
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
		this.loadComponents().then(() => this.createEditorApp(store));
	}

	openPreview(store)
	{
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
				onLoad: () => this.createPreview(store),
			}
		});
	}

	createPreview(store)
	{
		this.loadComponents().then(() => this.createPreviewApp(store));
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
			},
			store,
			data: function() {
				return {
					newInterval: null,
					showSelectInternalPopup: false,
				}
			},
			computed:
			{
				EntityGroup: () => EntityGroup,
				dateLog()
				{
					const sentQueue = this.$store.state.monitor.sentQueue;
					const sentQueueDateLog = Type.isArrayFilled(sentQueue) ? sentQueue[0].dateLog : '';

					const history = this.$store.state.monitor.history;
					const historyDateLog = Type.isArrayFilled(history) ? history[0].dateLog : '';

					if (Type.isStringFilled(sentQueueDateLog))
					{
						return DateFormatter.toLong(sentQueueDateLog);
					}
					else if (Type.isStringFilled(historyDateLog))
					{
						return DateFormatter.toLong(historyDateLog);
					}

					return DateFormatter.toLong(new Date());
				},
				isAllowedToStartDay()
				{
					let currentDateLog = new Date(MonitorModel.prototype.getDateLog());
					let reportDateLog = new Date(this.$store.state.monitor.reportState.dateLog);
					let isHistorySent = BX.Timeman.Monitor.isHistorySent;

					if (currentDateLog > reportDateLog && !isHistorySent)
					{
						return false;
					}

					return true;
				},
				isPermissionGranted()
				{
					return true;
				}
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
			},
			// language=Vue
			template: `
				<div class="pwt-report">
					<Consent v-if="!isPermissionGranted"/>
					<template v-else>
						<div class="pwt-report-header-container">
							<div class="pwt-report-header-title">
								{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE') }}
							</div>
						</div>
						<div
							v-if="!isAllowedToStartDay"
							class="pwt-report-alert ui-alert ui-alert-md ui-alert-danger ui-alert-icon-danger">
							<span class="ui-alert-message">
								{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_ALERT_NOT_SENT') }}
							</span>
						</div>
						<div class="pwt-report-content-container">
							<div class="pwt-report-content">
								<div class="pwt-report-content-header">
									<div class="pwt-report-content-header-title">
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, {{ dateLog }}
									</div>
								</div>
								<Timeline
									@intervalClick="onIntervalClick"
								/>
							</div>
							<div class="pwt-report-content">
								<div class="pwt-report-content-groups">
									<Group 
										:group="EntityGroup.working.value"
										@selectIntervalClick="onSelectIntervalClick"
									/>
									<Group 
										:group="EntityGroup.personal.value"
									/>
								</div>
							</div>
						</div>
						<div 
							class="
								pwt-report-button-panel-wrapper 
								ui-pinner 
								ui-pinner-bottom 
								ui-pinner-full-width" 
							style="z-index: 0"
						>
							<div class="pwt-report-button-panel">
								<bx-timeman-component-day-control
									v-if="isAllowedToStartDay"
									:isButtonCloseHidden="true"
								/>
								<button 
									class="ui-btn ui-btn-success" 
									style="margin-left: 16px;"
									onclick="BX.Timeman.Monitor.openReportPreview()"
								>
									{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_PREVIEW_BUTTON') }}
								</button>
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
				Control,
			},
			store,
			computed:
			{
				EntityGroup: () => EntityGroup,
				dateLog()
				{
					const sentQueue = this.$store.state.monitor.sentQueue;
					const sentQueueDateLog = Type.isArrayFilled(sentQueue) ? sentQueue[0].dateLog : '';

					const history = this.$store.state.monitor.history;
					const historyDateLog = Type.isArrayFilled(history) ? history[0].dateLog : '';

					if (Type.isStringFilled(sentQueueDateLog))
					{
						return DateFormatter.toLong(sentQueueDateLog);
					}
					else if (Type.isStringFilled(historyDateLog))
					{
						return DateFormatter.toLong(historyDateLog);
					}

					return DateFormatter.toLong(new Date());
				}
			},
			// language=Vue
			template: `
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
									:readOnly="true"
								/>
							</div>
						</div>
					</div>
					<div class="pwt-report-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width" style="z-index: 0">
						<div class="pwt-report-button-panel">
							<Control/>
						</div>
					</div>
					<div id="bx-timeman-pwt-popup-preview" class="bx-timeman-pwt-popup"/>
				</div>
			`,
		}).mount('#pwt');
	}
}

const report = new Report();

export {report as Report};