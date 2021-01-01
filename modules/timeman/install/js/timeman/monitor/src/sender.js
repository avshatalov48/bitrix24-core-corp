import {Type} from 'main.core';
import {Monitor} from './monitor';
import {ProgramManager} from './model/programmanager';
import {Logger} from './lib/logger';
import {Debug} from './lib/debug';

class Sender
{
	init(sendTimeout = 5000, resendTimeout = 5000)
	{
		this.enabled = false;
		this.sendTimeout = sendTimeout;
		this.resendTimeout = resendTimeout;
		this.sendTimeoutId = null;
		this.attempt = 0;
	}

	send()
	{
		if (!this.enabled)
		{
			return;
		}

		const request = this.immediatelySendHistoryOnce();

		Logger.warn('Trying to send history...');

		request.then(result =>
			{
				if (result.status === 'success')
				{
					const response = result.data;

					Logger.warn('SUCCESS!');
					this.saveLastSuccessfulSendDate();
					ProgramManager.removeHistoryBeforeDate(this.getLastSuccessfulSendDate());

					this.attempt = 0;
					this.startSendingTimer();

					if (response.state === Monitor.getStateStop())
					{
						Logger.warn('Stopped after server response');
						Debug.log('Stopped after server response');

						Monitor.setState(response.state);
						Monitor.stop();
					}

					if (response.enabled === Monitor.getStatusDisabled())
					{
						Logger.warn('Disabled after server response');
						Debug.log('Disabled after server response');

						Monitor.disable();
					}
				}
				else
				{
					Logger.error('ERROR!');
					this.attempt++
					this.startSendingTimer();
				}
			})
			.catch(() =>
			{
				Logger.error('CONNECTION ERROR!');
				this.attempt++
				this.startSendingTimer();
			});
	}

	immediatelySendHistoryOnce()
	{
		const history = JSON.stringify(ProgramManager.getGroupedHistory());
		Debug.log('History sent');

		return BX.ajax.runAction('bitrix:timeman.api.monitor.recordhistory', { data: { history } });
	}

	startSendingTimer()
	{
		this.sendTimeoutId = setTimeout(this.send.bind(this), this.getSendingDelay());
		Logger.log(`Next send in ${this.getSendingDelay() / 1000} seconds...`);
	}

	getSendingDelay()
	{
		return (this.attempt === 0 ? this.sendTimeout : this.resendTimeout);
	}

	saveLastSuccessfulSendDate()
	{
		BX.desktop.setLocalConfig('bx_timeman_monitor_last_successful_send_date', ProgramManager.getDateForHistoryKey());
	}

	getLastSuccessfulSendDate()
	{
		return BX.desktop.getLocalConfig('bx_timeman_monitor_last_successful_send_date');
	}

	start()
	{
		if (this.enabled)
		{
			Logger.warn('Sender already started');
			return;
		}

		this.enabled = true;
		this.attempt = 0;
		this.startSendingTimer();

		Logger.log("Sender started");
	}

	stop()
	{
		if (!this.enabled)
		{
			Logger.warn('Sender already stopped');
			return;
		}

		this.enabled = false;
		this.attempt = 0;
		clearTimeout(this.sendTimeoutId);

		this.immediatelySendHistoryOnce();
		Logger.log("Immediately send request sent");
		Logger.log("Sender stopped");
	}
}

const sender = new Sender();

export {sender as Sender};