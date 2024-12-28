import { Dialog } from 'ui.entity-selector';
import { memberRoles } from 'humanresources.company-structure.api';
import { AddDialogHeader } from './add-dialog-header';
import { AddDialogFooter } from './add-dialog-footer';
import './style.css';

const dialogId = 'hr-add-user-to-department-dialog';
const employeeType = memberRoles.employee;
const headType = memberRoles.head;

type Options = {
	type: string,
	nodeId: ?number,
}

export class AddUserDialog
{
	#role: string;
	#type: string;
	#dialog: Dialog;
	#nodeId: ?number;

	constructor(options: Options = {})
	{
		this.#type = options.type === 'head' ? 'head' : 'employee';
		this.id = `${dialogId}-${this.#type}`;
		this.#role = options.type === 'head' ? headType : employeeType;
		this.#nodeId = options.nodeId ?? null;
		this.#createDialog();
	}

	static openDialog(options: Options = {}): void
	{
		const previousDialog = Dialog.getById(`${dialogId}-${options.type ?? 'employee'}`);
		if (previousDialog)
		{
			previousDialog.show();

			return;
		}

		const instance = new AddUserDialog(options);
		instance.show();
	}

	show(): void
	{
		this.#dialog.show();
	}

	#createDialog(): void
	{
		this.#dialog = new Dialog({
			id: this.id,
			width: 400,
			height: 511,
			multiple: true,
			cacheable: false,
			dropdownMode: true,
			compactView: false,
			enableSearch: true,
			showAvatars: true,
			autoHide: false,
			header: AddDialogHeader,
			popupOptions: {
				overlay: { opacity: 40 },
			},
			headerOptions: {
				role: this.#role,
			},
			footer: AddDialogFooter,
			footerOptions: {
				role: this.#role,
				nodeId: this.#nodeId,
			},
			entities: [
				{
					id: 'user',
					options: {
						intranetUsersOnly: true,
						inviteEmployeeLink: false,
					},
				},
			],
		});
	}
}
