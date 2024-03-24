/**
 * @module layout/ui/button-list/sliding-button-list
 */

jn.define('layout/ui/button-list/sliding-button-list', (require, exports, module) => {
	const { PillButton } = require('layout/ui/button-list/pill-button');

	class SlidingButtonList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.bindButtons(props);
		}

		componentWillReceiveProps(props)
		{
			this.bindButtons(props);
		}

		bindButtons(props)
		{
			const { buttonsData } = props;
			const buttons = new Map();

			buttonsData.forEach((button, index) => {
				buttons.set(button.id, button);
			});

			this.state = { buttons };
		}

		onDeleteButton(buttonId)
		{
			return () => {
				const { buttons } = this.state;

				buttons.delete(buttonId);

				this.setState({ buttons });
			};
		}

		renderButtons()
		{
			const { buttons } = this.state;
			const pillButtons = [];

			buttons.forEach((button) => {
				pillButtons.push(PillButton({
					...button,
					onDeleteButton: this.onDeleteButton(button.id),
				}));
			});

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				...pillButtons,
			);
		}

		render()
		{
			return ScrollView(
				{
					style: {
						height: 30,
					},
					bounces: false,
					horizontal: true,
					showsHorizontalScrollIndicator: false,
					showsVerticalScrollIndicator: false,
				},
				this.renderButtons(),
			);
		}
	}

	module.exports = {
		SlidingButtonList: (props) => new SlidingButtonList(props),
	};
});
