import {Type} from 'main.core';
import {Base} from "./base";

class Cashbox extends Base
{
	constructor(props)
	{
		super(props);

		this.info = Type.isString(props.info) && props.info.length > 0 ? props.info : '';
	}

	static type()
	{
		return 'cashbox';
	}
	getType()
	{
		return Cashbox.type();
	}
}

export {
	Cashbox
}