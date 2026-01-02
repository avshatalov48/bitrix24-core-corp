import { ajax } from 'main.core';
import { Header } from './header/header';
import { Content } from './content/content';
import { Footer } from './footer/footer';
import './app.css';

export const App = {
	name: 'ConfirmPageApp',
	components: {
		Header,
		Content,
		Footer,
	},
	props: {
		booking: {
			type: Object,
			required: true,
		},
		hash: {
			type: String,
			required: true,
		},
		company: {
			type: String,
			required: true,
		},
		context: {
			type: String,
			required: true,
		},
	},
	data(): Object
	{
		return {
			confirmedBooking: this.booking,
			confirmedContext: this.context,
			icsDownloadRequested: false,
		};
	},
	methods: {
		async bookingCancelHandler(): Promise<void>
		{
			try
			{
				await ajax.runComponentAction(
					'bitrix:booking.pub.confirm',
					'cancel',
					{
						mode: 'class',
						data: {
							hash: this.hash,
						},
					},
				);

				this.confirmedBooking.isDeleted = true;

				if (
					this.confirmedContext === 'delayed.pub.page'
					|| this.confirmedContext === 'info.pub.page'
				)
				{
					this.confirmedContext = 'cancel.pub.page';
				}
			}
			catch (error)
			{
				console.error('Confirm page: cancel error', error);
			}
		},
		async bookingConfirmHandler(): Promise<void>
		{
			try
			{
				await ajax.runComponentAction(
					'bitrix:booking.pub.confirm',
					'confirm',
					{
						mode: 'class',
						data: {
							hash: this.hash,
						},
					},
				);

				this.confirmedBooking.isConfirmed = true;

				if (this.confirmedContext === 'delayed.pub.page')
				{
					this.confirmedBooking.confirmedByDelayed = true;
					this.confirmedContext = 'cancel.pub.page';
				}
			}
			catch (error)
			{
				console.error('Confirm page: confirm error', error);
			}
		},
		async downloadIcsHandler(): Promise<void>
		{
			try
			{
				this.icsDownloadRequested = true;

				const { data } = await ajax.runComponentAction(
					'bitrix:booking.pub.confirm',
					'getIcsContent',
					{
						mode: 'class',
						data: {
							hash: this.hash,
						},
					},
				);

				const fileContent = data?.ics;

				if (!fileContent)
				{
					console.error('Receive empty ics file');

					return;
				}

				const fileName = 'booking.ics';
				const link = document.createElement('a');
				link.href = `data:text/calendar,${encodeURI(fileContent)}`;
				link.download = fileName;
				link.click();
			}
			catch (error)
			{
				console.error('Confirm page: can not receive ics file', error);
			}
			finally
			{
				this.icsDownloadRequested = false;
			}
		},
	},
	template: `
		<div class="confirm-page-container">
			<div class="confirm-page-body">
				<Header 
					:booking="confirmedBooking"
					:company="company"
					:context="confirmedContext"
				/>
				<Content :booking="confirmedBooking"/>
				<Footer 
					:booking="confirmedBooking"
					:context="confirmedContext"
					:icsDownloadRequested="icsDownloadRequested"
					@bookingCanceled="bookingCancelHandler"
					@bookingConfirmed="bookingConfirmHandler"
					@downloadIcs="downloadIcsHandler"
				/>
			</div>
		</div>
	`,
};
