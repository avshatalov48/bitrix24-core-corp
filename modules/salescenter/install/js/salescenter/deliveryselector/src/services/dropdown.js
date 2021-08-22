import {Menu} from 'main.popup'

export default {
	props: {
		name: {required: false},
		initValue: {required: false},
		options: {required: true, type: Array}
	},
	created()
	{
		this.value = this.initValue;
	},
	data()
	{
		return {
			value: null
		}
	},
	methods: {
		getSelectedItemTitle()
		{
			let selectedItem = this.getSelectedItem();
			if (!selectedItem || selectedItem.id === 'null')
			{
				return this.name;
			}
			return selectedItem.title;
		},
		getSelectedItem()
		{
			for (let option of this.options)
			{
				if (option.id === this.value)
				{
					return option;
				}
			}

			return null;
		},

		showPopupMenu(e)
		{
			let menuItems = [];
			for (let option of this.options)
			{
				menuItems.push(
					{
						'text': option.title,
						onclick: () => {
							this.value =  option.id;
							this.$emit('change', this.value);

							this.popupMenu.close();
						}
					}
				);
			}

			this.popupMenu = new Menu({
				bindElement: e.target,
				items: menuItems,
				angle: true,
				closeByEsc: true,
				offsetLeft: 40,
			});

			this.popupMenu.show();
		}
	},
	template: `
		<div @click="showPopupMenu($event)" class="salescenter-delivery-selector salescenter-delivery-selector--dropdown">
			<span class="salescenter-delivery-selector-text">{{getSelectedItemTitle()}}</span>
		</div>
	`
};
