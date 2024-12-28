/**
 * @module im/messenger/controller/sidebar/chat/sidebar-profile-btn
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-profile-btn', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');
	const { EventType } = require('im/messenger/const');

	/**
	 * @class SidebarProfileBtn
	 * @typedef {LayoutComponent<SidebarProfileBtnProps, SidebarProfileBtnState>} SidebarProfileBtn
	 */
	class SidebarProfileBtn extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {SidebarProfileBtnProps} props
		 */
		constructor(props)
		{
			super(props);
			this.state = { buttonElements: props.buttonElements };
		}

		render()
		{
			return View(
				{
					style: {
						marginTop: 16,
						marginHorizontal: 14,
						marginBottom: 14,
						flexDirection: 'row',
						alignSelf: 'stretch',
						alignItems: 'center',
						justifyContent: 'flex-start',
					},
				},
				...this.state.buttonElements,
			);
		}

		componentDidMount()
		{
			Logger.log(`${this.constructor.name}.view.componentDidMount`);
			this.bindMethods();
			this.subscribeStoreEvents();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			this.unsubscribeStoreEvents = this.unsubscribeStoreEvents.bind(this);
			this.onChangeMuteButton = this.onChangeMuteButton.bind(this);
			this.onUpdateAllButton = this.onUpdateAllButton.bind(this);
		}

		subscribeStoreEvents()
		{
			Logger.log(`${this.constructor.name}.view.subscribeStoreEvents`);
			BX.addCustomEvent(EventType.sidebar.closeWidget, this.unsubscribeStoreEvents);
			BX.addCustomEvent(EventType.sidebar.changeMuteButton, this.onChangeMuteButton);
			BX.addCustomEvent(EventType.sidebar.updateAllButton, this.onUpdateAllButton);
		}

		unsubscribeStoreEvents()
		{
			Logger.log(`${this.constructor.name}.view.unsubscribeStoreEvents`);
			BX.removeCustomEvent(EventType.sidebar.closeWidget, this.unsubscribeStoreEvents);
			BX.removeCustomEvent(EventType.sidebar.changeMuteButton, this.onChangeMuteButton);
			BX.removeCustomEvent(EventType.sidebar.updateAllButton, this.onUpdateAllButton);
		}

		/**
		 * @desc Handler update mute button
		 * @param {LayoutComponent} button
		 * @void
		 */
		onChangeMuteButton(button)
		{
			const oldState = this.state.buttonElements;
			const indexMute = oldState.findIndex((btnComponent) => btnComponent.props.id === 'mute');
			const newState = [...oldState];
			newState[indexMute] = button;
			this.updateStateView({ buttonElements: newState });
		}

		/**
		 * @desc Handler update all buttons
		 * @param {Array<LayoutComponent>} buttons
		 * @void
		 */
		onUpdateAllButton(buttons)
		{
			this.updateStateView({ buttonElements: buttons });
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

	module.exports = { SidebarProfileBtn };
});
