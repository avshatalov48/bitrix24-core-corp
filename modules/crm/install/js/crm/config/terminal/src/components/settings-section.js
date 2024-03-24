// @flow

import 'ui.switcher';

export const SettingsSection = {

	data(): Object
	{
		return {
			isEnabled: this.active,
			switcher: null,
		};
	},

	mounted()
	{
		(new BX.UI.Switcher({
			node: this.$refs.switcher,
			size: 'small',
			checked: this.isEnabled,
			handlers: {
				toggled: this.onSwitcherToggle.bind(this),
			},
		}));

		BX.UI.Hint.init(this.$refs.title);
	},

	methods: {
		onSwitcherToggle()
		{
			this.isEnabled = !this.isEnabled;
			this.$emit('toggle', this.isEnabled);
		},
		onTitleClick()
		{
			this.$emit('titleClick');
		},
	},

	props: {
		title: String,
		switchable: {
			type: Boolean,
			default: false,
		},
		active: {
			type: Boolean,
			default: false,
		},
		hint: {
			type: String,
			default: '',
		},
		leftIconClass: {
			type: String,
			default: '',
		},
	},

	// language=Vue
	template: `
		<div>
			<div class="ui-slider-heading-4 settings-section-header">
				<div v-if="switchable" class="settings-setction-switcher-container">
					<span ref="switcher" class="ui-switcher"></span>
				</div>
				<div v-if="leftIconClass" :class="leftIconClass"></div>
				<div
					style="font-size: 16px; margin-right: 0px;"
				>
					{{ title }}
				</div>
				<span
					v-if="hint !== ''"
					class="ui-hint"
					data-hint-html
					data-hint-interactivity
					:data-hint="hint"
				>
					<span class="ui-hint-icon"></span>
				</span>
			</div>
		</div>
	`,
};
