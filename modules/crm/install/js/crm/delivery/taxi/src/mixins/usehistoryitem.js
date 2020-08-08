import UseLocalize from './uselocalize';
import UseAuthor from './useauthor';

export default {
	mixins: [UseLocalize, UseAuthor],
	props: {
		self: {
			required: true,
			type: Object
		},
		createdAt: {
			required: true,
			type: String
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
	}
};
