<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

use Bitrix\Tasks\Util\Result;
use Bitrix\Tasks\Internals\Task\TemplateTable;
use Bitrix\Tasks\Internals\Runtime;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Util\Process\ExecutionResult;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Integration\Disk;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\UserField;
use Bitrix\Tasks\Util;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksUtilProcessComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		// todo: make ability to switch process to run, dont forget to make it secure (subscribe request?)
		static::tryParseEnumerationParameter($this->arParams['PROCESS_CONTROLLER'], array(
			// list of process controller classes here
		), false);

		return $this->errors->checkNoFatals();
	}

	public function getData()
	{
		parent::getData();

		$this->arResult['CONVERTED'] = TasksUtilProcessComponentProcessFiles::isConverted();
		$this->arResult['CAN_CONVERT'] = Disk::isInstalled() && User::isSuper();
	}

	public static function getAllowedMethods()
	{
		return array(
			'doStep',
			'setConverted', // for manual skip
		);
	}

	public static function doStep(array $parameters = array())
	{
		$result = new Result();

		$process = new TasksUtilProcessComponentProcessFiles();
		$process->setTimeLimit(2); // in seconds

		$percent = 0;
		$step = intval($parameters['step']);
		try
		{
			if(!$step)
			{
				$process->reset();
			}

			$execResult = $process->execute();
			$percent = $execResult->getPercent();

			$result->getErrors()->load($execResult->getErrors());
		}
		catch(SystemException $e)
		{
			$result->addError('PROCESS_FAILURE', $e->getMessage());
		}

		$result->setData(array(
			'PERCENT' => $percent
		));

		return $result;
	}

	public static function setConverted()
	{
		TasksUtilProcessComponentProcessFiles::setConverted();
	}
}

