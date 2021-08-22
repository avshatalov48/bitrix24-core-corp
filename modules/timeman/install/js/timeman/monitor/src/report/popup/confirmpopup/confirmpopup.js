import {BitrixVue} from "ui.vue";

import "./confirmpopup.css";

export const ConfirmPopup = BitrixVue.localComponent('bx-timeman-monitor-report-popup-confirm', {
	props: {
		popupInstance: Object,
		title: String,
		text: String,
		buttonOkTitle: String,
		buttonCancelTitle: String,
	},
	mounted()
	{
		this.popupInstance.show();
	},
	beforeDestroy()
	{
		this.close();
	},
	methods: {
		ok()
		{
			this.$emit('okClick');
			this.close();
		},
		close()
		{
			this.$emit('cancelClick');
			this.popupInstance.destroy();
		}
	},
	//language=Vue
	template: `
		<div class="bx-timeman-monitor-report-popup-confirm">
			<div class="popup-window popup-window-with-titlebar ui-message-box ui-message-box-medium-buttons popup-window-fixed-width popup-window-fixed-height" style="padding: 0">
				<div class="bx-timeman-monitor-popup-title popup-window-titlebar">
					<span class="bx-timeman-monitor-popup--titlebar-text popup-window-titlebar-text">
						{{ title }}
					</span>
				</div>
				<div class="popup-window-content" style="overflow: auto; background: transparent;">
					{{ text }}
				</div>
				<div class="popup-window-buttons">
					<button @click="ok" class="ui-btn ui-btn-success">
						<span class="ui-btn-text">
							{{ buttonOkTitle }}
						</span>
					</button>
					<button @click="close" class="ui-btn ui-btn-light">
						<span class="ui-btn-text">
							{{ buttonCancelTitle }}
						</span>
					</button>
				</div>
			</div>
		</div>
	`
});