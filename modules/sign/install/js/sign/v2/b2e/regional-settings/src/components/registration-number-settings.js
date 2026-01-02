import { Tag, Text } from 'main.core';
import { Popup } from 'main.popup';
import { Switcher } from 'ui.vue3.components.switcher';
import { SwitcherSize } from 'ui.switcher';
import { LocMixin, Notice, SignSelector } from 'sign.v2.b2e.vue-util';
import { type ExternalIdSettings } from '../types';

// @vue/component
export const RegistrationNumberSettings = {
	name: 'RegistrationNumberSettings',
	components: {
		Switcher,
		SignSelector,
		Notice,
	},
	mixins: [LocMixin],
	props: {
		hcmLinkTypeList: {
			type: Array,
			required: false,
			default: () => [],
		},
		templateMode: {
			type: Boolean,
			required: false,
			default: false,
		},
		settings: {
			/** @type ExternalIdSettings */
			type: Object,
			required: true,
		},
		isIntegrationEnabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		isIntegrationDisabledByProvider: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	emits: ['onChange'],
	data(): Object
	{
		return {
			isValid: true,
			switcherSize: SwitcherSize.extraSmall,
		};
	},
	computed: {
		dropdownItems(): Array<{ id: string, title: string, }>
		{
			return this.hcmLinkTypeList.map(({ id, title }) => ({
				id,
				title,
			}));
		},
		isChecked(): boolean
		{
			return this.settings.sourceType === 'hcmlink' && this.isIntegrationEnabled;
		},
	},
	watch: {
		isChecked(newValue: boolean)
		{
			this.toggleHcmLink(newValue);
		},
	},
	methods: {
		toggleHcmLink(value: boolean): void
		{
			this.$emit('onChange', this.settings);
			if (value === true)
			{
				if (this.hcmLinkTypeList.length <= 0 || !this.isIntegrationEnabled)
				{
					return;
				}

				this.changeSettings({
					...this.settings,
					sourceType: 'hcmlink',
				});

				return;
			}

			this.changeSettings({
				...this.settings,
				sourceType: 'manual',
			});
		},
		selectHcmLinkSetting(id: number): void
		{
			this.changeSettings({
				...this.settings,
				hcmLinkSettingId: id,
			});
		},
		changeManualValue(value: string): void
		{
			this.changeSettings({
				...this.settings,
				value,
			});
		},
		changeSettings(settings: ExternalIdSettings): void
		{
			this.$emit('onChange', settings);
		},
		validate(): boolean
		{
			this.isValid = !(this.isChecked === false && !this.settings.value);

			return this.isValid;
		},
		showHideTooltipIfDisabled(event): void
		{
			this.hintDisabled?.close();
			this.hintDisabled?.destroy();

			const commonHintSettings = {
				bindElement: event?.target,
				darkMode: true,
				autoHide: true,
				cacheable: false,
			};
			if (this.isIntegrationDisabledByProvider)
			{
				this.hintDisabled = new Popup({
					content: Tag.render`
						<span class='ui-hint-content'>
							${Text.encode(this.loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_EXTERNAL_ID_DISABLED_HINT'))}
						</span>
					`,
					...commonHintSettings,
				});
			}
			else if (this.isIntegrationEnabled && this.hcmLinkTypeList.length === 0)
			{
				this.hintDisabled = new Popup({
					content: Tag.render`
						<span class='ui-hint-content'>
							${Text.encode(this.loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_EXTERNAL_ID_NO_SETTINGS'))}
						</span>
					`,
					...commonHintSettings,
				});
			}

			this.hintDisabled?.show();
		},
		created(): void
		{
			this.hintDisabled = null;
		},
	},

	template: `
		<div class="sign-b2e-regional-settings__item">
			<p class="sign-b2e-regional-settings__item-text">
				<Switcher style="height: 13px"
					:isChecked="isChecked"
					:options="{ size: switcherSize }"
					@check="toggleHcmLink(true)"
					@uncheck="toggleHcmLink(false)"
					@click="showHideTooltipIfDisabled"
				/>
				<span style="margin-left: 8px">
				  {{ loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_EXTERNAL_ID_HCMLINK_SWITCHER_TEXT') }}
				</span>
		    </p>
			<SignSelector v-if="isChecked"
				:items="dropdownItems"
				:selectedId="settings.hcmLinkSettingId"
				@onSelect="selectHcmLinkSetting"
			/>
			<div v-else-if="!templateMode"
				class="ui-ctl-textbox ui-ctl-w100 ui-ctl-md"
			>
				<input
					:value="settings.value"
					:placeholder="loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_EXTERNAL_ID_INPUT_HINT')" 
					type="text" 
					class="ui-ctl-element sign-b2e-regional-settings-reg-number-input"
					:class="{'ui-element-invalid': !isValid}"
					maxlength="255"
					@input="changeManualValue($event.target.value)"
				>
			</div>
		  	<Notice style="margin-top: 8px">
			  {{ loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_EXTERNAL_ID_NOTICE') }}
			</Notice>
		</div>
	`,
};
