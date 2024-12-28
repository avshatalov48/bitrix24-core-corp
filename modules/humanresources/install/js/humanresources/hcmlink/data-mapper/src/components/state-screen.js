import { Loc } from 'main.core';
import { Button } from 'ui.buttons';

import '../styles/state-screen.css';

export const StateScreen = {
	name: 'StateScreen',

	props: {
		status: {
			required: true,
			type: String,
		},
	},

	data() {
		return {
			state: this.isDoneState() ? 'done' : 'pending',
		};
	},

	emits: ['completeMapping'],

	methods: {
		isDoneState() {
			return ['done', 'salaryDone'].includes(this.status);
		},

		getButton()
		{
			const text = this.status === 'done'
				? Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_BUTTON_CLOSE')
				: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_BUTTON_SALARY_CLOSE')
			;

			return new Button({
				color: Button.Color.LIGHT_BORDER,
				round: true,
				size: Button.Size.LARGE,
				text,
				onclick: () => {
					this.$emit('completeMapping');
					BX.SidePanel.Instance.getTopSlider().close();
				},
			});
		},
	},

	mounted()
	{
		this.getButton().renderTo(this.$refs.buttonContainer);
	},

	computed: {
		title() {
			switch (this.status)
			{
				case 'pending': return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_PENDING');
				case 'done': return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_DONE');
				case 'salaryDone': return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_SALARY_DONE');
				default: return '';
			}
		},
		description() {
			switch (this.status)
			{
				case 'pending': return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_PENDING');
				case 'done': return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_DONE');
				case 'salaryDone': return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_SALARY_DONE');
				default: return '';
			}
		},

		stateClassList() {
			return {
				'--done': this.isDoneState(),
				'--pending': this.status === 'pending',
			};
		},
	},

	template: `
		<div 
			class="hr-hcmlink-mapping-person__state-screen"
            :class="stateClassList"
		>
			<div class="hr-hcmlink-mapping-person__state-screen_icon"></div>
			<div class="hr-hcmlink-mapping-person__state-screen_title">{{ title }}</div>
			<div 
				class="hr-hcmlink-mapping-person__state-screen_desc"
				v-html="description"
			>
			</div>
			<div 
				v-if="state === 'done'"
				class="hr-hcmlink-mapping-person__state-screen_close-button" ref="buttonContainer"
			></div>
		</div>
	`,
};
