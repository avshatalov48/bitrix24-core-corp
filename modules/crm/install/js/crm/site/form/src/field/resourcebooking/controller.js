import * as BaseField from '../base/controller';
import * as Component from './component';

type Options = {
	label: ?string;
	booking: Object;
};

class Controller extends BaseField.Controller
{
	randomId: number;

	constructor(options: Options)
	{
		super(options);
		this.booking = options.booking;
		this.multiple = true;

		this.randomId = Math.random();
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