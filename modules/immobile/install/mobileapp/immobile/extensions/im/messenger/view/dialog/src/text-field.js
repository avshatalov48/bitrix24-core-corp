/**
 * @module im/messenger/view/dialog/text-field
 */
jn.define('im/messenger/view/dialog/text-field', (require, exports, module) => {
	const { EventFilterType } = require('im/messenger/const');

	const { StateManager } = require('im/messenger/view/lib/state-manager');
	const { ProxyView } = require('im/messenger/view/lib/proxy-view');

	/**
	 * @class DialogTextField
	 */
	class DialogTextField extends ProxyView
	{
		/**
		 * @constructor
		 * @param {JNBaseClassInterface} ui
		 * @param {EventFilter} eventFilter
		 */
		constructor(ui, eventFilter)
		{
			super(ui, eventFilter);

			this.initStateManager();
		}

		initStateManager()
		{
			const state = {
				isShow: true,
				placeholder: null,
				actionButton: null,
				quoteParams: null,
			};

			this.stateManager = new StateManager(state);
		}

		/**
		 * @return {AvailableEventCollection}
		 */
		getAvailableEvents()
		{
			return {
				[EventFilterType.selectMessagesMode]: [],
			};
		}

		/**
		 * @param {string} text
		 */
		setText(text)
		{
			if (this.isUiAvailable())
			{
				this.ui.setText(text);
			}
		}

		/**
		 * @param {number} fromIndex
		 * @param {number} toIndex
		 * @param {string} bbCodeText
		 */
		replaceText(fromIndex, toIndex, bbCodeText)
		{
			if (this.isUiAvailable())
			{
				this.ui.replaceText(fromIndex, toIndex, bbCodeText);
			}
		}

		/**
		 * @return {string} text
		 */
		getText()
		{
			if (this.isUiAvailable())
			{
				return this.ui.getText();
			}

			return '';
		}

		/**
		 * @param {string} text
		 */
		setPlaceholder(text)
		{
			const newState = { placeholder: text };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.setPlaceholder(text);
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @return {number}
		 */
		getCursorIndex()
		{
			if (this.isUiAvailable())
			{
				return this.ui.getCursorIndex();
			}

			return 0;
		}

		/**
		 * @param {boolean} [isAnimated=false]
		 */
		show(isAnimated = false)
		{
			const newState = { isShow: true };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.show({ animated: isAnimated });
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @param { string } params.id
		 * @param { string } params.icon.name
		 * @param { string } params.icon.tintColor
		 */
		showActionButton(params)
		{
			const newState = { actionButton: params };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.showActionButton(params);
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @param {[Array<object>, Array<object>]} params
		 */
		showActionButtonPopupMenu(params)
		{
			if (this.isUiAvailable())
			{
				this.ui.showActionButtonPopupMenu(...params);
			}
		}

		hideActionButton()
		{
			const newState = { actionButton: null };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.hideActionButton();
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @param {boolean} [isAnimated=false]
		 */
		hide(isAnimated = false)
		{
			const newState = { isShow: false };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.hide({ animated: isAnimated });
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @void
		 */
		clear()
		{
			if (this.isUiAvailable())
			{
				this.ui.clear();
			}
		}

		/**
		 * @param {object} message
		 * @param {string} type
		 * @param {boolean} [openKeyboard=true]
		 * @param {string?} [title=null] (56+ API)
		 * @param {string?} [text=null] (56+ API)
		 */
		setQuote(message, type, openKeyboard, title, text)
		{
			const newState = { quoteParams: { message, type, openKeyboard, title, text } };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.setQuote(message, type, openKeyboard, title, text);
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @void
		 */
		removeQuote()
		{
			const newState = { quoteParams: null };
			const hasChanges = this.stateManager.hasChanges(newState);

			if (this.isUiAvailable() && hasChanges)
			{
				this.ui.removeQuote();
				this.stateManager.updateState(newState);
			}
		}

		/**
		 * @return {boolean} enable
		 */
		enableAlwaysSendButtonMode(enable)
		{
			if (this.isUiAvailable())
			{
				this.ui.enableAlwaysSendButtonMode(enable);
			}
		}

		/**
		 * @void
		 */
		showKeyboard()
		{
			if (this.isUiAvailable())
			{
				this.ui.showKeyboard?.();
			}
		}

		/**
		 * @void
		 */
		hideKeyboard()
		{
			if (this.isUiAvailable())
			{
				this.ui.hideKeyboard?.();
			}
		}

		/**
		 * @param {string} enabled
		 * @param {string} disabled
		 */
		setSendButtonColors({ enabled, disabled })
		{
			if (this.isUiAvailable())
			{
				this.ui.setSendButtonColors?.({ enabled, disabled });
			}
		}
	}

	module.exports = {
		DialogTextField,
	};
});
