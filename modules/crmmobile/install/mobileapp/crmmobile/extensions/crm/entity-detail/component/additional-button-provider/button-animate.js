/**
 * @module crm/entity-detail/component/communication-button/button-animate
 */
jn.define('crm/entity-detail/component/communication-button/button-animate', (require, exports, module) => {
	const { inRange } = require('utils/number');

	const SCROLL_DIRECTION = {
		UP: 'up',
		DOWN: 'down',
	};

	const buttonPosition = 22;
	let verticalOffset = 0;
	let directionOffset = null;
	let inProgress = false;

	/**
	 * @function animateScrollButton
	 */
	const animateScrollButton = (scrollParams, button, scrollTab, activeTab) => {
		if (scrollTab !== activeTab || inProgress)
		{
			return;
		}

		const { contentOffset: { y, x }, contentSize: { height } } = scrollParams;
		const verticalInRange = inRange(y, verticalOffset - 10, verticalOffset + 10);

		let direction = directionOffset;
		if (verticalOffset > y || verticalOffset < 0 || verticalOffset >= height)
		{
			direction = SCROLL_DIRECTION.DOWN;
		}
		else if (verticalOffset < y)
		{
			direction = SCROLL_DIRECTION.UP;
		}

		verticalOffset = y;

		if (
			!button.buttonRef
			|| directionOffset === direction
			|| (x === 0 && y === 0)
			|| verticalInRange
		)
		{
			return;
		}

		const transitionOffset = direction === SCROLL_DIRECTION.UP
			? -100
			: buttonPosition;

		inProgress = true;
		button.buttonRef.animate({
			duration: 500,
			bottom: transitionOffset,
		}, () => {
			inProgress = false;
		});

		directionOffset = direction;
	};

	module.exports = { animateScrollButton };
});
