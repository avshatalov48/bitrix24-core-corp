import {Type} from 'main.core';
import {Program} from './program';
import {Logger} from '../lib/logger';
import {Debug} from '../lib/debug';

class ProgramManager
{
	init(bounceTimeout)
	{
		this.bounceTimeout = bounceTimeout;
		this.history = this.loadHistory();
		this.removeUnfinishedEvents();
	}

	add(appName, siteTitle, siteUrl)
	{
		const date = this.getDateForHistoryKey();
		if (!this.history[date])
		{
			this.history[date] = [new Program(appName, siteTitle, siteUrl)];
			Logger.log('Created history for: ' + date);
			return;
		}

		const site = this.findByAppNameAndSiteUrl(this.history[date], appName, siteUrl);
		const app = this.findByAppName(this.history[date], appName);

		this.finishLastInterval();
		if (site !== undefined)
		{
			this.startInterval(site);
			this.saveHistory();
			Logger.log('Started interval for host: ' + site.siteUrl + ' | URL: ' + site.appName);

			return;
		}
		else if (app !== undefined && siteUrl === '')
		{
			this.startInterval(app);
			this.saveHistory();
			Logger.log('Started interval for app ' + app.appName);

			return;
		}

		this.history[date].push(new Program(appName, siteTitle, siteUrl));
		this.saveHistory();
		Logger.log(this.history);
	}

	getDateForHistoryKey()
	{
		const date = new Date();
		const addZero = (num) => (num >= 0 && num <= 9) ? '0' + num : num;

		return date.getFullYear() + '-' + addZero(date.getMonth() + 1) + '-' + addZero(date.getDate());
	}

	findByAppName(programs, appName)
	{
		return programs.find((program) => program.appName === appName);
	}

	findByAppNameAndSiteUrl(programs, appName, siteUrl)
	{
		return programs.find((program) => (program.appName === appName && program.siteUrl === siteUrl));
	}

	startInterval(program)
	{
		program.time.push({ start: new Date(), finish: null });
		Logger.log(this.history);
		if (program.siteTitle)
		{
			Debug.log('Started site', `Host: ${program.host}`, `title: ${program.siteTitle}`, `URL: ${program.siteUrl}`);
		}
		else
		{
			Debug.log('Started app', `Name: ${program.appName}`);
		}
	}

	finishLastInterval()
	{
		for (let day in this.history)
		{
			this.history[day].forEach((program) =>
			{
				program.time.forEach((time) =>
				{
					if (time.finish === null)
					{
						time.finish = new Date();

						if (program.siteTitle)
						{
							Debug.log('Finished site', `Host: ${program.host}`, `title: ${program.siteTitle}`, `URL: ${program.siteUrl}`);
						}
						else
						{
							Debug.log('Finished app', `Name: ${program.appName}`);
						}
					}
				})
			});
		}
	}

	getGroupedHistory()
	{
		const history = BX.util.objectClone(this.history);
		const bounceTime = Math.round(this.bounceTimeout / 1000);
		const groupedHistory = {};

		for (let day in history)
		{
			groupedHistory[day] = history[day]
				.map(this.calculateTimeInProgram)
				.filter(program => program.time > bounceTime);
		}

		return groupedHistory;
	}

	calculateTimeInProgram(program)
	{
		program.time = program.time.map(interval =>
		{
			const finish = interval.finish ? new Date(interval.finish) : new Date();
			return finish - new Date(interval.start);
		}).reduce((sum, interval) => sum + interval, 0);

		program.time = Math.round(program.time / 1000);

		return program;
	}

	loadHistory()
	{
		return BX.desktop.getLocalConfig('bx_timeman_monitor_history', '{}');
	}

	saveHistory()
	{
		BX.desktop.setLocalConfig('bx_timeman_monitor_history', this.history);
	}

	removeHistoryBeforeDate(actualDate)
	{
		if (!actualDate)
		{
			return;
		}

		const actualHistoryDate = new Date(actualDate + ' 00:00:00');
		for (let date in this.history)
		{
			let historyDate = new Date(date + ' 00:00:00');
			if (historyDate < actualHistoryDate)
			{
				delete this.history[date];
				this.saveHistory();

				Logger.warn('History for the ' + date + ' has been deleted');
			}
		}
	}

	removeUnfinishedEvents()
	{
		for (let day in this.history)
		{
			this.history[day] = this.history[day].map((program) =>
				{
					program.time = program.time.filter((time) => time.finish !== null);
					return program;
				}
			);
		}

		this.saveHistory();
	}
}

const programManager = new ProgramManager();

export {programManager as ProgramManager};