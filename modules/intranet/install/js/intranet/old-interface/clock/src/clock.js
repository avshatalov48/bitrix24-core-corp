import { EventEmitter } from 'main.core.events';

import './clock.css';

export class Clock
{
	init()
	{
		EventEmitter.subscribe('onJCClockInit', (config) => {
			window.JCClock.setOptions({
				centerXInline: 83,
				centerX: 83,
				centerYInline: 67,
				centerY: 79,
				minuteLength: 31,
				hourLength: 26,
				popupHeight: 229,
				inaccuracy: 15,
				cancelCheckClick: true,
			});
		});
	}
}
