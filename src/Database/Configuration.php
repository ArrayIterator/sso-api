<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database;

use DateTimeZone;
use Exception;
use Pentagonal\Sso\Core\Database\Utils\DateHelper;
use Pentagonal\Sso\Core\Utils\Encryption\SimpleOpenSSL;
use SensitiveParameter;
use Serializable;
use function date_default_timezone_get;
use function get_object_vars;
use function is_string;
use function property_exists;
use function serialize;
use function trim;
use function unserialize;
use const OPENSSL_RAW_DATA;

class Configuration implements Serializable
{
    /**
     * @var bool locked object
     */
    protected bool $locked = false;

    /**
     * @var string host of database
     */
    protected string $host = 'localhost';

    /**
     * @var string username of database
     */
    protected string $username = 'root';

    /**
     * @var string password of database
     */
    protected string $password = '';

    /**
     * @var string database name
     */
    protected string $database = '';

    /**
     * @var string charset
     */
    protected string $charset = 'utf8mb4';

    /**
     * @var string collation
     */
    protected string $collation = 'utf8mb4_unicode_ci';

    /**
     * @var string prefix
     */
    protected string $prefix = '';

    /**
     * @var int port
     */
    protected int $port = 3306;

    /**
     * @var string timezone
     */
    protected string $timezone;

    /**
     * @var bool strict
     */
    protected bool $strict = false;

    /**
     * @var string|null unix socket
     */
    protected ?string $unixSocket = null;

    /**
     * @var bool persistent
     */
    protected bool $persistent = false;

    /**
     * @var bool log query
     */
    protected bool $logQuery = false;

    /**
     * @var int max log
     */
    protected int $maxLog = 100;

    /**
     * @var string default timezone
     */
    private string $serverTimezone;

