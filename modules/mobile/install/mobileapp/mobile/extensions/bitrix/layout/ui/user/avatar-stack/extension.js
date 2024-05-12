/**
 * @module layout/ui/user/avatar-stack
 * */

jn.define('layout/ui/user/avatar-stack', (require, exports, module) => {
	const { ReduxAvatar } = require('layout/ui/user/avatar');

	const defaultSize = 30;

	/**
	 * @class AvatarStack
	 * @param {object} props
	 * @param {array<string>} props.avatars
	 * @param {boolean} props.size = 30
	 * @param {boolean} props.reverse = true
	 * @param {object} props.styles
	 */

	class AvatarStack extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						paddingLeft: this.size / 1.5,
						flexDirection: this.reverse ? 'row-reverse' : 'row',
						justifyContent: this.reverse ? 'flex-end' : 'flex-start',
						...this.styles.container,
					},
				},
				...this.avatars.map((id) => {
					return ReduxAvatar({
						id: Number(id),
						size: this.size,
						additionalStyles: {
							image: this.styles.avatar,
							wrapper: {
								marginLeft: -1 * (this.size / 1.5),
							},
						},
					});
				}),
			);
		}

		get avatars()
		{
			return Array.isArray(this.props.avatars) ? this.props.avatars : [];
		}

		get size()
		{
			return this.props.size > 0 ? this.props.size : defaultSize;
		}

		get reverse()
		{
			return this.props.reverse !== false;
		}

		get styles()
		{
			return this.props.styles || {};
		}
	}

	module.exports = { AvatarStack };
});
