//@flow

import {SmsSettingsSection} from "./sms-settings-section";
import {mapGetters} from "ui.vue3.vuex";

export const TerminalSettings = {
	components: {
		'SmsSettingsSection': SmsSettingsSection,
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

	methods: {
		save()
		{
			this.$emit('onSave');
		},
		cancel()
		{
			this.$emit('onCancel');
		},
	},

	// language=Vue
	template: `
		<div class="settings-container">
			<!-- Settings title -->
			<div class="ui-slider-heading-4" style="margin-bottom: 40px">
				{{ $Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SUBTITLE_NOTIFICATION') }}
			</div>
			
			<!-- Settings content -->
			<div class="settings-section-list">
				<SmsSettingsSection />
			</div>
			
			
			<!-- Save panel -->
			<div
				:class="buttonsPanelClass"
			>
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
		</div>
	`
};
