/**
 * @module im/messenger/controller/sidebar/chat/sidebar-profile-user-counter
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-profile-user-counter', (require, exports, module) => {
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-profile-user-counter');
	const { Loc } = require('loc');
	const { EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

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

			this.store = serviceLocator.get('core').getStore();
			this.storeManager = serviceLocator.get('core').getStoreManager();
			this.state = { userCounterLocal: '', userCounter: 0 };
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.renderText(),
			);
		}

		renderText()
		{
			if (this.props.isCopilot && this.state.userCounter <= 2)
			{
				return View(
					{
						style: {
							flexDirection: 'row',
						},
					},
				);
			}

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
			logger.log(`${this.constructor.name}.view.componentDidMount`);
			this.bindMethods();
			this.subscribeEvents();
			this.buildUserCounterLocal();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			this.onClose = this.onClose.bind(this);
			this.onChangeProfileUserCounter = this.onChangeProfileUserCounter.bind(this);
		}

		subscribeEvents()
		{
			logger.log(`${this.constructor.name}.view.subscribeStoreEvents`);
			this.storeManager.on('dialoguesModel/update', this.onChangeProfileUserCounter);

			BX.addCustomEvent(EventType.sidebar.closeWidget, this.onClose);
		}

		unsubscribeEvents()
		{
			logger.log(`${this.constructor.name}.view.unsubscribeStoreEvents`);
			this.storeManager.off('dialoguesModel/update', this.onChangeProfileUserCounter);

			BX.removeCustomEvent(EventType.sidebar.closeWidget, this.onClose);
		}

		onClose()
		{
			logger.log(`${this.constructor.name}.onClose`);
			this.unsubscribeEvents();
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
