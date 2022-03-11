<?php
namespace Bitrix\ImConnector\InteractiveMessage\Connectors\IMessage;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Type\DateTime,
	Bitrix\Main\Localization\Loc;
use \Bitrix\ImConnector\Library,
	\Bitrix\ImConnector\InteractiveMessage;
use \Bitrix\ImOpenLines\Crm;

/**
 * Class Base
 * @package Bitrix\ImConnector\InteractiveMessage\IMessage
 */
class Input extends InteractiveMessage\Input
{
	/**
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function processing(): array
	{
		$action = $this->message['message']['action'];
		$values = [];
		$crmForm = false;
		$resultSave = false;

		if(Loader::includeModule('imopenlines'))
		{
			if(!empty($action['listPicker']))
			{
				$formId = $action['listPicker']['form_id'];

				foreach ($action['listPicker']['items'] as $item)
				{
					if (!empty($item['field_code']) && !empty($item['option_id']) && $formId > 0)
					{
						$values[$item['field_code']][] = $item['option_id'];
					}
				}

				if(!empty($values))
				{
					$crmForm = new Crm\Form($this->idConnector, $formId);
					$resultSave = $crmForm->save($values);
					if($resultSave->isSuccess())
					{
						$this->isProcessing = true;
					}
				}
			}

			if(!empty($action['timePicker']))
			{
				$formId = $action['timePicker']['form_id'];

				foreach ($action['timePicker']['items'] as $item)
				{
					$values[$item['field_id']] = Crm\Form::prepareFormDateValues($item['field_id'], unserialize($item['option_id'], ['allowed_classes' => [DateTime::class, \DateTime::class]]), $formId);
				}

				if(!empty($values))
				{
					$crmForm = new Crm\Form($this->idConnector, $formId);
					$resultSave = $crmForm->save($values);
					if($resultSave->isSuccess())
					{
						$this->isProcessing = true;
					}
				}
			}
		}

		if(
			$this->isProcessing === true &&
			!empty($crmForm) &&
			!empty($resultSave)
		)
		{
			$activityId = $resultSave->getResult()->getResultEntity()->getActivityId();
			$nameForm = $crmForm->getForm()->get()['NAME'];
			unset($this->message['message']);

			if(
				!empty($activityId) &&
				!Library::isEmpty($nameForm)
			)
			{
				$this->message['message']['text'] = Loc::getMessage(
					'IMCONNECTOR_INTERACTIVE_MESSAGE_IMESSAGE_CRM_WEBFORM_RESULT_ENTITY_NOTIFY_SUBJECT_NEW',
					[
						'#title_from#' => '[URL=' . self::getActivityUrl($activityId) . ']' . htmlspecialcharsbx($nameForm) . '[/URL]'
					]
				);
			}
		}

		return $this->message;
	}

	/**
	 * @return bool
	 */
	public function isSendMessage(): bool
	{
		$result = true;

		if(empty($this->message['message']))
		{
			$result = false;
		}

		return $result;
	}
}
