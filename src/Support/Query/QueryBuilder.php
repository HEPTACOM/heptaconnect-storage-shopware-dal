<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Doctrine\DBAL\Types\Types;

class QueryBuilder extends BaseQueryBuilder
{
    public const string PARAM_FIRST_RESULT = 'frf0703687f4ca4b70a4cc85bf9e7377c7';

    public const string PARAM_MAX_RESULT = 'mrf0703687f4ca4b70a4cc85bf9e7377c7';

    public function __construct(
        Connection $connection,
        private readonly string $identifier,
    ) {
        parent::__construct($connection);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    #[\Override]
    public function setFirstResult($firstResult): static
    {
        /** @var int|mixed $firstResult */
        if (\is_int($firstResult) && $firstResult > 0) {
            return $this->setParameter(self::PARAM_FIRST_RESULT, $firstResult, Types::INTEGER);
        }

        $params = $this->getParameters();
        $types = $this->getParameterTypes();

        unset($params[self::PARAM_FIRST_RESULT], $types[self::PARAM_FIRST_RESULT]);

        return $this->setParameters($params, $types);
    }

    #[\Override]
    public function getFirstResult(): int
    {
        return $this->getParameter(self::PARAM_FIRST_RESULT) ?? 0;
    }

    #[\Override]
    public function setMaxResults($maxResults): static
    {
        if (\is_int($maxResults)) {
            return $this->setParameter(self::PARAM_MAX_RESULT, $maxResults, Types::INTEGER);
        }

        $params = $this->getParameters();
        $types = $this->getParameterTypes();

        unset($params[self::PARAM_MAX_RESULT], $types[self::PARAM_MAX_RESULT]);

        return $this->setParameters($params, $types);
    }

    #[\Override]
    public function getMaxResults(): ?int
    {
        return $this->getParameter(self::PARAM_MAX_RESULT);
    }

    #[\Override]
    public function getSQL(): string
    {
        $result = parent::getSQL();

        if ($this->getMaxResults() !== null) {
            $result .= ' LIMIT :' . self::PARAM_MAX_RESULT;
            /** @var int|mixed $firstResult */
            $firstResult = $this->getFirstResult();

            if (\is_int($firstResult) && $firstResult > 0) {
                $result .= ' OFFSET :' . self::PARAM_FIRST_RESULT;
            }
        }

        return ' # heptaconnect-query-id ' . $this->identifier . \PHP_EOL . $result;
    }
}
