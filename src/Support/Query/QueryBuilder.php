<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Query\QueryBuilder as BaseQueryBuilder;
use Doctrine\DBAL\Types\Type;

class QueryBuilder extends BaseQueryBuilder
{
    public const PARAM_FIRST_RESULT = 'frf0703687f4ca4b70a4cc85bf9e7377c7';

    public const PARAM_MAX_RESULT = 'mrf0703687f4ca4b70a4cc85bf9e7377c7';

    public function setFirstResult($firstResult)
    {
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
                    $firstResult = $this->getFirstResult();

                    if (\is_int($firstResult) && $firstResult > 0) {
                        $result .= ' OFFSET :' . self::PARAM_FIRST_RESULT;
                    }
                }

                break;
        }

        return $result;
    }
}
