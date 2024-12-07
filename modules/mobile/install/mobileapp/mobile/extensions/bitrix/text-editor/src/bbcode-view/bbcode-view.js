/**
 * @module text-editor/bbcode-view/bbcode-view
 */
jn.define('text-editor/bbcode-view/bbcode-view', (require, exports, module) => {
	const { Color } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { RequestExecutor } = require('rest');
	const { Loc } = require('loc');

	class BbcodeView extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);

			this.state = {
				html: props.html ?? '',
			};
		}

		setHtml(html)
		{
			this.setState({
				html,
			});
		}

		static async show(props = {})
		{
			const bbcodeView = new BbcodeView();

			const html = await BbcodeView.loadHtml(props.bbcode);
			bbcodeView.setHtml(html);

			const bottomSheet = new BottomSheet({
				titleParams: {
					type: 'dialog',
					text: Loc.getMessage('MOBILEAPP_TEXT_EDITOR_TABLE_VIEW_TITLE'),
				},
				component: bbcodeView,
			});

			bottomSheet
				.setParentWidget(props.parentWidget || PageManager)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.disableContentSwipe()
				.alwaysOnTop()
				.open()
				.then((layout) => {
					layout.preventBottomSheetDismiss(true);
					layout.on('preventDismiss', () => {
						props?.onClose?.();
						layout.close();
					});
				})
				.catch((error) => {
					console.error(error);
				});
		}

		static async loadHtml(bbcode)
		{
			const re = new RequestExecutor(
				'mobile.texteditor.texteditor.gethtml',
				{
					bbcode: bbcode.replaceAll('<', '&lt;').replaceAll('>', '&gt;'),
				},
			);

			let html = '';
			try
			{
				const { result } = await re.call();
				html = result.html;
			}
			catch (err)
			{
				console.error(err);
			}

			return html;
		}

		render()
		{
			return WebView({
				style: {
					backgroundColor: Color.bgSecondary.toHex(),
				},
				data: {
					content: `
						<html>
							<head>
								<style>
									body {
										background-color: ${Color.bgSecondary.toHex()};
									}
									table {
										border-collapse: collapse;
									}
									td, th {
										border: 1px solid grey;
										padding: 3px;
										color: ${Color.base1.toHex()};
										text-align: left;
									}
									a {
										color: ${Color.accentMainLink.toHex()};
									}
								</style>
							</head>
							<body>${this.state.html}</body>
						</html>
					`,
					mimeType: 'text/html',
					charset: 'UTF-8',
				},
			});
		}
	}

	module.exports = {
		BbcodeView,
	};
});
