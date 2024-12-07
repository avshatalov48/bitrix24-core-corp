import { BaseField } from './base-field';
import { Loc, Tag, Dom } from 'main.core';
import { GridManager } from '../grid-manager';
import 'ui.cnt';
import { ReinvitePopup, FormType } from 'intranet.reinvite';

export type ActivityFieldType = {
	gridId: string,
	userId: number,
	action: string,
	enabled: boolean,
	email: ?string,
	phoneNumber: ?string,
	isExtranet: boolean,
	isCloud: boolean,
}

export class ActivityField extends BaseField
{
	render(params: ActivityFieldType): void
	{
		let title = '';
		let color = '';

		switch (params.action ?? 'invite')
		{
			case 'accept':
				title = Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_ACCEPT_ENTER');
				color = BX.UI.Button.Color.PRIMARY;
				break;
			case 'invite':
			default:
				title = Loc.getMessage('INTRANET_JS_CONTROL_BUTTON_INVITE_AGAIN');
				color = BX.UI.Button.Color.LIGHT_BORDER;
				break;
		}

		const counter = Tag.render`
			<div class="ui-counter user-grid_invitation-counter">
				<div class="ui-counter-inner">1</div>
			</div>
		`;

		Dom.append(counter, this.getFieldNode());

		const button = new BX.UI.Button({
			text: title,
			color,
			noCaps: true,
			size: BX.UI.Button.Size.EXTRA_SMALL,
			tag: BX.UI.Button.Tag.INPUT,
			round: true,
			onclick: () => {
				this.#onClick(params, button);
			},
		});

		button.renderTo(this.getFieldNode());
	}

	#updateData(data): void
	{
		const row = GridManager.getInstance(this.gridId).getGrid()?.getRows().getById(this.userId);
		row?.stateLoad();
		GridManager.reinviteCloudAction(data).then((response) => {
			row?.update();
			row?.stateUnload();
		});
	}

	#onClick(params: ActivityFieldType, button: BX.UI.Button): void
	{
		if (!params.enabled)
		{
			const popup = BX.PopupWindowManager.create(
				'intranet-user-grid-invitation-disabled',
				null,
				{
					darkMode: true,
					content: Loc.getMessage('INTRANET_USER_LIST_ACTION_REINVITE_DISABLED'),
					closeByEsc: true,
					angle: true,
					offsetLeft: 40,
					maxWidth: 300,
					overlay: false,
					autoHide: true,
				},
			);
			popup.setBindElement(button.getContainer());
			popup.show();
		}
		else
		{
			this.#actionFactory(params.action).call(this, params, button);
		}
	}

	#actionFactory(action: string): function
	{
		switch (action)
		{
			case 'accept':
				return this.#acceptAction;
				break;
			case 'invite':
				return this.#inviteAction;
			default:
				return this.#inviteAction;
				break;
		}
	}

	#inviteAction(params, button): void
	{
		if (params.isCloud === true)
		{
			const reinvitePopup = new ReinvitePopup({
				userId: params.userId,
				transport: this.#updateData.bind(params), //callback,
				formType: params.email ? FormType.EMAIL : FormType.PHONE,
				bindElement: button.getContainer(),
				inputValue: params.email ?? params.phoneNumber ?? '',
			});
			//This is a hack. When the row is updated, a new button is created.
			reinvitePopup.getPopup().setBindElement(button.getContainer());
			reinvitePopup.show();
		}
		else
		{
			button.setWaiting(true);
			GridManager.reinviteAction(params.userId, params.isExtranet).then(() => {
				button.setWaiting(false);
			});
		}
	}

	#acceptAction(params, button): void
	{
		GridManager.getInstance(params.gridId).confirmAction({
			isAccept: true,
			userId: params.userId,
		});
	}
}
