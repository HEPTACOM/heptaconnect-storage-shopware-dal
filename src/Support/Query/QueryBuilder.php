<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Doctrine\DBAL\Types\Type;

class QueryBuilder extends BaseQueryBuilder
{
    public const PARAM_FIRST_RESULT = 'frf0703687f4ca4b70a4cc85bf9e7377c7';

    public const PARAM_MAX_RESULT = 'mrf0703687f4ca4b70a4cc85bf9e7377c7';

    private bool $isForUpdate = false;

    private QueryIterator $queryIterator;

    private string $identifier;

    private int $paginationPageSize;

    public function __construct(
        Connection $connection,
        QueryIterator $queryIterator,
        string $identifier,
        int $paginationPageSize
    ) {
        parent::__construct($connection);
        $this->queryIterator = $queryIterator;
        $this->paginationPageSize = $paginationPageSize;
        $this->identifier = $identifier;
    }

    public function getIsForUpdate(): bool
    {
        return $this->isForUpdate;
    }

    public function setIsForUpdate(bool $isForUpdate): void
    {
        $this->isForUpdate = $isForUpdate;
    }

    public function setFirstResult($firstResult)
    {
        /** @var int|mixed $firstResult */
        if (\is_int($firstResult) && $firstResult > 0) {
            return $this->setParameter(self::PARAM_FIRST_RESULT, $firstResult, Type::INTEGER);
        }

        $params = $this->getParameters();
        $types = $this->getParameterTypes();

        unset($params[self::PARAM_FIRST_RESULT], $types[self::PARAM_FIRST_RESULT]);

        return $this->setParameters($params, $types);
    }

    public function getFirstResult()
    {
        return $this->getParameter(self::PARAM_FIRST_RESULT) ?? 0;
    }

    public function setMaxResults($maxResults)
    {
        if (\is_int($maxResults)) {
            return $this->setParameter(self::PARAM_MAX_RESULT, $maxResults, Type::INTEGER);
        }

        $params = $this->getParameters();
        $types = $this->getParameterTypes();

        unset($params[self::PARAM_MAX_RESULT], $types[self::PARAM_MAX_RESULT]);

        return $this->setParameters($params, $types);
    }

    public function getMaxResults()
    {
        return $this->getParameter(self::PARAM_MAX_RESULT);
    }

    public function getSQL()
    {
        $result = parent::getSQL();

        switch ($this->getType()) {
            case self::INSERT:
            case self::DELETE:
            case self::UPDATE:
                break;
            case self::SELECT:
            default:
                if ($this->getMaxResults() !== null) {
                    $result .= ' LIMIT :' . self::PARAM_MAX_RESULT;
                    /** @var int|mixed $firstResult */
                    $firstResult = $this->getFirstResult();

                    if (\is_int($firstResult) && $firstResult > 0) {
                        $result .= ' OFFSET :' . self::PARAM_FIRST_RESULT;
                    }
                } elseif ($this->isForUpdate) {
                    $result .= ' FOR UPDATE';
                }

                break;
        }

        return ' # heptaconnect-query-id ' . $this->identifier . \PHP_EOL . $result;
    }

    /**
     * @return mixed|null
     */
    public function fetchSingleValue()
    {
        $oldLimit = $this->getMaxResults();
        $oldOffset = $this->getFirstResult();

        $this->setFirstResult(0);
        $this->setMaxResults(2);

        $rows = $this->queryIterator->fetchAssociateRows($this);

        $this->setMaxResults($oldLimit);
        $this->setFirstResult($oldOffset);

        switch (\count($rows)) {
            case 0:
                return null;
            case 1:
                return \current(\current($rows));
            default:
                throw new \LogicException('Too many rows in result for a single value selection', 1645901522);
        }
    }

    /**
     * @return iterable<int, array<string, string|null>>
     */
    public function fetchAssocPaginated(): iterable
    {
        return $this->queryIterator->iterate($this, $this->paginationPageSize);
    }
}
