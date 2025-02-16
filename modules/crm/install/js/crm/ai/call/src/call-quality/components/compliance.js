import { Loc } from 'main.core';
import { ViewMode } from './common/view-mode';

export const Compliance = {
	props: {
		title: {
			type: String,
			required: true,
		},
		assessment: {
			type: Number,
			default: null,
		},
		lowBorder: {
			type: Number,
			default: 30,
		},
		highBorder: {
			type: Number,
			default: 70,
		},
		viewMode: {
			type: String,
			default: null,
		},
	},

	mounted(): void
	{
		this.startAnimate();
	},

	methods: {
		startAnimate(): void
		{
			if (this.$refs.assessment)
			{
				this.animateCounter(this.$refs.assessment, this.assessment);
			}
		},
		animateCounter(counterElement: HTMLElement, targetNumber: number): void
		{
			let startNumber = 0;

			const duration = 1500;
			const increment = targetNumber / (duration / 50);

			const interval = setInterval(() => {
				startNumber += increment;

				if (startNumber >= targetNumber)
				{
					startNumber = targetNumber;
					clearInterval(interval);
				}

				// eslint-disable-next-line no-param-reassign
				counterElement.textContent = Math.floor(startNumber);
			}, 50);
		},
	},

	computed: {
		classList(): Object
		{
			return {
				'call-quality__compliance__container': true,
				'--empty-state': !this.isUsedCurrentVersionOfScript,
				'--low': this.assessment <= this.lowBorder,
				'--high': this.assessment >= this.highBorder,
			};
		},
		isUsedCurrentVersionOfScript(): boolean
		{
			return this.viewMode === ViewMode.usedCurrentVersionOfScript;
		},
		infoTitle(): string
		{
			return (
				this.viewMode === ViewMode.emptyScriptList
					? Loc.getMessage('CRM_COPILOT_CALL_QUALITY_COMPLIANCE_EMPTY_SCRIPT_LIST_TITLE')
					: Loc.getMessage('CRM_COPILOT_CALL_QUALITY_COMPLIANCE_TITLE')
			);
		},
		valueTitle(): string
		{
			return (
				this.viewMode === ViewMode.emptyScriptList
					? Loc.getMessage('CRM_COPILOT_CALL_QUALITY_COMPLIANCE_EMPTY_SCRIPT_LIST_VALUE')
					: this.title
			);
		},
	},

	template: `
		<div :class="classList">
			<div class="call-quality__compliance">
				<div
					v-if="isUsedCurrentVersionOfScript"
					class="call-quality__compliance__assessment"
				>
					<span ref="assessment" class="call-quality__compliance__assessment-value">
						{{ assessment }}
					</span>
					<div class="call-quality__compliance__assessment-measure">
					</div>
				</div>
				<div class="call-quality__compliance__info">
					<span class="call-quality__compliance__info-title">
						{{ infoTitle }}
					</span>
					<span class="call-quality__compliance__info-value">
						{{ valueTitle }}
					</span>
				</div>
			</div>
		</div>
	`,
};
