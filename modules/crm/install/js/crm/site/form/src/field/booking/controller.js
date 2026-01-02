import * as BaseField from '../base/controller';
import * as Component from './component';

class Controller extends BaseField.Controller
{
	static type(): string
	{
		return 'booking';
	}

	static component(): Object
	{
		return Component.BookingField;
	}
}

export { Controller };
