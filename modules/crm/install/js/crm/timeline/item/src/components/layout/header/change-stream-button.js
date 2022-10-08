import {Action} from "../../../action";

export const ChangeStreamButton = {
	props: {
		disableIfReadonly: Boolean,
		type: String,
		title: String,
		action: Object,
	},
	inject: [
		'isReadOnly',
	],

	computed: {
		isShowPinButton() {
			return this.type === 'pin' && !this.isReadOnly;
		},

		isShowUnpinButton() {
			return this.type==='unpin' && !this.isReadOnly;
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
		}
	},

	template: `
		<div class="crm-timeline__card-top_controller">
			<input
				v-if="type === 'complete'"
				@click="executeAction"
				type="checkbox"
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
