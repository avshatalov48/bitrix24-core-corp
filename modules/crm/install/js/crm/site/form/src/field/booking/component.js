import * as Mixins from '../base/components/mixins';

// @vue/component
const BookingField = {
	name: 'BookingField',
	props: {
		field: {
			type: Object,
			required: true,
		},
	},
	data(): Object
	{
		return {
			component: null,
			dependencies: null,
		};
	},
	async mounted(): Promise<void>
	{
		const scriptLink = b24form.common?.properties?.booking?.link;
		if (!scriptLink)
		{
			return;
		}

		await new Promise((resolve) => {
			const node = document.createElement('script');
			node.src = `${scriptLink}?${Math.trunc(Date.now() / 60000)}`;
			node.onload = resolve;
			document.head.append(node);
		});

		this.component = BX.Booking.CrmForms.Field;
		this.dependencies = {
			mixinDropdown: Mixins.MixinDropDown,
			mixinField: Mixins.MixinField,
		};
	},
	methods: {
		onChange(value): void
		{
			const selected = true;

			this.field.items = [];
			if (value)
			{
				this.field.addItem({ value, selected });
			}
		},
		async runAction(action, options): Promise<any>
		{
			const formData = this.createFormData(options?.data ?? {});
			const host = this.$root.form.identification.address;
			const url = `${host}/bitrix/services/main/ajax.php?action=${action}`;

			const response = await window.b24form.App.post(url, formData);

			return response.json();
		},
		createFormData(data: { [key: string]: mixed }): FormData
		{
			if (data instanceof FormData)
			{
				return data;
			}

			const formData = new FormData();
			Object.keys(data).forEach((key) => {
				const value = data[key];
				if (Array.isArray(value))
				{
					value.forEach((it) => formData.append(`${key}[]`, it));
				}
				else
				{
					formData.set(key, JSON.stringify(value));
				}
			});

			return formData;
		},
	},
	template: `
		<div>
			<component
				v-if="component"
				v-bind="{ field, runAction, dependencies }"
				:is="component"
				@change="onChange"
			/>
		</div>
	`,
};

export { BookingField };
