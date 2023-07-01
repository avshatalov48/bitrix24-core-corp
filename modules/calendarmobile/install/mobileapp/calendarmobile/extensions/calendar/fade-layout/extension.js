/**
 * @module calendar/fade-layout
 */
jn.define('calendar/fade-layout', (require, exports, module) => {

	const FadeConfig = {
		DURATION: 250,
		OPTION: 'linear',
		OPACITY_OUT: 0,
		OPACITY_IN: 1,
	};

	/**
	 * @class FadeLayout
	 */
	class FadeLayout
	{
		fadeIn(ref, config={})
		{
			const option = BX.prop.getString(config, 'option', FadeConfig.OPTION);
			const opacity = BX.prop.getInteger(config, 'opacityIn', FadeConfig.OPACITY_IN);
			const duration = BX.prop.getInteger(config, 'duration', FadeConfig.DURATION);

			return new Promise((resolve) => {
				ref.animate({
					opacity: opacity,
					duration: duration,
					option: option,
				}, resolve);
			})
		}

		fadeOut(ref, config={})
		{
			const option = BX.prop.getString(config, 'option', FadeConfig.OPTION);
			const opacity = BX.prop.getInteger(config, 'opacityOut', FadeConfig.OPACITY_OUT);
			const duration = BX.prop.getInteger(config, 'duration', FadeConfig.DURATION);

			return new Promise((resolve) => {
				ref.animate({
					opacity: opacity,
					duration: duration,
					option: option,
				}, resolve);
			})
		}

		animate(value, stage1, stage2)
		{
			return new Promise((resolve) => {

				const animations = [];

				stage1.forEach((item) => animations.push(
					value
						? this.fadeOut(item.ref,  BX.prop.getObject(item, 'config', {}))
						: this.fadeIn(item.ref,  BX.prop.getObject(item, 'config', {}))
				));

				stage2.forEach((item) => animations.push(
					value
						? this.fadeIn(item.ref,  BX.prop.getObject(item, 'config', {}))
						: this.fadeOut(item.ref,  BX.prop.getObject(item, 'config', {}))
				));

				Promise.all(animations)
					.then(() => resolve())
					.catch((response) =>
					{
						console.error('alert error');
						console.error(response);
					});
			})
		}
	}

	module.exports = {
		FadeLayout: new FadeLayout(),
		FadeConfig
	};
});
