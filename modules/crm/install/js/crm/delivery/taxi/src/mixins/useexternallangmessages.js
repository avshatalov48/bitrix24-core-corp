export default {
	props: {
		langMessages:
			{
				required: false,
				type: Object
			}
	},
	methods: {
		getLangMessage(key)
		{
			return this.langMessages.hasOwnProperty(key) ? this.langMessages[key] : key;
		}
	}
};
