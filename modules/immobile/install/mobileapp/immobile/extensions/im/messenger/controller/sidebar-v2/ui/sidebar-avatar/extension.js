/**
 * @module im/messenger/controller/sidebar-v2/ui/sidebar-avatar
 */
jn.define('im/messenger/controller/sidebar-v2/ui/sidebar-avatar', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const { ChatAvatar } = require('im/messenger/lib/ui/avatar');
	const { PositionEnum } = require('im/messenger/controller/sidebar-v2/ui/sidebar-avatar/src/position-enum');

	class SidebarAvatar extends LayoutComponent
	{
		render()
		{
			const { dialogId, statusIcons, isNotes, ...restProps } = this.props;

			return View(
				{},
				ChatAvatar({
					dialogId: this.getDialogId(),
					testId: `${this.testId}-sidebar`,
					size: 40,
					...restProps,
					...this.getNoticeParams(),
				}),
				...this.renderStatusIcons(),
			);
		}

		getNoticeParams()
		{
			const { isNotes, size } = this.props;

			const params = {
				isNotes,
			};

			if (size > 40)
			{
				params.placeholderSvgSize = 47;
			}

			return params;
		}

		getDialogId()
		{
			const { dialogId } = this.props;

			return dialogId;
		}

		renderStatusIcons()
		{
			const { statusIcons } = this.props;

			if (isEmpty(statusIcons))
			{
				return [];
			}

			return statusIcons.map((statusIcon, index) => this.renderStatus(statusIcon, index));
		}

		renderStatus({ statusIcon, position }, index)
		{
			const statusPosition = position || ((index === 0) ? PositionEnum.TOP_RIGHT : PositionEnum.BOTTOM_RIGHT);

			return View(
				{
					style: {
						position: 'absolute',
						...statusPosition.getValue(),
					},
				},
				statusIcon,
			);
		}
	}

	module.exports = {
		SidebarAvatar: (props) => new SidebarAvatar(props),
		PositionEnum,
	};
});
