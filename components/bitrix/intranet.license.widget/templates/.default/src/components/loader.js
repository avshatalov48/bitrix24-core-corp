import {Loader} from "main.loader";

export const LoaderComponent = {
	props: {
		size: {
			type: Number,
			default: 85,
		},
	},
	template: `
		<div></div>
	`,
	mounted()
	{
		this.loader = new Loader({
			target: this.$el,
			size: this.size,
		});
		this.loader.show();
	},
	beforeDestroy()
	{
		this.loader.destroy();
	},
};