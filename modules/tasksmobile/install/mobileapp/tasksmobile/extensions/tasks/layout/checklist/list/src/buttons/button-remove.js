/**
 * @module tasks/layout/checklist/list/src/buttons/button-remove
 */
jn.define('tasks/layout/checklist/list/src/buttons/button-remove', (require, exports, module) => {
	const { animate } = require('animation');
	const { PropTypes } = require('utils/validation');
	const { Color } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');

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
					testId: 'bin_block',
					style: {
						padding: 4,
						opacity: 0,
					},
					ref: (ref) => {
						this.buttonRef = ref;
					},
					onClick: () => {
						if (this.isShow && onClick)
						{
							onClick();
						}
					},
				},
				IconView({
					icon: Icon.TRASHCAN,
					color: Color.base5,
				}),
			);
		}
	}

	ButtonRemove.propTypes = {
		onClick: PropTypes.func,
	};

	module.exports = { ButtonRemove };
});
