import { mapGetters } from 'ui.vue3.vuex';

export const ButtonsPanel = {
	methods: {
		save()
		{
			this.$Bitrix.eventEmitter.emit('crm:terminal:onSettingsSave');
		},
		cancel()
		{
			this.$Bitrix.eventEmitter.emit('crm:terminal:onSettingsCancel');
		},
	},

	computed: {
		...mapGetters({
			isSettingsChanged: 'isChanged',
			isSaving: 'isSaving',
		}),

		buttonsPanelClass()
		{
			return {
				'ui-button-panel-wrapper': true,
				'ui-pinner': true,
				'ui-pinner-bottom': true,
				'ui-pinner-full-width': true,
				'ui-button-panel-wrapper-hide': !this.isSettingsChanged,
			};
		},
		saveButtonClasses()
		{
			return {
				'ui-btn': true,
				'ui-btn-success': true,
				'ui-btn-wait': this.isSaving,
			};
		},
	},

	template: `
		<div :class="buttonsPanelClass">
			<div class="ui-button-panel ui-button-panel-align-center">
				<button
					@click="save"
					:class="saveButtonClasses"
				>
					{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SAVE_BTN') }}
				</button>
				<a
					@click="cancel"
					class="ui-btn ui-btn-link"
				>
					{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_CANCEL_BTN') }}
				</a>
			</div>
		</div>
	`,
};
