import { Type } from 'main.core';
import { Base } from './base';

class CheckSent extends Base
{
	constructor(props)
	{
		super(props);
		this.url = Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	}

	static type()
	{
		return 'check-sent';
	}

	getType()
	{
		return CheckSent.type();
	}

	getIcon()
	{
		return 'check-sent';
	}
}

export
{
	CheckSent,
};
