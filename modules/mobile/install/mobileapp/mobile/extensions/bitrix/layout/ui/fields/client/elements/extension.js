/**
 * @module layout/ui/fields/client/elements
 */
jn.define('layout/ui/fields/client/elements', (require, exports, module) => {

	const { ClientItemTitle } = require('layout/ui/fields/client/elements/title');
	const { ClientItemInfo } = require('layout/ui/fields/client/elements/info');
	const { ClientItemAction } = require('layout/ui/fields/client/elements/action');

	/**
	 * @class ClientItem
	 */
	class ClientItem extends LayoutComponent
	{
		/**
		 * @param {String} props.title
		 * @param {String} props.subtitle
		 * @param {String | String[]} props.phone
		 * @param {String | String[]} props.email
		 * @param {String | String[]} props.addresses
		 * @param {Function} props.onOpenBackdrop
		 * @param {Boolean} props.readOnly
		 * @param {Boolean} props.hidden
		 * @param {Object} props.actionParams
		 * @return ClientItem
		 */
		constructor(props)
		{
			super(props);
		}

		renderTitle()
		{
			const { title } = this.props;

			if (!title)
			{
				return null;
			}

			return ClientItemTitle(this.props);
		}

		renderAdditionalInfo()
		{

			if (!this.props.showClientInfo)
			{
				return null;
			}

			const { subtitle, phone, email, addresses, testId } = this.props;

			return new ClientItemInfo({ subtitle, phone, email, addresses, testId });
		}

		renderRightAction()
		{
			if (!this.props.showClientInfo || !this.props.actionParams)
			{
				return null;
			}

			const { type, readOnly, actionParams } = this.props;
			const { onClick, element, show = true } = actionParams;

			return show && View(
				{
					testId: 'ClientElementRightAction',
					onClick: () => {
						if (onClick)
						{
							onClick(type);
						}
					},
				},
				typeof element === 'object'
					? element
					: ClientItemAction({ readOnly }),
			);
		}

		render()
		{
			return View({
					style: {
						flexDirection: 'row',
						alignItems: 'flex-start',
						justifyContent: 'space-between',
					},
				},
				View({
						style: {
							flexDirection: 'column',
							flex: 1,
							flexShrink: 2,
						},
					},
					this.renderTitle(),
					this.renderAdditionalInfo(),
				),
				this.renderRightAction(),
			);
		}
	}

	module.exports = { ClientItem };

});
