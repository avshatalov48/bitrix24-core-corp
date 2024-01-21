/**
 * @module layout/ui/user-selection-manager/src/user-selected-list
 */
jn.define('layout/ui/user-selection-manager/src/user-selected-list', (require, exports, module) => {
	const { withPressed } = require('utils/color');
	const { PropTypes } = require('utils/validation');
	const { SafeImage } = require('layout/ui/safe-image');
	const { addButton, cross, avatar } = require('assets/icons');
	const { viewProfileBackdrop } = require('user/profile/view-profile-backdrop');

	const IMAGE_SIZE = 22;
	const CROSS_SIZE = 20;
	const ADD_ICON_COLOR = {
		true: '#a7a7a7',
		false: '#0075ff',
	};

	/**
	 * @class UserSelectedList
	 */
	class UserSelectedList extends LayoutComponent
	{
		hasUsers()
		{
			const { users } = this.props;

			return users.length > 0;
		}

		renderRow(params)
		{
			const { testId = '', image, text, textStyle = {}, onClick } = params;

			return View(
				{
					testId,
					style: {
						flexDirection: 'row',
						marginVertical: 8,
						backgroundColor: withPressed('#ffffff'),
					},
					onClick,
				},
				SafeImage({
					style: {
						width: IMAGE_SIZE,
						height: IMAGE_SIZE,
						borderRadius: 12,
					},
					resizeMode: 'contain',
					...image,
				}),
				Text({
					style: {
						marginLeft: 8,
						...textStyle,
					},
					ellipsize: 'end',
					numberOfLines: 1,
					text,
				}),
			);
		}

		renderUser(params)
		{
			const { sectionId, onRemoveUser, getParentWidget } = this.props;
			const { id, image, ...restParams } = params;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
					},
				},
				this.renderRow({
					onClick: () => {
						viewProfileBackdrop({ userId: id, parentWidget: getParentWidget() });
					},
					image: {
						...image,
						placeholder: {
							content: avatar(),
						},
					},
					...restParams,
				}),
				Image({
					testId: `removeUserFrom${sectionId}`,
					style: {
						width: CROSS_SIZE,
						height: CROSS_SIZE,
						marginLeft: 16,
						backgroundColor: withPressed('#ffffff'),
					},
					svg: {
						content: cross(),
					},
					onClick: () => onRemoveUser({ userId: id, sectionId }),
				}),
			);
		}

		renderAddButton()
		{
			const { sectionId, addButtonText, onAddUser } = this.props;

			return this.renderRow({
				testId: `addUserTo${sectionId}`,
				text: addButtonText,
				textStyle: {
					color: this.hasUsers() ? '#909090' : '#333333',
				},
				image: {
					placeholder: {
						content: addButton({ color: ADD_ICON_COLOR[this.hasUsers()] }),
					},
				},
				onClick: onAddUser,
			});
		}

		render()
		{
			const { sectionTitle, users } = this.props;

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				Text({
					style: {
						color: '#909090',
						marginTop: 14,
						marginBottom: 6,
					},
					text: sectionTitle,
					ellipsize: 'end',
					numberOfLines: 1,
				}),
				...users.map(({ id, title, image }) => this.renderUser({
					id,
					text: title,
					image: {
						uri: image,
					},
				})),
				this.renderAddButton(),
			);
		}
	}

	UserSelectedList.propTypes = {
		users: PropTypes.arrayOf(
			PropTypes.shape({
				title: PropTypes.string,
				image: PropTypes.string,
				section: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
			}),
		),
		sectionTitle: PropTypes.string,
		addButtonText: PropTypes.string,
		onAddUser: PropTypes.func,
		onRemoveUser: PropTypes.func,
		parentWidget: PropTypes.object,
	};

	module.exports = { UserSelectedList };
});
