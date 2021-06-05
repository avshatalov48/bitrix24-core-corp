import {BitrixVue} from "ui.vue";

export const Windows = BitrixVue.localComponent('bx-timeman-monitor-report-consent-windows', {
	// language=Vue
	template: `
		<div class="bx-timeman-monitor-report-consent-windows">
			<div class="ui-form bx-timeman-monitor-report-consent-form">
				<div class="ui-form-row">
					<label class="ui-ctl ui-ctl-checkbox">
						<input type="checkbox" class="ui-ctl-element">
						<div class="ui-ctl-label-text">Windows</div>
					</label>
				</div>
			</div>
		</div>
	`
});