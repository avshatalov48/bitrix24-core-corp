import { Type } from 'main.core';
import { Base } from './base';

class PaySystem extends Base
{
	constructor(props)
	{
		super(props);

		this.info = Type.isString(props.info) && props.info.length > 0 ? props.info : '';
		this.sort = Type.isInteger(props.sort) ? props.sort : 0;
		this.psModeName = Type.isString(props.psModeName) && props.psModeName.length > 0 ? props.psModeName : this.name;
	}

	static type()
	{
		return 'paysystem';
	}

	getType()
	{
		return PaySystem.type();
	}
}

export {
	PaySystem,
};
