import {Cache, Tag, Text, Type, Runtime} from 'main.core';
import 'ui.vue.components.audioplayer';
import {Vue} from 'ui.vue';
import File from './file';

export default class Audio extends File
{
	static checkForPaternity(fileData)
	{
		return fileData['extension'] === 'mp3';
	}

	constructor(data, container, options)
	{
		super(data, container, options);
		setTimeout(this.renderPlayer.bind(this), 10);
	}
	renderPlayer()
	{
		this.getNode().classList.add('post-item-attached-audio');
		this.getNode().innerHTML = '';

		Vue.create({
			el: this.getNode().appendChild(document.createElement('DIV')),
			template: `<bx-audioplayer src="${this.data.downloadUrl}" background="dark"/>`
		});
	}
}