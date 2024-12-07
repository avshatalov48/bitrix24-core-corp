import { Button as UIButton } from 'ui.buttons';
import { ButtonState } from '../enums/button-state';
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
		tooltip: {
			type: String,
			required: false,
			default: '',
		},
		state: {
			type: String,
			required: false,
			default: ButtonState.DEFAULT,
		},
		props: Object,
		action: Object,
	},

	data(): Object
	{
		return {
			currentState: this.state,
		};
	},

	computed: {
		itemStateToButtonStateDict(): Object
		{
			return {
				[ButtonState.LOADING]: UIButton.State.WAITING,
				[ButtonState.DISABLED]: UIButton.State.DISABLED,
				[ButtonState.AI_LOADING]: UIButton.State.AI_WAITING,
			};
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
			if (
				this.action && this.currentState !== ButtonState.DISABLED
				&& this.currentState !== ButtonState.LOADING
				&& this.currentState !== ButtonState.AI_LOADING
			)
			{
				const action = new Action(this.action);

				action.execute(this);
			}
		},
	},

	created(): void
	{
		this.$Bitrix.eventEmitter.subscribe('layout:updated', this.onLayoutUpdated);
	},

	beforeUnmount(): void
	{
		this.$Bitrix.eventEmitter.unsubscribe('layout:updated', this.onLayoutUpdated);
	},

	template: `<button></button>`,
};
