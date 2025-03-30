<?php

namespace Bitrix\HumanResources\Marketplace\Rest;

use Bitrix\HumanResources\Item\HcmLink\Company;
use Bitrix\HumanResources\Item\HcmLink\Employee;
use Bitrix\HumanResources\Item\HcmLink\Field;
use Bitrix\HumanResources\Item\HcmLink\FieldValue;
use Bitrix\HumanResources\Item\HcmLink\Person;
use Bitrix\HumanResources\Service\Container;
use Bitrix\HumanResources\Type\HcmLink\FieldEntityType;
use Bitrix\HumanResources\Type\HcmLink\FieldType;
use Bitrix\HumanResources\Type\HcmLink\JobStatus;
use Bitrix\HumanResources\Type\HcmLink\PlacementType;
use Bitrix\HumanResources\Type\HcmLink\RestEventType;
use Bitrix\Main\Application;
use Bitrix\Rest\Exceptions\ArgumentException;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AuthTypeException;
use Bitrix\Rest\Exceptions\ObjectNotFoundException;
use Bitrix\Rest\OAuth\Auth as OauthAuth;
use Bitrix\Rest\RestException;
use CRestServer;
use CRestUtil;
use Exception;
use IRestService;
use function ExecuteModuleEventEx;

Loader::includeModule('rest');

class HcmLink extends IRestService
{
	public const MODULE_ID = 'humanresources';
	public const SCOPE = 'humanresources.hcmlink';

	public const LIMIT = 100;
	public const LIMIT_MAX = 1000;

