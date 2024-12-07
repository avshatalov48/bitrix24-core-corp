export default {
	props: {
		author: {
			required: true,
			type: Object
		},
	},
	computed: {
		linkStyle()
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
	template: `
		<a
			v-if="author.SHOW_URL"
			:href="author.SHOW_URL"
			:style="linkStyle"
			class="crm-entity-stream-content-detail-employee">	
		</a>
	`
};
