class MoveObserver
{
	element: Element;
	detecting: boolean = false;
	x: number = 0;
	y: number = 0;
	touch: Touch;
	deltaX: number = 0;
	deltaY: number = 0;
	handler: Function;
	listeners: Object;

	constructor (handler: Function, element: Element)
	{
		this.element = element;
		this.handler = handler;

		this.listeners = {
			start: this.onTouchStart.bind(this),
			move: this.onTouchMove.bind(this),
			end: this.onTouchEnd.bind(this),
		};
	}

	toggle (mode, element: Element)
	{
		if (element)
		{
			this.element = element;
		}

		mode ? this.run() : this.stop();
	}

	run ()
	{
		this.element.setAttribute('draggable', false);
		this.element.addEventListener('touchstart', this.listeners.start);
		this.element.addEventListener('touchmove', this.listeners.move);
		this.element.addEventListener('touchend', this.listeners.end);
		this.element.addEventListener('touchcancel', this.listeners.end);
	}

	stop ()
	{
		this.element.removeAttribute('draggable');
		this.element.removeEventListener('touchstart', this.listeners.start);
		this.element.removeEventListener('touchmove', this.listeners.move);
		this.element.removeEventListener('touchend', this.listeners.end);
		this.element.removeEventListener('touchcancel', this.listeners.end);
	}

	onTouchStart (e: TouchEvent)
	{
		if (e.touches.length !== 1 || this.detecting)
		{
			return;
		}

		let touch = e.changedTouches[0];
		this.detecting = true;
		this.x = touch.pageX;
		this.y = touch.pageY;
		this.deltaX = 0;
		this.deltaY = 0;
		this.touch = touch;
	}

	onTouchMove (e: TouchEvent)
	{
		if (!this.detecting)
		{
			return;
		}

		let touch = e.changedTouches[0];
		let newX = touch.pageX;
		let newY = touch.pageY;

		if (!this.hasTouch(e.changedTouches, touch))
		{
			return;
		}

		if (!this.detecting)
		{
			return;
		}

		e.preventDefault();

		this.deltaX = this.x - newX;
		this.deltaY = this.y - newY;

		this.handler(this, false);
	}

	onTouchEnd (e: TouchEvent)
	{
		if (!this.hasTouch(e.changedTouches, this.touch) || !this.detecting)
		{
			return;
		}

		if (this.deltaY > 2 && this.deltaX > 2)
		{
			e.preventDefault();
		}

		this.detecting = false;
		this.handler(this, true);
	}

	hasTouch (list: TouchList, item: Touch)
	{
		for (let i = 0; i < list.length; i++)
		{
			if (list.item(i).identifier === item.identifier)
			{
				return true;
			}
		}

		return false;
	}
}

export {
	MoveObserver,
}