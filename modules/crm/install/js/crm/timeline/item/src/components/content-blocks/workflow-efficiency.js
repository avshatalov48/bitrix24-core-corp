import { Dom, Loc, Runtime, Tag } from 'main.core';
import { WorkflowResultStatus } from 'bizproc.types';

import 'ui.hint';

export default {
	data(): Object {
		return {
			formattedAverageDuration: '',
			formattedExecutionTime: '',
		};
	},
	props: {
		averageDuration: Number,
		efficiency: String,
		executionTime: Number,
		processTimeText: String,
		workflowResult: Object,
		author: Object,
	},
	computed: {
		itemClassName(): string
		{
			return `bizproc-workflow-timeline-eff-icon --${this.efficiency}`;
		},
		efficiencyCaption(): string
		{
			let notice = this.efficiency === 'fast' ? 'QUICKLY' : 'SLOWLY';
			if (this.efficiency === 'stopped')
			{
				notice = 'NO_PROGRESS';
			}

			return Loc.getMessage(`BIZPROC_WORKFLOW_TIMELINE_SLIDER_PERFORMED_${notice}`);
		},
		hasResult(): boolean
		{
			return this.workflowResult !== undefined;
		},
		href(): string
		{
			if (this.author && this.author.link)
			{
				return this.author.link;
			}

			return '';
		},
		imageStyle(): {}
		{
			if (this.author && this.author.avatarSize100)
			{
				return {
					backgroundImage: `url('${encodeURI(this.author.avatarSize100)}')`,
				};
			}

			return {};
		},
		workflowResultHtml(): HTMLElement | string | null
		{
			if (this.workflowResult && this.workflowResult.status === WorkflowResultStatus.NO_RIGHTS_RESULT)
			{
				this.workflowResult.text = Loc.getMessage('CRM_TIMELINE_WORKFLOW_RESULT_NO_RIGHTS_VIEW');
			}

			return this.workflowResult?.text ?? null;
		},
		averageDurationText(): string
		{
			return Loc.getMessage('CRM_TIMELINE_WORKFLOW_EFFICIENCY_AVERAGE_PROCESS_TIME');
		},
		resultCaption(): string
		{
			if (!this.userResult)
			{
				return Loc.getMessage('CRM_TIMELINE_WORKFLOW_RESULT_TITLE');
			}

			return '';
		},
		userResult(): ?string
		{
			if (!this.hasResult)
			{
				const userLink = Tag.render`<a href="${this.href}"></a>`;
				userLink.textContent = this.author?.fullName;

				return Loc.getMessage(
					'CRM_TIMELINE_WORKFLOW_NO_RESULT',
					{ '#USER#': userLink.outerHTML },
				);
			}

			if (this.workflowResult && this.workflowResult.status === WorkflowResultStatus.USER_RESULT)
			{
				return Loc.getMessage(
					'CRM_TIMELINE_WORKFLOW_NO_RESULT',
					{ '#USER#': this.workflowResult.text ?? '' },
				);
			}

			return null;
		},
	},
	mounted(): void {
		if (this.workflowResult && this.workflowResult.status === WorkflowResultStatus.NO_RIGHTS_RESULT)
		{
			this.showHint();
		}

		Runtime.loadExtension('bizproc.workflow.timeline')
			.then(({ DurationFormatter }) => {
				this.formattedAverageDuration = DurationFormatter.formatTimeInterval(this.averageDuration);
				this.formattedExecutionTime = DurationFormatter.formatTimeInterval(this.executionTime);
			})
			.catch((e) => {
				console.error('Error loading DurationFormatter:', e);
			});
	},
	methods: {
		showHint(): void {
			const resultBlock = this.$refs.resultBlock;
			if (resultBlock)
			{
				const hintAnchor = Tag.render`<span data-hint="${Loc.getMessage('CRM_TIMELINE_WORKFLOW_RESULT_NO_RIGHTS_TOOLTIP')}"></span>`;
				Dom.append(hintAnchor, resultBlock);

				BX.UI.Hint.init(resultBlock);
			}
		},
	},
	template: `
		<div class="crm-timeline__text-block crm-timeline__workflow-efficiency-block">
			<div class="bizproc-workflow-timeline-item --result">
				<div class="">
					<div class="bizproc-workflow-timeline-content">
						<div v-if="!userResult" class="bp-result">
							<div class="bizproc-workflow-timeline-caption">{{ resultCaption }}</div>
							<div class="bizproc-workflow-timeline-result" ref="resultBlock" v-html="workflowResultHtml"></div>
						</div>
						<div v-if="userResult" class="bp-result" v-html="userResult"></div>
					</div>
				</div>
			</div>
			<div class="bizproc-workflow-timeline-item --efficiency">
				<div class="bizproc-workflow-timeline-item-wrapper">
					<div class="bizproc-workflow-timeline-content">
						<div class="bizproc-workflow-timeline-content-inner">
							<div class="bizproc-workflow-timeline-caption">{{ efficiencyCaption }}</div>
							<div class="bizproc-workflow-timeline-notice">
								<div class="bizproc-workflow-timeline-subject">{{ processTimeText }}</div>
								<span class="bizproc-workflow-timeline-text">{{ formattedExecutionTime }}</span>
							</div>
							<div class="bizproc-workflow-timeline-notice">
								<div class="bizproc-workflow-timeline-subject">{{ averageDurationText }}</div>
								<span class="bizproc-workflow-timeline-text">{{ formattedAverageDuration }}</span>
							</div>
						</div>
						<div :class="itemClassName"></div>
					</div>
				</div>
			</div>
		</div>
	`,
};
