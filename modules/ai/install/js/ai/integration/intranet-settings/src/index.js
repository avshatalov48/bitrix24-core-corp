import { EventEmitter } from 'main.core.events';
import { AiPage } from './ai_page';

EventEmitter.subscribe(
	EventEmitter.GLOBAL_TARGET,
	'BX.Intranet.Settings:onExternalPageLoaded:ai',
	() => new AiPage()
);
