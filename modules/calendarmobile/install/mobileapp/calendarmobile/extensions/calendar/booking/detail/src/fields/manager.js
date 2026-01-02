/**
 * @module calendar/booking/detail/fields/manager
 */
jn.define('calendar/booking/detail/fields/manager', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { NotifyManager } = require('notify-manager');

	const { UserFieldClass } = require('layout/ui/fields/user');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { withTheme } = require('layout/ui/fields/theme');

	const { UserFieldAirTheme } = require('calendar/layout/user-field-air-theme');

	class ManagerField extends UserFieldClass
	{
		get ownerId()
		{
			return this.props.ownerId;
		}

		hasCustomTitle()
		{
			return false;
		}

		hasPermissions()
		{
			return true;
		}

		isEmpty()
		{
			return false;
		}

		renderRightIcons()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				this.renderMessageIcon(),
			);
		}

		renderMessageIcon()
		{
			return View(
				{
					testId: 'calendar-event-view-form-entity-relation-message-icon',
					style: {
						marginLeft: Indent.XL.toNumber(),
					},
					onClick: this.handleMessageIconClick,
				},
				IconView({
					icon: Icon.MESSAGE,
					size: 24,
					color: Color.base5,
				}),
			);
		}

		handleMessageIconClick = () => {
			try
			{
				const dialogId = String(this.ownerId);

				void NotifyManager.showLoadingIndicator();

				void requireLazy('im:messenger/api/dialog-opener').then(({ DialogOpener }) => {
					NotifyManager.hideLoadingIndicator(true);
					DialogOpener.open({ dialogId });
				});
			}
			catch (errors)
			{
				NotifyManager.hideLoadingIndicator(false);
				console.error(errors);
			}
		};
	}

	module.exports = { ManagerField: withTheme(ManagerField, UserFieldAirTheme) };
});
