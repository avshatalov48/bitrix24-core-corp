/**
 * @module layout/ui/detail-card/toolbar/actions-panel
 */
jn.define('layout/ui/detail-card/toolbar/actions-panel', (require, exports, module) => {
	const { throttle } = require('utils/function');
	const { get } = require('utils/object');
	const { ButtonsToolbar } = require('layout/ui/buttons-toolbar');

	const ButtonTypes = {
		PRIMARY: 'primary',
		SECONDARY: 'secondary',
	};

	/**
	 * @class ActionsPanel
	 */
	class ActionsPanel extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				visible: false,
			};

			this.setModel(this.props.model);

			this.handleActionClick = throttle(this.handleActionClick, 500, this);
		}

		componentWillReceiveProps(newProps)
		{
			this.setModel(newProps.model);
		}

		setModel(model)
		{
			this.model = model;
		}

		show()
		{
			if (this.state.visible !== true)
			{
				this.setState({ visible: true });
			}
		}

		hide()
		{
			if (this.state.visible !== false)
			{
				this.setState({ visible: false });
			}
		}

		getSafeAreaBottomHeight()
		{
			if (Application.getPlatform() === 'android')
			{
				return 0;
			}

			return get(device.screen, ['safeArea', 'bottom'], 0);
		}

		getHeight(visible)
		{
			if (!visible)
			{
				return 0;
			}

			return 58 + this.getSafeAreaBottomHeight();
		}

		render()
		{
			const { visible } = this.state;

			return View(
				{
					style: {
						position: 'absolute',
						left: 0,
						right: 0,
						bottom: 0,
						height: this.getHeight(visible),
					},
				},
				visible && ButtonsToolbar({
					safeArea: visible,
					buttons: this.prepareButtons(),
				}),
			);
		}

		prepareButtons()
		{
			const filteredItems = this.props.actions.filter((action) => action.onActiveCallback(this.props.item));

			return (
				filteredItems
					.map((action) => new PrimaryButton({
						text: action.title,
						onClick: () => this.handleActionClick(action),

					}))
			);
		}

		handleActionClick(action)
		{
			this.props.onActionStart(action);

			action
				.onClickCallback(this.model)
				.then((data) => this.props.onActionSuccess(action, data))
				.catch((data) => this.props.onActionFailure(action, data))
			;
		}
	}

	module.exports = {
		ActionsPanel,
		ButtonTypes,
	};
});
