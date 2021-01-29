import {Type} from 'main.core';
import {Base} from "./base";

class PaySystem extends Base
{
	constructor(props)
	{
		super(props);

		this.info = Type.isString(props.info) && props.info.length > 0 ? props.info : '';
		this.sort = Type.isInteger(props.sort) ? props.sort : 0;
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
	PaySystem
}