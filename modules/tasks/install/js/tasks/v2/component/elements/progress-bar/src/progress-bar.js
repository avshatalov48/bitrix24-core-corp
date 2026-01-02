import './progress-bar.css';

// @vue/component
export const ProgressBar = {
	name: 'UiProgressBar',
	props: {
		totalValue: {
			type: Number,
			required: true,
		},
		completedValue: {
			type: Number,
			required: true,
		},
		width: {
			type: Number,
			default: 100,
		},
		height: {
			type: Number,
			default: 10,
		},
		color: {
			type: String,
			default: '#000000',
		},
		bgColor: {
			type: String,
			default: '#ffffff',
		},
		borderRadius: {
			type: Number,
			default: 5,
		}
	},
	computed: {
		progressPercentage(): number
		{
			if (this.totalValue === 0)
			{
				return 0;
			}

			const percentage = (this.completedValue / this.totalValue) * 100;

			return Math.min(Math.max(percentage, 0), 100);
		},
		containerStyle(): Object
		{
			return {
				width: `${this.width}px`,
				height: `${this.height}px`,
				backgroundColor: this.bgColor,
				borderRadius: `${this.borderRadius}px`,
			}
		},
		progressStyle(): Object
		{
			return {
				width: `${this.progressPercentage}%`,
				backgroundColor: this.color,
			};
		},
	},
	template: `
		<div class="progress-bar-container" :style="containerStyle">
			<div class="progress-bar" :style="progressStyle"></div>
		</div>
	`,
};
