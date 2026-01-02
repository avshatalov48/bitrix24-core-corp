import { Switcher } from 'ui.vue3.components.switcher';
import { SwitcherSize } from 'ui.switcher';
import { LocMixin } from 'sign.v2.b2e.vue-util';
import { Hint } from 'ui.vue3.components.hint';

// @vue/component
export const SettingsSwitcher = {
	name: 'SettingsSwitcher',
	components: {
		Switcher,
		Hint,
	},
	mixins: [LocMixin],
	props: {
		modelValue: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['update:modelValue'],
	data(): Object
	{
		return {
			isApplySettingsForAll: false,
			switcherSize: SwitcherSize.extraSmall,
		};
	},
	mounted()
	{
		this.isApplySettingsForAll = this.modelValue;
	},
	methods: {
		setApplySettingsForAll(isApplySettingsForAll: boolean): void
		{
			this.isApplySettingsForAll = isApplySettingsForAll;
			this.$emit('update:modelValue', isApplySettingsForAll);
		},
	},
	template: `
		<div>
			<Switcher class="sign-b2e-regional-settings-company_selector_switcher"
					  :isChecked="isApplySettingsForAll"
					  :options="{ size: switcherSize }"
					  @check="setApplySettingsForAll(true)" 
					  @uncheck="setApplySettingsForAll(false)"
			/>
			<span class="sign-b2e-settings__item_title">{{ loc('SIGN_V2_B2E_REGIONAL_SETTINGS_APPLY_FOR_ALL_TITLE') }}</span>
			<Hint class="sign-b2e-regional-settings__hint" :text="loc('SIGN_V2_B2E_REGIONAL_SETTINGS_APPLY_FOR_ALL_HINT')"/>
		</div>
	`,
};
