import { Dom } from 'main.core';

import './style.css';

// @vue/component
export const CollapseTransition = {
	name: 'CollapseTransition',
	props: {
		initialHeight: {
			type: Number,
			default: 0,
		},
	},
	emits: ['start', 'end'],
	created(): void
	{
		if (!this.$slots.default)
		{
			throw new Error('Slot is required');
		}
	},
	methods: {
		onEnter(el: HTMLElement): void
		{
			this.targetWidth = el.offsetWidth;
			this.targetHeight = Math.max(el.offsetHeight, this.initialHeight);

			const fromHeight = Math.min(el.offsetHeight, this.initialHeight);

			Dom.style(el, {
				width: 0,
				height: `${fromHeight}px`,
			});

			requestAnimationFrame(() => {
				Dom.style(el, {
					width: `${this.targetWidth}px`,
					height: `${this.targetHeight}px`,
				});
			});
		},
		onBeforeLeave(el: HTMLElement): void
		{
			const minHeight = Math.min(this.initialHeight, el.offsetHeight);

			Dom.style(el, {
				width: `${el.offsetWidth}px`,
				height: `${el.offsetHeight}px`,
			});

			requestAnimationFrame(() => {
				Dom.style(el, {
					width: 0,
					height: `${minHeight}px`,
				});
			});

			this.$emit('start');
		},
	},
	template: `
		<Transition
			name="intranet-user-mini-profile-collapse"
			@enter="onEnter"
			@beforeLeave="onBeforeLeave"
			@afterEnter="this.$emit('end')"
			@afterLeave="this.$emit('end')"
			@beforeEnter="this.$emit('start')"
		>
			<slot></slot>
		</Transition>
	`,
};
