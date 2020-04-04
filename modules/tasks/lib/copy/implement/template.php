<?
namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Main\Copy\Container;
use Bitrix\Main\Copy\ContainerCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Tasks\Copy\Template as TemplateCopier;
use Bitrix\Tasks\Internals\Task\Template\DependenceTable;

class Template extends Base
{
	const TEMPLATE_COPY_ERROR = "TEMPLATE_COPY_ERROR";

	/**
	 * @var TemplateCopier|null
	 */
	private $templateCopier = null;

	protected $ufEntityObject = "TASKS_TASK_TEMPLATE";
	protected $ufDiskFileField = "UF_TASK_WEBDAV_FILES";

	/**
	 * To copy child templates needs template copier.
	 *
	 * @param TemplateCopier $templateCopier Template copier.
	 */
	public function setTemplateCopier(TemplateCopier $templateCopier): void
	{
		$this->templateCopier = $templateCopier;
	}

	/**
	 * Creates template and return template id.
	 *
	 * @param Container $container
	 * @param array $fields Template fields.
	 * @return bool
	 */
	public function add(Container $container, array $fields)
	{
		$taskTemplates = new \CTaskTemplates();

		$result = $taskTemplates->add($fields);

		if (!$result)
		{
			if ($taskTemplates->getErrors())
			{
				$this->result->addErrors($taskTemplates->getErrors());
			}
			else
			{
				$this->result->addError(new Error("Failed to copy template", self::TEMPLATE_COPY_ERROR));
			}
		}

		return $result;
	}

	/**
	 * Updates template.
	 *
	 * @param integer $templateId Template id.
	 * @param array $fields Template fields.
	 */
	public function update($templateId, array $fields)
	{
		$taskTemplates = new \CTaskTemplates();
		$taskTemplates->update($templateId, $fields);
	}

	/**
	 * Returns template fields.
	 *
	 * @param Container $container
	 * @param int $entityId
	 * @return array $fields
	 */
	public function getFields(Container $container, $entityId)
	{
		$queryObject = $this->getList([], ["ID" => $entityId], ["*"]);

		return (($fields = $queryObject->fetch()) ? $fields : []);
	}

	/**
	 * Preparing data before creating a new entity.
	 *
	 * @param Container $container
	 * @param array $fields List entity fields.
	 * @return array $fields
	 */
	public function prepareFieldsToCopy(Container $container, array $fields)
	{
		$fields = $this->cleanDataToCopy($fields);

		if (!empty($container->getParentId()))
		{
			$fields["BASE_TEMPLATE_ID"] = $container->getParentId();
		}

		return $fields;
	}

	/**
	 * Starts copying children entities.
	 *
	 * @param Container $container
	 * @param int $entityId Template id.
	 * @param int $copiedEntityId Copied template id.
	 * @return Result
	 */
	public function copyChildren(Container $container, $entityId, $copiedEntityId)
	{
		$this->copyUfFields($entityId, $copiedEntityId, $this->ufEntityObject);

		$results = [];

		$results[] = $this->copyChildTemplate($entityId, $copiedEntityId);

		return $this->getResult($results);
	}

	/**
	 * Returns query object.
	 *
	 * @param array $order Order.
	 * @param array $filter Filter.
	 * @param array $select Select.
	 * @return bool|\CDBResult
	 */
	public function getList($order = [], $filter = [], $select = [])
	{
		return \CTaskTemplates::getList($order, $filter, false, false, $select);
	}

	/**
	 * Returns template description.
	 *
	 * @param integer $templateId Template id.
	 * @return array
	 */
	protected function getText($templateId)
	{
		$queryObject = $this->getList([], ["ID" => $templateId], ["DESCRIPTION"]);
		if ($template = $queryObject->fetch())
		{
			return ["DESCRIPTION", $template["DESCRIPTION"]];
		}
		else
		{
			return ["DESCRIPTION", ""];
		}
	}

	/**
	 * Returns ids template children.
	 *
	 * @param integer $templateId Template id.
	 * @return array
	 */
	public function getChildrenIds($templateId)
	{
		$childrenIds = [];

		$queryObject = DependenceTable::getSubTree($templateId,
			["filter" => ["DIRECT" => true]], ["INCLUDE_SELF" => false]);
		while ($template = $queryObject->fetch())
		{
			$childrenIds[] = $template["TEMPLATE_ID"];
		}

		return $childrenIds;
	}

	private function copyChildTemplate(int $templateId, int $copiedTemplateId)
	{
		if (!$this->templateCopier)
		{
			return new Result();
		}

		$containerCollection = new ContainerCollection();

		$childrenIds = $this->getChildrenIds($templateId);
		foreach ($childrenIds as $childrenId)
		{
			$container = new Container($childrenId);
			$container->setParentId($copiedTemplateId);
			$containerCollection[] = $container;
		}

		if (!$containerCollection->isEmpty())
		{
			return $this->templateCopier->copy($containerCollection);
		}

		return new Result();
	}

	private function cleanDataToCopy(array $fields)
	{
		unset($fields["TPARAM_TYPE"]);

		$fields = $this->cleanPrimary($fields);
		return $fields;
	}

	private function cleanPrimary(array $fields)
	{
		unset($fields["ID"]);
		return $fields;
	}
}