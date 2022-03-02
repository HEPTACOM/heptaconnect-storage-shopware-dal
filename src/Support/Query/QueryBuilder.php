<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Doctrine\DBAL\Types\Type;

class QueryBuilder extends BaseQueryBuilder
{
    public const PARAM_FIRST_RESULT = 'frf0703687f4ca4b70a4cc85bf9e7377c7';

    public const PARAM_MAX_RESULT = 'mrf0703687f4ca4b70a4cc85bf9e7377c7';

    private bool $isForUpdate = false;

    private string $identifier;

    private int $paginationPageSize;

    public function __construct(Connection $connection, string $identifier, int $paginationPageSize)
    {
        parent::__construct($connection);
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

        $rows = $this->fetchAssoc();

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

    public function fetchAssocSingleRow(): ?array
    {
        $oldLimit = $this->getMaxResults();
        $oldOffset = $this->getFirstResult();

        $this->setFirstResult(0);
        $this->setMaxResults(2);

        $rows = $this->fetchAssoc();

        $this->setMaxResults($oldLimit);
        $this->setFirstResult($oldOffset);

        switch (\count($rows)) {
            case 0:
                return null;
            case 1:
                return \current($rows);
            default:
                throw new \LogicException('Too many rows in result for a single row selection', 1645901523);
        }
    }

    /**
     * @return iterable<int, array>
     */
    public function fetchAssocPaginated(): iterable
    {
        $maxResults = $this->getMaxResults();

        if (\is_int($maxResults)) {
            return $this->fetchAssoc();
        }

        $fallbackPageSize = $this->paginationPageSize;

        if ($fallbackPageSize < 1) {
            throw new \LogicException('Fallback page size is too small', 1645901524);
        }

        if ($this->getQueryPart('orderBy') === []) {
            throw new \LogicException('Pagination without order is not reliable', 1645901525);
        }

        return $this->fetchAllPages($fallbackPageSize);
    }

    /**
     * @return iterable<int, array>
     */
    private function fetchAllPages(int $limit): iterable
    {
        $oldLimit = $this->getMaxResults();
        $oldOffset = $this->getFirstResult();
        $rowId = 0;
        $offset = 0;

        $this->setMaxResults($limit);

        do {
            $this->setFirstResult($offset);

            $rows = $this->fetchAssoc();

            foreach ($rows as $row) {
                yield $rowId++ => $row;
            }

            $offset += $limit;
        } while ($rows !== []);

        $this->setMaxResults($oldLimit);
        $this->setFirstResult($oldOffset);
    }

    /**
     * @return array<int, array>
     */
    private function fetchAssoc(): array
    {
        $statement = $this->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1645901521);
        }

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
