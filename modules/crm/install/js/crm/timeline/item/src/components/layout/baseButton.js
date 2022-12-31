import { ButtonState } from '../enums/button-state';
import { Button as UIButton } from 'ui.buttons';
import { Action } from '../../action';

export const BaseButton = {
	props: {
		id: {
			type: String,
			required: false,
			default: '',
		},
		title: {
			type: String,
			required: false,
			default: '',
		},
		state: {
			type: String,
			required: false,
			default: ButtonState.DEFAULT,
		},
		action: Object,
	},
	data() {
		return {
			currentState: this.state,
		}
	},

	computed: {
		itemStateToButtonStateDict() {
			return {
				[ButtonState.LOADING]: UIButton.State.WAITING,
				[ButtonState.DISABLED]: UIButton.State.DISABLED,
			}
		},
	},

	methods: {
		setDisabled(disabled: boolean): void
		{
			if (disabled)
			{
				this.setButtonState(ButtonState.DISABLED);
			}
			else
			{
				this.setButtonState(ButtonState.DEFAULT);
			}
		},

		setLoading(loading: boolean): void
		{
			if (loading)
			{
				this.setButtonState(ButtonState.LOADING);
			}
			else
			{
				this.setButtonState(ButtonState.DEFAULT);
			}
		},

		setButtonState(state): void
		{
			if (this.currentState !== state)
			{
				this.currentState = state;
			}
		},

		onLayoutUpdated(): void
		{
			this.setButtonState(this.state);
		},

		executeAction(): void
		{
			if (this.action && this.currentState !== ButtonState.DISABLED && this.currentState !== ButtonState.LOADING)
			{
				const action = new Action(this.action);
				action.execute(this);
			}
		},


	},

	created() {
		this.$Bitrix.eventEmitter.subscribe('layout:updated', this.onLayoutUpdated);
	},
	beforeDestroy(): void
	{
		this.$Bitrix.eventEmitter.unsubscribe('layout:updated', this.onLayoutUpdated);
	},

	template: `<button></button>`
}