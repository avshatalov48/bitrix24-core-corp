import { LocationType, WidgetMinimumSize } from "../const";
import { EventEmitter } from "main.core.events";
import { Type } from "main.core";

export class WidgetResizeHandler extends EventEmitter
{
	static events = {
		onSizeChange: 'onSizeChange',
		onStopResize: 'onStopResize'
	};

	isResizing: boolean = false;
	widgetLocation: number = null;
	availableWidth: number = null;
	availableHeight: number = null;

	constructor({widgetLocation, availableWidth, availableHeight, events})
	{
		super();
		this.setEventNamespace('BX.IMOL.WidgetResizeHandler');
		this.subscribeToEvents(events);

		this.widgetLocation = widgetLocation;
		this.availableWidth = availableWidth;
		this.availableHeight = availableHeight;
	}

	subscribeToEvents(configEvents)
	{
		const events = Type.isObject(configEvents) ? configEvents : {};
		Object.entries(events).forEach(([name, callback]) => {
			if (Type.isFunction(callback))
			{
				this.subscribe(name, callback);
			}
		});
	}

	startResize(event, currentHeight, currentWidth)
	{
		if (this.isResizing)
		{
			return false;
		}

		this.isResizing = true;
		event = event.changedTouches ? event.changedTouches[0] : event;

		this.cursorStartPointY = event.clientY;
		this.cursorStartPointX = event.clientX;
		this.heightStartPoint = currentHeight;
		this.widthStartPoint = currentWidth;

		this.addWidgetResizeEvents();
	}

	onContinueResize(event)
	{
		if (!this.isResizing)
		{
			return false;
		}

		event = event.changedTouches ? event.changedTouches[0] : event;

		this.cursorControlPointY = event.clientY;
		this.cursorControlPointX = event.clientX;

		const maxHeight = this.isBottomLocation()
			? Math.min(this.heightStartPoint + this.cursorStartPointY - this.cursorControlPointY, this.availableHeight)
			: Math.min(this.heightStartPoint - this.cursorStartPointY + this.cursorControlPointY, this.availableHeight)
		;
		const height = Math.max(maxHeight, WidgetMinimumSize.height);

		const maxWidth = this.isLeftLocation()
			? Math.min(this.widthStartPoint - this.cursorStartPointX + this.cursorControlPointX, this.availableWidth)
			: Math.min(this.widthStartPoint + this.cursorStartPointX - this.cursorControlPointX, this.availableWidth)
		;
		const width = Math.max(maxWidth, WidgetMinimumSize.width);

		this.emit(WidgetResizeHandler.events.onSizeChange, {newHeight: height, newWidth: width});
	}

	onStopResize()
	{
		if (!this.isResizing)
		{
			return false;
		}

		this.isResizing = false;
		this.removeWidgetResizeEvents();

		this.emit(WidgetResizeHandler.events.onStopResize);
	}

	setAvailableWidth(width)
	{
		this.availableWidth = width;
	}

	setAvailableHeight(height)
	{
		this.availableHeight = height;
	}

	addWidgetResizeEvents()
	{
		this.onContinueResizeHandler = this.onContinueResize.bind(this);
		this.onStopResizeHandler = this.onStopResize.bind(this);
		document.addEventListener('mousemove', this.onContinueResizeHandler);
		document.addEventListener('mouseup', this.onStopResizeHandler);
		document.addEventListener('mouseleave', this.onStopResizeHandler);
	}

	removeWidgetResizeEvents()
	{
		document.removeEventListener('mousemove', this.onContinueResizeHandler);
		document.removeEventListener('mouseup', this.onStopResizeHandler);
		document.removeEventListener('mouseleave', this.onStopResizeHandler);
	}

	isBottomLocation()
	{
		return [LocationType.bottomLeft, LocationType.bottomMiddle, LocationType.bottomRight].includes(this.widgetLocation);
	}

	isLeftLocation()
	{
		return [LocationType.bottomLeft, LocationType.topLeft, LocationType.topMiddle].includes(this.widgetLocation);
	}

	destroy()
	{
		this.removeWidgetResizeEvents();
	}
}