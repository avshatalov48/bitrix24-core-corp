import sharingSlotsListItem from "./sharing-slots-list-item";

export default {
	props: {
		listItems: {
			type: Array,
			required: true,
			default: [],
		},
		isEditable: {
			type: Boolean,
			required: true,
			default: false,
		},
	},
	components: {
		sharingSlotsListItem,
	},
	template: `
		<div>
			<sharingSlotsListItem
				v-for="item in listItems"
				v-bind="item.properties"
				:is-editable="isEditable"
			/>
		</div>
	`,
};