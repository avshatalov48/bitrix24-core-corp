/**
 * @module tasks/layout/checklist/list/src/checkbox/checkbox-counter/important
 */
jn.define('tasks/layout/checklist/list/src/checkbox/checkbox-counter/important', (require, exports, module) => {
	const { Corner, Color } = require('tokens');
	const { OutlineIconTypes } = require('assets/icons/types');
	const { animate } = require('animation');
	const { PureComponent } = require('layout/pure-component');
	const { IconView } = require('ui-system/blocks/icon');
	const IMPORTANT_SIZE = 16;

	/**
	 * @class ChecklistImportant
	 */
	class ChecklistImportant extends PureComponent
	{
		/**
		 * @param {object} props
		 * @param {function} [props.onClick]
		 * @param {boolean} [props.important]
		 */
		constructor(props)
		{
			super(props);

			this.handleOnClick = this.handleOnClick.bind(this);
			this.importantRef = null;

			this.state = {
				important: props.important,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				important: props.important,
			};
		}

		handleOnClick()
		{
			const { onClick } = this.props;

			if (onClick)
			{
				onClick();
			}
		}

		/**
		 * @param {boolean} important
		 * @returns {Promise<void>}
		 */
		toggleAnimateImportant(important)
		{
			if (this.importantRef)
			{
				return animate(this.importantRef, {
					duration: 300,
					opacity: important ? 1 : 0,
				});
			}

			return new Promise((resolve) => {
				this.setState({ important }, resolve);
			});
		}

		render()
		{
			const { important } = this.state;

			return View(
				{
					testId: 'importance_block',
					ref: (importantRef) => {
						this.importantRef = importantRef;
					},
					style: {
						position: 'absolute',
						top: -1,
						right: -1,
						alignItems: 'center',
						justifyContent: 'center',
						opacity: important ? 1 : 0,
						borderRadius: Corner.circle,
						backgroundColor: Color.bgContentPrimary,
					},
					onClick: this.handleOnClick,
				},
				IconView({
					icon: OutlineIconTypes.fire,
					iconColor: Color.accentMainWarning,
					iconSize: IMPORTANT_SIZE,
				}),
			);
		}
	}

	module.exports = { ChecklistImportant };
});
