import { Dom } from 'main.core';
import { Switcher } from '../../../vue-util/src/components/switcher/switcher';
import { LocMixin, Notice } from 'sign.v2.b2e.vue-util';
import { HcmLinkCompanySelector } from 'sign.v2.b2e.hcm-link-company-selector';
import { Hint } from 'ui.vue3.components.hint';

// @vue/component
export const CompanySelector = {
	name: 'CompanySelector',
	components: {
		Switcher,
		Notice,
		Hint,
	},
	mixins: [LocMixin],
	props: {
		companyId: {
			type: Number,
			required: true,
		},
		isChecked: {
			type: Boolean,
			default: false,
		},
		isEnabled: {
			type: Boolean,
			default: false,
		},
	},
	data(): Object
	{
		return {
			isHcmLinkCompanySelectorVisible: false,
			isCompanySelectorActive: false,
		};
	},
	watch: {
		companyId(
			newValue: number | null,
			oldValue: number | null,
		): void
		{
			if (!this.isHcmLinkCompanySelectorVisible)
			{
				return;
			}

			if (newValue === oldValue)
			{
				return;
			}

			this.hcmLinkCompanySelector.setCompanyId(newValue);
		},
		isChecked(newValue: boolean): void
		{
			this.isHcmLinkCompanySelectorVisible = newValue;
		},
		isHcmLinkCompanySelectorVisible(newValue: boolean, oldValue: boolean): void
		{
			if (newValue === oldValue)
			{
				return;
			}

			this.onHcmLinkCompanySelectorVisibleChange(newValue);
		},
		isEnabled(newValue: boolean): void
		{
			if (newValue)
			{
				return;
			}

			this.isHcmLinkCompanySelectorVisible = newValue;
		},
	},
	created(): void
	{
		this.initCompanySelector();
	},
	mounted(): void
	{
		if (this.$refs.hcmLinkCompanySelector)
		{
			Dom.append(this.hcmLinkCompanySelector.render(), this.$refs.hcmLinkCompanySelector);
		}

		this.isHcmLinkCompanySelectorVisible = this.isEnabled && this.isChecked;
	},
	methods: {
		onHcmLinkCompanySelectorVisibleChange(isVisible: boolean): void
		{
			if (this.isEnabled === false)
			{
				return;
			}

			if (isVisible && this.companyId > 0)
			{
				this.hcmLinkCompanySelector.setCompanyId(this.companyId);
			}
			else
			{
				this.$emit(
					'on-change',
					null,
					[],
					[],
					[],
				);
			}
		},
		initCompanySelector(): void
		{
			this.hcmLinkCompanySelector = new HcmLinkCompanySelector();
			this.hcmLinkCompanySelector.setAvailability(true);
			this.hcmLinkCompanySelector.subscribe(
				'selected',
				(event): void => {
					const { id, availableSettings } = event.data;
					this.$emit(
						'on-change',
						id ?? 0,
						availableSettings?.documentType ?? [],
						availableSettings?.externalId ?? [],
						availableSettings?.date ?? [],
					);
				},
			);
		},
	},
	template: `
		<div>
			<p class="sign-b2e-settings__item_title">
				<Switcher
					v-model="isHcmLinkCompanySelectorVisible"
					:title="loc('SIGN_V2_B2E_REGIONAL_SETTINGS_HCMLINK_INTEGRATION')"
					:is-enabled="isEnabled"
				/>
			</p>
			<div v-show="isHcmLinkCompanySelectorVisible" ref="hcmLinkCompanySelector"></div>
			<Notice class="sign-b2e-regional-settings-company_selector_notice">
				<template v-if="isHcmLinkCompanySelectorVisible">
					{{ loc('SIGN_V2_B2E_REGIONAL_SETTINGS_HCMLINK_INTEGRATION_ENABLED_NOTICE') }}
				</template>
				<template v-else>
					{{ loc('SIGN_V2_B2E_REGIONAL_SETTINGS_HCMLINK_INTEGRATION_DISABLED_NOTICE') }}
				</template>
			</Notice>
		</div>
	`,
};
