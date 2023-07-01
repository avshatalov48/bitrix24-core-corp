/**
 * @module crm/entity-detail/toolbar/content/templates/audio-player
 */
jn.define('crm/entity-detail/toolbar/content/templates/audio-player', (require, exports, module) => {
	const { ToolbarContentTemplateBase } = require('crm/entity-detail/toolbar/content/templates/base');
	const { AudioPlayerWrapper } = require('crm/entity-detail/toolbar/content/templates/audio-player-wrapper');

	const nothing = () => {};

	/**
	 * @typedef PinnableAudioPlayerProps
	 * @property {string} title
	 * @property {number} duration
	 * @property {number} speed
	 * @property {number} currentTime
	 * @property {string} uid
	 * @property {string} uri
	 */

	class AudioPlayer extends ToolbarContentTemplateBase
	{
		/**
		 * @return {PinnableAudioPlayerProps}
		 */
		getProps()
		{
			return this.props;
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						top: -80,
						position: 'absolute',
						width: '100%',
					},
					ref: (ref) => this.ref = ref,
					interactable: true,
					clickable: false,
				},
				new AudioPlayerWrapper({
					...this.props,
					ref: (ref) => this.wrapperRef = ref,
					clickable: this.state.visible,
				}),
				View(
					{
						style: {
							position: 'absolute',
							bottom: 3,
							left: 0,
							width: '100%',
							height: 30,
						},
						clickable: this.state.visible,
						onPan: this.state.visible && nothing,
						onTouchesBegan: ({ x }) => {
							if (this.getProps().duration && this.wrapperRef)
							{
								this.wrapperRef.onTouchesBegan(x);
							}
						},
						onTouchesMoved: ({ x }) => {
							if (this.getProps().duration && this.wrapperRef)
							{
								this.wrapperRef.onTouchesMoved(x);
							}
						},
						onTouchesEnded: ({ x }) => {
							if (this.getProps().duration && this.wrapperRef)
							{
								this.wrapperRef.onTouchesEnded(x);
							}
						},
					},
				),
			);
		}

		shouldHighlightOnShow()
		{
			return false;
		}
	}

	module.exports = { AudioPlayer };
});
