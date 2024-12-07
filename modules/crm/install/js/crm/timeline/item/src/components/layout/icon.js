import { Dom, Text, Type } from 'main.core';
import { Counter } from 'ui.cnt';
import { IconBackgroundColor } from '../enums/icon-background-color';

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
		backgroundUri: String,
		backgroundColor: {
			type: String,
			required: false,
			default: null,
		},
	},
	inject: ['isLogMessage'],
	computed: {
		className(): Object
		{
			return {
				'crm-timeline__card_icon': true,
				[`--bg-${this.backgroundColorToken}`]: Boolean(this.backgroundColorToken),
				[`--code-${this.code}`]: Boolean(this.code) && !this.backgroundUri,
				'--custom-bg': Boolean(this.backgroundUri),
				'--muted': this.isLogMessage,
			};
		},

		counterNodeContainer(): HTMLDivElement
		{
			return this.$refs.counter;
		},

		styles(): Object
		{
			if (!this.backgroundUri)
			{
				return {};
			}

			return {
				backgroundImage: `url('${encodeURI(Text.encode(this.backgroundUri))}')`,
			};
		},

		iconStyle(): Object
		{
			if (Type.isStringFilled(this.backgroundColor))
			{
				return {
					'--crm-timeline-card-icon-background': Text.encode(this.backgroundColor),
				};
			}

			return {};
		},
	},

	methods: {
		renderCounter() {
			if (!this.counterType)
			{
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
			void this.$nextTick(() => {
				this.renderCounter();
			});
		},
	},
	template: `
		<div :class="className" :style="iconStyle">
			<i :style="styles"></i>
			<div ref="counter" v-show="!!counterType" class="crm-timeline__card_icon_counter"></div>
		</div>
	`
};
