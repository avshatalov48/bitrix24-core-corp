/**
 * @module haptics
 */
jn.define('haptics', (require, exports, module) => {
	include('haptic');

	const NotificationFeedbackType = {
		Success: 'success',
		Warning: 'warning',
		Error: 'error',
	};

	const ImpactFeedbackStyle = {
		Light: 'light',
		Medium: 'medium',
		Heavy: 'heavy',
	};

	const NotificationFeedbackTypeValues = Object.values(NotificationFeedbackType);
	const ImpactFeedbackStyleValues = Object.values(ImpactFeedbackStyle);

	const vibrate = (arg) => {
		if (Application.getApiVersion() >= 42 && typeof haptic !== 'undefined')
		{
			haptic.vibrate(arg);
		}
	};

	/**
	 * @class Haptics
	 */
	class Haptics
	{
		/**
		 * Vibrates just once or for a given milliseconds or by pattern.
		 *
		 * @param {number|string|null} ms
		 */
		static vibrate(ms = null)
		{
			vibrate(ms);
		}

		/**
		 * Vibrates with alternating vibration/pause melody in ms.
		 *
		 * @param {int[]} melody
		 */
		static vibrateMelody(melody)
		{
			if (!Array.isArray(melody))
			{
				throw new Error('Melody must be an array of vibrations/pauses sequence in ms.');
			}

			vibrate(melody);
		}

		/**
		 * Vibrates indicating that a task or action has done.
		 *
		 * @param {'success', 'warning', 'error'} notificationType
		 */
		static notify(notificationType)
		{
			if (!NotificationFeedbackTypeValues.includes(notificationType))
			{
				throw new RangeError(
					`Notification type must be one of the following [${NotificationFeedbackTypeValues.join(', ')}].`,
				);
			}

			this.vibrate(notificationType);
		}

		/**
		 * Vibrates indicating that a task or action has completed.
		 */
		static notifySuccess()
		{
			this.notify(NotificationFeedbackType.Success);
		}

		/**
		 * Vibrates indicating that a task or action has produced a warning of some kind.
		 */
		static notifyWarning()
		{
			this.notify(NotificationFeedbackType.Warning);
		}

		/**
		 * Vibrates indicating that a task or action has failed.
		 */
		static notifyFailure()
		{
			this.notify(NotificationFeedbackType.Error);
		}

		/**
		 * Vibrates providing a physical metaphor you can use to complement a visual experience.
		 *
		 * @param {'light'|'medium'|'heavy'} impactType
		 */
		static impact(impactType)
		{
			if (!ImpactFeedbackStyleValues.includes(impactType))
			{
				throw new RangeError(
					`Impact type must be one of the following [${ImpactFeedbackStyleValues.join(', ')}].`,
				);
			}

			this.vibrate(impactType);
		}

		/**
		 * Vibrates indicating a collision between small or lightweight UI objects.
		 */
		static impactLight()
		{
			this.impact(ImpactFeedbackStyle.Light);
		}

		/**
		 * Vibrates indicating a collision between medium-sized or medium-weight UI objects.
		 */
		static impactMedium()
		{
			this.impact(ImpactFeedbackStyle.Medium);
		}

		/**
		 * Vibrates indicating a collision between large or heavyweight UI objects.
		 */
		static impactHeavy()
		{
			this.impact(ImpactFeedbackStyle.Heavy);
		}
	}

	module.exports = { Haptics, NotificationFeedbackType, ImpactFeedbackStyle };
});
