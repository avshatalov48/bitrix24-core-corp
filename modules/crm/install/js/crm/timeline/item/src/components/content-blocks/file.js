import { FileIcon } from 'ui.icons.generator';

export default {
	props: {
		text: String,
		href: String,
		size: Number,
		attributes: Object
	},

	computed: {
		fileExtension() {
			return this.text.split('.').slice(-1)[0] || '';
		},
	},

	mounted() {
		const fileIcon = new FileIcon({
			name: this.fileExtension,
		});

		fileIcon.renderTo(this.$refs.icon);
	},
	template:
		`
			<div class="crm-timeline__file">
				<div ref="icon" class="crm-timeline__file_icon"></div>
				<a
					target="_blank"
					class="crm-timeline__card_link"
					v-if="href"
					:title="text"
					:href="href"
					v-bind="attributes"
				>
					{{text}}
				</a>
			</div>
		`
};
