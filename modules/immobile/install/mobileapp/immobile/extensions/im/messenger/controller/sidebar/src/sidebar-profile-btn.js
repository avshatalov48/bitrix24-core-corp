/**
 * @module im/messenger/controller/sidebar/sidebar-profile-btn
 */
jn.define('im/messenger/controller/sidebar/sidebar-profile-btn', (require, exports, module) => {
	const { Logger } = require('im/messenger/lib/logger');

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
						marginBottom: 12,
						flexDirection: 'row',
					},
				},
				...this.state.buttonElements,
			);
		}

		componentDidMount()
		{
			Logger.log('SidebarProfileBtn.view.componentDidMount');
			this.bindListener();
			this.subscribeStoreEvents();
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindListener()
		{
			this.unsubscribeStoreEvents = this.unsubscribeStoreEvents.bind(this);
			this.onChangeMuteBtn = this.onChangeMuteBtn.bind(this);
			this.onUpdateBtn = this.onUpdateBtn.bind(this);
		}

		subscribeStoreEvents()
		{
			Logger.log('SidebarProfileBtn.view.subscribeStoreEvents');
			BX.addCustomEvent('onCloseSidebarWidget', this.unsubscribeStoreEvents);
			BX.addCustomEvent('onChangeMuteBtn', this.onChangeMuteBtn);
			BX.addCustomEvent('onUpdateBtn', this.onUpdateBtn);
		}

		unsubscribeStoreEvents()
		{
			Logger.log('SidebarProfileBtn.view.unsubscribeStoreEvents');
			BX.removeCustomEvent('onCloseSidebarWidget', this.unsubscribeStoreEvents);
			BX.removeCustomEvent('oncChangeMuteBtn', this.onChangeMuteBtn);
			BX.removeCustomEvent('onUpdateBtn', this.onUpdateBtn);
		}

		/**
		 * @desc Handler update mute btn
		 * @param {LayoutComponent} btn
		 * @void
		 */
		onChangeMuteBtn(btn)
		{
			const oldState = this.state.buttonElements;
			const newState = [...oldState];
			newState[2] = btn;
			this.updateStateView({ buttonElements: newState });
		}

		/**
		 * @desc Handler update all btns
		 * @param {Array<LayoutComponent>} btns
		 * @void
		 */
		onUpdateBtn(btns)
		{
			this.updateStateView({ buttonElements: btns });
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
