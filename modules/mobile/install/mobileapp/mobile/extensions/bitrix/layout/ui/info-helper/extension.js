/**
 * @module layout/ui/info-helper
 */
jn.define('layout/ui/info-helper', (require, exports, module) => {
	const AppTheme = require('apptheme');

	class InfoHelper extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				WebView({
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						height: '100%',
					},
					data: {
						url: this.props.url,
					},
				}),
			);
		}

		static openByCode(code, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction('ui.infoHelper.getInitParams').then((result) => {
					if (result?.data?.frameUrlTemplate)
					{
						const url = result.data.frameUrlTemplate.replace('/code/', `/${code}/`);

						const widgetParams = {
							modal: true,
							backdrop: {
								showOnTop: true,
								topPosition: 100,
								forceDismissOnSwipeDown: false,
								swipeAllowed: false,
								horizontalSwipeAllowed: false,
								hideNavigationBar: true,
							},
						};

						parentWidget.openWidget('layout', widgetParams)
							.then((layout) => {
								layout.showComponent(new this({ url }));
								resolve(layout);
							})
						;
					}

					reject();
				});
			});
		}

		static async getUrlByCode(code)
		{
			const result = await BX.ajax.runAction('ui.infoHelper.getInitParams');
			if (result?.data?.frameUrlTemplate)
			{
				return result.data.frameUrlTemplate.replace('/code/', `/${code}/`);
			}

			return null;
		}
	}

	module.exports = { InfoHelper };
});