    public function __construct(#[SensitiveParameter] array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->setConfig($key, $value);
        }
    }

    /**
     * @return string
     */
    public function getServerTimezone(): string
    {
        if (isset($this->serverTimezone)) {
            return $this->serverTimezone;
        }
        try {
            $timezone = new DateTimeZone(date_default_timezone_get());
            $timezone = DateHelper::convertDateTimeZoneToSQLTimezone($timezone);
            $this->serverTimezone = $timezone;
        } catch (Exception) {
            $this->serverTimezone = '+00:00';
        }
        return $this->serverTimezone;
    }

    protected function setConfig($key, $value): void
    {
        switch ($key) {
            case 'timezone':
                $timezone = $this->getServerTimezone();
                try {
                    if (!$value instanceof DateTimeZone) {
                        if (!is_string($value)) {
                            return;
                        }
                        $value = new DateTimeZone($value);
                    }
                    $value = DateHelper::convertDateTimeZoneToSQLTimezone($value);
                } catch (Exception) {
                    $value = $timezone;
                }
                $this->timezone = $value;
                if ($this->timezone !== $timezone) {
                    try {
                        $dateTimeZone = new DateTimeZone($this->timezone);
                        $this->timezone = DateHelper::convertDateTimeZoneToSQLTimezone($dateTimeZone);
                    } catch (Exception) {
                        $this->timezone = $timezone;
                    }
                }
                return;
            case 'logQuery':
            case 'log':
            case 'log_query':
                $this->logQuery = (bool) $value;
                return;
            case 'maxLog':
            case 'maxlog':
            case 'max_log':
                $this->maxLog = (int) $value;
                return;
            case 'unixSocket':
            case 'unixsocket':
            case 'unix_socket':
                $this->unixSocket = $value;
                return;

            case 'database':
            case 'dbname':
            case 'db':
                $this->database = (string) $value;
                return;
            case 'strict':
            case 'persistent':
                $value = (bool) $value;
                break;
            case 'port':
                $value = (int) $value;
                break;
            case 'host':
            case 'username':
            case 'password':
            case 'charset':
            case 'collation':
            case 'prefix':
                $value = (string) $value;
                break;
        }
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
        }
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Set host
     *
     * @param string $host
     * @return $this
     */
    public function setHost(string $host): static
    {
        if (!$this->locked) {
            $this->host = $host;
        }
        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return $this
     */
    public function setUsername(#[SensitiveParameter] string $username): static
    {
        if (!$this->locked) {
            $this->username = $username;
        }
        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return $this
     */
    public function setPassword(#[SensitiveParameter] string $password): static
    {
        if (!$this->locked) {
            $this->password = $password;
        }
        return $this;
    }

    /**
     * Get database
     *
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Set database
     *
     * @param string $database
     * @return $this
     */
    public function setDatabase(string $database): static
    {
        if (!$this->locked) {
            $this->database = $database;
        }
        return $this;
    }

    /**
     * Get charset
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Set charset
     *
     * @param string $charset
     * @return $this
     */
    public function setCharset(string $charset): static
    {
        if (!$this->locked) {
            $this->charset = $charset;
        }
        return $this;
    }

    /**
     * Get collation
     *
     * @return string
     */
    public function getCollation(): string
    {
        return $this->collation;
    }

    /**
     * Set collation
     *
     * @param string $collation
     * @return $this
     */
    public function setCollation(string $collation): static
    {
        if (!$this->locked) {
            $this->collation = $collation;
        }
        return $this;
    }

    /**
     * Get prefix
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Set prefix
     *
     * @param string $prefix
     * @return $this
     */
    public function setPrefix(string $prefix): static
    {
        if (!$this->locked) {
            $this->prefix = $prefix;
        }
        return $this;
    }

    /**
     * Get port
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Set port
     *
     * @param int $port
     * @return $this
     */
    public function setPort(int $port): static
    {
        if (!$this->locked) {
            $this->port = $port;
        }
        return $this;
    }

    /**
     * Get timezone
     *
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone ??= $this->getServerTimezone();
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     * @return $this
     */
    public function setTimezone(string $timezone): static
    {
        if (!$this->locked) {
            $this->timezone = $timezone;
        }
        return $this;
    }

    /**
     * Is strict
     *
     * @return bool
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * Set strict
     * Set sql mode to "STRICT_TRANS_TABLES" if true
     *
     * @param bool $strict
     * @return $this
     */
    public function setStrict(bool $strict): static
    {
        if (!$this->locked) {
            $this->strict = $strict;
        }
        return $this;
    }

    /**
     * Get unix socket
     *
     * @return ?string
     */
    public function getUnixSocket(): ?string
    {
        return $this->unixSocket;
    }

    /**
     * Set unix socket
     *
     * @param ?string $unixSocket
     * @return $this
     */
    public function setUnixSocket(?string $unixSocket): static
    {
        if (!$this->locked) {
            $this->unixSocket = $unixSocket;
        }
        return $this;
    }

    /**
     * Is persistent
     *
     * @return bool
     */
    public function isPersistent(): bool
    {
        return $this->persistent;
    }

    /**
     * Set persistent
     *
     * @param bool $persistent
     * @return $this
     */
    public function setPersistent(bool $persistent): static
    {
        if (!$this->locked) {
            $this->persistent = $persistent;
        }
        return $this;
    }

    /**
     * Get log query
     *
     * @return bool
     */
    public function isLogQuery(): bool
    {
        return $this->logQuery;
    }

    /**
     * Set log query
     *
     * @param bool $logQuery
     * @return $this
     */
    public function setLogQuery(bool $logQuery): static
    {
        if (!$this->locked) {
            $this->logQuery = $logQuery;
        }
        return $this;
    }

    /**
     * Set max log
     *
     * @param int $maxLog
     * @return $this
     */
    public function setMaxLog(int $maxLog): static
    {
        if (!$this->locked) {
            $this->maxLog = $maxLog;
        }
        return $this;
    }

    /**
     * Get max log
     *
     * @return int
     */
    public function getMaxLog(): int
    {
        return $this->maxLog;
    }

    /**
     * Get DSN
     *
     * @return string
     */
    public function getDSN(): string
    {
        $dsn = 'mysql:';
        if ($this->unixSocket && trim($this->unixSocket) !== '') {
            $dsn .= 'unix_socket=' . $this->unixSocket . ';';
        } else {
            $dsn .= 'host=' . $this->host . ';port=' . $this->port . ';';
        }
        $dsn .= 'dbname=' . $this->database . ';';
        $dsn .= 'charset=' . $this->charset . ';';
        return $dsn;
    }

    /**
     * Lock the object
     *
     * @return $this
     */
    public function getLockedObject() : static
    {
        $obj = clone $this;
        $obj->locked = true;
        return $obj;
    }

    /**
     * Create new instance
     *
     * @param array $config
     * @return static
     */
    public static function create(#[SensitiveParameter] array $config = []): static
    {
        return new static($config);
    }

    /**
     * Debug info, protect sensitive data
     *
     * @return ?array
     */
    public function __debugInfo(): ?array
    {
        $prop = get_object_vars($this);
        // protect sensitive data
        $prop['username'] = '<redacted>';
        $prop['password'] = '<redacted>';
        return $prop;
    }

    public function __set(string $name, $value): void
    {
        // avoid
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return property_exists(
            $this,
            $name
        ) ? $this->{$name} : null;
    }

    /**
     * @implements Serializable
     */
    public function __serialize(): array
    {
        $vars = get_object_vars($this);
        $iv = SimpleOpenSSL::generateIV(
            SimpleOpenSSL::getIVLength(SimpleOpenSSL::DEFAULT_CIPHER)
        );
        $vars['username'] = SimpleOpenSSL::encryptData(
            $vars['username'],
            $iv,
            $iv,
            SimpleOpenSSL::DEFAULT_CIPHER,
            OPENSSL_RAW_DATA
        );
        $vars['password'] = SimpleOpenSSL::encryptData(
            $vars['password'],
            $iv,
            $iv,
            SimpleOpenSSL::DEFAULT_CIPHER,
            OPENSSL_RAW_DATA
        );
        return $vars;
    }

    /**
     * @implements Serializable
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'password' || $key === 'username') {
                $value = SimpleOpenSSL::decryptData(
                    $value[1],
                    $value[0],
                    $value[0],
                    SimpleOpenSSL::DEFAULT_CIPHER
                );
            }
            $this->setConfig($key, $value);
        }
    }

    /**
     * @implements Serializable
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @implements Serializable
     */
    public function unserialize($data): void
    {
        $data = unserialize($data);
        $this->__unserialize($data);
    }
}
