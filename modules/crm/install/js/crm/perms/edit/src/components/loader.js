import '../css/loader.css';
import { Loader as BXLoader } from 'ui.loader';

export const Loader = {
	name: 'Loader',
	mounted() {
		const loader = new BXLoader({
			target: this.$refs.loaderRef,
			type: 'BULLET',
			size: 'XL',
		});
		loader.render();
		loader.show();
	},
	template: `
		<div>
			<div class="bx-crm-perms-edit-loader"></div>
			<div class="bx-crm-perms-edit-loader-progress" ref="loaderRef"></div>
		</div>
	`,
};
