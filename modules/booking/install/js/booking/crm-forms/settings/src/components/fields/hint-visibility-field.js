import { Loc, Type } from 'main.core';
import { BookingBaseField } from './booking-base-field';

export class HintVisibilityField extends BookingBaseField<BX.Landing.UI.Field.Checkbox, boolean>
{
	constructor(value: boolean, onChange: ?(isVisibleHint: boolean) => void)
	{
		super(new BX.Landing.UI.Field.Checkbox({
			selector: 'isVisibleHint',
			compact: true,
			multiple: false,
			items: [
				{
					name: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_IS_VISIBLE_HINT_LABEL'),
					value: 'isVisibleHint',
				},
			],
		}));

		if (Type.isFunction(onChange))
		{
			this.getField().subscribe('onChange', () => onChange(Boolean(this.getValue())));
		}

		this.setValue(value);
	}

	setValue(value: boolean): void
	{
		super.setValue(value ? ['isVisibleHint'] : false);
	}
}
