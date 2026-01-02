import { Switcher, SwitcherSize } from 'ui.switcher';
import { AdditionalInfoComponent } from './additional-info-component';

export const AiSwitcherComponent = {
	emits: [
		'change',
	],

	components: {
		AdditionalInfoComponent,
	},

	props: {
		checked: {
			type: Boolean,
			required: true,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},

	mounted(): void
	{
		this.renderSwitcher();
	},

	methods: {
		renderSwitcher(): void
		{
			if (!this.switcher)
			{
				this.switcher = new Switcher({
					checked: this.checked,
					disabled: this.readOnly,
					size: SwitcherSize.small,
					showStateTitle: false,
					handlers: {
						checked: (event) => {
							this.emitChange(false);
						},
						unchecked: (event) => {
							this.emitChange(true);
						},
					},
				});

				this.switcher.renderTo(this.$refs.switcher);
			}
		},
		emitChange(value: boolean): void
		{
			this.$emit('change', value);
		},
	},

	computed: {
		badgeClassList(): Object
		{
			return {
				'--enabled': this.checked,
			};
		},
		badgeTitle(): string
		{
			if (this.checked)
			{
				return this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_COPILOT_ENABLED');
			}

			return this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_COPILOT_DISABLED');
		},
	},

	watch: {
		checked(newValue: boolean): void
		{
			this.switcher.check(newValue, false);
		},
	},

	// language=Vue
	template: `
		<div class="crm-repeat-sale__segment-ai-switcher-wrapper">
			<div>
				<div class="crm-repeat-sale__segment-ai-switcher-title">
					{{this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_COPILOT_TITLE')}}
					<span :class="badgeClassList">{{badgeTitle}}</span>
				</div>
				<div class="crm-repeat-sale__segment-ai-switcher-description">
					{{this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_COPILOT_DESCRIPTION')}}
				</div>
			</div>
			<div class="crm-repeat-sale__segment-ai-switcher" ref="switcher"></div>
		</div>
	`,
};
