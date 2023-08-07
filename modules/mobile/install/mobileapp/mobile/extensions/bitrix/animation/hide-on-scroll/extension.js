/**
 * @module animation/hide-on-scroll
 */
jn.define('animation/hide-on-scroll', (require, exports, module) => {
	const { inRange } = require('utils/number');

	const SCROLL_DIRECTION = {
		UP: 'up',
		DOWN: 'down',
	};

	/**
	 * @class HideOnScrollAnimator
	 */
	class HideOnScrollAnimator
	{
		constructor({ initialTopPosition = 0 } = {})
		{
			this.initialTopPosition = initialTopPosition;
			this.currentDirection = null;
			this.currentPositionY = null;

			this.animator = null;
		}

		/**
		 * @public
		 * @param {object} ref
		 * @param {object} scrollParams
		 * @param {number} scrollParams.contentOffset.y
		 * @param {number} scrollParams.contentSize.height
		 * @param {number} scrollViewHeight
		 */
		animateByScroll(ref, scrollParams, scrollViewHeight)
		{
			if (!ref)
			{
				return;
			}

			const { contentOffset: { y }, contentSize: { height } } = scrollParams;

			if (this.currentPositionY === null)
			{
				this.currentPositionY = y;
			}

			const verticalInRange = inRange(y, this.currentPositionY - 10, this.currentPositionY + 10);
			if (verticalInRange)
			{
				return;
			}

			const currentPositionY = this.currentPositionY;

			this.currentPositionY = y;

			const direction = this.getDirection(currentPositionY, y, height, scrollViewHeight);
			if (this.currentDirection === direction)
			{
				return;
			}

			void this.animate(ref, direction);
		}

		getDirection(currentPositionY, positionY, contentHeight, scrollViewHeight)
		{
			// is scrolled outside the starting point of content
			if (positionY < 0)
			{
				return SCROLL_DIRECTION.DOWN;
			}

			// is scrolled outside the ending point of content
			if (positionY + scrollViewHeight > contentHeight)
			{
				return SCROLL_DIRECTION.UP;
			}

			// is scrolled downside
			if (currentPositionY < positionY)
			{
				return SCROLL_DIRECTION.UP;
			}

			// is scrolled upside
			if (currentPositionY > positionY)
			{
				return SCROLL_DIRECTION.DOWN;
			}

			return this.currentDirection;
		}

		animate(ref, direction)
		{
			if (!ref)
			{
				return Promise.reject();
			}

			if (this.currentDirection === direction || this.animator)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				this.currentDirection = direction;

				this.animator = ref.animate({
					duration: 300,
					bottom: direction === SCROLL_DIRECTION.UP ? -100 : this.initialTopPosition,
					options: 'easeInOut',
				}, () => {
					this.animator = null;
					resolve();
				});
			});
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		show(ref)
		{
			this.currentPositionY = null;

			return this.animate(ref, SCROLL_DIRECTION.DOWN);
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		hide(ref)
		{
			this.currentPositionY = null;

			return this.animate(ref, SCROLL_DIRECTION.UP);
		}
	}

	module.exports = { HideOnScrollAnimator };
});
