import { Text } from 'main.core';
import { Section } from 'ui.section';
import { StepHint } from './step-hint';
import '../css/step.css';

export const StepBlock = {
	data(): Object
	{
		return {
			section: null,
		};
	},
	props: {
		title: {
			type: String,
			required: true,
		},
		hint: {
			type: String,
			required: false,
			default: '',
		},
		isOpenInitially: {
			type: Boolean,
			required: false,
			default: true,
		},
		canCollapse: {
			type: Boolean,
			required: false,
			default: true,
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		customClasses: {
			type: Array,
			default: [],
		},
		hintClass: {
			type: String,
			required: false,
		},
	},
	computed: {
		additionalClasses()
		{
			const custom = this.customClasses.reduce((acc, key) => {
				acc[key] = true;

				return acc;
			}, {});

			return {
				'dataset-import-step__disabled': this.disabled,
				...custom,
			};
		},
	},
	methods: {
		toggleCollapse(open: ?boolean)
		{
			this.section.toggle(open);
		},
	},
	mounted()
	{
		const contentContainer = this.$refs.contentContainer;

		const section = new Section({
			title: this.title,
			isOpen: this.isOpenInitially,
			canCollapse: this.canCollapse,
		});

		section.append(this.$refs.content);
		section.renderTo(contentContainer);
		this.section = section;
	},
	watch: {
		title(newValue)
		{
			if (!this.section)
			{
				return;
			}

			this.section.getContent().querySelector('.ui-section__title').innerHTML = Text.encode(newValue);
		},
	},
	components: {
		StepHint,
	},
	// language=Vue
	template: `
		<div class="dataset-import-step" :class="additionalClasses" ref="contentContainer">
		</div>
		<div ref="content" class="dataset-import-step__content">
			<StepHint v-if="hint" :hint-class="hintClass">
				<span v-html="hint"></span>
			</StepHint>
			<slot></slot>
		</div>
	`,
};
