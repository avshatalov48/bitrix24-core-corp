import type { Scenario } from './scenario';
import { RuWhatsApp } from './scenarios/ru-whatsapp';
import { Telegram } from './scenarios/telegram';
import { WhatsApp } from './scenarios/whatsapp';

export class Factory
{
	static getScenarioInstance(name: string, params: Object): Scenario
	{
		if (name === 'telegrambot')
		{
			return new Telegram(params);
		}

		if (name === 'ru-whatsapp') // for RU region
		{
			return new RuWhatsApp(params);
		}

		if (name === 'whatsapp') // for not RU region
		{
			return new WhatsApp(params);
		}

		throw new RangeError(`Unknown scenario name: ${name}`);
	}
}
