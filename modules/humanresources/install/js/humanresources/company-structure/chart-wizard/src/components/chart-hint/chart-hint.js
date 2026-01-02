import { Reflection, Event } from 'main.core';
import 'ui.hint';

/**
 * ui.hint with reactive content
 */
export const ChartHint = {
	name: 'ChartHint',

	props: {
		content: {
			type: String,
			required: true,
		},
		width: {
			type: Number,
			default: 300,
		},
	},

	created(): void
	{
		this.hint = null;
	},

	mounted(): void
	{
		Event.bind(this.$refs['hint-container'], 'mouseenter', () => {
			this.hint = Reflection.getClass('BX.UI.Hint').createInstance({
				popupParameters: {
					width: this.width,
				},
			});
			this.hint.show(this.$refs['hint-container'], this.content);
		});

		Event.bind(this.$refs['hint-container'], 'mouseleave', () => {
			this.hint?.hide(); // hide() function also destroys popup
		});
	},

	unmounted(): void
	{
		this.hint?.hide();
	},

	template: `
		<span class="ui-hint" ref="hint-container">
			<span class="ui-hint-icon"/>
		</span>
	`,
};
