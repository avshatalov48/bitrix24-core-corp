import {Tag} from 'main.core';

export default class Markingcode
{
	constructor(props)
	{
		this._id = props.id || 0;
		this._value = props.value || '';
	}

	get id()
	{
		return this._id;
	}

	get value()
	{
		return this._value;
	}

	render()
	{
		return Tag.render`<input type="text" value="${this._value}" onchange="${this.onChange.bind(this)}">`;
	}

	onChange(e)
	{
		this._value = e.target.value;
	}
}