import UseLocalize from './uselocalize';
import UseAuthor from './useauthor';

export default {
	mixins: [UseLocalize, UseAuthor],
	props: {
		self: {
			required: true,
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
		createdAt()
		{
			return (this.self instanceof BX.CrmHistoryItem) ? this.self.formatTime(this.self.getCreatedTime()) : '';
		},
	}
};
