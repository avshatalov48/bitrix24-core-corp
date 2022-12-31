/**
 * @module crm/timeline/ui/widget-header-button
 */
jn.define('crm/timeline/ui/widget-header-button', (require, exports, module) => {

	const { isEqual } = require('utils/object');

	/**
	 * @class WidgetHeaderButton
	 */
	class WidgetHeaderButton
	{
		constructor(props)
		{
			const { widget, text, disabled } = props;

			this.props = props;
			this.widget = widget;

			this.state = {
				text,
				disabled,
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
					color: this.state.disabled || this.state.loading ? '#BDC1C6' : '#0B66C3',
					callback: () => this.onClick(),
					name: this.state.text,
					id: 'save',
					type: 'text',
				},
			]);
		}

		/**
		 * @private
		 */
		onClick()
		{
			if (this.state.disabled || this.state.loading) {
				return;
			}

			this.startLoading();

			this.props.onClick().finally(() => this.stopLoading());
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
		 * @param {object} nextState
		 */
		setState(nextState = {})
		{
			const oldState = {};
			Object.keys(nextState).forEach(key => {
				oldState[key] = this.state[key];
				this.state[key] = nextState[key];
			});
			if (!isEqual(oldState, nextState)) {
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
		 */
		enable()
		{
			this.setState({ disabled: false });
		}
	}

	module.exports = { WidgetHeaderButton };

});