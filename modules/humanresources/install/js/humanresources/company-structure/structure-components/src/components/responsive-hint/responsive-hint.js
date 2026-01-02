import { Reflection, Event } from 'main.core';
import 'ui.hint';

/**
 * ui.hint with reactive content
 */
export const ResponsiveHint = {
	name: 'ResponsiveHint',

	props: {
		content: {
			type: String,
			required: true,
		},
		width: {
			type: [Number, null],
			default: 300,
		},
		extraClasses: {
			type: Object,
		},
		defaultClass: {
			type: String,
			default: 'ui-hint',
		},
		top: {
			type: Boolean,
			default: false,
		},
		alignCenter: {
			type: Boolean,
			default: false,
		},
		checkScrollWidth: {
			type: Boolean,
			default: false,
		},
	},

	created(): void
	{
		this.hint = null;
	},

	mounted(): void
	{
		const container = this.$refs['hint-container'];
		const parameters = {
			width: this.width,
		};

		if (this.top)
		{
			parameters.bindOptions = { position: 'top' };
		}

		if (this.alignCenter)
		{
			parameters.offsetLeft = (container.offsetWidth / 2) - this.width / 2 + 39;
			parameters.angle = { offset: this.width / 2 - 33 / 2 };
		}

		Event.bind(this.$refs['hint-container'], 'mouseenter', () => {
			if (this.checkScrollWidth && this.$refs['hint-container'].scrollWidth === this.$refs['hint-container'].offsetWidth)
			{
				return;
			}

			this.hint = Reflection.getClass('BX.UI.Hint').createInstance({
				popupParameters: { ...parameters }, // destruct parameters to recreate hint
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
		<span :class="[defaultClass, extraClasses]" ref="hint-container">
			<slot/>
		</span>
	`,
};
