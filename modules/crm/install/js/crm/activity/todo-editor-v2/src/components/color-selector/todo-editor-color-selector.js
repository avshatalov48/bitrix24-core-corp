import { ColorSelector, ColorSelectorEvents } from 'crm.field.color-selector';
import { EventEmitter } from 'main.core.events';
import { hint } from 'ui.vue3.directives.hint';

const DEFAULT_COLOR_ID = 'default';

export const TodoEditorColorSelector = {
	directives: { hint },
	emits: ['onChange'],
	props: {
		valuesList: {
			type: Object,
			required: true,
		},
		selectedValueId: {
			type: String,
			default: DEFAULT_COLOR_ID,
		},
	},

	data(): Object
	{
		return {
			currentValueId: this.selectedValueId || DEFAULT_COLOR_ID,
		};
	},

	methods: {
		getValue(): Array
		{
			return this.currentValueId;
		},
		setValue(value: ?string): void
		{
			if (value === null)
			{
				this.resetToDefault();

				return;
			}

			this.currentValueId = value;
			this.itemSelector?.setValue(value);
		},
		onColorSelectorValueChange({ data }): void
		{
			this.currentValueId = data.value;

			this.$emit('onChange');
		},
		resetToDefault(): void
		{
			this.setValue(DEFAULT_COLOR_ID);
			this.itemSelector?.setValue(DEFAULT_COLOR_ID);
		},
	},

	computed: {
		hint(): Object
		{
			return {
				text: this.$Bitrix.Loc.getMessage('CRM_ACTIVITY_TODO_COLOR_SELECTOR_HINT'),
				popupOptions: {
					angle: {
						offset: 30,
						position: 'top',
					},
					offsetTop: 2,
				},
			};
		},
	},

	mounted(): void
	{
		void this.$nextTick(() => {
			this.itemSelector = new ColorSelector({
				target: this.$refs.itemSelectorRef,
				colorList: this.valuesList,
				selectedColorId: this.currentValueId,
			});

			EventEmitter.subscribe(
				this.itemSelector,
				ColorSelectorEvents.EVENT_COLORSELECTOR_VALUE_CHANGE,
				this.onColorSelectorValueChange,
			);
		});
	},

	template: `
		<div class="crm-activity__todo-editor-v2_color-selector" ref="itemSelectorRef" v-hint="hint"></div>
	`,
};
