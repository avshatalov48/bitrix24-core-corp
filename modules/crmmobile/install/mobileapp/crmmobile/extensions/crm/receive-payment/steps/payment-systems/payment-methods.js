/**
 * @module crm/receive-payment/steps/payment-systems/payment-methods
 */
jn.define('crm/receive-payment/steps/payment-systems/payment-methods', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { PaymentMethodEntry } = require('crm/receive-payment/steps/payment-systems/payment-method-entry');
	const { NotifyManager } = require('notify-manager');
	const { BackdropHeader } = require('layout/ui/banners');
	const { BottomSheet } = require('bottom-sheet');
	const { handleErrors } = require('crm/error');
	const { Random } = require('utils/random');
	const { Oauth } = require('crm/payment-system/creation/actions/oauth');

	/**
	 * @class PaymentMethods
	 */
	class PaymentMethods extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.uid = props.uid || Random.getString();
		}

		render()
		{
			return View(
				{
					style: styles.backdrop,
				},
				this.renderList(),
			);
		}

		renderList()
		{
			const items = this.props.items.map((item) => {
				return {
					type: 'entry',
					key: item.modeId,
					props: item,
				};
			});

			return ScrollView(
				{
					style: styles.container,
				},
				View(
					{},
					BackdropHeader({
						title: Loc.getMessage('M_RP_PS_METHODS_BANNER_TITLE'),
						description: Loc.getMessage('M_RP_PS_METHODS_DESC'),
						image: Oauth.createBannerImage('payments'),
					}),
					View(
						{
							style: styles.items,
						},
						...items.map((item) => this.renderItem(item)),
					),
				),
			);
		}

		renderItem(item)
		{
			return new PaymentMethodEntry({
				uid: this.uid,
				item,
				handler: this.props.handler,
			});
		}

		static open(props)
		{
			const parentWidget = props.parentWidget || PageManager;
			NotifyManager.showLoadingIndicator();

			BX.ajax.runAction(
				'crmmobile.ReceivePayment.PaySystemMode.getPaySystemModeList',
				{
					json: {
						handlerName: props.handler,
					},
				},
			).then((response) => {
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				const title = Loc.getMessage('M_RP_PS_METHODS_TITLE');

				const component = (layout) => new this({
					...props,
					items: response.data,
					layout,
				});

				(new BottomSheet({ title, component }))
					.setParentWidget(parentWidget)
					.alwaysOnTop()
					.enableResizeContent()
					.disableContentSwipe()
					.open();
			}).catch((response) => {
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				handleErrors(response);
			});
		}
	}

	const styles = {
		backdrop: {
			backgroundColor: AppTheme.colors.bgPrimary,
			flex: 1,
		},
		container: {
			flex: 1,
			backgroundColor: AppTheme.colors.bgPrimary,
			borderRadius: 12,
		},
		items: {
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
			paddingBottom: 10,
			marginTop: 10,
			marginBottom: 20,
		},
	};

	module.exports = { PaymentMethods };
});
