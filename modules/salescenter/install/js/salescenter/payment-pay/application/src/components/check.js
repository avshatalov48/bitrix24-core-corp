export default {
	props: {
		status: {
			type: String,
			default: '',
			required: false,
		},
		link: {
			type: String,
			default: '',
			required: false,
		},
		title: {
			type: String,
			required: true,
		},
	},
	computed: {
		processing() {
			return this.status === 'P';
		},
		downloadable() {
			return this.status === 'Y' && this.link !== '';
		},
	},
	// language=Vue
	template: `
		<div class="mb-2" :class="{'check-print': processing}">
			<a :href="link" target="_blank" class="check-link" v-if="downloadable">{{ title }}</a>
			<span v-else>{{ title }}</span>
		</div>
	`,
};