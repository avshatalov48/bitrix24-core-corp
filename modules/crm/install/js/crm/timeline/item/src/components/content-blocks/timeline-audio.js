import { AudioPlayer } from 'crm.audio-player';
import { LogoType } from '../enums/logo-type';

export const TimelineAudio = AudioPlayer.getComponent({
	methods: {
		changeLogoIcon(icon: String)
		{
			if (!this.$parent || !this.$parent.getLogo)
			{
				return;
			}

			const logo = this.$parent.getLogo();
			if (!logo)
			{
				return;
			}

			logo.setIcon(icon);
		},

		audioEventRouterWrapper(eventName: String, event)
		{
			this.audioEventRouter(eventName, event);

			if (eventName === 'play')
			{
				this.changeLogoIcon(LogoType.CALL_AUDIO_PAUSE);
			}

			if (eventName === 'pause')
			{
				this.changeLogoIcon(LogoType.CALL_AUDIO_PLAY);
			}
		},
	},
});
