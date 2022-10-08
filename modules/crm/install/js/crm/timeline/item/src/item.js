import {Dom, Type} from 'main.core'

export default class Item
{
	constructor()
	{
		this._id = '';
		this._isTerminated = false;
		this._wrapper = null;
	}

	getId(): string
	{
		return this._id;
	}

	_setId(id): void
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	}

	/**
	 * @abstract
	 */
	setData(data): void
	{
		throw new Error('Item.setData() must be overridden');
	}

	/**
	 * @abstract
	 */
	layout(options): void
	{
		throw new Error('Item.layout() must be overridden');
	}

	refreshLayout()
	{
		const anchor = this._wrapper.previousSibling;
		this.clearLayout();

		this.layout({ anchor: anchor });
	}

	clearLayout()
	{
		Dom.remove(this._wrapper);
		this._wrapper = undefined;
	}

	destroy()
	{
		this.clearLayout();
	}

	getWrapper(): ?HTMLElement
	{
		return this._wrapper;
	}

	setWrapper(wrapper: HTMLElement): void
	{
		this._wrapper = wrapper;
	}

	addWrapperClass(className, timeout): void
	{
		if(!this._wrapper)
		{
			return;
		}
		Dom.addClass(this._wrapper, className);

		if(Type.isNumber(timeout) && timeout >= 0)
		{
			window.setTimeout(this.removeWrapperClass.bind(this, className), timeout);
		}
	}

	removeWrapperClass(className, timeout): void
	{
		if(!this._wrapper)
		{
			return;
		}

		Dom.removeClass(this._wrapper, className);
		if(Type.isNumber(timeout) && timeout >= 0)
		{
			window.setTimeout(this.addWrapperClass.bind(this, className), timeout);
		}
	}

	isTerminated(): boolean
	{
		return this._isTerminated;
	}

	markAsTerminated(terminated): void
	{
		terminated = !!terminated;

		if (this._isTerminated === terminated)
		{
			return;
		}

		this._isTerminated = terminated;
		if (!this._wrapper)
		{
			return;
		}

		if (terminated)
		{
			Dom.addClass(this._wrapper, 'crm-entity-stream-section-last');
		}
		else
		{
			Dom.removeClass(this._wrapper, 'crm-entity-stream-section-last');
		}
	}

	getAssociatedEntityTypeId(): ?Number
	{
		return null;
	}

	getAssociatedEntityId(): ?Number
	{
		return null;
	}

}
