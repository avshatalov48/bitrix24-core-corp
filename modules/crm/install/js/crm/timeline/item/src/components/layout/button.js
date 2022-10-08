import {Action} from "../../action";
import { ButtonOptions, Button as UIButton } from 'ui.buttons';
import {ButtonType} from '../enums/button-type';
import {ButtonState} from '../enums/button-state';
import {Type} from 'main.core';

export const Button = {
	props: {
		title: {
			type: String,
			required: false,
			default: '',
		},
		type: {
			type: String,
			required: false,
			default: ButtonType.SECONDARY,
		},
		state: {
			type: String,
			required: false,
			default: ButtonState.DEFAULT,
		},
		iconName: {
			type: String,
			required: false,
			default: '',
		},
		size: {
			type: String,
			required: false,
			default: 'extra_small'
		},
		action: Object,
	},
	data() {
		return {
			popup: null,
		}
	},
	computed: {
		buttonOptions(): ButtonOptions {
			const upperCaseIconName = Type.isString(this.iconName) ? this.iconName.toUpperCase() : '';
			const upperCaseButtonSize = Type.isString(this.size) ? this.size.toUpperCase() : 'extra_small';
			const color = this.itemTypeToButtonColorDict[this.type] || UIButton.Color.LIGHT_BORDER;
			const text = this.type === ButtonType.ICON ? '' : this.title;
			return {
				round: true,
				dependOnTheme: false,
				size: UIButton.Size[upperCaseButtonSize],
				text: text,
				color: color,
				state: this.itemStateToButtonStateDict[this.state],
				icon: UIButton.Icon[upperCaseIconName],
			}
		},

		itemTypeToButtonColorDict() {
			return {
				[ButtonType.PRIMARY]: UIButton.Color.PRIMARY,
				[ButtonType.SECONDARY]: UIButton.Color.LIGHT_BORDER,
				[ButtonType.LIGHT]: UIButton.Color.LIGHT,
				[ButtonType.ICON]: UIButton.Color.LINK,
			}
		},

		itemStateToButtonStateDict() {
			return {
				[ButtonState.LOADING]: UIButton.State.WAITING,
				[ButtonState.DISABLED]: UIButton.State.DISABLED,
			}
		},

		buttonContainerRef(): HTMLElement | undefined {
			return this.$refs.buttonContainer;
		},

	},
	methods: {
		renderButton(): void {
			if (!this.buttonContainerRef) {
				return;
			}
			this.buttonContainerRef.innerHTML = '';
			const button = new UIButton(this.buttonOptions);
			button.renderTo(this.buttonContainerRef);
		},

		executeAction(): void
		{
			if (this.action && this.state !== ButtonState.DISABLED && this.state !== ButtonState.LOADING)
			{
				const action = new Action(this.action);
				action.execute(this);
			}
		},
	},
	mounted() {
		this.renderButton();
	},
	updated() {
		this.renderButton();
	},
	template: `
		<div
			:class="$attrs.class"
			ref="buttonContainer"
			@click="executeAction">
		</div>
	`
};
