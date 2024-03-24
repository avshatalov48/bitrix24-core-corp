/**
 * @module tasks/layout/checklist/list/src/buttons/button-remove
 */
jn.define('tasks/layout/checklist/list/src/buttons/button-remove', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { animate } = require('animation');
	const { outline: { trashCan } } = require('assets/icons');
	const { PropTypes } = require('utils/validation');

	const ERASE_SIZE = 20;

	/**
	 * @class ButtonRemove
	 */
	class ButtonRemove extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.isShow = false;
			this.buttonRef = null;
		}

		show()
		{
			this.animateToggleButton({ show: true });
		}

		hide()
		{
			this.animateToggleButton({ show: false });
		}

		/**
		 * @private
		 * @param {boolean} show
		 */
		animateToggleButton({ show })
		{
			this.isShow = show;
			animate(this.buttonRef, {
				opacity: show ? 1 : 0,
				duration: 300,
			}).catch(console.error);
		}

		render()
		{
			const { onClick } = this.props;

			return View(
				{
					style: {
						padding: 4,
					},
					onClick: () => {
						if (this.isShow && onClick)
						{
							onClick();
						}
					},
				},
				Image({
					ref: (ref) => {
						this.buttonRef = ref;
					},
					tintColor: AppTheme.colors.base5,
					style: {
						opacity: 0,
						width: ERASE_SIZE,
						height: ERASE_SIZE,

					},
					svg: {
						content: trashCan(),
					},
				}),
			);
		}
	}

	ButtonRemove.propTypes = {
		onClick: PropTypes.func,
	};

	module.exports = { ButtonRemove };
});
