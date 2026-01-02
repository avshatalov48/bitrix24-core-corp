import { Event } from 'main.core';
import { mapGetters } from 'ui.vue3.vuex';
import { BottomSheet } from 'tasks.v2.component.elements.bottom-sheet';
import { Model } from 'tasks.v2.const';

// @vue/component
export const CheckListSheet = {
	name: 'TaskCheckListSheet',
	components: {
		BottomSheet,
	},
	props: {
		isShown: {
			type: Boolean,
			required: true,
		},
		getBindElement: {
			type: Function,
			default: null,
		},
		getTargetContainer: {
			type: Function,
			default: null,
		},
	},
	emits: ['show', 'close', 'isShown'],
	computed: {
		...mapGetters({
			titleFieldOffsetHeight: `${Model.Interface}/titleFieldOffsetHeight`,
		}),
	},
	watch: {
		titleFieldOffsetHeight(): void
		{
			this.$refs.childComponent?.adjustPosition();
		},
		async isShown(value): void
		{
			await this.$nextTick();

			if (value === true)
			{
				Event.bind(document, 'keydown', this.handleKeyDown, { capture: true });
			}
			else
			{
				Event.unbind(document, 'keydown', this.handleKeyDown, { capture: true });
			}

			this.$emit('isShown', value);
		},
	},
	methods: {
		handleShow(): void
		{
			this.$emit('show');
		},
		handleClose(): void
		{
			this.$emit('close');
		},
		handleKeyDown(event: KeyboardEvent): void
		{
			if (event.key === 'Escape')
			{
				this.handleClose();

				event.stopPropagation();
			}
		},
	},
	template: `
		<BottomSheet
			v-if="isShown"
			:padding="0"
			:getBindElement="getBindElement"
			:getTargetContainer="getTargetContainer"
			ref="childComponent"
		>
			<slot
				:handleShow="handleShow"
				:handleClose="handleClose"
			></slot>
		</BottomSheet>
	`,
};
