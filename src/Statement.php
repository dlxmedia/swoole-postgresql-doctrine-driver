<?php declare(strict_types=1);

namespace Doctrine\DBAL\Driver\Swoole\Coroutine\PostgreSQL;

use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Swoole\Coroutine\PostgreSQL;

final class Statement implements StatementInterface
{
    private PostgreSQL $connection;
    private array $params;
    private string $key;

    public function __construct(PostgreSQL $connection, string $sql)
    {
        $this->connection = $connection;
        $this->params = [];
        $this->key = uniqid(md5($sql));

        if (isset($this->statements[$this->key])) {
            return;
        }

        $count = 0;
        $sql = preg_replace_callback('/\s(\?[\s\d]?)/', function () use (&$count) {
            $count++;
            return ' $'.$count.' ';
        }, $sql);

        try {
            $result = $this->connection->prepare($this->key, $sql);
        } catch (\Exception $e) {
            throw new \Doctrine\DBAL\Exception('Error adding statement: '.$sql, $e->getCode(), $e);
        }

        if (!$result) {
            throw new \Doctrine\DBAL\Exception($this->connection->errCode. ': '.$this->connection->error);
        }
    }

    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->params[$param] = $this->escape($value, $type);
    }

    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        $this->params[$param] = $this->escape($variable, $type);
    }

    public function execute($params = null): ResultInterface
    {
        $params = array_merge($this->params, $params ?? []);
        $result = $this->connection->execute($this->key, $params);

        if (is_bool($result)) {
            throw new \Doctrine\DBAL\Exception($this->connection->error);
        }

        return new Result($this->connection, $result);
    }

    private function escape($value, int $type): string
    {
        if ($type === ParameterType::STRING) {
            return (string) $this->connection->escape((string) $value);
        }

        return (string) $value;
    }
}
