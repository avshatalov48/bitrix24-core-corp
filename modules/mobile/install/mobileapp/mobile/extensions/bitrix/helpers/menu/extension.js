(() =>
{
	/**
	 *
	 * @class Menu
	 */
	let Menu = {
		/**
		 *
		 * @param screen
		 * @param params
		 */
		setButtonMenu(screen, params = null)
		{
			params = params || {code: "default", items: [], sections: [], callback: () => {}};
			if (screen && typeof screen["setRightButtons"] === "function")
			{
				if (!this.popupMenu)
					this.popupMenu = dialogs.createPopupMenu();

				this.popupMenu.setData(params.items, params.sections, (event, item) =>
				{
					if (event === "onItemSelected")
					{
						params.callback.call(null, item)
					}
				});

				screen.setRightButtons([
					{
						type: 'more',
						badgeCode: params.code,
						callback: () => this.popupMenu.show(),
					}
				]);
			}

		}
	};

	jnexport([Menu, "Menu"]);
})();