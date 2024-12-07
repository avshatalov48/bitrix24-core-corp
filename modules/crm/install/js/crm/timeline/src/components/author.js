export default {
	props: {
		author: {
			required: true,
			type: Object
		},
	},
	computed: {
		iStyle()
		{
			if (!this.author.IMAGE_URL)
			{
				return {};
			}

			return {
				'background-image': 'url(' + encodeURI(this.author.IMAGE_URL) + ')',
				'background-size': '21px',
			};
		},
	},
	// language=Vue
	template: `
		<a
			v-if="author.SHOW_URL"
			:href="author.SHOW_URL"
			target="_blank"
			:title="author.FORMATTED_NAME"
			class="ui-icon ui-icon-common-user crm-entity-stream-content-detail-employee"
		>
			<i :style="iStyle"></i>	
		</a>
	`
};
