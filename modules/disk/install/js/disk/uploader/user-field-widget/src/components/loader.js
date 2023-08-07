import { Runtime } from 'main.core';

import type { BitrixVueComponentProps } from 'ui.vue3';

export const Loader: BitrixVueComponentProps = {
	name: 'Loader',
	props: {
		size: {
			type: Number,
			default: 70,
		},
		color: {
			type: String,
			default: '#2fc6f6',
		},
		offset: {
			type: Object,
			default: null,
		},
		mode: {
			type: String,
			default: '',
		},
	},
	created(): void
	{
		this.loader = null;
	},
	mounted(): void
	{
		Runtime.loadExtension('main.loader').then((exports): void => {
			const { Loader } = exports;
			this.loader = new Loader({
				target: this.$refs.container,
				size: this.size,
				color: this.color,
				offset: this.offset,
				mode: this.mode,
			});

			this.loader.show();
		});
	},
	beforeUnmount(): void
	{
		if (this.loader)
		{
			this.loader.destroy();
			this.loader = null;
		}
	},
	template: `<span ref="container"></span>`
};
