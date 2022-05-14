/**
 * Bitrix Mobile Dialog
 * Dialog Pull commands (Pull Command Handler)
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2020 Bitrix
 */

export class MobileImCommandHandler
{
	static create(params = {})
	{
		return new this(params);
	}

	constructor(params = {})
	{
		this.controller = params.controller;
		this.store = params.store;
		this.dialog = params.dialog;
	}

	getModuleId()
	{
		return 'im';
	}

	handleUserInvite(params, extra, command)
	{
		if (!params.invited)
		{
			setTimeout(() => {
				this.dialog.redrawHeader();
			}, 100);
		}
	}

	handleMessage(params, extra, command)
	{
		let currentHeaderName = BXMobileApp.UI.Page.TopBar.title.params.text;
		let senderId = params.message.senderId;
		if (params.users[senderId].name !== currentHeaderName)
		{
			this.dialog.redrawHeader();
		}
	}

	handleGeneralChatAccess()
	{
		app.closeController();
	}

	handleChatUserLeave(params)
	{
		if (
			params.userId === this.controller.application.getUserId()
			&& params.dialogId === this.controller.application.getDialogId()
		)
		{
			app.closeController();
		}
	}
}