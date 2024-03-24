import '../css/loader.css';

export const Loader = {
	name: 'Loader',
	data() {
		return {
			loaderInstance: null,
		};
	},
	template: `
		<div ref="root" class="bx-crm-ai-merge-fields-loading">
			<div class="bx-crm-ai-merge-fields-loading__image"></div>
		</div>
	`,
};
