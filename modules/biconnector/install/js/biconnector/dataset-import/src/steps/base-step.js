import { StepBlock } from '../layout/step-block';

export const BaseStep = {
	emits: ['validation'],
	props: {
		title: {
			type: String,
			required: false,
		},
		hint: {
			type: String,
			required: false,
		},
		isOpenInitially: {
			type: Boolean,
			required: false,
			default: true,
		},
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	computed: {
		displayedTitle()
		{
			return this.title ?? this.defaultTitle;
		},
		displayedHint()
		{
			return this.hint ?? this.defaultHint;
		},
		defaultTitle()
		{
			return '';
		},
		defaultHint()
		{
			return '';
		},
	},
	methods: {
		open()
		{
			if (this.$refs.stepBlock)
			{
				this.$refs.stepBlock.toggleCollapse(true);
			}
		},
		close()
		{
			if (this.$refs.stepBlock)
			{
				this.$refs.stepBlock.toggleCollapse(false);
			}
		},
		validate()
		{
			return true;
		},
		showValidationErrors()
		{},
	},
	components: {
		Step: StepBlock,
	},
};
