import { Dom, Loc, Runtime } from 'main.core';
import { Popup, PopupManager } from 'main.popup';
import { DateTimeFormat } from 'main.date';
import { Draggable, DragMoveEvent, DragStartEvent } from 'ui.draganddrop.draggable';

import { Model } from 'booking.const';
import { Core } from 'booking.core';
import { busySlots } from 'booking.lib.busy-slots';
import { bookingService } from 'booking.provider.service.booking-service';
import type { BookingModel } from 'booking.model.bookings';

type Params = {
	draggable: string,
	container: HTMLElement,
};

export class Drag
{
	#params: Params;
	#dragManager: Draggable;

	constructor(params: Params)
	{
		this.#params = params;

		this.#dragManager = new Draggable({
			container: this.#params.container,
			draggable: this.#params.draggable,
			elementsPreventingDrag: ['.booking-booking-resize'],
			delay: 200,
		});

		this.#dragManager.subscribe('start', this.#onDragStart.bind(this));
		this.#dragManager.subscribe('move', this.#onDragMove.bind(this));
		this.#dragManager.subscribe('end', this.#onDragEnd.bind(this));
	}

	destroy(): void
	{
		this.#dragManager.destroy();
	}

	async #onDragStart(event: DragStartEvent): Promise<void>
	{
		const { draggable, source: { dataset }, clientX, clientY } = event.getData();

		await Promise.all([
			Core.getStore().dispatch(`${Model.Interface}/setDraggedBookingId`, Number(dataset.id)),
			Core.getStore().dispatch(`${Model.Interface}/setDraggedBookingResourceId`, Number(dataset.resourceId)),
		]);

		Dom.style(draggable, 'pointer-events', 'none');
		this.#getAdditionalBookingElements(this.#params.container).forEach((element) => {
			const clone = Runtime.clone(element);
			draggable.append(clone);

			const translateX = element.getBoundingClientRect().left - draggable.getBoundingClientRect().left;
			const translateY = element.getBoundingClientRect().top - draggable.getBoundingClientRect().top;
			Dom.style(clone, 'transition', 'none');
			Dom.style(clone, 'transform', `translate(${translateX}px, ${translateY}px)`);
			Dom.style(clone, 'animation', 'none');
		});

		this.#getBookingElements(this.#params.container).forEach((element) => {
			if (draggable.contains(element))
			{
				return;
			}

			Dom.addClass(element, '--drag-source');
			Dom.style(element, 'visibility', 'visible');
		});

		const transformOriginX = clientX - draggable.getBoundingClientRect().left;
		const transformOriginY = clientY - draggable.getBoundingClientRect().top;
		this.#getBookingElements(draggable).forEach((clone) => {
			Dom.style(clone, 'transform-origin', `${transformOriginX}px ${transformOriginY}px`);
		});

		PopupManager.getPopups().forEach((popup: Popup) => popup.close());

		void busySlots.loadBusySlots();
	}

	#onDragMove(event: DragMoveEvent): void
	{
		const { draggable, clientX, clientY } = event.getData();

		this.#getAdditionalBookingElements(draggable).forEach((clone, index) => {
			Dom.style(clone, 'transition', '');
			Dom.style(clone, 'transform', `rotate(${index === 1 ? 4 : 0}deg)`);
			Dom.style(clone, 'zIndex', `-${index + 1}`);
		});

		if (this.#isDragDeleteHovered(clientX, clientY))
		{
			Dom.addClass(draggable, '--deleting');
		}
		else
		{
			Dom.removeClass(draggable, '--deleting');
		}

		draggable.querySelectorAll('[data-element="booking-booking-time"]').forEach((time) => {
			time.innerText = this.#timeFormatted;
		});

		this.#updateScroll(draggable, clientX, clientY);
	}

	async #onDragEnd(): Promise<void>
	{
		clearInterval(this.scrollTimeout);

		this.#getBookingElements(this.#params.container).forEach((element) => {
			Dom.removeClass(element, '--drag-source');
			Dom.style(element, 'visibility', '');
		});

		if (this.#hoveredCell)
		{
			this.#moveBooking({
				booking: this.#draggedBooking,
				resourceId: this.#draggedBookingResourceId,
				cell: this.#hoveredCell,
			});
		}

		await Core.getStore().dispatch(`${Model.Interface}/setDraggedBookingId`, null);

		void busySlots.loadBusySlots();
	}

	#getBookingElements(container: HTMLElement): HTMLElement[]
	{
		const element = 'booking-booking';
		const id = this.#draggedBookingId;

		return [...container
			.querySelectorAll(`[data-element="${element}"][data-id="${id}"]`),
		];
	}

	#getAdditionalBookingElements(container: HTMLElement): HTMLElement[]
	{
		const element = 'booking-booking';
		const id = this.#draggedBookingId;
		const resourceId = this.#draggedBookingResourceId;

		return [...container
			.querySelectorAll(`[data-element="${element}"][data-id="${id}"]:not([data-resource-id="${resourceId}"])`),
		];
	}

	#updateScroll(draggable: HTMLElement, x: number, y: number): void
	{
		clearTimeout(this.scrollTimeout);
		if (this.#isDragDeleteHovered(x, y))
		{
			return;
		}

		const gridRect = this.#gridWrap.getBoundingClientRect();
		const draggableRect = draggable.getBoundingClientRect();

		this.scrollTimeout = setTimeout(() => this.#updateScroll(draggable), 16);

		if (draggableRect.left < gridRect.left)
		{
			this.#gridColumns.scrollLeft -= this.#getSpeed(draggableRect.left, gridRect.left);
		}
		else if (draggableRect.right > gridRect.right)
		{
			this.#gridColumns.scrollLeft += this.#getSpeed(draggableRect.right, gridRect.right);
		}
		else if (draggableRect.top < gridRect.top)
		{
			this.#gridWrap.scrollTop -= this.#getSpeed(draggableRect.top, gridRect.top);
		}
		else if (draggableRect.bottom > gridRect.bottom)
		{
			this.#gridWrap.scrollTop += 2 * this.#getSpeed(draggableRect.bottom, gridRect.bottom);
		}
		else
		{
			clearTimeout(this.scrollTimeout);
		}
	}

	#getSpeed(a: number, b: number): number
	{
		return (Math.floor(Math.sqrt(Math.abs(a - b))) + 1) / 2;
	}

	#isDragDeleteHovered(x: number, y: number): boolean
	{
		if (!x || !y)
		{
			return false;
		}

		return document.elementFromPoint(x, y)?.closest('[data-element="booking-drag-delete"]');
	}

	#moveBooking({ booking, resourceId, cell }: { booking: BookingModel, resourceId: number, cell: CellDto }): void
	{
		if (cell.fromTs === booking.dateFromTs && cell.toTs === booking.dateToTs && cell.resourceId === resourceId)
		{
			return;
		}

		const additionalResourcesIds = booking.resourcesIds.includes(cell.resourceId)
			? booking.resourcesIds
			: booking.resourcesIds.filter((id: number) => id !== resourceId)
		;

		void bookingService.update({
			id: booking.id,
			dateFromTs: cell.fromTs,
			dateToTs: cell.toTs,
			resourcesIds: [...new Set([
				cell.resourceId,
				...additionalResourcesIds,
			])],
			timezoneFrom: booking.timezoneFrom,
			timezoneTo: booking.timezoneTo,
		});
	}

	get #timeFormatted(): string
	{
		const timeFormat = DateTimeFormat.getFormat('SHORT_TIME_FORMAT');
		const from = this.#hoveredCell?.fromTs ?? this.#draggedBooking.dateFromTs;
		const to = this.#hoveredCell?.toTs ?? this.#draggedBooking.dateToTs;

		return Loc.getMessage('BOOKING_BOOKING_TIME_RANGE', {
			'#FROM#': DateTimeFormat.format(timeFormat, (from + this.#offset) / 1000),
			'#TO#': DateTimeFormat.format(timeFormat, (to + this.#offset) / 1000),
		});
	}

	get #draggedBooking(): BookingModel | null
	{
		return Core.getStore().getters[`${Model.Bookings}/getById`](this.#draggedBookingId) ?? null;
	}

	get #draggedBookingId(): number
	{
		return Core.getStore().getters[`${Model.Interface}/draggedBookingId`];
	}

	get #draggedBookingResourceId(): number
	{
		return Core.getStore().getters[`${Model.Interface}/draggedBookingResourceId`];
	}

	get #hoveredCell(): CellDto
	{
		return Core.getStore().getters[`${Model.Interface}/hoveredCell`];
	}

	get #offset(): number
	{
		return Core.getStore().getters[`${Model.Interface}/offset`];
	}

	get #gridWrap(): HTMLElement
	{
		return BX('booking-booking-grid-wrap');
	}

	get #gridColumns(): HTMLElement
	{
		return BX('booking-booking-grid-columns');
	}
}
