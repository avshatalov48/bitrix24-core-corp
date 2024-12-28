import { Loader } from 'ui.loader';
import './loader.css';

const LOADER_SIZE = 'xs';
const LOADER_TYPE = 'BULLET';

// @vue/component
export const CallLoader = {
	name: 'CallLoader',
	props:
	{
		isLight: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	computed: {
		loaderStyle(): boolean
		{
			return this.isLight ? 'rgba(255, 255, 255, 0.24)' : '';
		},
	},
	mounted()
	{
		this.loader = new Loader({
			target: this.$refs['call-loader'],
			type: LOADER_TYPE,
			size: LOADER_SIZE,
			color: this.loaderStyle,
		});
		this.loader.render();
		this.loader.show();
	},
	beforeUnmount()
	{
		this.loader.hide();
		this.loader = null;
	},
	template: `
		<div class="bx-call-elements-loader__container" ref="call-loader"></div>
	`,
};
