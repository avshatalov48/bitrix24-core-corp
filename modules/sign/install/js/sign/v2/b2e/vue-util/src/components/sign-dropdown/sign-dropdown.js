import { SignDropdown } from 'sign.v2.b2e.sign-dropdown';
import { Type, Dom } from 'main.core';

// @vue/component
export const SignDropdownComponent = {
	name: 'SignDropdownComponent',
	props: {
		tabs: {
			type: Array,
			required: true,
			default: () => [],
		},
		entities: {
			type: Array,
			required: true,
			default: () => [],
		},
		items: {
			type: Array,
			required: true,
			default: () => [],
		},
		isEnableSearch: {
			type: Boolean,
			required: false,
			default: false,
		},
		isWithCaption: {
			type: Boolean,
			required: false,
			default: false,
		},
		className: {
			type: String,
			required: false,
			default: 'sign-b2e-document-setup__type-selector',
		},
		// eslint-disable-next-line vue/require-prop-types
		selectedId: {
			required: false,
			default: null,
		},
	},
	emits: ['onSelected'],

	watch: {
		items(value: Array<{ id: number | string, title: string, caption?: string }>): void
		{
			this.setItems(value);
		},
		selectedId(value: number | string): void
		{
			if (this.dropdown.getSelectedId() !== value)
			{
				this.dropdown.selectItem(value);
			}
		},
	},

	created(): void
	{
		this.dropdown = new SignDropdown({
			tabs: this.tabs,
			entities: this.entities,
			className: this.className,
			withCaption: this.isWithCaption,
			isEnableSearch: this.isEnableSearch,
		});

		this.setItems(this.items);
	},

	mounted(): void
	{
		this.dropdown.subscribe('onSelect', (event) => this.onSelected(event.data.item));

		const dropdownContainer = this.$refs.wrapper;
		if (dropdownContainer)
		{
			Dom.append(this.dropdown.getLayout(), dropdownContainer);
		}
	},

	methods: {
		setItems(items: Array<{ id: number | string, title: string, caption?: string }>): void
		{
			const dropdown = this.dropdown;
			dropdown.removeItems();

			const filledItems = items.filter((item) => {
				return Type.isPlainObject(item)
					&& Type.isStringFilled(String(item.id))
					&& Type.isStringFilled(item.title)
				;
			});

			filledItems.forEach((item) => {
				const { id, title, caption, entityId, tabId } = item;
				dropdown.addItem({
					id,
					title,
					caption,
					entityId,
					tabs: tabId,
					deselectable: false,
				});
			});

			if (this.selectedId)
			{
				dropdown.selectItem(this.selectedId);
			}
		},

		onSelected(item: { id: number | string, title: string, caption?: string }): void
		{
			this.$emit('onSelected', item);
		},
	},

	template: `
		<div class="sign-b2e-sign-dropdown-wrapper" ref="wrapper"></div>
	`,
};
