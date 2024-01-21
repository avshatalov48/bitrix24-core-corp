/**
 * @module crm/timeline/item/ui/body/blocks/link-block
 */
jn.define('crm/timeline/item/ui/body/blocks/link-block', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TimelineFontWeight } = require('crm/timeline/item/ui/styles');
	const AppTheme = require('apptheme');

	/**
	 * @class TimelineItemBodyLinkBlock
	 */
	class TimelineItemBodyLinkBlock extends TimelineItemBodyBlock
	{
		render()
		{
			return View(
				{
					onClick: () => this.onAction(),
				},
				Text({
					text: this.props.text,
					ellipsize: 'end',
					numberOfLines: this.props.inline ? 1 : 0,
					style: {
						fontSize: 14,
						color: AppTheme.colors.accentMainLinks,
						fontWeight: this.props.bold ? TimelineFontWeight.BOLD : TimelineFontWeight.NORMAL,
					},
				}),
			);
		}

		onAction()
		{
			if (this.props.action)
			{
				const { actionParams = {} } = this.props.action;
				actionParams.linkText = this.props.text;

				this.emitAction({
					...this.props.action,
					actionParams,
				});
			}
		}
	}

	module.exports = { TimelineItemBodyLinkBlock };
});