import { Type } from 'main.core';
import { BitrixVue, BitrixVueComponentProps, BitrixVueComponentProxy, VueCreateAppResult } from 'ui.vue3';
import { AudioPlayer as UIAudioPlayer } from 'ui.vue3.components.audioplayer';
import { AudioPlayerProps } from './components-props/audio-player.js';

export class AudioPlayer
{
	#application: VueCreateAppResult;
	rootNode: HTMLElement | string;
	audioProps: Object;

	constructor(options)
	{
		this.setRootNode(options.rootNode);
		this.setAudioProps(options.audioProps);

		const AudioPlayerComponent = AudioPlayer.getComponent({});
		this.#application = BitrixVue.createApp({
			name: 'crm-audio-player',
			components: { AudioPlayerComponent },
			data: () => {
				return {
					audioProps: this.audioProps,
				};
			},
			template: '<AudioPlayerComponent v-bind="audioProps" />',
		});
	}

	attachTemplate(): void
	{
		this.#application.mount(this.rootNode);
	}

	detachTemplate(): void
	{
		this.#application.unmount(this.rootNode);
	}

	static getComponent(mutations: BitrixVueComponentProps): BitrixVueComponentProxy
	{
		const defaultMutations = AudioPlayerProps;

		Object.keys(mutations).forEach((mutationKey) => {
			defaultMutations[mutationKey] = (defaultMutations[mutationKey])
				? BX.util.objectMerge(defaultMutations[mutationKey], mutations[mutationKey])
				: mutations[mutationKey]
			;
		});

		return BitrixVue.cloneComponent(UIAudioPlayer, defaultMutations);
	}

	setRootNode(rootNode): void
	{
		if (rootNode === null || rootNode === undefined)
		{
			return;
		}

		if (Type.isString(rootNode))
		{
			this.rootNode = document.querySelector(`#${rootNode}`);
			if (!this.rootNode)
			{
				throw new Error('Crm.AudioPlayer: \'rootNode\' not found');
			}

			return;
		}

		if (Type.isElementNode(rootNode))
		{
			this.rootNode = rootNode;

			return;
		}

		throw new Error('Crm.AudioPlayer: \'rootNode\' Must be either a string or an ElementNode');
	}

	setAudioProps(audioProps): void
	{
		this.audioProps = audioProps;
	}
}

export const AudioPlayerComponent = AudioPlayer.getComponent({});
