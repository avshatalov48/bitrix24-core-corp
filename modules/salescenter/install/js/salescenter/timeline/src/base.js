import { Type } from 'main.core';

class Base
{
	constructor(props)
	{
		this.icon = this.getIcon();
		this.type = this.getType();
		this.content = Type.isString(props.content) && props.content.length > 0 ? props.content : '';
		this.disabled = Type.isBoolean(props.disabled) ? props.disabled : false;
	}

	getType()
	{
		return '';
	}

	getIcon()
	{
		return '';
	}
}

export {
	Base,
};
