/**
 * @module crm/receive-payment/steps/payment-systems/expandable-list
 */
jn.define('crm/receive-payment/steps/payment-systems/expandable-list', (require, exports, module) => {
	const { Loc } = require('loc');
	const { chevronDown } = require('assets/common');
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');

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
						color: AppTheme.colors.base3,
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
						color: AppTheme.colors.base3,
						lineHeightMultiple: 1.05,
					},
					text: Loc.getMessage('M_RP_PS_SHOW_MORE', { '#COUNT#': this.list.length - 4 }),
				}),
				Image({
					svg: {
						content: chevronDown(),
					},
					tintColor: AppTheme.colors.base3,
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
