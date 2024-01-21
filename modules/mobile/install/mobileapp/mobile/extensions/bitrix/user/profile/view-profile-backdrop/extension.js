/**
 * @module user/profile/view-profile-backdrop
 */
jn.define('user/profile/view-profile-backdrop', (require, exports, module) => {
	const { ProfileView } = require('user/profile');

	/**
	 * @function viewProfileBackdrop
	 */
	const viewProfileBackdrop = (props) => {
		const { parentWidget = PageManager, ...restProps } = props;

		const profileProps = {
			...restProps,
			isBackdrop: true,
		};

		return new Promise((resolve) => {
			parentWidget.openWidget('list', {
				groupStyle: true,
				backdrop: {
					bounceEnable: false,
					swipeAllowed: true,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
			}).then((layoutWidget) => {
				ProfileView.open(profileProps, layoutWidget);
				resolve(layoutWidget);
			}).catch(console.error);
		});
	};

	module.exports = { viewProfileBackdrop };
});
