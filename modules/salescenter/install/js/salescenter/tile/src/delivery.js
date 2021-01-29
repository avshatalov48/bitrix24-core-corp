import {Type} from 'main.core';
import {Base} from "./base";

class Delivery extends Base
{
	constructor(props)
	{
		super(props);

		this.code = 		Type.isString(props.code) && props.code.length > 0 ? props.code : '';
		this.info = 		Type.isString(props.info) && props.info.length > 0 ? props.info : '';
		this.showTitle = 	Type.isBoolean(this.showTitle) ? this.showTitle : false;

		this.width = 		835;
	}

	static type()
	{
		return 'delivery';
	}
	getType()
	{
		return Delivery.type();
	}
}

export {
	Delivery
}