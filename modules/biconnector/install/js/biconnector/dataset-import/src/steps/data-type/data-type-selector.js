import { DataTypeMenu } from './data-type-menu';
import { DataTypeDescriptions } from '../../types/data-types';

export const DataTypeSelector = {
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
			isFocused: false,
		};
	},
	computed: {
		iconClass()
		{
			return DataTypeDescriptions[this.selectedType].icon;
		},
		typeTitle()
		{
			return DataTypeDescriptions[this.selectedType].title;
		},
	},
	methods: {
		onClick(event)
		{
			if (this.isEditMode)
			{
				return;
			}

			this.isFocused = true;

			if (!this.typeMenu)
			{
				return;
			}

			this.typeMenu.getPopupWindow().setWidth(this.$refs.formatButton.offsetWidth);
			this.typeMenu.show();
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
					onClose: () => {
						this.isFocused = false;
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
		<div
			class="ui-ctl ui-ctl-before-icon ui-ctl-after-icon ui-ctl-w100 format-table__type-control ui-ctl-dropdown"
			ref="formatButton"
			@click="onClick"
			:class="{
				'format-table__type-control--disabled': isEditMode,
				'ui-ctl-hover': isFocused,
			}"
		>
			<div class="ui-ctl-after ui-ctl-icon-angle" v-if="!isEditMode"></div>
			<div class="ui-ctl-before">
				<div class="format-table__type-button" :class="{ 'format-table__type-button--disabled': isEditMode }">
					<div class="ui-icon-set" :class="iconClass"></div>
				</div>
			</div>
			<div class="ui-ctl-element format-table__text-input">
				<span>{{ typeTitle }}</span>
			</div>
		</div>
	`,
};
