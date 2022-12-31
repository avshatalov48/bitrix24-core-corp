import {Action} from "../../../action";

export const ChangeStreamButton = {
	props: {
		disableIfReadonly: Boolean,
		type: String,
		title: String,
		action: Object,
	},
	data(): Object {
		return {
			isReadonlyMode: false,
		}
	},
	inject: [
		'isReadOnly',
	],
	mounted()
	{
		this.isReadonlyMode = this.isReadOnly;
	},
	computed: {
		isShowPinButton() {
			return this.type === 'pin' && !this.isReadonlyMode;
		},

		isShowUnpinButton() {
			return this.type==='unpin' && !this.isReadonlyMode;
		},
	},
	methods: {
		executeAction() {
			if (!this.action)
			{
				return;
			}

			const action = new Action(this.action);
			action.execute(this);
		},
		onClick(): void
		{
			if (this.action)
			{
				const action = new Action(this.action);
				action.execute(this);
			}
		},
		setDisabled(disabled: boolean)
		{
			if (!this.isReadonly && !disabled)
			{
				this.isReadonlyMode = false;
			}
			if (disabled)
			{
				this.isReadonlyMode = true;
			}
		}
	},

	template: `
		<div class="crm-timeline__card-top_controller">
			<input
				v-if="type === 'complete'"
				@click="executeAction"
				type="checkbox"
				:disabled="isReadonlyMode"
				class="crm-timeline__card-top_checkbox"
			/>
			<div
				v-else-if="isShowPinButton"
				:title="title"
				@click="executeAction"
				class="crm-timeline__card-top_icon --pin"
			></div>
			<div
				v-else-if="isShowUnpinButton"
				:title="title"
				@click="executeAction"
				class="crm-timeline__card-top_icon --unpin"
			></div>
		</div>
	`,
};
