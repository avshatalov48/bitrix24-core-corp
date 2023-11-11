/**
 * @module im/messenger/controller/sidebar/sidebar-profile-user-counter
 */
jn.define('im/messenger/controller/sidebar/sidebar-profile-user-counter', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { Loc } = require('loc');
	const { core } = require('im/messenger/core');

	/**
	 * @class SidebarProfileUserCounter
	 * @typedef {LayoutComponent<SidebarProfileCounterProps, SidebarProfileCounterState>} SidebarProfileUserCounter
	 */
	class SidebarProfileUserCounter extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {SidebarProfileCounterProps} props
		 */
		constructor(props)
		{
			super(props);

			this.store = core.getStore();
			this.storeManager = core.getStoreManager();
			this.state = { userCounterLocal: '', userCounter: 0 };
		}

		render()
		{
			return Text({
				style: {
					color: '#959CA4',
					fontSize: 14,
					fontWeight: 400,
					textStyle: 'normal',
					textAlign: 'center',
				},
				text: this.state.userCounterLocal,
				testId: 'SIDEBAR_USER_COUNTER_LOCAL',
			});
		}

		componentDidMount()
		{
			Logger.log('SidebarProfileBtn.view.componentDidMount');
			this.bindListener();
			this.subscribeEvents();
			this.buildUserCounterLocal();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindListener()
		{
			this.unsubscribeEvents = this.unsubscribeEvents.bind(this);
			this.onChangeProfileUserCounter = this.onChangeProfileUserCounter.bind(this);
		}

		subscribeEvents()
		{
			Logger.log('SidebarProfileUserCounter.view.subscribeStoreEvents');
			this.storeManager.on('dialoguesModel/update', this.onChangeProfileUserCounter);

			BX.addCustomEvent('onCloseSidebarWidget', this.unsubscribeEvents);
		}

		unsubscribeEvents()
		{
			Logger.log('SidebarProfileUserCounter.view.unsubscribeStoreEvents');
			this.storeManager.off('dialoguesModel/update', this.onChangeProfileUserCounter);

			BX.removeCustomEvent('onCloseSidebarWidget', this.unsubscribeEvents);
		}

		/**
		 * @desc Handler update user counter
		 * @void
		 */
		onChangeProfileUserCounter(event)
		{
			if (event.payload.actionName === 'addParticipants' || event.payload.actionName === 'removeParticipants')
			{
				this.buildUserCounterLocal();
			}
			else
			{
				const eventCount = event.payload.data.fields.userCounter;
				const currentCount = this.state.userCounter;
				if (eventCount && eventCount !== currentCount)
				{
					this.buildUserCounterLocal();
				}
			}
		}

		buildUserCounterLocal()
		{
			const count = this.getCountParticipants();
			const local = this.createUserCounterLabel(count);
			this.updateStateView({ userCounterLocal: local, userCounter: count });
		}

		/**
		 * @desc Create string for label user counter by number
		 * @param {number} userCounter
		 * @return {string}
		 */
		createUserCounterLabel(userCounter)
		{
			return Loc.getMessagePlural(
				'IMMOBILE_DIALOG_SIDEBAR_USER_COUNTER',
				userCounter,
				{
					'#COUNT#': userCounter,
				},
			);
		}

		/**
		 * @desc Returns count participants
		 * @return {number}
		 */
		getCountParticipants()
		{
			const dialogData = this.store.getters['dialoguesModel/getById'](this.props.dialogId);

			return dialogData ? dialogData.userCounter : 0;
		}

		/**
		 * @desc Method update state component
		 * @param {object} newState
		 * @void
		 */
		updateStateView(newState)
		{
			this.setState(newState);
		}
	}

	module.exports = { SidebarProfileUserCounter };
});
