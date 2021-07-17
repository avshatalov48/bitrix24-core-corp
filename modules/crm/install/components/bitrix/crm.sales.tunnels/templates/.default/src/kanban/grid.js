import {Dom, Event} from 'main.core';
import {Kanban} from 'main.kanban';
import createStub from './internal/create-stub';
import Marker from '../marker/marker';

export default class Grid extends Kanban.Grid
{
	emitter = new Event.EventEmitter();

	adjustLayout() {}
	adjustHeight() {}

	getEmptyStub()
	{
		return createStub();
	}

	getDropZoneArea()
	{
		const area = super.getDropZoneArea();
		Dom.addClass(area.getContainer(), 'crm-st-kanban-stub');
		return area;
	}

	getGridContainer()
	{
		const container = super.getGridContainer();
		Dom.addClass(container, 'crm-st-kanban-grid');
		return container;
	}

	getInnerContainer(): HTMLElement
	{
		const container = super.getInnerContainer();
		Dom.addClass(container, 'crm-st-kanban-inner');
		return container;
	}

	getOuterContainer(): HTMLElement
	{
		const container = super.getOuterContainer();
		Dom.addClass(container, 'crm-st-kanban');
		return container;
	}

	getLeftEar(): HTMLElement
	{
		return createStub();
	}

	getRightEar(): HTMLElement
	{
		return createStub();
	}

	onColumnDragStart(column)
	{
		super.onColumnDragStart(column);
		Marker.adjustLinks();
	}

	onColumnDragStop(column)
	{
		super.onColumnDragStop(column);
		this.emitter.emit('Kanban.Grid:columns:sort');

		setTimeout(() => {
			Marker.adjustLinks();
		});

		this.getColumns().forEach((column) => {
			clearInterval(column.intervalId);
		});
	}

	getColumns(): Array<Column> {
		this.columnsOrder.sort((a, b) => {
			if (a.getContainer().parentNode)
			{
				const aIndex = [...a.getContainer().parentNode.children].indexOf(a.getContainer());
				const bIndex = [...b.getContainer().parentNode.children].indexOf(b.getContainer());

				return aIndex > bIndex ? 1 : -1;
			}
		});

		return this.columnsOrder;
	}
}