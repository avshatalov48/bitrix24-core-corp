(() => {
	const require = (ext) => jn.require(ext);

	const { PaymentCreate } = require('crm/terminal/entity/payment-create');
	const { PureComponent } = require('layout/pure-component');

	/**
	 * @class CrmTerminalEntityPaymentCreateComponent
	 */
	class CrmTerminalEntityPaymentCreateComponent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;
		}

		render()
		{
			return new PaymentCreate({
				entityTypeId: BX.componentParameters.get('entityTypeId'),
				entityId: BX.componentParameters.get('entityId'),
				uid: BX.componentParameters.get('uid'),
				productCount: BX.componentParameters.get('productCount', 0),
				layout: this.layout,
			});
		}
	}

	BX.onViewLoaded(() => {
		layout.enableNavigationBarBorder(true);
		layout.showComponent(new CrmTerminalEntityPaymentCreateComponent({ layout }));
	});
})();
