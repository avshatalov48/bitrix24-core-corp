import {BitrixVue} from "ui.vue";
import {Windows} from "./windows/windows";
import {Mac} from "./mac/mac";

import "./consent.css";

export const Consent = BitrixVue.localComponent('bx-timeman-monitor-report-consent', {
	components:
	{
		Windows,
		Mac,
	},
	computed:
	{
		isWindows()
		{
			return navigator.userAgent.toLowerCase().includes('windows') || (!this.isMac() && !this.isLinux());
		},
		isMac()
		{
			return navigator.userAgent.toLowerCase().includes('macintosh');
		},
	},
	// language=Vue
	template: `
		<div class="bx-timeman-monitor-report-consent">
			<div class="pwt-report-header-container">
				<div class="pwt-report-header-title">
					{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE') }}
				</div>
			</div>
			<div class="pwt-report-content-container">
				<div class="pwt-report-content">
                  	<div class="">
					<div class="bx-timeman-monitor-report-consent-logo-container">
						<svg class="bx-timeman-monitor-report-consent-logo"/>
					</div>
					<div class="">
						{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PRODUCT_DESCRIPTION') }}
					</div>
					<Windows v-if="isWindows"/>
					<Mac v-else-if="isMac"/>
				</div>
			</div>
			<div class="pwt-report-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width" style="z-index: 0">
				<div class="pwt-report-button-panel">
					<button
						class="ui-btn ui-btn-success"
						style="margin-left: 16px;"
					>
						{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE') }}
					</button>
				</div>
			</div>
      </div>
	`
});