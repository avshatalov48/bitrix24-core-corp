import { Type } from 'main.core';
import { Base } from './base';

class Custom extends Base
{
	constructor(props)
	{
		super(props);

		this.icon = Type.isString(props.icon) && props.icon.length > 0 ? props.icon : '';
	}

	static type()
	{
		return 'custom';
	}

	getType()
	{
		return Custom.type();
	}

	getIcon()
	{
		return this.icon;
	}
}

export
{
	Custom,
};
