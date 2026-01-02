/**
 * @module ui-system/form/buttons/button/src/state-decorator
 */
jn.define('ui-system/form/buttons/button/src/state-decorator', (require, exports, module) => {
	class ButtonStateDecorator extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.#updateState(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.#updateState(nextProps);
		}

		#updateState(props)
		{
			this.state = {
				loading: props.loading,
				disabled: props.disabled,
			};
		}

		render()
		{
			const { component: ButtonComponent, ref, ...restProps } = this.props;

			return new ButtonComponent({ ...restProps, ...this.state });
		}

		setLoading(loading)
		{
			this.setState({ loading });
		}

		setDisabled(disabled)
		{
			this.setState({ disabled });
		}
	}

	module.exports = { ButtonStateDecorator };
});
