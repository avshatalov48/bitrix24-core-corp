/**
 * @module crm/receive-payment/steps/payment-systems/expandable-list
 */
jn.define('crm/receive-payment/steps/payment-systems/expandable-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/receive-payment/steps/payment-systems`;

	/**
	 * @class ExpandableList
	 */
	class ExpandableList extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.state.expanded = false;
		}

		get list()
		{
			return BX.prop.getArray(this.props, 'list', []);
		}

		shouldComponentUpdate(nextProps, nextState)
		{
			return true;
		}

		render()
		{
			const viewElementList = [];
			for (let itemIndex = 0; itemIndex < this.list.length; itemIndex++)
			{
				if (this.isNeedToExpand() && itemIndex >= 4)
				{
					break;
				}

				viewElementList.push(Text({
					style: {
						fontSize: 13,
						color: '#6a737f',
						lineHeightMultiple: 1.05,
					},
					text: this.list[itemIndex].NAME,
					ellipsize: 'end',
					numberOfLines: 1,
				}));
			}

			return View(
				{
					style: {
						alignItems: 'flex-start',
					},
				},
				...viewElementList,
				this.isNeedToExpand() && this.renderExpandableButton(),
			);
		}

		isNeedToExpand()
		{
			return !this.state.expanded && this.list.length > 5;
		}

		renderExpandableButton()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
					onClick: () => {
						this.setState({ expanded: true });
					},
				},
				Text({
					style: {
						fontSize: 13,
						color: '#6a737f',
						lineHeightMultiple: 1.05,
					},
					text: Loc.getMessage('M_RP_PS_SHOW_MORE', { '#COUNT#': this.list.length - 4 }),
				}),
				Image({
					svg: { uri: `${pathToExtension}/images/arrow.svg` },
					style: {
						marginTop: 7,
						marginLeft: 5,
						width: 10,
						height: 7,
					},
				}),
			);
		}
	}

	module.exports = { ExpandableList };
});
