import HistoryItem from "../items/history";

export default {
	props: {
		self: {
			required: true,
			type: Object
		},
		langMessages: {
			required: false,
			type: Object
		},
	},
	computed: {
		data()
		{
			return this.self._data;
		},
		fields()
		{
			return this.data.FIELDS ? this.data.FIELDS : null;
		},
		author()
		{
			return this.data.AUTHOR ? this.data.AUTHOR : null;
		},
		createdAt()
		{
			return (this.self instanceof HistoryItem) ? this.self.formatTime(this.self.getCreatedTime()) : '';
		},
	},
	methods: {
		getLangMessage(key)
		{
			return this.langMessages.hasOwnProperty(key) ? this.langMessages[key] : key;
		}
	}
};
