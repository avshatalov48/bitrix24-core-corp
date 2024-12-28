import { BaseField } from './base-field';
import { Switcher, SwitcherSize } from 'ui.switcher';
import '../../css/switcher-field.css';

export const SwitcherField = {
	extends: BaseField,
	mounted()
	{
		new Switcher({
			node: this.$refs.switcher,
			size: SwitcherSize.small,
			checked: this.defaultValue,
			handlers: {
				toggled: () => {
					this.value = !this.value;
					this.onInputChange(this.value);
				},
			},
		});
	},
	// language=Vue
	template: `
		<div class="ui-form-row">
			<div class="switcher-field">
				<div ref="switcher"></div>
				<div class="switcher-field__label">
					<span>{{ title }}</span>
				</div>
			</div>
		</div>
	`,
};
