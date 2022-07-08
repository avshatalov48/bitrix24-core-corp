import { ReactionHandler } from 'im.event-handler';
import { Logger } from 'im.lib.logger';

export class WidgetReactionHandler extends ReactionHandler
{
	onOpenMessageReactionList({data})
	{
		Logger.warn('Reactions list is blocked for the widget', data);
	}
}