	public static function onRestServiceBuildDescription(): array
	{
		return [
			self::SCOPE => [
				self::SCOPE . '.company.add' => [
					'callback' => [
						self::class,
						'companyAdd',
					],
					'options' => [],
				],
				self::SCOPE . '.company.update' => [
					'callback' => [
						self::class,
						'companyAdd',
					],
					'options' => [],
				],
				self::SCOPE . '.company.delete' => [
					'callback' => [
						self::class,
						'companyDelete',
					],
					'options' => [],
				],
				self::SCOPE . '.company.list' => [
					'callback' => [
						self::class,
						'companyList',
					],
					'options' => [],
				],
				self::SCOPE . '.company.user.list' => [
					'callback' => [
						self::class,
						'companyUserList',
					],
					'options' => [],
				],
				self::SCOPE . '.employee.set' => [
					'callback' => [
						self::class,
						'receiveEmployeeList',
					],
					'options' => [],
				],
				self::SCOPE . '.employee.list' => [
					'callback' => [
						self::class,
						'mappedEmployeeList',
					],
					'options' => [],
				],
				self::SCOPE . '.field.value.set' => [
					'callback' => [
						self::class,
						'receiveFieldValue',
					],
					'options' => [],
				],
				self::SCOPE . '.job.update' => [
					'callback' => [
						self::class,
						'jobUpdate',
					],
					'options' => [],
				],
				self::SCOPE . '.job.status.get' => [
					'callback' => [
						self::class,
						'getJobStatus',
					],
					'options' => [],
				],

				CRestUtil::EVENTS => self::getEvents(),
				CRestUtil::PLACEMENTS => self::getPlacements(),
			],
		];
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return int|bool|array
	 * @throws RestException
	 */
	public static function companyAdd(array $query, int $start, CRestServer $restServer): int|bool|array
	{
		self::checkAuth($restServer);

		$companyRepository = Container::getHcmLinkCompanyRepository();
		$fieldRepository = Container::getHcmLinkFieldRepository();

		$id = (int)($query['id'] ?? 0);
		$request =
			is_array($query['fields'])
				? $query['fields']
				: [];

		$companyUuid = (string)$request['company'];
		$crmCompanyId = $request['crmCompanyId'] ?? null;
		$title = (string)$request['title'];
		$data = (is_array($request['data']) ?? null)
				? $request['data']
				: []
		;
		$fields = (is_array($request['fields']) ?? null)
				? $request['fields']
				: []
		;

		try
		{
			if (is_null($crmCompanyId))
			{
				throw new ArgumentException('Missed crmCompanyId');
			}

			if (empty($companyUuid))
			{
				throw new ArgumentException('Property company is incorrect');
			}

			if (empty($title))
			{
				throw new ArgumentException('Property title is incorrect');
			}

			if (empty($fields))
			{
				throw new ArgumentException('Property fields is incorrect');
			}

			$crmCompanyService = \Bitrix\Crm\Service\Container::getInstance()->getCompanyBroker();
			$crmCompany = $crmCompanyService->getById((int)$crmCompanyId);

			if (!$crmCompany)
			{
				throw new ObjectNotFoundException("Crm company {$crmCompanyId} not found");
			}

			$company = new Company(
				code: $companyUuid,
				myCompanyId: $crmCompanyId,
				title: $title,
				data: $data,
			);

			$savedCompany = $companyRepository->getById($id);
			if ($savedCompany !== null)
			{
				$company->id = $savedCompany->id;
				$company = $companyRepository->update($company);
			}
			else
			{
				$company = $companyRepository->add($company);
			}

			$fieldIdsForDelete = array_map(
				fn() => true,
				$fieldRepository->getByCompany($company->id)->getItemMap(),
			);

			foreach ($fields as $field)
			{
				$type = FieldType::fromName(strtoupper($field['type'] ?? '')) ?? FieldType::UNKNOWN;
				$fieldUuid = (string)$field['field'];
				$title = (string)$field['title'];
				$ttl = (is_numeric($field['ttl'] ?? null)) ? (int)$field['ttl'] : 86400;
				$entityType = FieldEntityType::fromName(strtoupper($field['entityType'] ?? ''))
					?? FieldEntityType::UNKNOWN
				;

				$item = $fieldRepository->save(
					new Field(
						companyId: $company->id,
						field: $fieldUuid,
						title: $title,
						type: $type,
						entityType: $entityType,
						ttl: $ttl,
					),
				);
				$fieldIdsForDelete[$item->id] = false;
			}

			foreach ($fieldIdsForDelete as $fieldId => $flagDelete)
			{
				if ($flagDelete === true)
				{
					$fieldRepository->delete($fieldId);
				}
			}
		}
		catch (Exception $e)
		{
			return self::formatException($e);
		}

		return $id ? true : $company->id;
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return bool|array
	 * @throws RestException
	 */
	public static function companyDelete(array $query, int $start, CRestServer $restServer): bool|array
	{
		self::checkAuth($restServer);

		$companyRepository = Container::getHcmLinkCompanyRepository();
		$FieldRepository = Container::getHcmLinkFieldRepository();

		try
		{
			$id = (int)$query['id'] ?? 0;
			$company = $companyRepository->getById($id);

			if ($company === null)
			{
				throw new ObjectNotFoundException("Company {$id} not found");
			}

			$FieldRepository->deleteByCompany($company->id);
			$companyRepository->delete($company->id);

			return true;
		}
		catch (Exception $e)
		{
			return self::formatException($e);
		}
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return array
	 * @throws RestException
	 */
	public static function companyList(array $query, int $start, CRestServer $restServer): array
	{
		self::checkAuth($restServer);

		$limit = filter_var($query['limit'] ?? self::LIMIT, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 1,
				'max_range' => self::LIMIT_MAX,
				'default' => self::LIMIT,
			],
		]);
		$offset = filter_var($query['offset'] ?? 0, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'default' => 0,
			],
		]);

