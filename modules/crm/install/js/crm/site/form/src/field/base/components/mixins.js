import * as Common from '../components/common';

let MixinField = {
	props: ['field'],
	components: Object.assign(
		{},
		Common.Definition
	),
	computed: {
		selected: {
			get: function () {
				return this.field.multiple
					? this.field.values()
					: this.field.values()[0];
			},
			set: function (newValue) {
				this.field.items.forEach(item => {
					item.selected = Array.isArray(newValue)
						? newValue.includes(item.value)
						: newValue === item.value
				});
			}
		},
	},
	methods: {
		controlClasses()
		{
			//b24-form-control-checked
		}
	},
};


let MixinDropDown = {
	components: {
		'field-item-dropdown': Common.Dropdown,
	},
	data: function () {
		return {
			dropDownOpened: false,
		};
	},
	methods: {
		toggleDropDown()
		{
			if (this.dropDownOpened)
			{
				this.closeDropDown();
			}
			else
			{
				this.dropDownOpened = true;
			}
		},
		closeDropDown()
		{
			setTimeout(() => {
				this.dropDownOpened = false;
			}, 0);
		},
	},
};

export {
	MixinField,
	MixinDropDown,
}
