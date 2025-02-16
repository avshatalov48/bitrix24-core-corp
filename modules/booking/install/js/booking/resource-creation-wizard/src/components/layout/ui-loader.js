import { Loader } from 'main.loader';

export const UiLoader = {
	name: 'UiLoader',
	props: {
		show: Boolean,
	},
	data(): { loader: Loader }
	{
		return {
			loader: new Loader(),
		};
	},
	mounted(): void
	{
		void this.loader.show(this.$refs.loader);
	},
	template: `
		<div ref="loader"></div>
	`,
};
