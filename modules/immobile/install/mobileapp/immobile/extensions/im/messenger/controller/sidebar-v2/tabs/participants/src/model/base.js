/**
 * @module im/messenger/controller/sidebar-v2/tabs/participants/src/model/base
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/participants/src/model/base', (require, exports, module) => {
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	/**
	 * @class ParticipantBaseModelData
	 */
	class ParticipantBaseModelData
	{
		constructor(props)
		{
			this.props = props;
			this.data = {};
			this.store = serviceLocator.get('core').getStore();

			this.createData();
		}

		/**
		 * @public
		 */
		getData()
		{
			return {
				id: this.getUserId(),
				...this.data,
			};
		}

		/**
		 * @protected
		 * @abstract
		 */
		createData()
		{}

		getUserId()
		{
			const { userId } = this.props;

			return userId;
		}

		/**
		 * @returns {DialogId}
		 */
		getDialogId()
		{
			const { dialogId } = this.props;

			return dialogId;
		}

		getDialogModel()
		{
			return DialogHelper.getDialogModel(this.getDialogId());
		}
	}

	module.exports = {
		ParticipantBaseModelData,
	};
});
