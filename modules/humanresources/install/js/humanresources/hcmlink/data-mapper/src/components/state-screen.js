import { Loc } from 'main.core';
import { Button } from 'ui.buttons';
import { Status } from '../types';

import '../styles/state-screen.css';

export const StateScreen = {
	name: 'StateScreen',

	props: {
		status: {
			required: true,
			type: String,
		},
		isBlock: {
			type: Boolean,
			default: false,
		},
		mode: {
			required: true,
			type: String,
		},
	},

	emits: ['completeMapping', 'abortSync'],

	mounted(): void
	{
		this.getCloseButton().renderTo(this.$refs.buttonContainer);
		this.getAbortSyncButton().renderTo(this.$refs.abortSyncButtonContainer);
	},

	methods: {
		getCloseButton(): Button
		{
			const text = this.status === Status.done
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

		getAbortSyncButton(): Button
		{
			return new Button({
				color: Button.Color.LIGHT_BORDER,
				round: true,
				size: Button.Size.LARGE,
				text: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_ABORT_LOAD_BUTTON'),
				onclick: () => {
					this.$emit('abortSync');
				},
			});
		},
	},

	computed: {
		title(): string {
			switch (this.status)
			{
				case Status.loading:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_LOADING');
				case Status.pending:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_PENDING');
				case Status.done:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_DONE');
				case Status.salaryDone:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_SALARY_DONE');
				case Status.searchNotFound:
					return this.mode === 'direct'
						? Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_SEARCH_NOT_FOUND_DIRECT')
						: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_TITLE_STATUS_SEARCH_NOT_FOUND_REVERSE');
				default:
					return '';
			}
		},
		description(): string {
			switch (this.status)
			{
				case Status.loading:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_LOADING');
				case Status.pending:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_PENDING_MSGVER_1');
				case Status.done:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_DONE');
				case Status.salaryDone:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_SALARY_DONE');
				case Status.searchNotFound:
					return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_STATE_DESCRIPTION_STATUS_SEARCH_NOT_FOUND');
				default: return '';
			}
		},

		stateClassList(): Object {
			return {
				'--done': this.isDoneState,
				'--pending': this.isPendingState,
				'--search-not-found': this.status === Status.searchNotFound,
				'--block': this.isBlock,
				'--flex': !this.isBlock,
			};
		},

		isDoneState(): boolean {
			return [Status.done, Status.salaryDone].includes(this.status);
		},

		isPendingState(): boolean {
			return [Status.pending, Status.loading].includes(this.status);
		},
	},

	template: `
		<div class="hr-hcmlink-mapping-person__state-screen" :class="stateClassList">
			<div class="hr-hcmlink-mapping-person__state-screen_icon"></div>
			<div 
				class="hr-hcmlink-mapping-person__state-screen_title"
				v-html="title"
			>
			</div>
			<div 
				class="hr-hcmlink-mapping-person__state-screen_desc"
				v-html="description"
			>
			</div>
			<div 
				v-if="isDoneState"
				class="hr-hcmlink-mapping-person__state-screen_close-button" ref="closeButtonContainer"
			></div>
			<div 
				v-if="status === 'pending'"
				class="hr-hcmlink-mapping-person__state-screen_abort-sync-button" ref="abortSyncButtonContainer"
			></div>
		</div>
	`,
};