if(CModule::IncludeModule('tasks'))
{
	final class TasksUtilProcessComponentProcessFiles extends \Bitrix\Tasks\Util\Process
	{
		protected static function getSessionKey()
		{
			return 'template-file-process';
		}

		protected function getStages()
		{
			return array(
				'stageEstimate' => array(
					'PERCENT' => 10,
				),
				'stageConversion' => array(
					'PERCENT' => 100,
				),
			);
		}

		public function stageEstimateAction(ExecutionResult $result)
		{
			if(!Disk::isInstalled())
			{
				$result->addError('DISK_NOT_INSTALLED', 'Disk module is not installed');
				return false;
			}
			if(!User::isSuper())
			{
				$result->addError('ACCESS_DENIED', 'Only admin can run the converter');
				return false;
			}

			$item = TemplateTable::getList(Runtime::apply(array(
				'select' => array('RECORD_COUNT'),
				'filter' => static::getFilter(),
			), array(
				Runtime::getRecordCount(),
			)))->fetch();

			$this->data['PAGER'] = array(
				'PAGE' => 0,
				'COUNT' => intval($item['RECORD_COUNT'])
			);

			$this->data['ITEM_COUNT'] = intval($item['RECORD_COUNT']);
			$this->data['ITEM_PROCESSED'] = 0;

			// also, pre-create template disk field, if needed
			UserField\Task\Template::getScheme();
			$ufName = Disk\UserField::getMainSysUFCode();

			if(!UserField\Task\Template::checkFieldExists($ufName))
			{
				$result->addError('USER_FIELD_FAILURE', 'Disk user field was not created for the template entity');
				return false;
			}

			return true;
		}

		public function stageConversionAction(ExecutionResult $result)
		{
			$limit = 10;

			Util::printDebug('=== NEXT STEP ====== '.$this->step);

			if(!Disk::isInstalled())
			{
				$result->addError('DISK_NOT_INSTALLED', 'Disk module is not installed');
				return false;
			}
			if(!User::isSuper())
			{
				$result->addError('ACCESS_DENIED', 'Only admin can run the converter');
				return false;
			}

			try
			{
				$res = TemplateTable::getList(array(
					'select' => array('ID', 'TITLE', 'FILES', 'CREATED_BY'),
					'filter' => static::getFilter(),
					'limit' => $limit,
					'offset' => 0, // non-converted template count decreases on each step, so sooner or later we will get zero records here
				));
			}
			catch(SystemException $e)
			{
				$result->addError('QUERY_FAILURE', $e->getMessage());
				return true;
			}

			$this->data['PAGER']['PAGE'] += 1;

			if(!is_array($this->data['FILES']))
			{
				$this->data['FILES'] = array();
			}

			// process each template
			$count = 0;
			while($item = $res->fetch())
			{
				Util::printDebug('Converting: '.$item['TITLE'].' ('.$item['ID'].') ...');

				if(!$result->isSuccess()) // there were some errors previously
				{
					Util::printDebug('Were errors, cancel');
					break;
				}

				$files = Type::unSerializeArray($item['FILES']);
				if(is_array($files) && !empty($files) && intval($item['CREATED_BY']))
				{
					Util::printDebug('files look good');
					Util::printDebug($files);

					$attachmentIds = array();
					$file2Attachment = array();

					// for each file we try to upload it to disk
					$fRes = \CFile::getList(array(), array('@ID' => implode(', ', $files)));
					while($fItem = $fRes->fetch())
					{
						$existedId = Disk::getAttachmentIdByLegacyFileId($fItem['ID'], 'TASK_TEMPLATE');
						$attachmentId = 0;

						if($existedId) // it had been uploaded
						{
							// clone attachment then
							$clones = Disk::cloneFileAttachmentHash(array($existedId));
							if(is_array($clones) && count($clones) == 1)
							{
								$attachmentId = array_shift($clones);
							}
						}
						else // it had not
						{
							Util::printDebug('Meet new file '.$fItem['ID']);
							$uResult = Disk::addFile($fItem, intval($item['CREATED_BY']));
							if($uResult->isSuccess())
							{
								$uData = $uResult->getData();

								$attachmentId = $uData['ATTACHMENT_ID'];
							}
						}

						if($attachmentId)
						{
							$attachmentIds[] = $attachmentId;
							$file2Attachment[$fItem['ID']] = $attachmentId;
						}
						else
						{
							$result->addError('FILE_UPLOAD_FAILURE', Loc::getMessage('TASKS_TUP_CANNOT_UPLOAD_FILE', array('#ID#' => $item['ID'])));
						}
					}

					if($result->isSuccess()) // there were no errors
					{
						// all files uploaded, ready to update the template
						$updateResult = static::markTemplateAsConverted($item['ID'], $attachmentIds);
						if(!$updateResult->isSuccess())
						{
							// cleanup temporal attachments...
							Disk::deleteUnattachedFiles($file2Attachment);

							$result->addError('TEMPLATE_UPDATE_FAILURE', Loc::getMessage('TASKS_TUP_CANNOT_SAVE_TEMPLATE', array('#ID#' => $item['ID'])));
							$subErrors = $updateResult->getErrors();
							foreach($subErrors as $error)
							{
								$exploded = explode('<br>', $error->getMessage());
								foreach($exploded as $expValue)
								{
									$result->addError($error->getCode(), $expValue);
								}
							}

							return false;
						}
						else
						{
							// mark files as moved
							$this->data['FILES'] = $this->data['FILES'] + $file2Attachment;
						}
					}
				}
				else
				{
					static::markTemplateAsConverted($item['ID']);
				}

				$count++;
			}
			if(!$count)
			{
				return true; // all converted
			}

			$this->data['ITEM_PROCESSED'] += $count;

			return false;
		}

		public function stageConversionLocalPercent()
		{
			return floor(($this->data['ITEM_PROCESSED'] / $this->data['ITEM_COUNT']) * 100);
		}

		public function onAfterPerformIteration()
		{
			if($this->getPercent() >= 100)
			{
				Util::printDebug($this->data['ITEM_PROCESSED'].' of '.$this->data['ITEM_COUNT'].' processed!');

				static::setConverted();
			}
		}

		public static function haveUnConverted()
		{
			$item = TemplateTable::getList(array(
				'filter' => static::getFilter(),
				'select' => array('ID'),
				'limit' => 1,
			))->fetch();

			return intval($item['ID']);
		}

		public static function isConverted()
		{
			$converted = Option::get('tasks', 'template.files.converted', '?');
			if($converted != '1') // unknown
			{
				$have = static::haveUnConverted();
				if(!$have)
				{
					static::setConverted();
					return true;
				}
			}

			return $converted == '1';
		}

		public static function setConverted()
		{
			Option::set('tasks', 'template.files.converted', '1');
		}

		private static function getFilter()
		{
			return array(
				array('!FILES' => false),
				array('!FILES' => ''),
				'=CREATOR.ACTIVE' => true,
			);
		}

		private static function markTemplateAsConverted($id, $attachmentIds = null)
		{
			$data = array(
				'FILES' => false,
			);
			if($attachmentIds !== null)
			{
				$data[Disk\UserField::getMainSysUFCode()] = $attachmentIds;
			}

			return TemplateTable::update($id, $data);
		}
	}
}