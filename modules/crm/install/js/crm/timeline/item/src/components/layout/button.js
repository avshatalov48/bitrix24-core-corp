import {BitrixVue} from 'ui.vue3';
import {BaseButton} from './baseButton';
import { ButtonOptions, Button as UIButton } from 'ui.buttons';
import {ButtonType} from '../enums/button-type';
import {ButtonState} from '../enums/button-state';
import {Type} from 'main.core';

export const Button  = BitrixVue.cloneComponent(BaseButton, {
	props: {
		type: {
			type: String,
			required: false,
			default: ButtonType.SECONDARY,
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
	},

	data() {
		return {
			popup: null,
			uiButton: Object.freeze(null),
			timerSecondsRemaining: 0,
		}
	},

	computed: {
		itemTypeToButtonColorDict(): Object {
			return {
				[ButtonType.PRIMARY]: UIButton.Color.PRIMARY,
				[ButtonType.SECONDARY]: UIButton.Color.LIGHT_BORDER,
				[ButtonType.LIGHT]: UIButton.Color.LIGHT,
				[ButtonType.ICON]: UIButton.Color.LINK,
			}
		},

		buttonContainerRef(): HTMLElement | undefined {
			return this.$refs.buttonContainer;
		},
	},

	methods: {
		getButtonOptions(): ButtonOptions {
			const upperCaseIconName = Type.isString(this.iconName) ? this.iconName.toUpperCase() : '';
			const upperCaseButtonSize = Type.isString(this.size) ? this.size.toUpperCase() : 'extra_small';
			const color = this.itemTypeToButtonColorDict[this.type] || UIButton.Color.LIGHT_BORDER;
			const text = this.type === ButtonType.ICON ? '' : this.title;

			return {
				id: this.id,
				round: true,
				dependOnTheme: false,
				size: UIButton.Size[upperCaseButtonSize],
				text: text,
				color: color,
				state: this.itemStateToButtonStateDict[this.currentState],
				icon: UIButton.Icon[upperCaseIconName],
			}
		},

		getUiButton(): ?UIButton
		{
			return this.uiButton;
		},

		disableWithTimer(sec: number)
		{
			this.setButtonState(ButtonState.DISABLED);
			const btn = this.getUiButton();
			let remainingSeconds = sec;

			btn.setText(this.formatSeconds(remainingSeconds));

			const timer = setInterval(() => {
				if (remainingSeconds < 1)
				{
					clearInterval(timer);
					btn.setText(this.title);
					this.setButtonState(ButtonState.DEFAULT);
					return;
				}

				remainingSeconds--;
				btn.setText(this.formatSeconds(remainingSeconds));
			}, 1000);
		},

		formatSeconds(sec: number): string {
			const minutes = Math.floor(sec / 60);
			const seconds = sec % 60;

			const formatMinutes = this.formatNumber(minutes);
			const formatSeconds = this.formatNumber(seconds);

			return `${formatMinutes}:${formatSeconds}`;
		},

		formatNumber(num: number): string {
			return num < 10 ? `0${num}` : num;
		},

		setButtonState(state): void
		{
			this.parentSetButtonState(state);
			this.getUiButton()?.setState(this.itemStateToButtonStateDict[this.currentState] ?? null);
		},

		renderButton(): void {
			if (!this.buttonContainerRef) {
				return;
			}
			this.buttonContainerRef.innerHTML = '';
			const button = new UIButton(this.getButtonOptions());
			button.renderTo(this.buttonContainerRef);
			this.uiButton = button;
		},
	},

	watch: {
		state(newValue): void
		{
			this.setButtonState(newValue);
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
});
