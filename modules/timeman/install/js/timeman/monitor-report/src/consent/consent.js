import {BitrixVue} from "ui.vue";

import "./consent.css";

export const Consent = BitrixVue.localComponent('bx-timeman-monitor-report-consent', {
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
	methods:
	{
		grantPermissionMac()
		{
			//If no native permission window has appeared before, this method will cause it to appear
			BXDesktopSystem.ListScreenMedia(() => {});

			this.grantPermission();
		},
		grantPermissionWindows()
		{
			this.grantPermission();
		},
		grantPermission()
		{
			this.$store.dispatch('monitor/grantPermission').then(() => {
				BX.Timeman.Monitor.launch();
			});
		},
		openPermissionHelp()
		{
			this.openHelpdesk('13857358');
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
		<div class="bx-timeman-monitor-report-consent">
			<div class="pwt-report-header-container">
				<div class="pwt-report-header-title">
					{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_SLIDER_TITLE') }}
				</div>
			</div>
			<div class="pwt-report-content-container pwt-report-consent-content-container">
				<div class="pwt-report-content pwt-report-consent-content">
					<div class="bx-timeman-monitor-report-consent-logo-container">
						<svg class="bx-timeman-monitor-report-consent-logo"/>
					</div>
					<div class="bx-timeman-monitor-report-consent-description" v-html="$Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PRODUCT_DESCRIPTION')"/>
					<div v-if="isMac" class="bx-timeman-monitor-report-consent-mac">
						{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE_DESCRIPTION_MAC') + ' ' }}
						<span @click="openPermissionHelp" class="bx-timeman-monitor-report-consent-link">
							{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE_DESCRIPTION_MAC_DETAIL') }}
						</span>
					</div>
				</div>
				<div class="pwt-report-button-panel-wrapper ui-pinner ui-pinner-bottom ui-pinner-full-width" style="z-index: 0">
					<div class="pwt-report-button-panel">
						<button
							v-if="isMac"
							@click="grantPermissionMac"
							class="ui-btn ui-btn-success"
							style="margin-left: 16px;"
						>
							{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE') }}
						</button>
						<button
							v-else-if="isWindows"
							@click="grantPermissionWindows"
							class="ui-btn ui-btn-success"
							style="margin-left: 16px;"
						>
							{{ $Bitrix.Loc.getMessage('TIMEMAN_PWT_REPORT_CONSENT_PROVIDE') }}
						</button>
					</div>
				</div>
			</div>
		</div>
	`
});