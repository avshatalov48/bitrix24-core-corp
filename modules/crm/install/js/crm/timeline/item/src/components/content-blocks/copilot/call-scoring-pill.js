import { Text } from 'main.core';

import { Action } from '../../../action';

const STATE_LOADING = 'loading';
const STATE_PROCESSED = 'processed';
const STATE_UNPROCESSED = 'unprocessed';

export const CallScoringPill = {
	props: {
		title: {
			type: String,
			required: false,
			default: '',
		},
		value: {
			type: String,
			required: false,
			default: '',
		},
		state: {
			type: String,
			required: false,
			default: STATE_UNPROCESSED,
		},
		action: Object | null,
	},

	inject: ['isReadOnly'],

	computed: {
		className(): []
		{
			return [
				'crm-timeline__call-scoring-pill',
				{
					'--readonly': this.isPillReadonly,
				},
			];
		},

		renderValue(): String
		{
			switch (this.state)
			{
				case STATE_LOADING:
					return '<span class="loader"></span>';
				case STATE_PROCESSED:
					return Text.encode(this.value);
				case STATE_UNPROCESSED:
				default:
					return '<span class="arrow">&nbsp;</span>';
			}
		},

		isPillReadonly(): boolean
		{
			return this.isReadOnly || !this.action;
		},
	},

	methods: {
		executeAction(): void
		{
			if (this.isPillReadonly)
			{
				return;
			}

			const action = new Action(this.action);

			void action.execute(this);
		},
	},

	template: `
		<div
			:class='className'
			@click='executeAction'
		>
			<div class='crm-timeline__call-scoring-pill-left'>{{ this.title }}</div>
			<div class='crm-timeline__call-scoring-pill-separator'></div>
			<div class='crm-timeline__call-scoring-pill-right' v-html='renderValue'></div>
		</div>
	`,
};
