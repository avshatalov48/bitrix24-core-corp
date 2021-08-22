class NotificationBar
{
	constructor()
	{
		this.repo = [];
		this.color = {
			background: {
				error: '#affb0000',
				info: '#3a3735'
			},
			text: {
				error: '#ffffff',
				info: '#ffffff'
			}
		}
	}

	hideAll()
	{
		this.repo = this.repo.filter(notifyBar => {
			return notifyBar;
		});
		this.repo.forEach(notifyBar => {
			notifyBar.hide();
		});
	}

	showError(params)
	{
		const bar = new BXMobileApp.UI.NotificationBar({
				message: params.text ? params.text : '',
				color: this.color.background.error,
				textColor: this.color.text.error,
				useLoader: (params.useLoader ? !!params.useLoader : false),
				groupId: (params.groupId ? params.groupId : ''),
				align: (params.textAlign ? params.textAlign : 'center'),
				autoHideTimeout: (params.autoHideTimeout ? params.autoHideTimeout : 30000),
				hideOnTap: (params.hideOnTap ? !!params.hideOnTap : true),
				onTap: params.onTap ? params.onTap : () => {},
			}, (params.id ? params.id : parseInt(Math.random() * 100000)));

		this.repo.push(bar);
		bar.show();
	}

	showInfo(params)
	{
		const bar = new BXMobileApp.UI.NotificationBar({
				message: params.text ? params.text : '',
				color: this.color.background.info,
				textColor: this.color.text.info,
				useLoader: (params.useLoader ? !!params.useLoader : false),
				groupId: (params.groupId ? params.groupId : ''),
				maxLines: (params.maxLines ? params.maxLines : false),
				align: (params.textAlign ? params.textAlign : 'center'),
				isGlobal: (params.isGlobal ? !!params.isGlobal : true),
				useCloseButton: (params.useCloseButton ? !!params.useCloseButton : true),
				autoHideTimeout: (params.autoHideTimeout ? params.autoHideTimeout : 1000),
				hideOnTap: (params.hideOnTap ? !!params.hideOnTap : true)
			}, (params.id ? params.id : parseInt(Math.random() * 100000)));

		this.repo.push(bar);
		bar.show();
	}
}

export {
	NotificationBar
}