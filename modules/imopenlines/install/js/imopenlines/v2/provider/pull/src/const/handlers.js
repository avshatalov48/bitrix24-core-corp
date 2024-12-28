import { LinesPullHandler } from '../recent/recent';
import { SessionPullHandler } from '../session/session';
import { QueuePullHandler } from '../queue/queue';

export const OpenLinesHandlers = [
	LinesPullHandler,
	SessionPullHandler,
	QueuePullHandler,
];
