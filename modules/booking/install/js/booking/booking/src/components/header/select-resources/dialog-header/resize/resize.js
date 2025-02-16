import { Dom, Event } from 'main.core';
import './resize.css';

export const Resize = {
	emits: ['startResize', 'endResize'],
	props: {
		getNode: {
			type: Function,
			required: true,
		},
	},
	data(): Object
	{
		return {
			isResized: false,
			startMouseY: 0,
			startHeight: 0,
		};
	},
	methods: {
		startResize(event: MouseEvent): void
		{
			this.$emit('startResize');
			Dom.style(document.body, 'user-select', 'none');
			Event.bind(window, 'mouseup', this.endResize);
			Event.bind(window, 'pointermove', this.resize);
			this.isResized = true;
			this.startMouseY = event.clientY;
			this.startHeight = this.getNode().offsetHeight;
		},
		resize(event: MouseEvent): void
		{
			if (!this.isResized)
			{
				return;
			}

			event.preventDefault();

			const minHeight = 110;
			const maxHeight = 180;
			const height = this.startHeight + event.clientY - this.startMouseY;
			const newHeight = Math.min(maxHeight, Math.max(height, minHeight));

			Dom.style(this.getNode(), 'max-height', `${newHeight}px`);
		},
		endResize(): void
		{
			this.$emit('endResize');
			Dom.style(document.body, 'user-select', '');
			Event.unbind(window, 'mouseup', this.endResize);
			Event.unbind(window, 'pointermove', this.resize);
			this.isResized = false;
		},
	},
	template: `
		<div
			class="booking-booking-resources-dialog-header-resize"
			@mousedown="startResize"
		></div>
	`,
};
