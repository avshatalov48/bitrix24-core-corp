(() => {
	const require = (ext) => jn.require(ext);

	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const { PaymentList } = require('crm/terminal/payment-list');

	/**
	 * @class CrmTerminalListComponent
	 */
	class CrmTerminalListComponent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;
		}

		render()
		{
			return View(
				{
					resizableByKeyboard: true,
				},
				new PaymentList({ layout: this.layout }),
			);
		}
	}

	BX.onViewLoaded(() => {
		layout.enableNavigationBarBorder(false);
		layout.setTitle({
			text: Loc.getMessage('M_CRM_TL_CMP_PAYMENT_LIST_TITLE'),
			useLargeTitleMode: true,
		});
		layout.showComponent(new CrmTerminalListComponent({ layout }));
	});
})();
