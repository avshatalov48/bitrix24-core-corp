<?php
namespace Bitrix\ImConnector\InteractiveMessage\Connectors\IMessage;

use \Bitrix\Main\Error,
	\Bitrix\Main\Result,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\Crm\WebForm;
use \Bitrix\Calendar\UserField\ResourceBooking;
use \Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\InteractiveMessage;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage\IMessage
 */
class Output extends InteractiveMessage\Output
{
	protected const NATIVE_FORM = 'form';
	protected const NATIVE_PAYMENT = 'payment';
	protected const NATIVE_CUSTOM_APP = 'custom_app';
	protected const NATIVE_OAUTH = 'oauth';

	protected const NATIVE_FORM_TYPE_LIST = 'list';
	protected const NATIVE_FORM_TYPE_TIME = 'time';

	//form
	protected $formIds = [];
	protected $configFormsData = [];
	private const LIST_NATIVE_LIST_FIELDS_ID = [
		'checkbox',
		'list',
		'radio',
		'product'
	];
	public const LIST_NATIVE_TIME_FIELDS_ID = ['resourcebooking'];
	private const LIST_IGNORE_FIELDS_ID = ['layout'];

	//payment
	protected $paymentData = [];

	//custom apps
	private $appParams = [];

	//OAuth params
	private $oauthParams = [];

	/**
	 * Setting the list of id forms to send.
	 *
	 * @param array $ids
	 * @return bool
	 */
	public function setFormIds($ids = []): bool
	{
		$this->formIds = $ids;

		return true;
	}

	/**
	 * Setting payment data to be sent in a native message.
	 *
	 * @param array $data
	 * @return bool
	 */
	public function setPaymentData($data = []): bool
	{
		$this->paymentData = $data;

		return true;
	}

	public function setAppParams($params = []): bool
	{
		$this->appParams = $params;

		return true;
	}

	public function setOauthParams($params = []): bool
	{
		$this->oauthParams = $params;

		return true;
	}

