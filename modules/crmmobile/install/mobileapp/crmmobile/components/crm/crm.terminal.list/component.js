(() => {
	const { PaymentList } = jn.require('crm/terminal/payment-list');
	const { PureComponent } = jn.require('layout/pure-component');
	const { Loc } = jn.require('loc');

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
