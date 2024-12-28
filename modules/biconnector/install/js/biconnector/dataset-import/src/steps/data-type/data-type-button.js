import { DataTypeMenu } from './data-type-menu';
import { DataTypeDescriptions } from '../../types/data-types';

export const DataTypeButton = {
	props: {
		selectedType: {
			type: String,
			required: false,
			default: 'text',
		},
		isEditMode: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	emits: [
		'valueChange',
	],
	data()
	{
		return {
			typeMenu: null,
		};
	},
	computed: {
		iconClass()
		{
			return DataTypeDescriptions[this.selectedType].icon;
		},
	},
	methods: {
		onClick(event)
		{
			this.typeMenu?.show();
		},
		createMenu()
		{
			if (!this.isEditMode)
			{
				this.typeMenu = (new DataTypeMenu({
					selectedType: this.selectedType,
					bindElement: this.$refs.formatButton,
					onSelect: (selectedType) => {
						this.$emit('valueChange', selectedType);
					},
				})).getMenu();
			}
		},
		destroyMenu()
		{
			this.typeMenu?.destroy();
		},
	},
	mounted()
	{
		this.createMenu();
	},
	beforeUnmount()
	{
		this.destroyMenu();
	},
	watch: {
		selectedType(newValue)
		{
			this.destroyMenu();
			this.createMenu();
		},
	},
	// language=Vue
	template: `
		<div class="format-table__type-button" :class="{ 'format-table__type-button--disabled': isEditMode }" ref="formatButton" @click="onClick">
			<div class="ui-icon-set" :class="iconClass"></div>
			<div class="format-table__dropdown-chevron"></div>
		</div>
	`,
};
