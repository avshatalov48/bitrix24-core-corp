import { Text, Type } from 'main.core';
import { DeadlineAndPingSelectorBackgroundColor } from '../../enums/deadline-and-ping-selector-background';

export default {
	props: {
		isScheduled: Boolean,
		deadlineBlock: Object,
		pingSelectorBlock: Object,
		deadlineBlockTitle: String,
		backgroundToken: String,
		backgroundColor: {
			type: String,
			required: false,
			default: null,
		},
	},

	data(): Object
	{
		return {
			deadlineBlockData: this.deadlineBlock,
			pingSelectorBlockData: this.pingSelectorBlock,
		};
	},

	computed: {
		className(): Object
		{
			return {
				'crm-timeline__card-container_info': true,
				'--inline': true,
				'crm-timeline-block-deadline-and-ping-selector-deadline-wrapper': true,
				'--orange': this.backgroundToken === DeadlineAndPingSelectorBackgroundColor.ORANGE,
				'--gray': this.backgroundToken === DeadlineAndPingSelectorBackgroundColor.GRAY,
			};
		},
		deadlineBlockStyle(): Object
		{
			if (this.isScheduled && Type.isStringFilled(this.backgroundColor))
			{
				return {
					'--crm-timeline-block-deadline-and-ping-selector-deadline_bg-color': Text.encode(this.backgroundColor),
				};
			}

			return {};
		},
	},

	methods: {
		onDeadlineChange(deadline: number)
		{
			this.deadlineBlockData.properties.value = deadline;
			this.pingSelectorBlockData.properties.deadline = deadline;

			this.$refs.pingSelectorBlock.setDeadline(deadline);
		},
	},

	created()
	{
		this.$watch(
			'deadlineBlock',
			(deadlineBlock) => {
				this.deadlineBlockData = deadlineBlock;
			},
			{
				deep: true,
			},
		);

		this.$watch(
			'pingSelectorBlock',
			(pingSelectorBlock) => {
				this.pingSelectorBlockData = pingSelectorBlock;
			},
			{
				deep: true,
			},
		);
	},

	// language=Vue
	template: `
		<span class="crm-timeline-block-deadline-and-ping-selector">
			<div 
				:class="className" 
				ref="deadlineBlock" 
				v-if="deadlineBlock"
				:style="deadlineBlockStyle"
			>
				<div class="crm-timeline__card-container_info-title" v-if="deadlineBlockTitle">
					{{deadlineBlockTitle}}&nbsp;
				</div>
				<component
					:is="deadlineBlock.rendererName"
					v-bind="deadlineBlockData.properties"
					@onChange="onDeadlineChange"
				/>
			</div>
	
			<component
				v-if="pingSelectorBlock"
				:is="pingSelectorBlock.rendererName"
				v-bind="pingSelectorBlockData.properties"
				ref="pingSelectorBlock"
			/>
		</span>	
	`,
};
