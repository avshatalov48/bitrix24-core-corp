import { Loc, Type } from 'main.core';
import { BookingBaseField } from './booking-base-field';

export class HintField extends BookingBaseField<BX.Landing.UI.Field.Text, string>
{
	constructor(value: string, onChange: ?(value: string) => void)
	{
		super(new BX.Landing.UI.Field.Text({
			title: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_HINT_LABEL'),
			textOnly: true,
			content: value,
		}));

		if (Type.isFunction(onChange))
		{
			this.getField().subscribe('onChange', () => onChange(this.getValue() || ''));
		}

		this.setValue(value);
	}

	setValue(value: string): void
	{
		super.setValue(value);
	}
}
