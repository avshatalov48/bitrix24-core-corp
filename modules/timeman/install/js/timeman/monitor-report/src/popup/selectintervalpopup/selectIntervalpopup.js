import {BitrixVue} from "ui.vue";
import {Interval} from "./interval/interval";
import {EntityGroup} from "timeman.const";

import 'ui.design-tokens';
import "ui.icons";
import "ui.alerts";
import "../popup.css";

export const SelectIntervalPopup = BitrixVue.localComponent('bx-timeman-monitor-report-popup-selectintervalpopup', {
	components:
	{
		Interval,
	},
	computed:
	{
		inactiveIntervals()
		{
			return this.$store.getters['monitor/getChartData']
				.filter(interval =>
					interval.type === EntityGroup.inactive.value
					&& interval.start < new Date()
				);
		},
	},
	methods:
	{
		selectIntervalPopupCloseClick()
		{
			this.$emit('selectIntervalPopupCloseClick');
		},
		onIntervalSelected(event)
		{
			this.$emit('intervalSelected', event)
		}
	},
	// language=Vue
	template: `
		<div class="bx-timeman-monitor-report-popup-selectintervalpopup">
			<div class="bx-timeman-monitor-report-popup-wrap">
				<div 
					class="
						bx-timeman-monitor-report-popup
						popup-window 
						popup-window-with-titlebar 
						ui-message-box 
						ui-message-box-medium-buttons 
						popup-window-fixed-width 
						popup-window-fixed-height
					" 
					style="padding: 0"
				>
					<div class="popup-window-titlebar">
						<span class="popup-window-titlebar-text">
							{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_INTERVAL_SELECT_INTERVAL') }}
						</span>
					</div>
					<div
						class="
							popup-window-content
							bx-timeman-monitor-popup-window-content
						"
						style="
							overflow: auto; 
							background: transparent;
							width: 440px;
						"
					>
						<div class="bx-timeman-monitor-report-popup-items-container">
							<Interval
								v-for="interval of inactiveIntervals"
								:key="interval.start.toString()"
								:start="interval.start"
								:finish="interval.finish"
								@intervalSelected="onIntervalSelected"
							/>
						</div>
					</div>
					<div class="popup-window-buttons">
						<button 
							@click="selectIntervalPopupCloseClick" 
							class="ui-btn ui-btn-md ui-btn-light"
						>
							<span class="ui-btn-text">
								{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CANCEL_BUTTON') }}
							</span>
						</button>
					</div>
				</div>
			</div>
		</div>
	`
});