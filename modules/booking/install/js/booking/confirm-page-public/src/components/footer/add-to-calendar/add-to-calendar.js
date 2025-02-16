import './add-to-calendar.css';

export const AddToCalendar = {
	name: 'AddToCalendar',
	props: {
		booking: {
			type: Object,
			required: true,
		},
	},
	data(): Object
	{
		return {};
	},
	template: `
		<div></div>
	`,
};
