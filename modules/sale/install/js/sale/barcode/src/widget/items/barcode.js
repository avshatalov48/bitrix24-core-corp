import {Tag, Event} from 'main.core';

export default class Barcode
{
	constructor(props)
	{
		this._id = props.id || 0;
		this._value = props.value || '';

		this._node = null;
		this._inputNode = null;
		this._isExist = null;
		this._eventEmitter = new Event.EventEmitter()
	}

	render()
	{
		this._inputNode = Tag.render`<input type="text" value="${this._value}" onchange="${this.onChange.bind(this)}">`;
		this._node = Tag.render`<div class="sale-order-shipment-barcode">${this._inputNode}</div>`;
		return this._node;
	}

	onChange()
	{
		this._value = this._inputNode.value;
		this._eventEmitter.emit('onChange', this);
	}

	onChangeSubscribe(callback)
	{
		this._eventEmitter.subscribe('onChange', callback);
	}

	get id()
	{
		return this._id;
	}

	get value()
	{
		return this._value;
	}

	set value(value)
	{
		this._value = value;
		this._inputNode.value = value;
	}

	set isExist(isExist)
	{
		this._isExist = isExist;
		this.showExistence(isExist);
	}

	get isExist()
	{
		return this._isExist;
	}

	showExistence(isExist)
	{
		if(isExist === false)
		{
			this._node.classList.remove("exists");
			this._node.classList.add("not-exists");
		}
		else if(isExist === true)
		{
			this._node.classList.remove("not-exists");
			this._node.classList.add("exists");
		}
		else if(isExist === null)
		{
			this._node.classList.remove("not-exists");
			this._node.classList.remove("exists");
		}
	}
}