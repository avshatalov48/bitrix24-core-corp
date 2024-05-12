/**
 * @module calendar/layout/avatars/avatars
 */
jn.define('calendar/layout/avatars/avatars', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Avatar } = require('layout/ui/user/avatar');

	class Avatars extends LayoutComponent
	{
		get avatars()
		{
			return this.props.avatars || [];
		}

		get size()
		{
			return this.props.size || 36;
		}

		get density()
		{
			return this.props.density || 0.3;
		}

		get limit()
		{
			return this.props.limit || 8;
		}

		render()
		{
			return View(
				{
					testId: 'CalendarmobileMembers',
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingLeft: this.size * this.density,
					},
				},
				...this.avatars.slice(0, this.limit).map((avatar) => this.renderAvatar(avatar)),
				this.renderMoreAvatars(this.avatars.length - this.limit),
			);
		}

		renderAvatar(avatar)
		{
			const borderWidth = 2;
			const size = this.size;
			const containerSize = size + borderWidth;

			return Avatar({
				size,
				image: this.isAvatar(avatar) ? avatar : null,
				additionalStyles: {
					wrapper: {
						marginLeft: - size * this.density,
						width: containerSize,
						alignItems: 'center',
						justifyContent: 'center',
						height: containerSize,
						borderRadius: containerSize,
						borderColor: AppTheme.colors.bgContentPrimary,
						borderWidth,
					},
					image: {
						backgroundColor: AppTheme.colors.base5,
					},
				},
			});
		}

		isAvatar(imageUrl)
		{
			return imageUrl !== '/bitrix/images/1.gif' && imageUrl !== '';
		}

		renderMoreAvatars(count)
		{
			if (count <= 0)
			{
				return null;
			}

			const borderWidth = 2;
			const containerSize = 3 * this.size / 4 + 2 * borderWidth;

			return View(
				{
					testId: 'CalendarmobileMembersDots',
					style: {
						marginLeft: - this.size / 2,
						width: containerSize,
						height: containerSize,
						backgroundColor: AppTheme.colors.base7,
						borderRadius: this.size,
						borderColor: AppTheme.colors.bgContentPrimary,
						borderWidth,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				Text({
					text: `+${count}`,
					style: {
						color: AppTheme.colors.base2,
						fontSize: this.size / 3,
					},
				}),
			);
		}
	}

	module.exports = { Avatars };
});
