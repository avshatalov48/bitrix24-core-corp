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

				if (this.confirmedContext === 'delayed.pub.page')
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
					@bookingCanceled="bookingCancelHandler"
					@bookingConfirmed="bookingConfirmHandler"
				/>
			</div>
		</div>
	`,
};
