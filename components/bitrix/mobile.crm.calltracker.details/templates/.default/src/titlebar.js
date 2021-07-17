export default class Titlebar
{
	constructor(settings)
	{
		this.title = settings.hasOwnProperty('title') ? settings.title : '';
		this.subTitle = settings.hasOwnProperty('subTitle') ? settings.subTitle : '';
		this.photo = settings.hasOwnProperty('photo') ? settings.photo : '';

		this.init();
	}

	init()
	{
		BXMobileApp.UI.Page.TopBar.title.setText(this.title);

		if (this.subTitle.length)
		{
			BXMobileApp.UI.Page.TopBar.title.setDetailText(this.subTitle);
		}
		BXMobileApp.UI.Page.TopBar.title.setImage(this.photo);
		BXMobileApp.UI.Page.TopBar.title.show();
	}

	setMenu(menuItems)
	{
		app.menuCreate({ items: menuItems });
		window.BXMobileApp.UI.Page.TopBar.updateButtons({
			menuButton: {
				type: 'more',
				style: 'custom',
				callback: function() {
					app.menuShow();
				}
			}
		});
	}

	removeMenu()
	{
		window.BXMobileApp.UI.Page.TopBar.updateButtons({
			menuButton: {}
		});
	}

	static create(settings)
	{
		return new Titlebar(settings);
	}
}