
import { IconBackgroundColor } from '../enums/icon-background-color';
import { Counter } from 'ui.cnt';
import { Dom } from 'main.core';

export const Icon = {
	props: {
		code: {
			type: String,
			required: false,
			default: 'none',
		},
		counterType: {
			type: String,
			required: false,
			default: '',
		},
		backgroundColorToken: {
			type: String,
			required: false,
			default: IconBackgroundColor.PRIMARY,
		},
	},
	inject: ['isLogMessage'],
	computed: {
		className() {
			return {
				'crm-timeline__card_icon': true,
				[`--bg-${this.backgroundColorToken}`]: !!this.backgroundColorToken,
				[`--code-${this.code}`]: !!this.code,
				['--muted']: this.isLogMessage,
			}
		},

		counterNodeContainer() {
			return this.$refs.counter;
		},
	},

	methods: {
		renderCounter() {
			if (!this.counterType) {
				return;
			}
			Dom.clean(this.counterNodeContainer);
			const counter = new Counter({
				value: 1,
				border: true,
				color: Counter.Color[this.counterType.toUpperCase()],
			});
			counter.renderTo(this.counterNodeContainer);
		},
	},
	mounted() {
		this.renderCounter();
	},
	watch: {
		counterType(newCounterType): void // update if counter state changed
		{
			this.$nextTick(() => {
				this.renderCounter();
			});
		}
	},
	template: `
		<div :class="className">
			<i></i>
			<div ref="counter" v-show="!!counterType" class="crm-timeline__card_icon_counter"></div>
		</div>
	`
};
