/**
 * @module layout/ui/widget-header-button
 */
jn.define('layout/ui/widget-header-button', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { isEqual, merge } = require('utils/object');

	/**
	 * @typedef {object} WidgetHeaderButtonProps
	 * @property {object} widget
	 * @property {string} text
	 * @property {string} loadingText
	 * @property {boolean|function():boolean} disabled
	 * @property {function():Promise} onClick
	 * @property {?function():Promise} onDisabledClick
	 */

	/**
	 * @class WidgetHeaderButton
	 */
	class WidgetHeaderButton
	{
		/**
		 * @param {WidgetHeaderButtonProps} props
		 */
		constructor(props)
		{
			if (!props.widget)
			{
				throw new Error("WidgetHeaderButton: 'widget' property is required");
			}

			if (!props.text)
			{
				throw new Error("WidgetHeaderButton: 'text' property is required");
			}

			/** @type {WidgetHeaderButtonProps} */
			this.props = merge({
				loadingText: props.text,
				disabled: false,
			}, props);

			const { widget, text, disabled } = this.props;

			this.widget = widget;

			this.state = {
				text,
				disabled: Boolean(typeof disabled === 'function' ? disabled() : disabled),
				loading: false,
			};

			this.render();
		}

		/**
		 * @private
		 */
		render()
		{
			this.widget.setRightButtons([
				{
					color: this.state.disabled || this.state.loading ? AppTheme.colors.base5 : AppTheme.colors.accentMainLinks,
					callback: () => this.onClick(),
					name: this.state.text,
					id: 'WidgetHeaderButton_save',
					type: 'text',
				},
			]);
		}

		/**
		 * @private
		 */
		onClick()
		{
			const { disabled, loading } = this.state;
			const { onClick, onDisabledClick } = this.props;

			if (disabled && !loading && onDisabledClick)
			{
				onDisabledClick();

				return;
			}

			if (disabled || loading || !onClick)
			{
				return;
			}

			this.startLoading();

			const result = onClick();

			if (result instanceof Promise)
			{
				result.finally(() => this.stopLoading());
			}
			else
			{
				this.stopLoading();
			}
		}

		/**
		 * @private
		 */
		startLoading()
		{
			this.setState({
				loading: true,
				text: this.props.loadingText,
			});
		}

		/**
		 * @private
		 */
		stopLoading()
		{
			this.setState({
				loading: false,
				text: this.props.text,
			});
		}

		/**
		 * @private
		 * @param {object} patch
		 */
		setState(patch = {})
		{
			const nextState = merge({}, this.state, patch);
			if (!isEqual(this.state, nextState))
			{
				this.state = nextState;
				this.render();
			}
		}

		/**
		 * @public
		 */
		disable()
		{
			this.setState({ disabled: true });
		}

		/**
		 * @public
		 * @param {boolean} enabled
		 */
		enable(enabled = true)
		{
			this.setState({ disabled: !enabled });
		}

		/**
		 * @public
		 */
		refresh()
		{
			if (typeof this.props.disabled === 'function')
			{
				const disabled = Boolean(this.props.disabled());
				this.setState({ disabled });
			}
			else
			{
				console.warn('WidgetHeaderButton.refresh() requires disabled property to be function');
			}
		}
	}

	module.exports = { WidgetHeaderButton };
});
