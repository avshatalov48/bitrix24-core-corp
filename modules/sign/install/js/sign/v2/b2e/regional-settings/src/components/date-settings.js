import { Tag, Text } from 'main.core';
import { Popup } from 'main.popup';
import { Switcher } from 'ui.vue3.components.switcher';
import { SwitcherSize } from 'ui.switcher';
import { DateSelector, LocMixin, SignSelector, RoundedSmallSelectedItemView } from 'sign.v2.b2e.vue-util';
import type { DateSettings as DateSettingsType } from '../types';
import { Hint } from 'ui.vue3.components.hint';

// @vue/component
export const DateSettings = {
	name: 'DateSettings',
	components: {
		DateSelector,
		SignSelector,
		Switcher,
		RoundedSmallSelectedItemView,
		Hint,
	},
	mixins: [LocMixin],
	props: {
		hcmLinkTypeList: {
			type: Array,
			required: true,
			default: () => [],
		},
		templateMode: {
			type: Boolean,
			required: false,
			default: false,
		},
		isIntegrationEnabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		settings: {
			/** @type DateSettingsType */
			type: Object,
			required: true,
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
		selectDate(date: Date): void
		{
			this.changeSettings({
				...this.settings,
				value: date,
			});
		},
		changeSettings(settings: DateSettingsType): void
		{
			this.$emit('onChange', settings);
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
							${Text.encode(this.loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_DATE_DISABLED_HINT'))}
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
							${Text.encode(this.loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_DATE_NO_SETTINGS'))}
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
		<div class="sign-b2e-regional-settings__item --row">
			<p class="sign-b2e-regional-settings__item-text">
				<Switcher style="height: 13px"
					:isChecked="isChecked"
					:options="{ size: switcherSize }"
					@check="toggleHcmLink(true)"
					@uncheck="toggleHcmLink(false)"
					 @click="showHideTooltipIfDisabled"
				/>
				<span style="margin-left: 8px">
					{{ loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_DATE_HCMLINK_SWITCHER_TEXT') }}
				</span>
			</p>
			<div class="sign-b2e-regional-settings_date_container">
				<SignSelector v-if="isChecked"
					:items="dropdownItems"
					:selectedId="settings.hcmLinkSettingId"
					@onSelect="selectHcmLinkSetting"
					v-slot="{ title }"
				>
					<RoundedSmallSelectedItemView
						:title="title"
					/>
				</SignSelector>
				<div v-else-if="!templateMode">
					<DateSelector
						:value="settings.value"
						@onSelect="selectDate"
					/>
				</div>
			</div>
			<Hint v-if="isChecked" class="sign-b2e-regional-settings__hint sign-b2e-regional-settings_date_hint" :text="loc('SIGN_V2_B2E_REGIONAL_SETTINGS_DOCUMENT_DATE_HCMLINK_SWITCHER_HINT')"/>
		</div>
	`,
};
