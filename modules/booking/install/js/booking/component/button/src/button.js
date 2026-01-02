import {
	Button as UIButton,
	AirButtonStyle,
	ButtonColor,
	ButtonSize,
	ButtonState,
	ButtonStyle,
	ButtonIcon,
} from 'ui.buttons';
import { Type } from 'main.core';

export const Button = {
	name: 'UiButton',
	emits: ['click'],
	props: {
		text: {
			type: String,
			default: '',
		},
		size: String,
		state: {
			type: String,
			default: undefined,
			validator(val): boolean
			{
				return Type.isUndefined(val) || Object.values(ButtonState).includes(val);
			},
		},
		id: String,
		color: String,
		round: Boolean,
		icon: String,
		iconPosition: {
			type: String,
			validator(position: ?string): boolean
			{
				return !position || ['left', 'right'].includes(position);
			},
		},
		useAirDesign: Boolean,
		noCaps: Boolean,
		disabled: Boolean,
		clocking: Boolean,
		waiting: Boolean,
		dataset: Object,
		buttonClass: [String, Array],
	},
	created(): void
	{
		this.button = new UIButton({
			id: this.id,
			text: this.text,
			size: this.size,
			color: this.color,
			round: this.round,
			icon: this.icon,
			iconPosition: this.iconPosition,
			useAirDesign: Boolean(this.useAirDesign),
			noCaps: this.noCaps,
			onclick: () => {
				this.$emit('click');
			},
			dataset: this.dataset,
			className: Type.isArray(this.buttonClass) ? this.buttonClass.join(' ') : this.buttonClass,
		});

		if (this.useAirDesign)
		{
			this.button.setAirDesign(true);
		}
	},
	mounted(): void
	{
		const button = this.button?.render();

		const slot = this.$refs.button.firstElementChild;
		if (slot)
		{
			button.append(slot);
		}

		this.$refs.button.replaceWith(button);

		if (this.disabled)
		{
			this.button.setDisabled(this.disabled);
		}
	},
	watch: {
		text: {
			handler(text): void
			{
				this.button?.setText(text);
			},
		},
		size: {
			handler(size): void
			{
				this.button?.setSize(size);
			},
		},
		color: {
			handler(color): void
			{
				this.button?.setColor(color);
			},
		},
		state: {
			handler(state): void
			{
				this.button?.setState(state);
			},
		},
		icon: {
			handler(icon): void
			{
				this.button?.setIcon(icon, this.iconPosition);
			},
		},
		disabled: {
			handler(disabled): void
			{
				this.button?.setDisabled(Boolean(disabled));
			},
			immediate: true,
			flush: 'sync',
		},
		waiting: {
			handler(waiting): void
			{
				if (waiting !== this.button?.isWaiting())
				{
					this.button?.setWaiting(waiting);
				}
			},
			immediate: true,
		},
		clocking: {
			handler(clocking): void
			{
				if (clocking !== this.button?.isClocking())
				{
					this.button?.setClocking(clocking);
				}
			},
			immediate: true,
		},
	},
	template: `
		<span>
			<button ref="button">
				<slot></slot>
			</button>
		</span>
	`,
};

export { AirButtonStyle, ButtonColor, ButtonSize, ButtonStyle, ButtonIcon };
