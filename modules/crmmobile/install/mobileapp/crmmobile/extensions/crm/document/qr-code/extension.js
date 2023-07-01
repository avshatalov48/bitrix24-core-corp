/**
 * @module crm/document/qr-code
 */
jn.define('crm/document/qr-code', (require, exports, module) => {
	const { withCurrentDomain } = require('utils/url');
	const { Loc } = require('loc');
	const { ShimmedImage } = require('crm/document/qr-code/shimmed-image');

	const isAndroid = Application.getPlatform() === 'android';

	class CrmDocumentQrCode extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				loadingError: false,
			};
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;

			parentWidget
				.openWidget('layout', {
					modal: true,
					title: Loc.getMessage('M_CRM_DOCUMENT_QR_TITLE'),
					backgroundColor: '#eef2f4',
					backdrop: {
						onlyMediumPosition: true,
						showOnTop: true,
						forceDismissOnSwipeDown: true,
						mediumPositionHeight: 480,
						swipeAllowed: true,
						swipeContentAllowed: true,
						horizontalSwipeAllowed: false,
						navigationBarColor: '#eef2f4',
					},
				})
				.then((layoutWidget) => {
					layoutWidget.showComponent(new CrmDocumentQrCode({
						...props,
						layoutWidget,
					}));
					layoutWidget.enableNavigationBarBorder(false);
				});
		}

		get uri()
		{
			const action = 'crm.documentgenerator.document.showQrCode';
			const documentId = this.props.documentId;

			return withCurrentDomain(`/bitrix/services/main/ajax.php?action=${action}&id=${documentId}&t=${Date.now()}`);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: '#eef2f4',
					},
				},
				this.state.loadingError ? this.renderError() : this.renderContent(),
			);
		}

		renderError()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						justifyContent: 'center',
						alignItems: 'center',
						paddingVertical: 28,
					},
				},
				Text({
					text: Loc.getMessage('M_CRM_DOCUMENT_QR_ERROR'),
					style: {
						color: '#F4403B',
						fontSize: 17,
						textAlign: 'center',
					},
				}),
			);
		}

		renderContent()
		{
			return ScrollView(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
						backgroundColor: '#fff',
						borderRadius: 12,
					},
					safeArea: { bottom: true },
				},
				View(
					{
						style: {
							flexDirection: 'column',
							justifyContent: 'center',
							alignItems: 'center',
							flexGrow: 1,
						},
					},
					this.renderTopText(),
					this.renderQrCode(),
					this.renderBottomText(),
				),
			);
		}

		renderTopText()
		{
			return View(
				{
					style: {
						paddingVertical: isAndroid ? 18 : 28,
						paddingHorizontal: 25,
					},
				},
				Text({
					text: Loc.getMessage('M_CRM_DOCUMENT_QR_DESCRIPTION'),
					style: {
						color: '#333',
						fontSize: 17,
						textAlign: 'center',
					},
				}),
			);
		}

		renderQrCode()
		{
			return new ShimmedImage({
				uri: this.uri,
				width: 190,
				height: 190,
				onFailure: () => this.setState({ loadingError: true }),
			});
		}

		renderBottomText()
		{
			return View(
				{
					style: {
						paddingVertical: isAndroid ? 20 : 28,
						paddingHorizontal: 25,
					},
				},
				Text({
					text: Loc.getMessage('M_CRM_DOCUMENT_QR_BANK_APP_NOTE'),
					style: {
						color: '#A8ADB4',
						fontSize: 17,
						textAlign: 'center',
					},
				}),
			);
		}
	}

	module.exports = { CrmDocumentQrCode };
});
