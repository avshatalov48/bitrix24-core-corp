import { CommunicationsTypeDict } from 'humanresources.company-structure.structure-components';
import { ChannelSelectorDictionary } from './channel-selector-dictionary';
import { ChatSelectorDictionary } from './chat-selector-dictionary';
import { CollabSelectorDictionary } from './collab-selector-dictionary';
import { AbstractSelectorDictionary } from '../selector-dictionary';

export function createSelectorDictionary(type): AbstractSelectorDictionary
{
	switch (type)
	{
		case CommunicationsTypeDict.chat:
			return new ChatSelectorDictionary();
		case CommunicationsTypeDict.channel:
			return new ChannelSelectorDictionary();
		case CommunicationsTypeDict.collab:
			return new CollabSelectorDictionary();
		default:
			throw new Error('Unknown selector type');
	}
}
