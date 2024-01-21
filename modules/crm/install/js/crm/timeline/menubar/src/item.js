import { Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import Context from './context';

export default class Item
{
	static ON_FINISH_EDIT_EVENT = 'onFinishEdit';

	#context: Context = null;
	#settings = {};
	#eventEmitter: EventEmitter = null;
	#isVisible: Boolean = false;
	#container: ?HTMLElement = null;

	initialize(context: Context, settings: ?Object): void
	{
		this.#context = context;
		this.#settings = settings;
		this.#eventEmitter = new EventEmitter();
		this.#eventEmitter.setEventNamespace('BX.Crm.Timeline.MenuBar');

		this.initializeSettings();

		if (!this.#context.isReadonly() && this.supportsLayout())
		{
			this.#container = this.createLayout();
			Dom.prepend(this.#container, this.getMenuBarContainer());
			this.initializeLayout();
		}
	}

	getEntityTypeId(): Number
	{
		return this.#context.getEntityTypeId();
	}

	getEntityId(): Number
	{
		return this.#context.getEntityId();
	}

	getMenuBarContainer(): HTMLElement
	{
		return this.#context.getMenuBarContainer();
	}

	getContainer(): ?HTMLElement
	{
		return this.#container;
	}

	supportsLayout(): Boolean
	{
		return true;
	}

	activate(): void
	{
		if (this.supportsLayout())
		{
			this.setVisible(true);
		}
		else
		{
			this.showSlider();
		}
	}

	deactivate(): void
	{
		this.setVisible(false);
	}

	showSlider(): void
	{
		throw new Error('Method showSlider() must be overridden');
	}

	getSetting(setting: String, defaultValue = null)
	{
		return this.#settings[setting] ?? defaultValue;
	}

	getSettings(): ?Object
	{
		return this.#settings;
	}

	setVisible(visible: Boolean): void
	{
		visible = !!visible;
		if (this.#isVisible === visible)
		{
			return;
		}

		this.#isVisible = visible;
		const container = this.getContainer();
		if (!container)
		{
			return;
		}

		if (visible)
		{
			Dom.removeClass(container, '--hidden');
			this.onShow();
		}
		else
		{
			this.onHide();
			Dom.addClass(container, '--hidden');
		}
	}

	isVisible(): Boolean
	{
		return this.#isVisible;
	}

	setFocused(isFocused: Boolean): void
	{
		const container = this.getContainer();
		if (!container)
		{
			return;
		}

		if (isFocused)
		{
			Dom.addClass(container, '--focus');
		}
		else
		{
			Dom.removeClass(container, '--focus');
		}
	}

	setLocked(isLocked: Boolean): void
	{
		const container = this.getContainer();
		if (!container)
		{
			return;
		}
		if (isLocked)
		{
			Dom.addClass(container, '--locked');
		}
		else
		{
			Dom.removeClass(container, '--locked');
		}
	}

	isLocked(): Boolean
	{
		const container = this.getContainer();
		if (!container)
		{
			return false;
		}

		return Dom.hasClass(container, '--locked');
	}

	addFinishEditListener(callback)
	{
		this.#eventEmitter.subscribe(Item.ON_FINISH_EDIT_EVENT, callback);
	}

	emitFinishEditEvent()
	{
		this.#eventEmitter.emit(Item.ON_FINISH_EDIT_EVENT);
	}

	createLayout(): HTMLElement
	{
		throw new Error('Method createLayout() must be overridden');
	}

	initializeSettings(): void
	{}

	initializeLayout(): void
	{}

	onShow(): void
	{}

	onHide(): void
	{}
}
