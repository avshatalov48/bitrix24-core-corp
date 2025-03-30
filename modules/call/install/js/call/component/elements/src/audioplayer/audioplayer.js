import { BitrixVue } from 'ui.vue3';
import { AudioPlayer as UIAudioPlayer, AudioPlayerState } from 'ui.vue3.components.audioplayer';
import { Dom, bind, unbind, Type } from 'main.core';

import './audioplayer.css';

const defaultPlaybackRateValues = [0.5, 0.7, 1.0, 1.2, 1.5, 1.7, 2.0];
// @vue/component
export const AudioPlayer = BitrixVue.cloneComponent(UIAudioPlayer, {
	name: 'AudioPlayer',
	components: { },
	props: {
		context: {
			required: false,
			default: window,
		},
		playbackRateValues: {
			type: Array,
			required: false,
			default: () => defaultPlaybackRateValues,
		},
		isShowPlaybackRateMenu: {
			type: Boolean,
			required: false,
			default: true,
		},
		analyticsCallback: {
			type: Function,
			required: false,
			default: () => {},
		},
	},
	data() {
		return {
			...this.parentData(),
			playbackRate: defaultPlaybackRateValues[2],
			isSeeking: true,
		};
	},
	computed:
	{
		progressPosition()
		{
			return { width: `${this.progressInPixel}px` };
		},
		seekPosition(): string
		{
			const minSeekWidth = 20;
			const seekWidth = this.$refs.seek?.offsetWidth ?? minSeekWidth;

			return this.progressInPixel
				? `left: ${this.progressInPixel - (seekWidth / 2)}px;`
				: `left: ${this.progressInPixel - 2}px`;
		},
		playbackRateMenuItems(): Array
		{
			return this.playbackRateValues.map((rate) => {
				return this.createPlaybackRateMenuItem({
					text: this.getPlaybackRateOptionText(rate),
					rate,
					isActive: rate === this.playbackRate,
				});
			});
		},
		formatTimeCurrent(): string
		{
			return this.formatTime(this.timeCurrent);
		},
		formatTimeTotal(): string
		{
			return this.formatTime(this.timeTotal);
		},
	},
	methods: {
		choosePlaybackTime(seconds)
		{
			if (!this.source())
			{
				return;
			}
			this.source().currentTime = seconds;
			this.audioEventRouter('timeupdate');

			if (this.state !== AudioPlayerState.play)
			{
				this.clickToButton();
			}
		},
		startSeeking(event): void
		{
			this.isSeeking = true;
			const { clientX } = event;

			if (this.source())
			{
				this.source().pause();

				bind(this.context.document, 'mouseup', this.finishSeeking);
				bind(this.context.document, 'mousemove', this.seeking);

				this.setSeekByCursor(clientX);
			}
		},
		seeking(event): void
		{
			if (!this.isSeeking)
			{
				return;
			}

			const timeline = this.$refs.track;
			const { clientX } = event;
			const { left, right, width } = Dom.getPosition(timeline);

			this.seek = Math.max(0, Math.min(clientX - left, width - 1));

			this.setPosition();
			event.preventDefault();
		},
		finishSeeking(): void
		{
			this.isSeeking = false;
			this.setPosition();
			if (this.source())
			{
				this.source().play();

				unbind(this.context.document, 'mouseup', this.finishSeeking);
				unbind(this.context.document, 'mousemove', this.seeking);
			}
		},
		setPosition(): void
		{
			if (!this.loaded)
			{
				this.loadFile(true);

				return;
			}

			const pixelPerPercent = this.$refs.track.offsetWidth / 100;
			this.setProgress(this.seek / pixelPerPercent, this.seek);
			this.source().currentTime = this.timeTotal / 100 * this.progress;
		},
		setProgress(percent, pixel = -1): void
		{
			this.progress = Number.isNaN(percent) ? 0 : percent;
			this.progressInPixel = pixel > 0 ? pixel : Math.round(this.$refs.track.offsetWidth / 100 * percent);
		},
		setSeekByCursor(x: number): void
		{
			const timeline = this.$refs.track;
			const { left, width } = Dom.getPosition(timeline);

			this.seek = Math.max(0, Math.min(x - left, width));
		},
		showPlaybackRateMenu(): void {
			if (this.menu && this.menu.getPopupWindow().isShown()) {
				return;
			}

			const { BX } = this.context;
			const bindElement = this.$refs.playbackRateButtonContainer;

			this.menu = new BX.Main.Menu({
				id: `bx-call-audio-player-${this.id}`,
				className: 'bx-call-audio-player__playback-speed-menu_scope',
				width: 100,
				bindElement,
				events: {
					onPopupShow: () => {
						const { width: btnContainerWidth } = Dom.getPosition(bindElement);
						const popupWindow = this.menu.getPopupWindow();

						popupWindow.setOffset({ offsetLeft: btnContainerWidth / 2 });
						popupWindow.adjustPosition();
					},
					onPopupClose: () => {
						this.menu.destroy();
						this.menu = null;
					},
				},
				angle: { position: 'top' },
				offsetLeft: 0,
				items: this.playbackRateMenuItems,
			});

			this.menu.show();
		},
		getPlaybackRateOptionText(rate: number): string
		{
			return Type.isFloat(rate) ? `${rate}x` : `${rate}.0x`;
		},
		createPlaybackRateMenuItem({ rate = 1, text = '', isActive = false } = {}): Object
		{
			return {
				text,
				className: `bx-call-audio-player__playback-speed-menu-item ${isActive ? 'menu-popup-item-accept-sm' : ''}`,
				onclick: (event, item) => {
					this.changePlaybackRate(rate);
					item.menuWindow.popupWindow.close();
					return true;
				},
			};
		},
		changePlaybackRate(playbackRate: number): void
		{
			this.playbackRate = playbackRate;
			if (this.$refs?.source?.playbackRate)
			{
				this.$refs.source.playbackRate = playbackRate;
			}
		},
		onClickControlButton()
		{
			if (this.state !== AudioPlayerState.play)
			{
				this.analyticsCallback();
			}

			this.clickToButton();
		},
	},
	template: `
		<div 
			class="bx-call-audio-player__container bx-call-audio-player__scope" 
			ref="body"
		>
			<div class="bx-call-audio-player__control-container">
				<button :class="['bx-call-audio-player__control-button', {
					'bx-call-audio-player__control-loader': loading,
					'bx-call-audio-player__control-play': !loading && state !== State.play,
					'bx-call-audio-player__control-pause': !loading && state === State.play,
				}]" @click="onClickControlButton"></button>
			</div>
			<div class="bx-call-audio-player__timeline-container">
				<div class="bx-call-audio-player__track-container" @mousedown="startSeeking" ref="track">
					<div class="bx-call-audio-player__track-mask">
						<div v-for="n in 7" :key="n" class="bx-call-audio-player__track-mask-separator"></div>
					</div>
					<div class="bx-call-audio-player__track-mask --active" :style="progressPosition"></div>
					<div class="bx-call-audio-player__track-seek" @mousedown="startSeeking" :style="seekPosition">
						<div ref="seek" class="bx-call-audio-player__track-seek-icon"></div>
					</div>
				</div>
				<div class="bx-call-audio-player__timer-container">
					<span>{{formatTimeCurrent}}</span>
					<span>{{formatTimeTotal}}</span>
				</div>
			</div>
			<div
				@click="showPlaybackRateMenu"
				ref="playbackRateButtonContainer"
				class="bx-call-audio-player__playback-speed-menu-container"
				style="user-select: none;"
			>
				{{ getPlaybackRateOptionText(playbackRate) }}
			</div>
			<audio 
				v-if="src" 
				:src="src" 
				class="bx-call-audio-player__audio-source" 
				ref="source" 
				:preload="preload"
				@abort="audioEventRouter('abort', $event)"
				@error="audioEventRouter('error', $event)"
				@suspend="audioEventRouter('suspend', $event)"
				@canplay="audioEventRouter('canplay', $event)"
				@canplaythrough="audioEventRouter('canplaythrough', $event)"
				@durationchange="audioEventRouter('durationchange', $event)"
				@loadeddata="audioEventRouter('loadeddata', $event)"
				@loadedmetadata="audioEventRouter('loadedmetadata', $event)"
				@timeupdate="audioEventRouter('timeupdate', $event)"
				@play="audioEventRouter('play', $event)"
				@playing="audioEventRouter('playing', $event)"
				@pause="audioEventRouter('pause', $event)"
			></audio>
		</div>
	`,
});
