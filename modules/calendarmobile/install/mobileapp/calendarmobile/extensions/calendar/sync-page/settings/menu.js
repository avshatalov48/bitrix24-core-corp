/**
 * @module calendar/sync-page/settings/menu
 */
jn.define('calendar/sync-page/settings/menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ContextMenu } = require('layout/ui/context-menu');

	/**
	 * @class SyncSettingsMenu
	 */
	class SyncSettingsMenu
	{
		constructor(props)
		{
			this.props = props || {};

			this.menuInstance = new ContextMenu({
				actions: this.getActions(),
				params: {
					showCancelButton: true,
					showActionLoader: false,
				},
				layoutWidget: this.props.layoutWidget,
				testId: `sync_page_settings_menu`,
			});
		}

		getActions()
		{
			return [
				{
					id: 'disable_connection',
					title: Loc.getMessage('M_CALENDAR_SYNC_SETTINGS_MENU_DISABLE_CONNECTION'),
					data: {
						svgIcon: icons.disableConnection,
					},
					onClickCallback: () => new Promise((resolve) => {
						this.menuInstance.close(() => this.props.onChooseDisable());
						resolve({ closeMenu: false });
					}),
				},
			];
		}

		show()
		{
			this.menuInstance.show(this.props.layoutWidget);
		}
	}

	const icons = {
		disableConnection: '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.0607 7.32011V5.85837C12.0633 5.31886 11.6451 4.87901 11.1243 4.87501C10.6407 4.87256 10.2389 5.24705 10.1819 5.73147L10.175 5.85837V7.32011C10.175 7.85962 10.5971 8.29681 11.1179 8.29681C11.6387 8.29681 12.0607 7.85962 12.0607 7.32011Z" fill="#525C69"/><path d="M22.2858 6.91881C23.6352 7.00386 24.6807 8.17457 24.6563 9.57651V12.2948C23.8579 11.9447 22.9952 11.7142 22.0908 11.6259V10.9807H7.91114V21.5361H12.9106C13.0775 22.4859 13.4025 23.3812 13.8575 24.1938H6.62835C5.92026 24.1938 5.34559 23.5985 5.34559 22.865V9.57651C5.34046 9.50741 5.33789 9.43964 5.33789 9.37187C5.34046 8.01378 6.40515 6.91615 7.71612 6.91881H9.19386V7.58324C9.19386 8.68352 10.0546 9.57651 11.118 9.57651C12.1814 9.57651 13.0421 8.68352 13.0421 7.58324V6.91881H22.2858Z" fill="#525C69"/><path d="M15.9965 13.4153C15.2149 14.0349 14.5452 14.7895 14.0225 15.6439C13.9161 15.5508 13.8489 15.4143 13.8489 15.2623V13.8683C13.8489 13.5875 14.0781 13.3599 14.3607 13.3599H15.764C15.8477 13.3599 15.9267 13.3799 15.9965 13.4153Z" fill="#525C69"/><path d="M12.1249 13.3602H10.7216C10.439 13.3602 10.2099 13.5878 10.2099 13.8686V15.2626C10.2099 15.5434 10.439 15.771 10.7216 15.771H12.1249C12.4075 15.771 12.6367 15.5434 12.6367 15.2626V13.8686C12.6367 13.5878 12.4075 13.3602 12.1249 13.3602Z" fill="#525C69"/><path d="M10.7205 16.9762H12.1237C12.4064 16.9762 12.6355 17.2038 12.6355 17.4846V18.8786C12.6355 19.1594 12.4064 19.387 12.1237 19.387H10.7205C10.4378 19.387 10.2087 19.1594 10.2087 18.8786V17.4846C10.2087 17.2038 10.4378 16.9762 10.7205 16.9762Z" fill="#525C69"/><path fill-rule="evenodd" clip-rule="evenodd" d="M21.2569 26.3093C24.7087 26.3093 27.5069 23.5111 27.5069 20.0593C27.5069 16.6075 24.7087 13.8093 21.2569 13.8093C17.8051 13.8093 15.0069 16.6075 15.0069 20.0593C15.0069 23.5111 17.8051 26.3093 21.2569 26.3093ZM24.6419 22.2124L23.4111 23.4433L21.2573 21.2896L19.1029 23.444L17.872 22.2131L20.0264 20.0587L17.8721 17.9043L19.103 16.6735L21.2573 18.8278L23.411 16.6741L24.6419 17.905L22.4882 20.0587L24.6419 22.2124Z" fill="#525C69"/></svg>',
	};

	module.exports = { SyncSettingsMenu };
});
