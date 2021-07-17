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
				'background-image': 'url(' + this.author.IMAGE_URL + ')',
				'background-size': '21px',
			};
		},
	},
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
