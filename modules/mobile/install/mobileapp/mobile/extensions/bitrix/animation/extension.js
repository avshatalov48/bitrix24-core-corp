/**
 * @module animation
 */
jn.define('animation', (require, exports, module) => {
	/**
	 * Performs animation on node {ref} with specified {options}.
	 * @param {object} ref
	 * @param {object} options
	 * @returns {Promise}
	 */
	function animate(ref, options)
	{
		return new Promise((resolve) => (transitable(ref) ? ref.animate(options, resolve) : resolve()));
	}

	/**
	 * Creates function that will perform animation on specified ref.
	 * @param {object} ref
	 * @param {object} options
	 * @returns {function(): Promise}
	 */
	function transition(ref, options)
	{
		return () => animate(ref, options);
	}

	/**
	 * Creates function that will perform timeout for specified number of milliseconds.
	 * @param {number} ms
	 * @returns {function(): Promise}
	 */
	function pause(ms)
	{
		return () => new Promise((resolve) => setTimeout(resolve, ms));
	}

	/**
	 * Creates function that will perform a sequence of transitions.
	 * @param {function(): Promise} transitions
	 * @returns {function(): *}
	 */
	function chain(...transitions)
	{
		return () => transitions.reduce((prev, next) => prev.then(next), Promise.resolve());
	}

	/**
	 * Creates function that will perform parallel transitions.
	 * @param {function(): Promise} transitions
	 * @returns {function(): Promise}
	 */
	function parallel(...transitions)
	{
		return () => Promise.all(transitions.map((transition) => transition()));
	}

	/**
	 * Wraps specified ref into object with convenient proxy methods.
	 * @param {object} ref
	 * @returns {{
	 * 	transitable: (function(): boolean),
	 * 	animate: (function(*=): Promise),
	 * 	transition: (function(*=): function(): Promise)
	 * }}
	 */
	function useRef(ref)
	{
		return {
			animate: (options) => animate(ref, options),
			transition: (options) => transition(ref, options),
			transitable: () => transitable(ref),
		};
	}

	/**
	 * Checks if specified ref is valid node with animate() method.
	 * @param {object} ref
	 * @returns {boolean}
	 */
	function transitable(ref)
	{
		return (ref && typeof ref.animate === 'function');
	}

	module.exports = { animate, transition, pause, chain, parallel, useRef, transitable };
});
