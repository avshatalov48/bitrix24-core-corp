import {Loc, Type} from 'main.core';
import {Monitor} from './monitor';
import {Logger} from './lib/logger';
import {Debug} from './lib/debug';
import {UI} from 'ui.notification';

class Sender
{
	init(store)
	{
		this.enabled = false;
		this.store = store;

		this.attempt = 0;
		this.resendTimeout = 5000;
		this.resendTimeoutId = null;
	}

	send()
	{
		Logger.warn('Trying to send history...');

		BX.ajax.runAction('bitrix:timeman.api.monitor.recordhistory', {
			data: {
				history: JSON.stringify(this.getSentQueue()),
			}
		})
			.then(result =>
			{
				Debug.log('History sent');

				if (result.status === 'success')
				{
					Logger.warn('SUCCESS!');

					this.attempt = 0;

					this.afterSuccessSend();

					if (result.data.enabled === Monitor.getStatusDisabled())
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

	startSendingTimer()
	{
		this.resendTimeoutId = setTimeout(this.send.bind(this), this.getSendingDelay());
		Logger.log(`Next send in ${this.getSendingDelay() / 1000} seconds...`);
	}

	getSendingDelay()
	{
		return (this.attempt === 0 ? this.resendTimeout : this.resendTimeout * this.attempt);
	}

	getSentQueue()
	{
		return this.store.state.monitor.sentQueue;
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

		if (Type.isArrayFilled(this.getSentQueue()))
		{
			Logger.log('Preparing to send old history...');
			this.startSendingTimer();
		}

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

		clearTimeout(this.resendTimeoutId);
		Logger.log("Sender stopped");
	}

	afterSuccessSend()
	{
		Logger.warn('History sent');
		Debug.space();
		Debug.log('History sent');

		this.store.dispatch(
			'monitor/setLastSuccessfulSendDate',
			new Date(this.store.state.monitor.reportState.dateLog)
		)
			.then(() => {
				this.store.dispatch('monitor/clearSentHistory')
					.then(() => {
						this.store.dispatch('monitor/refreshDateLog');
						this.store.dispatch('monitor/clearSentQueue');

						BX.SidePanel.Instance.close();

						UI.Notification.Center.notify({
							content: Loc.getMessage('TIMEMAN_PWT_REPORT_NOTIFICATION_REPORT_SENT'),
							autoHideDelay: 5000,
						});
					});
			}
		);
	}
}

const sender = new Sender();

export {sender as Sender};