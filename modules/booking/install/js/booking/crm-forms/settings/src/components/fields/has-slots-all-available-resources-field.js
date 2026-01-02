import { Loc, Text, Type } from 'main.core';
import { BookingBaseField } from './booking-base-field';

const DATA_HINT = 'data-hint';
const DATA_HINT_NO_ICON = 'data-hint-no-icon';

export class HasSlotsAllAvailableResourcesField extends BookingBaseField<BX.Landing.UI.Field.Checkbox, boolean>
{
	#disabled: boolean;

	constructor(value: boolean, onChange: ?(value: boolean) => void, disabled = false)
	{
		super(new BX.Landing.UI.Field.Checkbox({
			selector: 'hasSlotsAllAvailableResources',
			compact: true,
			multiple: false,
			items: [],
		}));

		this.#addItem(disabled);
		this.#disabled = disabled;

		if (Type.isFunction(onChange))
		{
			this.getField().subscribe('onChange', () => {
				onChange(Boolean(this.getField().getValue()));
			});
		}

		this.setValue(value);
	}

	#addItem(disabled = false): void
	{
		const hintMessage = Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_SHOW_SLOTS_ALL_AVAILABLE_RESOURCES_HELP_HINT', {
			'#NBSP# ': '&nbsp;',
		});

		this.getField().addItem({
			name: Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_SHOW_SLOTS_ALL_AVAILABLE_RESOURCES'),
			html: `
				${Text.encode(Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_SHOW_SLOTS_ALL_AVAILABLE_RESOURCES'))}
				<span
					class="landing-ui-form-help booking--crm-forms-settins--show-slots-all-available-resources-help-hint"
					title=""
					${DATA_HINT}="${hintMessage}"
					onclick="return false;"
				>
					<div></div>
				</span>
			`,
			value: 'hasSlotsAllAvailableResources',
			disabled,
		});
	}

	setValue(value: boolean): void
	{
		super.setValue(value ? ['hasSlotsAllAvailableResources'] : false);
	}

	#updateLayoutDataAttrs(layout: HTMLElement): HTMLElement
	{
		if (this.#disabled)
		{
			layout.setAttribute(
				DATA_HINT,
				Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_SHOW_SLOTS_ALL_AVAILABLE_RESOURCES_DISABLED_HINT'),
			);
			layout.setAttribute(DATA_HINT_NO_ICON, true);
		}
		else
		{
			layout.removeAttribute(DATA_HINT);
			layout.removeAttribute(DATA_HINT_NO_ICON);
		}

		return layout;
	}

	getLayout(): HTMLElement
	{
		return this.#updateLayoutDataAttrs(super.getLayout());
	}
}
