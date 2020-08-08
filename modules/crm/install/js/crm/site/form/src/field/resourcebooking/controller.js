import * as BaseField from '../base/controller';
import * as Component from './component';

type Options = {
	label: ?string;
	booking: Object;
};

class Controller extends BaseField.Controller
{
	constructor(options: Options)
	{
		super(options);
		this.booking = options.booking;
		this.multiple = true;
	}

	static type(): string
	{
		return 'resourcebooking';
	}

	static component()
	{
		return Component.FieldResourceBooking;
	}
}

export {Controller, Options}