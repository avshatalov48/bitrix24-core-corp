import { Dialog } from 'ui.entity-selector';
import { MoveFromDialogHeader } from './move-from-dialog-header';
import { MoveFromDialogFooter } from './move-from-dialog-footer';
import './style.css';

const dialogId = 'hr-move-user-from-department-dialog';

export class MoveUserFromDialog
{
	#dialog: Dialog;
	#nodeId: number;

	constructor(nodeId: Number)
	{
		this.id = dialogId;
		this.#nodeId = nodeId;
		this.#createDialog();
	}

	static openDialog(nodeId: Number): void
	{
		const instance = new MoveUserFromDialog(nodeId);
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
			header: MoveFromDialogHeader,
			footer: MoveFromDialogFooter,
			footerOptions: {
				nodeId: this.#nodeId,
			},
			popupOptions: {
				overlay: { opacity: 40 },
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
