/**
 * @module user/profile/src/backdrop-profile
 */
jn.define('user/profile/src/backdrop-profile', (require, exports, module) => {

	/**
	 * @param {object} props
	 * @param {PageManager} [props.parentWidget=PageManager]
	 * @param {...*} props.restProps
	 * @return Promise<PageManager>
	 */
	const openUserProfile = async (props) => {
		const { parentWidget = PageManager, ...restProps } = props;
		const { ProfileView } = await requireLazy('user/profile');
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

	module.exports = { openUserProfile };
});
