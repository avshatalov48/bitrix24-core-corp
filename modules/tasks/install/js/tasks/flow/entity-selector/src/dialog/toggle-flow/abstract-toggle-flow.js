import { Loc } from 'main.core';
import { BaseEvent } from 'main.core.events';

import type { Item } from 'ui.entity-selector';

export class AbstractToggleFlow
{
	onSelectFlow(event: BaseEvent, itemBeforeUpdate: Item): void
	{
		throw new Error('AbstractToggleFlow: Calling an abstract changeFlow() without implementation');
	}

	onDeselectFlow(event: BaseEvent, selectedItem: ?Item): void
	{
		throw new Error('AbstractToggleFlow: Calling an abstract unChangeFlow() without implementation');
	}

	showConfirmChangeFlow(doneCallback, cancelCallback)
	{
		BX.UI.Dialogs.MessageBox.show({
			message: Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CHANGE_MESSAGE'),
			title: Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CHANGE_TITLE'),
			onOk: () => {
				doneCallback();
			},
			okCaption: Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CHANGE_OK_CAPTION'),
			cancelCallback: (messageBox) => {
				cancelCallback();
				messageBox.close();
			},
			cancelCaption: Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_CHANGE_CANCEL_CAPTION'),
			buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
			popupOptions: {
				events: {
					onPopupClose: () => {
						cancelCallback();
					},
				},
			},
		});
	}
}
