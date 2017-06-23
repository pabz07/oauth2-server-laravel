<?php

/*
 * This file is part of OAuth 2.0 Laravel.
 *
 * (c) Luca Degasperi <packages@lucadegasperi.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LucaDegasperi\OAuth2Server\Storage;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use League\OAuth2\Server\Storage\AbstractStorage;

/**
 * This is the abstract fluent adapter class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
abstract class AbstractFluentAdapter extends AbstractStorage
{
    protected $accessTokenTableName;
    protected $accessTokenScopeTableName;
    protected $scopeTableName;
    protected $grantTableName;
    protected $grantScopeTableName;
    protected $clientTableName;
    protected $clientEndpointTableName;
    protected $clientScopeTableName;
    protected $clientGrantTableName;
    protected $sessionTableName;
    protected $sessionScopeTableName;
    protected $authCodeTableName;
    protected $authCodeScopeTableName;
    protected $refreshTokenTableName;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The connection name.
     *
     * @var string
     */
    protected $connectionName;

    /**
     * Create a new abstract fluent adapter instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     */
    public function __construct(Resolver $resolver)
    {
        $prefix = env("OAUTH_TABLE_PREFIX", "luca_");
        $this->accessTokenTableName = "{$prefix}oauth_access_tokens";
        $this->accessTokenScopeTableName = "{$prefix}oauth_access_token_scopes";
        $this->scopeTableName = "{$prefix}oauth_scopes";
        $this->grantTableName = "{$prefix}oauth_grants";
        $this->grantScopeTableName = "{$prefix}oauth_grant_scopes";
        $this->clientTableName = "{$prefix}oauth_clients";
        $this->clientEndpointTableName = "{$prefix}oauth_client_endpoints";
        $this->clientScopeTableName = "{$prefix}oauth_client_scopes";
        $this->clientGrantTableName = "{$prefix}oauth_client_grants";
        $this->sessionTableName = "{$prefix}oauth_sessions";
        $this->sessionScopeTableName = "{$prefix}oauth_session_scopes";
        $this->authCodeTableName = "{$prefix}oauth_auth_codes";
        $this->authCodeScopeTableName = "{$prefix}oauth_auth_code_scopes";
        $this->refreshTokenTableName = "{$prefix}oauth_refresh_tokens";

        $this->resolver = $resolver;
        $this->connectionName = null;
    }

    /**
     * Set the resolver.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     */
    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the resolver.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Set the connection name.
     *
     * @param string $connectionName
     *
     * @return void
     */
    public function setConnectionName($connectionName)
    {
        $this->connectionName = $connectionName;
    }

    /**
     * Get the connection.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function getConnection()
    {
        return $this->resolver->connection($this->connectionName);
    }
}
