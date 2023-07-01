//@flow

import "ui.switcher";
export const SettingsSection = {

	data() {
		return {
			isEnabled: this.active,
			switcher: null,
		};
	},

	mounted()
	{
		(new BX.UI.Switcher({
			node: this.$refs.switcher,
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
	},

	props: {
		title: String,
		switchable: {
			type: Boolean,
			default: false
		},
		active: {
			type: Boolean,
			default: false
		},
		hint: {
			type: String,
			default: '',
		}
	},

	// language=Vue
	template: `
		<div>
			<div class="ui-slider-heading-4 settings-section-header">
				<div v-if="switchable">
					<span ref="switcher" class="ui-switcher"></span>
				</div>
				<div ref="title">
					{{ title }}
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
			<div v-if="isEnabled">
				<slot></slot>
			</div>
		</div>
	`
};
