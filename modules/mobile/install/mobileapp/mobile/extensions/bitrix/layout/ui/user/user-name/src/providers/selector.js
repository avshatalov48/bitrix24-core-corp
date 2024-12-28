/**
 * @module layout/ui/user/user-name/src/providers/selector
 */
jn.define('layout/ui/user/user-name/src/providers/selector', (require, exports, module) => {
	const { UserSelectorEntityType } = require('layout/ui/user/enums');
	const store = require('statemanager/redux/store');
	const { usersSelector } = require('statemanager/redux/slices/users');
	const { UserType } = require('layout/ui/user/user-name/src/enums/type-enum');

	/**
	 * @typedef {Object} SelectorDataProviderProps
	 * @property {'collaber' | 'extranet'} entityType
	 * @property {boolean} [withRedux=true]
	 *
	 * @class SelectorDataProvider
	 */
	class SelectorDataProvider
	{
		constructor(props)
		{
			this.props = props;
			this.user = this.getReduxData();
		}

		/**
		 * @param {SelectorDataProviderProps} props
		 * @returns {{title: {font: {color: string, useColor: boolean}}}}
		 */
		static getUserTitleStyle(props)
		{
			const selector = new SelectorDataProvider(props);

			return selector.getStyles();
		}

		getStyles()
		{
			return {
				title: {
					font: {
						color: this.getColor(),
						useColor: true,
					},
				},
			};
		}

		getColor()
		{
			let userEntityColor = UserType.USER;

			if (this.isExtranet())
			{
				userEntityColor = UserType.EXTRANET;
			}

			if (this.isCollaber())
			{
				userEntityColor = UserType.COLLAB;
			}

			return userEntityColor.toHex();
		}

		isCollaber()
		{
			const { entityType } = this.props;
			const { isCollaber } = this.user;

			return isCollaber || UserSelectorEntityType.isCollaber(entityType);
		}

		isExtranet()
		{
			const { entityType } = this.props;
			const { isExtranet } = this.user;

			return isExtranet || UserSelectorEntityType.isExtranet(entityType);
		}

		getReduxData()
		{
			if (!this.withRedux())
			{
				return {};
			}

			return usersSelector.selectById(store.getState(), this.getUserId()) || {};
		}

		withRedux()
		{
			const { withRedux = true } = this.props;

			return Boolean(withRedux);
		}

		getUserId()
		{
			const { id } = this.props;

			return id;
		}
	}

	module.exports = { SelectorDataProvider };
});