	/**
	 * Data loading.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function load(): bool
	{
		$result = $this->loadConfigForms();

		if(!empty($this->paymentData) || !empty($this->appParams) || !empty($this->oauthParams))
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * Preloading information about all forms.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function loadConfigForms(): bool
	{
		$result = false;

		if(!empty($this->formIds))
		{
			foreach ($this->formIds as $id)
			{
				$form = self::getConfigForm($id);

				if($form->isSuccess())
				{
					$this->configFormsData[$id] = $form->getData();

					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected static function getNativeFields(): array
	{
		return array_merge(self::LIST_NATIVE_LIST_FIELDS_ID, self::LIST_NATIVE_TIME_FIELDS_ID);
	}

	/**
	 * Getting information about the CRM form.
	 *
	 * @param $idForm
	 * @return Result
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function getConfigForm($idForm): Result
	{
		$result = new Result();

		$descriptionForm = [];
		$items = [];
		$isFormNative = true;
		$isTypeListPicker = false;
		$isTypeTimePicker = false;
		$alternativeTitle = '';

		if(Loader::includeModule('crm'))
		{
			$formConfig = WebForm\Embed\Config::createById($idForm)->toArray();
			$descriptionForm = [
				'id' => $formConfig['id'],
				'lang' => $formConfig['lang'],
				'title' => $formConfig['data']['title'],
				'desc' => $formConfig['data']['desc'],
				'titleReply' => Loc::getMessage('IMCONNECTOR_INTERACTIVE_MESSAGE_IMESSAGE_REPLY_MESSAGE_FORM_TITLE'),
				'fields' => [],
				'type' => false,
				'native' => false
			];

			if(!empty($formConfig['data']['fields']))
			{
				foreach ($formConfig['data']['fields'] as $fieldOrder => $field)
				{
					if(
						!empty($field) &&
						is_array($field) &&
						!empty($field['name']) &&
						!empty($field['type']) &&
						$field['visible'] == true
					)
					{
						if(in_array($field['type'], self::getNativeFields(), true))
						{
							//List picker
							if(
								!empty($field['items']) &&
								in_array($field['type'], self::LIST_NATIVE_LIST_FIELDS_ID, true)
							)
							{
								$isTypeListPicker = true;

								if(Library::isEmpty($field['label']))
								{
									$field['label'] = 'Field';
								}

								$items[$field['name']]['id'] = $field['name'];
								$items[$field['name']]['title'] = $field['label'];
								$items[$field['name']]['order'] = $fieldOrder;

								foreach ($field['items'] as $order => $item)
								{
									if (is_array($item['pics']))
									{
										foreach ($item['pics'] as $num => $pic)
										{
											$listItem['pictures'][] = $pic;
											$listItem['imageIdentifier'] = $num;
										}
									}

									if (
										isset($item['price']) &&
										is_array($formConfig['data']['currency']) &&
										Loader::includeModule('currency')
									)
									{
										$listItem['subtitle'] = html_entity_decode(\CCurrencyLang::CurrencyFormat($item['price'], $formConfig['data']['currency']['code']));
									}

									$listItem['identifier'] = $item['value'];
									$listItem['order'] = $order;
									$listItem['style'] = 'default';
									$listItem['title'] = $item['label'];

									$items[$field['name']]['items'][] = $listItem;
									unset($listItem);
								}
							}
							//Time Picker
							elseif(
								!empty($field['booking']) &&
								!empty($field['booking']['entity_field_name']) &&
								!empty($field['booking']['settings_data']) &&
								in_array($field['type'], self::LIST_NATIVE_TIME_FIELDS_ID, true) &&
								Loader::includeModule('calendar')
							)
							{
								if($isTypeTimePicker === false)
								{
									$isTypeTimePicker = true;
								}
								else
								{
									$isFormNative = false;
								}

								$items[$field['name']]['id'] = $field['name'];
								if(!Library::isEmpty($field['label']))
								{
									$items[$field['name']]['title'] = $field['label'];
								}

								$listTime = ResourceBooking::getFormDateTimeSlots($field['booking']['entity_field_name'], [
									'settingsData' => $field['booking']['settings_data']
								]);

								if(empty($listTime))
								{
									$isFormNative = false;
								}
								else
								{
									$items[$field['name']]['data']['timezoneOffset'] = '0';

									foreach ($listTime as $time)
									{
										$items[$field['name']]['data']['timeslots'][] = [
											'startTime' => $time->format('Y-m-d\TH:iO'),
											'identifier' => serialize($time),
											'duration' => 60 //TODO: Modify in the future to support the transfer of duration
										];
									}
								}
							}
						}
						else
						{
							if(in_array($field['type'], self::LIST_IGNORE_FIELDS_ID, false))
							{
								if(!Library::isEmpty($field['label']) && Library::isEmpty($alternativeTitle))
								{
									$alternativeTitle = $field['label'];
								}
							}
							else
							{
								$isFormNative = false;
							}
						}
					}
				}

				if(Library::isEmpty($descriptionForm['title']))
				{
					if(!empty($alternativeTitle))
					{
						$descriptionForm['title'] = $alternativeTitle;
					}
					else
					{
						if($isTypeTimePicker === true)
						{
							$descriptionForm['title'] = Loc::getMessage('IMCONNECTOR_INTERACTIVE_MESSAGE_IMESSAGE_TIME_FORM_TITLE');
						}
						else
						{
							$descriptionForm['title'] = 'Form';
						}
					}
				}

				if($isTypeListPicker === true && $isTypeTimePicker === true)
				{
					$isFormNative = false;
					$isTypeListPicker = false;
					$isTypeTimePicker = false;
				}

				if($isTypeListPicker === true)
				{
					$descriptionForm['type'] = self::NATIVE_FORM_TYPE_LIST;
				}
				elseif ($isTypeTimePicker === true)
				{
					$descriptionForm['type'] = self::NATIVE_FORM_TYPE_TIME;
				}
				$descriptionForm['native'] = $isFormNative;
			}
		}

		if(empty($descriptionForm) || empty($items))
		{
			$result->addError(new Error(
				'Failed to upload information about the CRM form'
			));
		}
		else
		{
			$descriptionForm['fields'] = $items;

			$result->setData($descriptionForm);
		}

		return $result;
	}

	/**
	 * Checking if the message is native.
	 *
	 * @return bool
	 */
	protected function isNative(): bool
	{
		$result = false;

		$typeNative = $this->getTypeNative();

		switch ($typeNative)
		{
			case self::NATIVE_FORM:
				if(
					!empty($this->configFormsData) &&
					is_array($this->configFormsData) &&
					count($this->configFormsData) > 0
				)
				{
					$native = true;
					foreach ($this->configFormsData as $id=>$field)
					{
						if(
							!isset($field['native']) ||
							$field['native'] === false
						)
						{
							$native = false;
						}
					}

					$result = $native;
				}
				break;
			case self::NATIVE_PAYMENT:
			case self::NATIVE_CUSTOM_APP:
			case self::NATIVE_OAUTH:
				$result = true;
				break;
		}

		return $result;
	}

