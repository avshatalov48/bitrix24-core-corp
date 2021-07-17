import {Loc} from 'main.core';
import {Manager} from "salescenter.manager";

export default {
	data()
	{
		return {
			isVisible: true,
		};
	},
	methods: {
		hide()
		{
			this.isVisible = false;
			this.$emit('on-hide');
		},
		openControlPanel()
		{
			Manager.openControlPanel();
		},
	},
	template: `
		<div v-if="isVisible" class="salescenter-app-banner" >
			<div class="salescenter-app-banner-inner">
				<div class="salescenter-app-banner-title">
					${Loc.getMessage('SALESCENTER_BANNER_TITLE')}
				</div>
				<div class="salescenter-app-banner-content">
					<div class="salescenter-app-banner-text">
						${Loc.getMessage('SALESCENTER_BANNER_TEXT')}
					</div>
					<div class="salescenter-app-banner-btn-block">
						<button
							@click="openControlPanel"
							class="ui-btn ui-btn-sm ui-btn-primary salescenter-app-banner-btn-connect"
						>
							${Loc.getMessage('SALESCENTER_BANNER_BTN_CONFIGURE')}
						</button>
						<button
							@click="hide"
							class="ui-btn ui-btn-sm ui-btn-link salescenter-app-banner-btn-hide"
						>
							${Loc.getMessage('SALESCENTER_BANNER_BTN_HIDE')}
						</button>
					</div>
				</div>
				<div
					@click="hide"
					class="salescenter-app-banner-close"
				>
				</div>
			</div>
		</div>
	`,
}
