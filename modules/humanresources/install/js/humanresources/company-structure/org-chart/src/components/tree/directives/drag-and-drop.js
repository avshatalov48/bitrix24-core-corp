import { TreeNodeDragController } from './classes/drag-and-drop-controller';

export const DragAndDrop = {
	mounted(el: HTMLElement): void
	{
		new TreeNodeDragController(el);
	},
	updated(el: HTMLElement, { value }: Object): void
	{
		const { x, y, zoom } = value;
		el.setAttribute('data-zoom', zoom);
		el.setAttribute('data-x', x);
		el.setAttribute('data-y', y);
	},
};
