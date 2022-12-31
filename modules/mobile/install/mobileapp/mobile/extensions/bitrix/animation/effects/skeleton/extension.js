/**
 * @module animation/effects/skeleton
 */
jn.define('animation/effects/skeleton', (require, exports, module) => {

	const { chain, pause, transition } = require('animation');

	const animate = (ref) => {
		if (ref)
		{
			const fadeOut = transition(ref, {
				opacity: 0.3,
				duration: 400,
				option: 'easeIn',
			});
			const fadeIn = transition(ref, {
				opacity: 1,
				duration: 600,
				option: 'easeOut',
			});
			const fadeInOut = chain(
				pause(500),
				fadeOut,
				fadeIn,
			);

			fadeInOut();

			return setInterval(() => fadeInOut(), 1800);
		}

		return null;
	};

	module.exports = { animate };

});