		$userId = filter_var($query['userId'] ?? 0, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'default' => 0,
			],
		]);

		$companyRepository = Container::getHcmLinkCompanyRepository();
		$personRepository = Container::getHcmLinkPersonRepository();
		$fieldRepository = Container::getHcmLinkFieldRepository();
		try
		{
			$companies = [];
			if ($userId > 0)
			{
				$personByCompanyIdMap = $personRepository->getByUserIdsAndGroupByCompanyId($userId);
				$companyIds = array_keys($personByCompanyIdMap);

				$companies = $companyRepository->getListByIds($companyIds, $limit, $offset);
			}
			else
			{
				$companies = $companyRepository->getList($limit, $offset);
			}

			$result = [];
			foreach ($companies as $company)
			{
				$fields = [];
				foreach ($fieldRepository->getByCompany($company->id) as $field)
				{
					$fields[] =
						array_intersect_key(
							$field->toArray(),
							array_flip(
								[
									'field',
									'title',
									'type',
									'ttl',
								],
							),
						);
				}

				$companyData = [
					'id' => $company->id,
					'company' => $company->code,
					'crmCompanyId' => $company->myCompanyId,
					'title' => $company->title,
					'data' => $company->data,
					'createdAt' => ($company->createdAt ?? new DateTime())->format(\DateTimeInterface::ATOM),
					'fields' => $fields,
				];

				if ($userId > 0)
				{
					$companyData['person'] = ($personByCompanyIdMap[$company->id] ?? null)?->code;
				}

				$result[] = $companyData;
			}
		}
		catch (Exception $e)
		{
			return self::formatException($e);
		}

		return $result;
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return array
	 * @throws RestException
	 */
	public static function companyUserList(array $query, int $start, CRestServer $restServer): array
	{
		$userId = self::checkAuthAndGetUserId($restServer);

		$limit = filter_var($query['limit'] ?? self::LIMIT, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 1,
				'max_range' => self::LIMIT_MAX,
				'default' => self::LIMIT,
			],
		]);
		$offset = filter_var($query['offset'] ?? 0, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'default' => 0,
			],
		]);

		$companyRepository = Container::getHcmLinkCompanyRepository();
		$personRepository = Container::getHcmLinkPersonRepository();
		try
		{
			$personByCompanyIdMap = $personRepository->getByUserIdsAndGroupByCompanyId($userId);
			if (empty($personByCompanyIdMap))
			{
				return [];
			}

			$companyIds = array_keys($personByCompanyIdMap);
			$companies = $companyRepository->getListByIds($companyIds, $limit, $offset);

			$result = [];
			foreach ($companies as $company)
			{
				$companyData = [
					'id' => $company->id,
					'company' => $company->code,
					'crmCompanyId' => $company->myCompanyId,
					'title' => $company->title,
					'data' => $company->data,
					'person' => ($personByCompanyIdMap[$company->id] ?? null)?->code,
				];

				$result[] = $companyData;
			}
		}
		catch (Exception $e)
		{
			return self::formatException($e);
		}

		return $result;
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return array
	 * @throws RestException
	 */
	public static function mappedEmployeeList(array $query, int $start, CRestServer $restServer): array
	{
		self::checkAuth($restServer);

		$limit = filter_var($query['limit'] ?? self::LIMIT, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 1,
				'max_range' => self::LIMIT_MAX,
				'default' => self::LIMIT,
			],
		]);
		$offset = filter_var($query['offset'] ?? 0, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'default' => 0,
			],
		]);
		$companyUuid = (string)($query['company'] ?? '');

		$updatedAt =
			($ts = strtotime($query['updatedAt']))
				? DateTime::createFromTimestamp($ts)
				: null;

		$companyRepository = Container::getHcmLinkCompanyRepository();
		$personRepository = Container::getHcmLinkPersonRepository();
		$employeeRepository = Container::getHcmLinkEmployeeRepository();
		try
		{
			$company = $companyRepository->getByUnique($companyUuid);
			if ($company === null)
			{
				throw new ObjectNotFoundException("Company {$companyUuid} not found");
			}

			$mapped = $personRepository->getList(
				$company->id,
				$limit,
				$offset,
				$updatedAt,
			);

			$personIds = $mapped->map(
				static fn(Person $person) => $person->id,
			);

			$employeeCollection = $employeeRepository->getByPersonIds($personIds);
			$employeesByPersonIdMap = [];

			foreach ($employeeCollection as $employee)
			{
				$employeesByPersonIdMap[$employee->personId][] = $employee;
			}

			$result = [];
			foreach ($mapped as $item)
			{
				$employees = $employeesByPersonIdMap[$item->id] ?? [];
				$employeesResult = [];

				foreach ($employees as $employee)
				{
					/* @var Employee $employee */
					$employeesResult[] = [
						'id' => $employee->id,
						'employee' => $employee->code,
						'data' => $employee->data,
						'createdAt' => $employee->createdAt?->format(\DateTimeInterface::ATOM),
					];
				}

				$result[] = [
					'id' => $item->id,
					'company' => $companyRepository->getById($item->companyId)?->code,
					'person' => $item->code,
					'employees' => $employeesResult,
					'userId' => $item->userId,
					'title' => $item->title,
					'createdAt' => $item->createdAt?->format(\DateTimeInterface::ATOM),
					'updatedAt' => $item->updatedAt?->format(\DateTimeInterface::ATOM),
				];
			}
		}
		catch (Exception $e)
		{
			return self::formatException($e);
		}

		return $result;
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return array|bool
	 * @throws RestException
	 */
	public static function receiveEmployeeList(array $query, int $start, CRestServer $restServer): array|bool
	{
		self::checkAuth($restServer);
		$errors = [];

		$employeeRepository = Container::getHcmLinkEmployeeRepository();
		$companyRepository = Container::getHcmLinkCompanyRepository();
		$personRepository = Container::getHcmLinkPersonRepository();

		$companyUuid = (string)($query['company'] ?? '');
		$employeeList =
			is_array($query['data'])
				? $query['data']
				: [];

		try
		{
			if (is_array($query['job'] ?? null))
			{
				$jobResult = self::jobUpdate($query['job'], $start, $restServer);
				if ($jobResult !== true)
				{
					return $jobResult;
				}
			}

			$company = $companyRepository->getByUnique($companyUuid);
			if ($company === null)
			{
				throw new ObjectNotFoundException("Company {$companyUuid} not found");
			}

			if (is_array($employeeList) && count($employeeList))
			{
				$employeeList = array_values($employeeList);
			}
			else
			{
				$errors[] = "Parameter 'data' must be a non empty array ";
				$employeeList = [];
			}

			foreach ($employeeList as $i => $employee)
			{
				$employeeUuid = (string)$employee['employee'] ?? '';
				$personUuid = (string)$employee['person'] ?? '';
				$data = array_filter(array_map('strval', $employee['data']));

				if (($employeeUuid && $personUuid && $data) !== true)
				{
					$errors[] = "Item #{$i} should have not empty 'employee', 'person', 'data'";

					continue;
				}

				$title = self::getFormattedName(
					$data['firstName'] ?? '',
					$data['lastName'] ?? '',
					$data['patronymicName'] ?? '',
				);

				$person = $personRepository->getByUnique($company->id, $personUuid);
				if ($person === null)
				{
					$person = $personRepository->add(
						new Person(
							companyId: $company->id,
							code: $personUuid,
							title: $title,
							userId: $employee['userId'] ?? null,
						),
					);
				}
				else
				{
					$person->title = $title;
					$person->userId = $employee['userId'] ?? $person->userId;

					$personRepository->update($person);
				}

				if ($person->id)
				{
					$employeeRepository->save(
						new Employee(
							personId: $person->id,
							code: $employeeUuid,
							data: $data,
						),
					);
				}
			}
		}

		catch (Exception $e)
		{
			$errors[] = $e->getMessage();
		}

		Container::getHcmLinkCompanyCounterService()->update();

		return [
			'status' => $errors
				? 'error'
				: 'ok',
			'errors' => $errors,
		];
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return array
	 * @throws RestException
	 */
	public static function receiveFieldValue(array $query, int $start, CRestServer $restServer): array
	{
		self::checkAuth($restServer);

		$fieldValueRepository = Container::getHcmLinkFieldValueRepository();
		$companyRepository = Container::getHcmLinkCompanyRepository();
		$fieldRepository = Container::getHcmLinkFieldRepository();
		$employeeRepository = Container::getHcmLinkEmployeeRepository();

		$companyUuid = $query['company'];
		$data = $query['data'];

		try
		{
			if (is_array($query['job'] ?? null))
			{
				$jobResult = self::jobUpdate($query['job'], $start, $restServer);
				if ($jobResult !== true)
				{
					return $jobResult;
				}
			}

			$company = $companyRepository->getByUnique($companyUuid);
			if (is_null($company))
			{
				throw new ObjectNotFoundException("Company not found");
			}

			$errors = [];

			if (is_array($data) && count($data))
			{
				$data = array_values($data);
			}
			else
			{
				$errors[] = "Parameter 'data' must be a non empty array ";
				$data = [];
			}

			foreach ($data as $i => $fieldValue)
			{
				$fieldCode = $fieldValue['field'] ?? '';
				$field = $fieldRepository->getByUnique($company->id, $fieldCode);
				if (is_null($field))
				{
					$errors[] = "Item #{$i} field '{$fieldCode}' not found for company '{$company->id}'";
					continue;
				}
				$employeeCode = $fieldValue['employee'] ?? '';
				$employee = $employeeRepository->getByUnique($company->id, $employeeCode);
				if (is_null($employee))
				{
					$errors[] = "Item #{$i} employee '{$employeeCode}' not found for company '{$company->id}'";
					continue;
				}

				$savedFieldValue = $fieldValueRepository->getByFieldAndEmployee($field, $employee);
				if ($savedFieldValue === null)
				{
					$fieldValueRepository->add(
						new FieldValue(
							employeeId: $employee->id,
							fieldId: $field->id,
							value: $fieldValue['value'] ?? '',
							createdAt: new DateTime(),
							expiredAt: DateTime::createFromTimestamp(time() + $field->ttl),
						),
					);
				}
				else
				{
					$savedFieldValue->value = $fieldValue['value'] ?? '';
					$savedFieldValue->expiredAt = DateTime::createFromTimestamp(time() + $field->ttl);
					$fieldValueRepository->update($savedFieldValue);
				}

			}
		}
		catch (Exception $e)
		{
			$errors[] = $e->getMessage();
		}

		return [
			'status' => $errors
				? 'error'
				: 'ok',
			'errors' => $errors,
		];
	}

	/**
	 * @param array $query Input parameters ($_GET, $_POST).
	 * @param int $start (int)$query['start']
	 * @param CRestServer $restServer REST server.
	 *
	 * @return bool|array
	 * @throws RestException
	 */
	public static function jobUpdate(array $query, int $start, CRestServer $restServer): bool|array
	{
		self::checkAuth($restServer);

		$jobId = (int)($query['id'] ?? 0);
		$request =
			is_array($query['fields'])
				? $query['fields']
				: [];
		$total =
			isset($request['total'])
				? (int)$request['total']
				: null;
		$done =
			isset($request['sent'])
				? (int)$request['sent']
				: null;
		$status =
			isset($request['status'])
				? JobStatus::fromName(strtoupper($request['status']))
				: null;
		$inputData =
			isset($request['data']) && is_array($request['data'])
				? $request['data']
				: [];

		$jobService = Container::getHcmLinkJobService();

		try
		{
			$job = Container::getHcmLinkJobRepository()->getById($jobId);
			if ($job === null)
			{
				throw new ArgumentException("Job not found");
			}

			if (!$status?->isActual())
			{
				throw new ArgumentException("Invalid job status");
			}

			$job->done = $done ?? $job->done;
			$job->total = $total ?? $job->total;
			$job->status = $status;
			$job->inputData = $inputData;

			$jobService->update($job);
		}
		catch (Exception $e)
		{
			return self::formatException($e);
		}

		return true;
	}


	public static function getJobStatus(array $query, int $start, CRestServer $restServer)
	{
		self::checkAuth($restServer);

		$jobId = (int)($query['id'] ?? 0);

		try
		{
			$job = Container::getHcmLinkJobRepository()->getById($jobId);
			if ($job === null)
			{
				throw new ArgumentException("Job not found");
			}
		}
		catch (Exception $e)
		{
			return self::formatException($e);
		}

		return [
			'alive' => !$job->status->isFinished(),
		];
	}

	public static function onEvent(array $query): array
	{
		//event data needs no processing - returned unchanged
		return $query;
	}

	/**
	 * @throws AccessException
	 */
	private static function checkAuth(CRestServer $restServer): void
	{
		global $USER;

		if (!$USER->isAuthorized())
		{
			throw new AccessException("User authorization required");
		}

		if ($restServer->getAuthType() !== OauthAuth::AUTH_TYPE)
		{
			throw new AuthTypeException("Application context required");
		}

		if (!\CRestUtil::isAdmin())
		{
			throw new AccessException("Access denied.");
		}
	}

	private static function checkAuthAndGetUserId(CRestServer $restServer): int
	{
		/**
		 * @throws AccessException
		 */

		global $USER;

		if (!$USER->isAuthorized() || !$USER->GetID())
		{
			throw new AccessException("User authorization required");
		}

		if ($restServer->getAuthType() !== OauthAuth::AUTH_TYPE)
		{
			throw new AuthTypeException("Application context required");
		}

		return $USER->GetID();
	}

	private static function formatException(Exception $e): array
	{
		if (\Bitrix\Main\Config\Option::get('humanresources', 'rest_detailed_exceptions', 'N') === 'Y')
		{
			return [
				'error' => $e->getCode(),
				'error_class' => get_class($e),
				'error_description' => $e->getMessage(),
			];
		}

		return [
			'error' => $e->getCode(),
			'error_description' => 'Operation failed',
		];
	}

	protected static function getFormattedName(?string $firstName, ?string $lastName, ?string $secondName): string
	{
		$culture = Application::getInstance()->getContext()?->getCulture();
		$nameFormat = $culture?->getNameFormat() ?? '#NAME# #LAST_NAME# #SECOND_NAME#';

		return str_replace(
			[
				'#NAME#',
				'#LAST_NAME#',
				'#SECOND_NAME#',
				'#NAME_SHORT#',
				'#LAST_NAME_SHORT#',
				'#SECOND_NAME_SHORT#',
			],
			[
				$firstName,
				$lastName,
				$secondName,
				mb_substr($firstName, 0, 1) . ".",
				mb_substr($lastName, 0, 1) . ".",
				mb_substr($secondName, 0, 1) . ".",
			],
			$nameFormat,
		);
	}

	private static function getEvents(): array
	{
		return [
			RestEventType::onEmployeeListRequested->value => [
				self::MODULE_ID,
				RestEventType::onEmployeeListRequested->name,
				[
					self::class,
					'onEvent',
				],
			],
			RestEventType::onFieldValueRequested->value => [
				self::MODULE_ID,
				RestEventType::onFieldValueRequested->name,
				[
					self::class,
					'onEvent',
				],
			],
			RestEventType::onEmployeeListMapped->value => [
				self::MODULE_ID,
				RestEventType::onEmployeeListMapped->name,
				[
					self::class,
					'onEvent',
				],
			],
		];
	}

	private static function getPlacements(): array
	{
		return [
			PlacementType::SALARY_VACATION->value => [
				'options' => [
					'width' => [
						'type' => 'int',
						'default' => 900,
						'require' => false,
					],
				],
			],
		];
	}
}
