// @flow

import { ajax } from 'main.core';
import { SettingsContainer } from '../settings-container';
import { SmsSettingsSection } from './sms-settings-section';
import { mapGetters, mapMutations } from 'ui.vue3.vuex';

export const SmsSettings = {
	components: {
		SettingsContainer,
		SmsSettingsSection,
	},

	computed: {
		...mapGetters({
			isSettingsChanged: 'isChanged',
			isSaving: 'isSaving',
			getIsSmsCollapsed: 'getIsSmsCollapsed',
		}),
	},
	methods: {
		...mapMutations([
			'updateIsSmsCollapsed',
		]),

		onTitleClick()
		{
			this.updateIsSmsCollapsed(!this.getIsSmsCollapsed);
			ajax.runComponentAction(
				'bitrix:crm.config.terminal.settings',
				'updateSmsCollapsed',
				{
					data: {
						collapsed: this.getIsSmsCollapsed,
					},
				},
			);
		},
	},

	// language=Vue
	template: `
		<SettingsContainer
			:title="$Bitrix.Loc.getMessage('CRM_CFG_TERMINAL_SETTINGS_SUBTITLE_NOTIFICATION')"
			iconStyle="settings-section-icon-sms"
			:collapsed="getIsSmsCollapsed"
			v-on:titleClick="onTitleClick"
		>
			<SmsSettingsSection />
		</SettingsContainer>
	`,
};
