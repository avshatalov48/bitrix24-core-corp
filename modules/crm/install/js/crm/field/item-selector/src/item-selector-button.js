import { Dom, Loc, Text } from 'main.core';
import { Button, ButtonOptions } from 'ui.buttons';

const DEFAULT_CLASS = 'crm-field-item-selector__add-btn';

export const ItemSelectorButtonState = Object.freeze({
	ADD: 'add',
	MORE_ADD: 'more-add',
	COUNTER_ADD: 'counter-add',
});

export class ItemSelectorButton extends Button
{
	constructor(options: ButtonOptions)
	{
		super(options);

		Dom.addClass(this.getContainer(), DEFAULT_CLASS);
	}

	getDefaultOptions(): Object
	{
		return {
			id: `item-selector-button-${Text.getRandom()}`,
			text: Loc.getMessage('CRM_FIELD_ITEM_SELECTOR_DEFAULT_ADD_BUTTON_LABEL'),
			tag: Button.Tag.SPAN,
			size: Button.Size.EXTRA_SMALL,
			color: Button.Color.LIGHT,
			round: true,
			dropdown: true,
		};
	}

	applyState(state: string, counter: number = 0): void
	{
		switch (state)
		{
			case ItemSelectorButtonState.MORE_ADD:
				this.setText(
					Loc.getMessage('CRM_FIELD_ITEM_SELECTOR_DEFAULT_MORE_BUTTON_LABEL'),
				);
				break;
			case ItemSelectorButtonState.COUNTER_ADD:
				this.setText(
					Loc.getMessage('CRM_FIELD_ITEM_SELECTOR_DEFAULT_MORE_COUNTER_BUTTON_LABEL')?.replace('#COUNTER#', counter),
				);
				break;
			case ItemSelectorButtonState.ADD:
			default:
				this.setText(
					Loc.getMessage('CRM_FIELD_ITEM_SELECTOR_DEFAULT_ADD_BUTTON_LABEL'),
				);
				break;
		}
	}
}
