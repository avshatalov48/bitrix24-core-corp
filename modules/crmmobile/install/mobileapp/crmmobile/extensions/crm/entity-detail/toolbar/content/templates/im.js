/**
 * @module crm/entity-detail/toolbar/content/templates/im
 */
jn.define('crm/entity-detail/toolbar/content/templates/im', (require, exports, module) => {
	const { ToolbarContentTemplateSingleAction } = require('crm/entity-detail/toolbar/content/templates/single-action');
	const { CommunicationEvents } = require('communication/events');
	const { im } = require('assets/communication');
	const AppTheme = require('apptheme');

	/**
	 * @class ActivityPinnedIm
	 */
	class ActivityPinnedIm extends ToolbarContentTemplateSingleAction
	{
		getTitle()
		{
			return this.props.title || '';
		}

		getSubTitle()
		{
			return this.props.subtitle || '';
		}

		getPrimaryIconSvgContent()
		{
			return im(AppTheme.colors.baseWhiteFixed);
		}

		handlePrimaryAction()
		{
			const { actionParams } = this.props;

			if (!actionParams)
			{
				return;
			}

			CommunicationEvents.execute(actionParams);
		}
	}

	module.exports = { ActivityPinnedIm };
});
