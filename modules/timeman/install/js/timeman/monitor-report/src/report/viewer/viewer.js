import {Loc, Tag, Type} from "main.core";
import {BitrixVue} from "ui.vue";
import {Timeline} from "../../timeline/timeline";
import {Group} from "../../group/group";
import {EntityGroup} from "timeman.const";
import {DateFormatter} from "timeman.dateformatter";

import "./viewer.css";

class Viewer
{
	open(event)
	{
		this.report = null;

		let data = event.currentTarget.dataset;

		let userId = data.user;
		let dateLog = data.date;

		BX.SidePanel.Instance.open("timeman:pwt-report-viewer", {
			contentCallback: () => this.getAppPlaceholder(),
			animationDuration: 200,
			width: 750,
			closeByEsc: true,
			cacheable: false,
			title: Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_SLIDER_TITLE'),
			label: {
				text: Loc.getMessage('TIMEMAN_PWT_REPORT_VIEWER_SLIDER_LABEL'),
			},
			contentClassName: 'pwt-report-viewer-side-panel-content-container',
			events: {
				onLoad: () => {
					this.loadReport(userId, dateLog)
						.then(response => {
							if (response.status === 'success')
							{
								this.report = response.data;

								if (!DateFormatter.isInit())
								{
									DateFormatter.init(this.report.info.date.format);
								}

								this.createApp(this.report);
							}
						})
						.catch(response => {
							if(response.errors)
							{
								response.errors.forEach(error => {
									console.error(error.message);
								});
							}
						});
				},
			}
		});
	}

	loadReport(userId, dateLog)
	{
		return BX.ajax.runAction('bitrix:timeman.api.monitor.getdayreport', {
			data: {
				userId,
				dateLog
			}
		});
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

	createApp(report)
	{
		let reports = {};

		let dateLog = report.info.date.value;
		reports[dateLog] = report;

		BitrixVue.createApp({
			components:
			{
				Timeline,
				Group,
			},
			data: function () {
				return {
					dateLog,
					reports,
				}
			},
			computed:
			{
				Type: () => Type,
				EntityGroup: () => EntityGroup,
				DateFormatter: () => DateFormatter,
				report()
				{
					return this.reports[this.dateLog];
				},
				date()
				{
					return this.report.info.date.value;
				},
				userId()
				{
					return this.report.info.user.id;
				},
				userName()
				{
					return this.report.info.user.fullName;
				},
				userIcon()
				{
					return this.report.info.user.icon;
				},
				userLink()
				{
					return this.report.info.user.link;
				},
				reportComment()
				{
					if (!Type.isArrayFilled(this.report.info.reportComment))
					{
						return null;
					}

					return this.report.info.reportComment[0].TEXT;
				},
				chartData()
				{
					if (this.report.timeline === null)
					{
						return [];
					}

					return this.report.timeline.data.map(interval => {
						interval.start = new Date(interval.start);
						interval.finish = new Date(interval.finish);

						return interval;
					});
				},
				workingEntities()
				{
					if (this.report.report === null)
					{
						return [];
					}

					return this.report.report.data;
				},
				workingTime()
				{
					if (this.report.report === null)
					{
						return [];
					}

					return this.report.report.data.reduce((sum, entity) => sum + entity.time, 0);
				},
				canShowRightEar()
				{
					return !(this.dateLog === DateFormatter.toString(new Date()))
				}
			},
			methods:
			{
				getPreviousReport()
				{
					let dateLog = new Date(this.date);
					dateLog.setDate(dateLog.getDate() - 1);

					this.getReport(DateFormatter.toString(dateLog));
				},
				getNextReport()
				{
					let dateLog = new Date(this.date);
					dateLog.setDate(dateLog.getDate() + 1);

					this.getReport(DateFormatter.toString(dateLog));
				},
				getReport(dateLog)
				{
					if (this.reports[dateLog])
					{
						this.dateLog = dateLog;
					}
					else
					{
						this.loadReport(this.userId, dateLog)
							.then(response => {
								if (response.status === 'success')
								{
									let dateLog = response.data.info.date.value;

									this.reports[dateLog] = response.data;

									this.dateLog = dateLog;
								}
							})
							.catch(response => {
								if(response.errors)
								{
									response.errors.forEach(error => {
										console.error(error.message);
									});
								}
							});
					}
				},
				loadReport(userId, dateLog)
				{
					return BX.ajax.runAction('bitrix:timeman.api.monitor.getdayreport', {
						data: {
							userId,
							dateLog
						}
					});
				},
			},
			// language=Vue
			template: `
				<div id="pwt-report-container-viewer" class="pwt-report-container pwt-report-container-viewer">
					<div class="pwt-report pwt-report-viewer">
						<div class="pwt-report-content">
							<div class="pwt-report-content-header" style="margin-bottom: 0">
								<div class="ui-icon ui-icon-common-user pwt-report-content-header-user-icon">
									<i v-if="userIcon" :style="{backgroundImage: 'url(' + encodeURI(userIcon) + ')'}"></i>
									<i v-else-if="!userIcon"></i>
								</div>
								<a class="pwt-report-content-header-title" :href="userLink">
									{{ userName }}
								</a>
							</div>
						</div>
						<div class="pwt-report-content-container">
							<div class="pwt-report-content">
								<div class="pwt-report-content-header">
									<div class="pwt-report-content-header-title">
										{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_WORKDAY') }}, 
										{{ DateFormatter.toLong(date) }}
									</div>
								</div>
								<div class="bx-timeman-component-monitor-timeline">
									<bx-timeman-component-timeline
										v-if="Type.isArrayFilled(chartData)"
										:chart="chartData"
										:fixedSizeType="EntityGroup.inactive.value"
										:readOnly="true"
									/>
								</div>
							</div>
							<div class="pwt-report-content">
								<div class="pwt-report-content-viewer-single-group">
									<Group
										:group="EntityGroup.working.value"
										:items="workingEntities"
										:time="workingTime"
										:reportComment="reportComment"
										:readOnly="true"
									/>
								</div>
							</div>
						</div>
					</div>
					<div class="pwt-report-viewer-ears">
						<div
                            @click="getPreviousReport"
							class="
								pwt-report-viewer-ear 
								pwt-report-viewer-ear-left 
								pwt-report-viewer-ear-show
							"
						/>
						<div 
							v-if="canShowRightEar"
							@click="getNextReport"
							class="
								pwt-report-viewer-ear 
								pwt-report-viewer-ear-right 
								pwt-report-viewer-ear-show
							"
						/>
					</div>
				</div>
			`,
		}).mount('#pwt');
	}
}

const viewer = new Viewer();

export {viewer as Viewer};