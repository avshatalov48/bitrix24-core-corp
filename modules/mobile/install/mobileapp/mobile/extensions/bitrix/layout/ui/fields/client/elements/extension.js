/**
 * @module layout/ui/fields/client/elements
 */
jn.define('layout/ui/fields/client/elements', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { ClientItemTitle } = require('layout/ui/fields/client/elements/title');
	const { ClientItemInfo } = require('layout/ui/fields/client/elements/info');
	const { ClientItemAction } = require('layout/ui/fields/client/elements/action');
	const { EntitySvg } = require('crm/assets/entity');
	const { SafeImage } = require('layout/ui/safe-image');
	const { withCurrentDomain } = require('utils/url');

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
	 * @class ClientItem
	 */
	class ClientItem extends LayoutComponent
	{
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

		renderAction()
		{
			if (!this.props.showClientInfo || !this.props.actionParams)
			{
				return null;
			}

			const { actionParams } = this.props;
			const { element, show = true } = actionParams;

			if (!show || typeof element !== 'object')
			{
				return null;
			}

			return element;
		}

		renderRightAction()
		{
			if (!this.props.actionParams)
			{
				return null;
			}
			const { type, readOnly, actionParams } = this.props;
			const { element, onClick, show = true } = actionParams;

			if (!show || typeof element === 'object')
			{
				return null;
			}

			return View(
				{
					testId: 'ClientElementRightAction',
					onClick: () => {
						if (onClick)
						{
							onClick(type);
						}
					},
				},
				ClientItemAction({ readOnly }),
			);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'flex-start',
						justifyContent: 'space-between',
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							flex: 1,
							flexShrink: 2,
						},
					},
					this.renderTitle(),
					this.renderAdditionalInfo(),
					this.renderAction(),
				),
				this.renderImage(),
				this.renderRightAction(),
			);
		}

		renderImage()
		{
			const { responsiblePhotoUrl, showResponsiblePhoto } = this.props;

			if (!showResponsiblePhoto)
			{
				return null;
			}

			return SafeImage({
				style: {
					width: 20,
					height: 20,
					resizeMode: 'contain',
					borderRadius: 10,
					borderColor: AppTheme.colors.bgSeparatorPrimary,
					borderWidth: 0.4,
				},
				placeholder: {
					content: EntitySvg.contactInverted(AppTheme.colors.base2),
				},
				uri: encodeURI(withCurrentDomain(responsiblePhotoUrl)),
			});
		}
	}

	module.exports = { ClientItem };
});
