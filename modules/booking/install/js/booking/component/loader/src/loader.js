import { Loader as UILoader } from 'ui.loader';
import './loader.css';

type BookingLoaderOptions = {
	target: HTMLElement,
	type: string,
	size: string,
}

export type BookingLoaderOptionsProp = Partial<BookingLoaderOptions>;

export const Loader = {
	name: 'BookingLoader',
	props: {
		options: {
			type: Object,
			default: null,
		},
	},
	methods: {
		getOptions(): BookingLoaderOptions
		{
			return { ...this.getDefaultOptions(), ...this.options };
		},
		getDefaultOptions(): BookingLoaderOptions
		{
			return {
				target: this.$refs.loader,
				type: 'BULLET',
				size: 'xs',
			};
		},
	},
	mounted(): void
	{
		this.loader = new UILoader(this.getOptions());
		this.loader.render();
		this.loader.show();
	},
	beforeUnmount(): void
	{
		this.loader?.hide?.();
		this.loader = null;
	},
	template: `
		<div class="booking-loader__container" ref="loader"></div>
	`,
};
