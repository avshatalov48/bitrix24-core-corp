import {PageInstance} from './feed';
import {EventEmitter} from "main.core.events";

class NextPageLoader
{
	constructor()
	{
		this.initialized = false;

		this.init();
		EventEmitter.subscribe('onFrameDataProcessed', () => {
			this.init();
		});
	}

	init()
	{
		const buttonNode = this.getButtonNode();

		if (
			!buttonNode
			|| this.initialized
		)
		{
			return;
		}

		this.initialized = true;
		this.initEvents();
	}

	initEvents()
	{
		const buttonNode = this.getButtonNode();

		buttonNode.addEventListener('click', (e) => {
			PageInstance.refresh(true);
			return false;
		});
	}

	getButtonNode()
	{
		return document.getElementById('next_page_refresh_needed_button');
	}

	startWaiter()
	{
		const button = this.getButtonNode();
		if (button)
		{
			button.classList.add('--loading');
		}
	}

	stopWaiter()
	{
		const button = this.getButtonNode();
		if (button)
		{
			button.classList.remove('--loading');
		}
	}
}

export {
	NextPageLoader,
}