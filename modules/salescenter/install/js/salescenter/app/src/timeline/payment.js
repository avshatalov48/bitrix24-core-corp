import {Type} from 'main.core'
import {Base} from './base'

class Payment extends Base
{
	constructor(props)
	{
		super(props);

		this.sum = typeof(props.sum) != 'undefined' ? props.sum : '0.00';//.toFixed(2)
		this.title = Type.isString(props.title) && props.title.length > 0 ? props.title : '';
		this.currency = Type.isString(props.currency) && props.currency.length > 0 ? props.currency : '';
	}
	static type()
	{
		return 'payment';
	}
	getType()
	{
		return Payment.type();
	}
	getIcon()
	{
		return 'cash';
	}
}

export
{
	Payment
}