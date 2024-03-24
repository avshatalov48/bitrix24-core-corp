import { Button as UIButton } from 'ui.buttons';
import { Action } from '../action';
import ButtonType from '../enums/button-type';
import ButtonState from '../enums/button-state';

export default
{
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
		type: {
			type: String,
			required: false,
			default: ButtonType.SECONDARY,
		},
		action: Object,
	},

	data(): Object
	{
		return {
			uiButton: Object.freeze(null),
		};
	},
	computed:
	{
		buttonContainerRef(): HTMLElement | undefined {
			return this.$refs.buttonContainer;
		},
		itemStateToButtonStateDict(): Object
		{
			return {
				[ButtonState.LOADING]: UIButton.State.WAITING,
				[ButtonState.DISABLED]: UIButton.State.DISABLED,
			};
		},
		itemTypeToButtonColorDict(): Object {
			return {
				[ButtonType.PRIMARY]: UIButton.Color.PRIMARY,
				[ButtonType.SECONDARY]: UIButton.Color.LINK,
			};
		},
		className(): Array {
			return [
				UIButton.BASE_CLASS,
				this.itemTypeToButtonColorDict[this.type] ?? UIButton.Color.LINK,
				UIButton.Size.EXTRA_SMALL,
				UIButton.Style.ROUND,
				this.itemStateToButtonStateDict[this.state] ?? '',
			];
		},
	},
	methods: {
		executeAction(): void
		{
			if (this.action && ![ButtonState.LOADING, ButtonState.DISABLED].includes(this.state))
			{
				const action = new Action(this.action);
				action.execute(this);
			}
		},
	},
	template: `
		<button :class="className" @click="executeAction">{{ title }}</button>
	`,
};
