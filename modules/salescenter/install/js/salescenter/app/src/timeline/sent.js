import {Type} from 'main.core';
import {Base} from './base'

class Sent extends Base
{
	constructor(props)
	{
		super(props);
		this.url = Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	}
	static type()
	{
		return 'sent';
	}
	getType()
	{
		return Sent.type();
	}
	getIcon()
	{
		return 'sent';
	}
}

export
{
	Sent
}