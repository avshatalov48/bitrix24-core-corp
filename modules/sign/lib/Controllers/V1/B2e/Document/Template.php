<?php

namespace Bitrix\Sign\Controllers\V1\B2e\Document;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Security\Random;
use Bitrix\Sign\Connector;
use Bitrix\Sign\Engine\Controller;
use Bitrix\Sign\Item\Document\TemplateCollection;
use Bitrix\Sign\Item\DocumentCollection;
use Bitrix\Sign\Item\Integration\Crm\MyCompanyCollection;
use Bitrix\Sign\Operation\Document\Template\Send;
use Bitrix\Sign\Result\Operation\Document\Template\SendResult;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Member\EntityType;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Operation;

class Template extends Controller
{
	/**
	 * @return array<array{uid: string, title: string, company: array{id: int, name: string, taxId: string}, fields: array}>
	 */
	public function listAction(): array
	{
		$leaveTypeField = [
			'uid' => Random::getString(32),
			'name' => 'Тип отпуска',
			'type' => 'list',
			'items' => [
				[
					'label' => 'Очередной',
					'code' => 'regular',
				],
				[
					'label' => 'За собственный счет',
					'code' => 'at_one_s_expense',
				],
				[
					'label' => 'Учебный',
					'code' => 'educational',
				],
				[
					'label' => 'Сдача донорской крови',
					'code' => 'blood_donation',
				],
				[
					'label' => 'Отгул',
					'code' => 'compensation',
				],
				[
					'label' => 'Диспанцеризация',
					'code' => 'dispensary_examination',
				],
			],
		];
		$dateStart = [
			'uid' => Random::getString(32),
			'name' => 'Дата начала',
			'type' => 'date',
		];
		$dateEnd = [
			'uid' => Random::getString(32),
			'name' => 'Дата окончания',
			'type' => 'date',
		];
		$templates = $this->container->getDocumentTemplateRepository()
			->listWithStatuses(Status::COMPLETED)
		;
		$documents = $this->container->getDocumentRepository()
			->listByTemplateIds($templates->getIdsWithoutNull())
		;
		$companyIds = $this->container->getDocumentService()
			->listMyCompanyIdsForDocuments($documents)
		;

		if (empty($companyIds))
		{
			return [];
		}

		$companies = $this->container->getCrmMyCompanyService()->listWithTaxIds(inIds: $companyIds);
		$result = [];
		$documents = $documents->sortByTemplateIdsDesc();
		foreach ($documents as $document)
		{
			$companyId = $companyIds[$document->id];
			$company = $companies->findById($companyId);
			if ($company === null)
			{
				continue;
			}
			$template = $templates->findById($document->templateId);
			if ($template === null)
			{
				continue;
			}

			$result[] = [
				'uid' => $template->uid,
				'title' => $template->title,
				'company' => [
					'name' => $company->name,
					'taxId' => $company->taxId,
					'id' => $company->id
				],
				// todo: remove hardcode
				'fields' => [
					$leaveTypeField,
					$dateStart,
					$dateEnd,
				],
			];
		}

		return $result;
	}

	public function sendAction(string $uid, Main\Engine\CurrentUser $user): array
	{
		$template = Container::instance()->getDocumentTemplateRepository()->getByUid($uid);
		if ($template === null)
		{
			$this->addError(new Main\Error('Template not found'));

			return [];
		}

		$result = (new Send($template, $user->getId()))->launch();
		if (!$result instanceof SendResult)
		{
			$this->addErrorsFromResult($result);

			return [];
		}

		$employeeMember = $result->employeeMember;

		return [
			'employeeMember' => [
				'id' => $employeeMember->id,
				'uid' => $employeeMember->uid,
			],
		];
	}

	public function completeAction(string $uid): array
	{
		$templateRepository = Container::instance()->getDocumentTemplateRepository();
		$template = $templateRepository->getByUid($uid);
		if ($template === null)
		{
			$this->addErrorByMessage('Template not found');

			return [];
		}

		$result = (new Operation\Document\Template\Complete($template))->launch();
		$this->addErrorsFromResult($result);

		return [];
	}
}
