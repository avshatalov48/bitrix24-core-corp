import { Type, Text } from 'main.core';
import { ImageStackSteps, imageTypeEnum } from 'ui.image-stack-steps';
import 'ui.icon-set.main';

import 'ui.design-tokens';
import '../../css/content-blocks/avatars-stack-steps.css';

const ICON_COLORS = Object.freeze({
	lightGrey: 'var(--crm-timeline-avatars-stack-steps-icon-color-light-gray)',
	blue: 'var(--crm-timeline-avatars-stack-steps-icon-color-blue)',
	lightGreen: 'var(--crm-timeline-avatars-stack-steps-icon-color-light-green)',
});

export default {
	props: {
		steps: {
			type: Array,
			required: true,
			validator: (value) => {
				return Type.isArrayFilled(value);
			},
		},
		styles: {
			type: Object,
			required: false,
		},
	},
	data(): {}
	{
		return {
			stack: null,
		};
	},
	mounted()
	{
		if (this.$refs.controlWrapper)
		{
			this.stack = new ImageStackSteps({
				steps: this.convertIconColors(this.steps),
			});
			this.stack.renderTo(this.$refs.controlWrapper);
		}
	},
	updated()
	{
		if (this.stack)
		{
			this.convertIconColors(this.steps).forEach((step) => {
				this.stack.updateStep(step, step.id);
			});
		}
	},
	unmounted()
	{
		if (this.stack)
		{
			this.stack.destroy();
		}
	},
	computed: {
		getStyles(): {}
		{
			const styles = {};

			if (this.styles?.minWidth)
			{
				styles['min-width'] = `${Text.toInteger(this.styles.minWidth)}px`;
			}

			return styles;
		},
	},
	methods: {
		convertIconColors(steps: []): []
		{
			const colors = Object.keys(ICON_COLORS);

			steps.forEach((step) => {
				const images = step.stack.images;
				if (Type.isArrayFilled(images))
				{
					images.forEach((image) => {
						if (image.type === imageTypeEnum.ICON)
						{
							const color = image.data?.color;
							if (colors.includes(color))
							{
								// eslint-disable-next-line no-param-reassign
								image.data.color = ICON_COLORS[color];
							}
						}
					});
				}
			});

			return steps;
		},
	},
	template: `
		<div class="crm-timeline__avatars-stack-steps" ref="controlWrapper" :style="getStyles"></div>
	`,
};