	/**
	 * Returns the native type of the message.
	 *
	 * @return string
	 */
	protected function getTypeNative(): string
	{
		$result = '';

		if(!empty($this->formIds) && empty($this->paymentData))
		{
			$result = self::NATIVE_FORM;
		}
		elseif(empty($this->formIds) && !empty($this->paymentData))
		{
			$result = self::NATIVE_PAYMENT;
		}
		elseif(!empty($this->appParams))
		{
			$result = self::NATIVE_CUSTOM_APP;
		}
		elseif(!empty($this->oauthParams))
		{
			$result = self::NATIVE_OAUTH;
		}

		return $result;
	}

	/**
	 * Returns a set of data describing the native form to send.
	 *
	 * @return array
	 */
	protected function getDataForSendForm(): array
	{
		$result = [];

		foreach ($this->configFormsData as $form)
		{
			unset($form['native']);

			$result[] = $form;
		}

		return $result;
	}

	/**
	 * Returns a set of data describing the native payment request to send.
	 *
	 * @return array
	 */
	protected function getDataForSendPayment(): array
	{
		$result = [];

		if (!empty($this->paymentData))
		{
			$result = $this->paymentData;
		}

		return $result;
	}

	/**
	 * The transformation of the description of the outgoing message in native format if possible.
	 *
	 * @param array $message
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function nativeMessageProcessing($message): array
	{
		if(
			$this->load() &&
			$this->isNative()
		)
		{
			$typeNative = $this->getTypeNative();
			switch ($typeNative)
			{
				//List Picker or Time Picker.
				case self::NATIVE_FORM:
					$dataForm = $this->getDataForSendForm();
					if(!empty($dataForm))
					{
						$message['form'] = $dataForm;
					}
					break;
				//Apple Pay
				case self::NATIVE_PAYMENT:
					$dataPayment = $this->getDataForSendPayment();
					if(!empty($dataPayment))
					{
						$message['payment'] = $dataPayment;
					}
					break;
				//Custom Apps
				case self::NATIVE_CUSTOM_APP:
					$dataApp = $this->getDataForSendAppMessage();
					if(!empty($dataApp))
					{
						$message['application'] = $dataApp;
					}
					break;
				//OAuth
				case self::NATIVE_OAUTH:
					$dataOAuth = $this->getDataForOauth();
					if(!empty($dataOAuth))
					{
						$message['oauth'] = $dataOAuth;
					}
					break;
			}
		}

		return $message;
	}

	/**
	 * @return array
	 */
	protected function getDataForOauth(): array
	{
		return $this->oauthParams;
	}

	/**
	 * @return array
	 */
	protected function getDataForSendAppMessage(): array
	{
		return $this->appParams;
	}
}