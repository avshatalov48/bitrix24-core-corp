import { ColorSelector as ColorSelectorPopup, ColorSelectorEvents } from 'crm.field.color-selector';
import { EventEmitter } from 'main.core.events';
import { hint } from 'ui.vue3.directives.hint';
import { Action } from '../../../action';

export const ColorSelector = {
	directives: { hint },

	props: {
		valuesList: {
			type: Object,
			required: true,
		},
		selectedValueId: {
			type: String,
			default: 'default',
		},
		readOnlyMode: {
			type: Boolean,
			required: false,
			default: false,
		},
	},

	data(): Object
	{
		return {
			currentValueId: this.selectedValueId,
		};
	},

	methods: {
		getValue(): Array
		{
			return this.currentValueId;
		},
		setValue(value: string): void
		{
			this.currentValueId = value;

			if (this.itemSelector)
			{
				this.itemSelector.setValue(value);
			}
		},
		onItemSelectorValueChange({ data }): void
		{
			const valueId = data.value;

			if (this.currentValueId !== valueId)
			{
				this.currentValueId = valueId;

				this.emitEvent('ColorSelector:Change', { colorId: valueId });
			}
		},

		emitEvent(eventName: string, actionParams: Object): void
		{
			const action = new Action({
				type: 'jsEvent',
				value: eventName,
				actionParams,
			});

			action.execute(this);
		},
	},

	mounted(): void
	{
		void this.$nextTick(() => {
			this.itemSelector = new ColorSelectorPopup({
				target: this.$refs.itemSelectorRef,
				colorList: this.valuesList,
				selectedColorId: this.currentValueId,
				readOnlyMode: this.readOnlyMode,
			});

			if (!this.readOnlyMode)
			{
				EventEmitter.subscribe(
					this.itemSelector,
					ColorSelectorEvents.EVENT_COLORSELECTOR_VALUE_CHANGE,
					this.onItemSelectorValueChange,
				);
			}
		});
	},

	computed: {
		hint(): ?Object
		{
			if (this.readOnlyMode)
			{
				return null;
			}

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

	template: `
		<div class="crm-activity__todo-editor-v2_color-selector">
			<div ref="itemSelectorRef" v-hint="hint"></div>
		</div>
	`,
};
