/**
 * @module calendar/state/observe-state
 */
jn.define('calendar/state/observe-state', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { isEqual } = require('utils/object');

	const observeState = (WrappedComponent, mapStateToProps, state) => {
		WrappedComponent.prototype.__proto__ = PureComponent.prototype;

		class StateComponent extends WrappedComponent
		{
			constructor(props)
			{
				super(props);

				this.state = mapStateToProps(state);

				this.handleStateChange = this.handleStateChange.bind(this);
			}

			componentDidMount()
			{
				state.subscribe(this.handleStateChange);
			}

			componentWillUnmount()
			{
				state.unsubscribe(this.handleStateChange);
			}

			render()
			{
				const props = { ...this.props, ...this.state };

				if (WrappedComponent.prototype)
				{
					return new WrappedComponent(props);
				}

				return WrappedComponent(props);
			}

			handleStateChange()
			{
				const newState = mapStateToProps(state);
				if (!isEqual(this.state, newState))
				{
					this.setState(newState);
				}
			}
		}

		return StateComponent;
	};

	module.exports = { observeState };
});
