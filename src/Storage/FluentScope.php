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
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\ScopeInterface;

/**
 * This is the fluent scope class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class FluentScope extends AbstractFluentAdapter implements ScopeInterface
{
    /*
     * Limit clients to scopes.
     *
     * @var bool
     */
    protected $limitClientsToScopes = false;

    /*
     * Limit scopes to grants.
     *
     * @var bool
     */
    protected $limitScopesToGrants = false;

    /**
     * Create a new fluent scope instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param bool|false $limitClientsToScopes
     * @param bool|false $limitScopesToGrants
     */
    public function __construct(Resolver $resolver, $limitClientsToScopes = false, $limitScopesToGrants = false)
    {
        parent::__construct($resolver);
        $this->limitClientsToScopes = $limitClientsToScopes;
        $this->limitScopesToGrants = $limitScopesToGrants;
    }

    /**
     * Set limit clients to scopes.
     *
     * @param bool|false $limit
     */
    public function limitClientsToScopes($limit = false)
    {
        $this->limitClientsToScopes = $limit;
    }

    /**
     * Set limit scopes to grants.
     *
     * @param bool|false $limit
     */
    public function limitScopesToGrants($limit = false)
    {
        $this->limitScopesToGrants = $limit;
    }

    /**
     * Check if clients are limited to scopes.
     *
     * @return bool|false
     */
    public function areClientsLimitedToScopes()
    {
        return $this->limitClientsToScopes;
    }

    /**
     * Check if scopes are limited to grants.
     *
     * @return bool|false
     */
    public function areScopesLimitedToGrants()
    {
        return $this->limitScopesToGrants;
    }

    /**
     * Return information about a scope.
     *
     * Example SQL query:
     *
     * <code>
     * SELECT * FROM oauth_scopes WHERE scope = :scope
     * </code>
     *
     * @param string $scope The scope
     * @param string $grantType The grant type used in the request (default = "null")
     * @param string $clientId The client id used for the request (default = "null")
     *
     * @return \League\OAuth2\Server\Entity\ScopeEntity|null If the scope doesn"t exist return false
     */
    public function get($scope, $grantType = null, $clientId = null)
    {
        $query = $this->getConnection()->table($this->scopeTableName)
                    ->select("{$this->scopeTableName}.id as id", "{$this->scopeTableName}.description as description")
                    ->where("{$this->scopeTableName}.id", $scope);

        if ($this->limitClientsToScopes === true && !is_null($clientId)) {
            $query = $query->join($this->clientScopeTableName, "{$this->scopeTableName}.id", "=", "{$this->clientScopeTableName}.scope_id")
                           ->where("{$this->clientScopeTableName}.client_id", $clientId);
        }

        if ($this->limitScopesToGrants === true && !is_null($grantType)) {
            $query = $query->join($this->grantScopeTableName, "{$this->scopeTableName}.id", "=", "{$this->grantScopeTableName}.scope_id")
                           ->join($this->grantTableName, "{$this->grantTableName}.id", "=", "{$this->grantScopeTableName}.grant_id")
                           ->where("{$this->grantTableName}.id", $grantType);
        }

        $result = $query->first();

        if (is_null($result)) {
            return;
        }

        $scope = new ScopeEntity($this->getServer());
        $scope->hydrate([
            "id" => $result->id,
            "description" => $result->description,
        ]);

        return $scope;
    }
}
