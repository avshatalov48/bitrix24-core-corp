import { DialogWrapperComponent } from '../common/dialog-wrapper-component';

export const StageSelector = {
	components: {
		DialogWrapperComponent,
	},

	props: {
		category: {
			type: Object,
		},
		currentStageId: {
			type: String,
			required: true,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},

	data(): Object
	{
		return {
			items: this.getPreparedItems(),
		};
	},

	created()
	{
		this.tabs = [
			{ id: 'stages', title: '' },
		];
	},

	methods: {
		destroy(): void
		{
			this.$refs.dialog.destroy();
		},
		getPreparedItems(setSelectedFirst: boolean = false): Array<Object>
		{
			const items = [];

			if (this.category === null)
			{
				return items;
			}

			let hasSelected = true;

			Object.values(this.category.items).forEach((item) => {
				items.push({
					id: item.id,
					title: item.name,
					entityId: 'stage',
					tabs: 'stages',
					selected: (setSelectedFirst ? hasSelected : item.id === this.currentStageId),
					avatarOptions: {
						bgColor: item.color,
						borderRadius: 6,
						bgSize: 20,
						bgImage: 'none',
					},
					customData: {
						color: item.color,
					},
				});

				hasSelected = false;
			});

			if (!items.some((item) => item.selected === true))
			{
				items.find(Boolean).selected = true;
			}

			return items;
		},
	},

	watch: {
		category(category: Object): void
		{
			this.items = this.getPreparedItems(true);
		},
		currentStageId(categoryId: string): void
		{
			this.items.forEach((item) => {
				// eslint-disable-next-line no-param-reassign
				item.selected = (item.id === categoryId);
			});
		},
	},

	// language=Vue
	template: `
		<DialogWrapperComponent
			ref="dialog"
			:items="items"
			:tabs="tabs"
			:showAvatars="true"
			:read-only="readOnly"
		/>
	`,
};
