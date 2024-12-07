/**
 * @module tasks/layout/task/view-new/services/layout-buttons
 */
jn.define('tasks/layout/task/view-new/services/layout-buttons', (require, exports, module) => {
	const { Color } = require('tokens');
	const { isEqual } = require('utils/object');
	const { TaskPriority } = require('tasks/enum');
	const store = require('statemanager/redux/store');
	const { selectByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks');

	class LayoutButtons
	{
		constructor({ taskId, layout, onTogglePriority, onShowContextMenu })
		{
			this.taskId = taskId;
			this.layout = layout;
			this.onTogglePriority = onTogglePriority;
			this.onShowContextMenu = onShowContextMenu;

			if (!this.isSubscribed())
			{
				this.subscribe();
			}

			this.initState();
		}

		/**
		 * @private
		 */
		initState()
		{
			const task = selectByTaskIdOrGuid(store.getState(), this.taskId);

			this.setState({
				isHighPriority: (task?.priority === TaskPriority.HIGH),
			});
		}

		/**
		 * @private
		 */
		subscribe()
		{
			this.cancelSubscription = store.subscribe(() => this.initState());
		}

		/**
		 * @private
		 * @return {boolean}
		 */
		isSubscribed()
		{
			return Boolean(this.cancelSubscription);
		}

		/**
		 * @public
		 */
		unsubscribe()
		{
			this.cancelSubscription?.();
			this.cancelSubscription = null;
		}

		/**
		 * @private
		 * @param {{
		 * 	isHighPriority: boolean
		 * }} nextState
		 */
		setState(nextState)
		{
			if (!isEqual(this.state, nextState))
			{
				this.state = nextState;
				this.render();
			}
		}

		/**
		 * @private
		 */
		render()
		{
			this.layout.setRightButtons([
				{
					testId: `isImportant_CONTENT_${this.state?.isHighPriority ? 'high' : 'normal'}`,
					svg: {
						content: Icons.fire(this.state?.isHighPriority),
					},
					callback: this.onTogglePriority,
				},
				{
					id: 'more-menu',
					type: 'more',
					callback: this.onShowContextMenu,
				},
			]);
		}
	}

	// todo take these icons from shared assets
	const Icons = {
		fire: (active = false) => `
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path 
					fill-rule="evenodd" 
					clip-rule="evenodd" 
					d="M13.5699 4.83956C13.5199 6.35956 14.5499 7.50956 15.7399 8.82956L15.7199 8.84956L15.7201 8.84982C17.0601 10.3398 18.5799 12.0297 18.5799 14.5596C18.5799 18.3313 14.1698 19.947 13.8969 20.047C13.8941 20.048 13.8918 20.0489 13.8899 20.0496C13.8299 20.0696 13.7699 20.0796 13.6999 20.0796H13.6799C13.5999 20.0796 13.5299 20.0596 13.4599 20.0296C13.4523 20.0257 13.4461 20.0219 13.4408 20.0187C13.4323 20.0134 13.4261 20.0096 13.4199 20.0096C13.3499 19.9696 13.2899 19.9196 13.2399 19.8596C13.2299 19.8396 13.2199 19.8396 13.2199 19.8396C13.2023 19.8219 13.195 19.8008 13.1879 19.7803C13.183 19.766 13.1781 19.7519 13.1699 19.7396C13.1665 19.7327 13.1607 19.7259 13.1546 19.7186C13.1429 19.7047 13.1299 19.6893 13.1299 19.6696V19.6496C13.1099 19.5996 13.1099 19.5596 13.1099 19.5096V19.4096C13.1099 19.3829 13.1188 19.3562 13.1277 19.3296C13.1321 19.3162 13.1366 19.3029 13.1399 19.2896C13.1434 19.279 13.1457 19.2672 13.1481 19.255C13.1524 19.2326 13.1569 19.209 13.1699 19.1896C13.1799 19.1746 13.1924 19.1596 13.2049 19.1446C13.2174 19.1296 13.2299 19.1146 13.2399 19.0996C13.2599 19.0696 13.2899 19.0396 13.3199 19.0096C13.3399 18.9996 13.3399 18.9896 13.3399 18.9896C13.3403 18.9892 13.3419 18.9878 13.3448 18.9854C13.4229 18.9197 14.3999 18.0978 14.3999 16.9596C14.3999 16.2196 13.9299 15.6096 13.3799 14.8996L13.359 14.8721C12.8544 14.2093 12.2964 13.4763 12.0399 12.5196C10.7599 13.4596 9.5999 14.6796 9.5999 16.4596C9.5999 17.2396 9.9799 18.0996 10.7599 19.0896C10.7732 19.1029 10.7777 19.1162 10.7821 19.1296C10.7843 19.1362 10.7866 19.1429 10.7899 19.1496C10.8199 19.1896 10.8399 19.2196 10.8499 19.2596C10.8536 19.2742 10.8586 19.2876 10.8635 19.3005C10.8719 19.3229 10.8799 19.3442 10.8799 19.3696V19.4796C10.8799 19.4996 10.8749 19.5196 10.8699 19.5396C10.8649 19.5596 10.8599 19.5796 10.8599 19.5996V19.6596C10.856 19.6674 10.8505 19.6738 10.8452 19.6798C10.8372 19.689 10.8299 19.6974 10.8299 19.7096C10.8236 19.7284 10.8134 19.7433 10.8042 19.7567C10.7987 19.7647 10.7936 19.7721 10.7899 19.7796C10.7599 19.8196 10.7299 19.8596 10.6899 19.8996C10.6866 19.9029 10.6832 19.9073 10.6799 19.9118C10.6732 19.9207 10.6666 19.9296 10.6599 19.9296H10.6499C10.5899 19.9796 10.5299 20.0096 10.4599 20.0296H10.4199C10.3799 20.0396 10.3299 20.0496 10.2899 20.0496H10.2499C10.2399 20.0496 10.2274 20.0471 10.2149 20.0446C10.2024 20.0421 10.1899 20.0396 10.1799 20.0396C10.1499 20.0296 10.1299 20.0296 10.0999 20.0296C7.2399 19.0496 5.3999 16.6196 5.3999 13.8396C5.3999 9.92956 9.5399 5.58956 12.3999 4.08956C12.6499 3.95956 12.9499 3.96956 13.1899 4.11956C13.4299 4.26956 13.5699 4.54956 13.5699 4.83956ZM15.5999 16.9696C15.5999 17.2696 15.5399 17.5496 15.4699 17.8196V17.8296C16.4399 17.1096 17.3999 16.0196 17.3999 14.5496C17.3999 12.4815 16.1623 11.1022 14.8537 9.64375L14.8499 9.63956L14.8425 9.63133C13.7348 8.40394 12.5995 7.14584 12.3999 5.47956C9.8599 7.07956 6.5999 10.7996 6.6199 13.8596C6.6199 15.5096 7.3999 16.9696 8.7199 17.9696C8.5199 17.4596 8.4099 16.9596 8.4099 16.4796C8.4099 13.8896 10.2499 12.2896 11.8599 11.1896C12.0899 11.0296 12.3899 11.0096 12.6499 11.1296C12.9099 11.2496 13.0899 11.4996 13.1199 11.7896C13.2198 12.7383 13.7585 13.4378 14.3277 14.1767L14.3299 14.1796C14.9499 14.9796 15.5999 15.8196 15.5999 16.9696Z" 
					fill="${active ? Color.accentMainWarning.toHex() : Color.base4.toHex()}"/>
			</svg>
		`,
	};

	module.exports = { LayoutButtons };
});
