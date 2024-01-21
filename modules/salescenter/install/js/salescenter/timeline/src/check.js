import { Type } from 'main.core';
import { Base } from './base';

class Check extends Base
{
	constructor(props)
	{
		super(props);
		this.url = Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	}

	static type()
	{
		return 'check';
	}

	getType()
	{
		return Check.type();
	}

	getIcon()
	{
		return 'check';
	}
}

export
{
	Check,
};
