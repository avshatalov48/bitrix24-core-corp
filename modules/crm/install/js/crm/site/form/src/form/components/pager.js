const PagerBlock = {
	props: {
		pager: {
			type: Object,
			required: true,
		},
		diameter: {
			type: Number,
			default: 44,
		},
		border: {
			type: Number,
			default: 4,
		},
	},
	template: `
		<div class="b24-form-progress-container"
			v-if="pager.iterable()"
		>
			<div class="b24-form-progress-bar-container">
				<svg class="b24-form-progress" 
					:viewport="'0 0 ' + diameter + ' ' + diameter" 
					:width="diameter" :height="diameter"
				>
					<circle class="b24-form-progress-track"
						:r="(diameter - border) / 2" 
						:cx="diameter / 2" :cy="diameter / 2" 
						:stroke-width="border" 
					></circle>
					<circle class="b24-form-progress-bar"
						:r="(diameter - border) / 2"
						:cx="diameter / 2" :cy="diameter / 2"
						:stroke-width="border"
						:stroke-dasharray="strokeDasharray" 
						:stroke-dashoffset="strokeDashoffset"
					></circle>
				</svg>
				<div class="b24-form-progress-bar-counter">
					<strong>{{ pager.index}}</strong>/{{ pager.count() }}
				</div>
			</div>
			<div class="b24-form-progress-bar-title">
				{{ pager.current().getTitle() }}
			</div>

		</div>
	`,
	computed: {
		strokeDasharray(): number
		{
			return this.getCircuit();
		},
		strokeDashoffset(): number
		{
			return this.getCircuit() - (
				this.getCircuit() / this.pager.count() * (this.pager.index)
			);
		}
	},
	methods: {
		getCircuit(): number
		{
			return (this.diameter - this.border) * 3.14;
		}
	}
};

export {
	PagerBlock,
}