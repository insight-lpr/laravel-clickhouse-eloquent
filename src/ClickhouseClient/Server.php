<?php

namespace LaravelClickhouseEloquent\ClickhouseClient;

use LaravelClickhouseEloquent\ClickhouseClient\Common\ServerOptions;

/**
 * Alone server credentials.
 */
class Server
{
    /**
     * Host.
     *
     * @var string
     */
    protected $host;

    /**
     * Port.
     *
     * @var string
     */
    protected $port;

    /**
     * Database.
     *
     * @var string
     */
    protected $database;

    /**
     * Username.
     *
     * @var string
     */
    protected $username;

    /**
     * Password.
     *
     * @var string
     */
    protected $password;

    /**
     * Options.
     *
     * @var ServerOptions
     */
    protected $options;

    /**
     * Server constructor.
     *
     * @param string                                          $host
     * @param string                                          $port
     * @param string                                          $database
     * @param string|null                                     $username
     * @param string|null                                     $password
     * @param \LaravelClickhouseEloquent\ClickhouseClient\Common\ServerOptions|null $options
     */
    public function __construct(
        string $host,
        string $port = '8123',
        ?string $database = 'default',
        ?string $username = null,
        ?string $password = null,
        ServerOptions $options = null
    ) {
        $this->setHost($host);
        $this->setPort($port);
        $this->setDatabase($database);
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setOptions($options);
    }

    /**
     * Sets host.
     *
     * @param string $host
     *
     * @return \LaravelClickhouseEloquent\ClickhouseClient\Server
     */
    public function setHost(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Returns host.
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Sets port.
     *
     * @param string $port
     *
     * @return \LaravelClickhouseEloquent\ClickhouseClient\Server
     */
    public function setPort(string $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Returns port.
     *
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * Sets database.
     *
     * @param string|null $database
     *
     * @return \LaravelClickhouseEloquent\ClickhouseClient\Server
     */
    public function setDatabase(string $database = null): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Returns database.
     *
     * @return null|string
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * Sets username.
     *
     * @param string|null $username
     *
     * @return \LaravelClickhouseEloquent\ClickhouseClient\Server
     */
    public function setUsername(string $username = null): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Returns username.
     *
     * @return null|string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Sets password.
     *
     * @param string|null $password
     *
     * @return \LaravelClickhouseEloquent\ClickhouseClient\Server
     */
    public function setPassword(string $password = null): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returns password.
     *
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Sets options.
     *
     * If no options provided, will use default options
     *
     * @param \LaravelClickhouseEloquent\ClickhouseClient\Common\ServerOptions|null $options
     *
     * @return \LaravelClickhouseEloquent\ClickhouseClient\Server
     */
    public function setOptions(ServerOptions $options = null): self
    {
        if (is_null($options)) {
            return $this->setDefaultOptions();
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Sets default options.
     *
     * @return \LaravelClickhouseEloquent\ClickhouseClient\Server
     */
    protected function setDefaultOptions(): self
    {
        $this->options = new ServerOptions();

        return $this;
    }

    /**
     * Returns options.
     *
     * @return \LaravelClickhouseEloquent\ClickhouseClient\Common\ServerOptions
     */
    public function getOptions(): ServerOptions
    {
        return $this->options;
    }
}
