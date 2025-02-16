import { EventEmitter } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';
import type { UserSelectorOptions } from './type';

const userEntityTypeId = 'user';

export const UserSelectorEvent = Object.freeze({
	onShow: 'onShow',
	onHide: 'onHide',
	onItemSelect: 'onItemSelect',
	onItemDeselect: 'onItemDeselect',
});

export class UserSelector extends EventEmitter
{
	#container: HTMLElement = null;

	#dialog: Dialog = null;

	constructor(options: UserSelectorOptions)
	{
		super();
		this.setEventNamespace('BX.Sign.V2.B2e.UserSelector');
		this.#container = options.container;
		this.#dialog = new Dialog({
			width: 425,
			height: 363,
			multiple: options.multiple ?? true,
			targetNode: this.#container,
			context: options.context ?? 'sign_b2e_user_selector',
			entities: [
				{
					id: userEntityTypeId,
					options: {
						intranetUsersOnly: true,
					},
				},
			],
			dropdownMode: true,
			enableSearch: true,
			preselectedItems: options.preselectedIds?.map((id) => [userEntityTypeId, id]),
			hideOnDeselect: true,
			events: {
				onHide: (event) => this.emit(UserSelectorEvent.onHide, {
					items: this.#dialog.getSelectedItems(),
				}),
				'Item:onSelect': (event) => this.emit(UserSelectorEvent.onItemSelect, {
					items: this.#dialog.getSelectedItems(),
				}),
				'Item:onDeselect': (event) => this.emit(UserSelectorEvent.onItemSelect, {
					items: this.#dialog.getSelectedItems(),
				}),
			},
		});
	}

	toggle(): void
	{
		this.getDialog().show();
	}

	getDialog(): Dialog
	{
		return this.#dialog;
	}
}
