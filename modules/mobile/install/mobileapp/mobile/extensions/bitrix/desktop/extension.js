(function()
{
	class Desktop
	{
		static getCurrentStatus(component)
		{
			return new Promise((resolve, reject) =>
			{
				if (!component)
				{
					reject('You need to provide component name');
				}

				const responseHandler = (response) => {
					resolve(response);
					BX.removeCustomEvent("onRequestDesktopStatus", responseHandler);
				};
				BX.addCustomEvent("onRequestDesktopStatus", responseHandler);
				BX.postComponentEvent("requestDesktopStatus", [{component}], "communication");
			});
		}

		static openPage(url)
		{
			return BX.rest.callMethod('im.desktop.page.open', {url});
		}
	}

	window.Desktop = Desktop;
})();