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

use Carbon\Carbon;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use League\OAuth2\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Storage\ClientInterface;

/**
 * This is the fluent client class.
 *
 * @author Luca Degasperi <packages@lucadegasperi.com>
 */
class FluentClient extends AbstractFluentAdapter implements ClientInterface
{
    /**
     * Limit clients to grants.
     *
     * @var bool
     */
    protected $limitClientsToGrants = false;

    /**
     * Create a new fluent client instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param bool $limitClientsToGrants
     */
    public function __construct(Resolver $resolver, $limitClientsToGrants = false)
    {
        parent::__construct($resolver);
        $this->limitClientsToGrants = $limitClientsToGrants;
    }

    /**
     * Check if clients are limited to grants.
     *
     * @return bool
     */
    public function areClientsLimitedToGrants()
    {
        return $this->limitClientsToGrants;
    }

    /**
     * Whether or not to limit clients to grants.
     *
     * @param bool $limit
     */
    public function limitClientsToGrants($limit = false)
    {
        $this->limitClientsToGrants = $limit;
    }

    /**
     * Get the client.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $grantType
     *
     * @return null|\League\OAuth2\Server\Entity\ClientEntity
     */
    public function get($clientId, $clientSecret = null, $redirectUri = null, $grantType = null)
    {
        $query = null;

        if (!is_null($redirectUri) && is_null($clientSecret)) {
            $query = $this->getConnection()->table($this->clientTableName)
                   ->select(
                       "{$this->clientTableName}.id as id",
                       "{$this->clientTableName}.secret as secret",
                       "{$this->clientEndpointTableName}.redirect_uri as redirect_uri",
                       "{$this->clientTableName}.name as name")
                   ->join("{$this->clientEndpointTableName}", "{$this->clientTableName}.id", "=", "{$this->clientEndpointTableName}.client_id")
                   ->where("{$this->clientTableName}.id", $clientId)
                   ->where("{$this->clientEndpointTableName}.redirect_uri", $redirectUri);
        } elseif (!is_null($clientSecret) && is_null($redirectUri)) {
            $query = $this->getConnection()->table($this->clientTableName)
                   ->select(
                       "{$this->clientTableName}.id as id",
                       "{$this->clientTableName}.secret as secret",
                       "{$this->clientTableName}.name as name")
                   ->where("{$this->clientTableName}.id", $clientId)
                   ->where("{$this->clientTableName}.secret", $clientSecret);
        } elseif (!is_null($clientSecret) && !is_null($redirectUri)) {
            $query = $this->getConnection()->table($this->clientTableName)
                   ->select(
                       "{$this->clientTableName}.id as id",
                       "{$this->clientTableName}.secret as secret",
                       "{$this->clientEndpointTableName}.redirect_uri as redirect_uri",
                       "{$this->clientTableName}.name as name")
                   ->join($this->clientEndpointTableName, "{$this->clientTableName}.id", "=", "{$this->clientEndpointTableName}.client_id")
                   ->where("{$this->clientTableName}.id", $clientId)
                   ->where("{$this->clientTableName}.secret", $clientSecret)
                   ->where("{$this->clientEndpointTableName}.redirect_uri", $redirectUri);
        }

        if ($this->limitClientsToGrants === true && !is_null($grantType)) {
            $query = $query->join($this->clientGrantTableName, "{$this->clientTableName}.id", "=", "{$this->clientGrantTableName}.client_id")
                   ->join($this->grantTableName, "{$this->grantTableName}.id", "=", "{$this->clientGrantTableName}.grant_id")
                   ->where("{$this->grantTableName}.id", $grantType);
        }

        $result = $query->first();

        if (is_null($result)) {
            return;
        }

        return $this->hydrateEntity($result);
    }

    /**
     * Get the client associated with a session.
     *
     * @param  \League\OAuth2\Server\Entity\SessionEntity $session The session
     *
     * @return null|\League\OAuth2\Server\Entity\ClientEntity
     */
    public function getBySession(SessionEntity $session)
    {
        $result = $this->getConnection()->table($this->clientTableName)
                ->select(
                    "{$this->clientTableName}.id as id",
                    "{$this->clientTableName}.secret as secret",
                    "{$this->clientTableName}.name as name")
                ->join($this->sessionTableName, "{$this->sessionTableName}.client_id", "=", "{$this->clientTableName}.id")
                ->where("{$this->sessionTableName}.id", "=", $session->getId())
                ->first();

        if (is_null($result)) {
            return;
        }

        return $this->hydrateEntity($result);
    }

    /**
     * Create a new client.
     *
     * @param string $name The client"s unique name
     * @param string $id The client"s unique id
     * @param string $secret The clients" unique secret
     *
     * @return string
     */
    public function create($name, $id, $secret)
    {
        return $this->getConnection()->table($this->clientTableName)->insertGetId([
            "id" => $id,
            "name" => $name,
            "secret" => $secret,
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now(),
        ]);
    }

    /**
     * Hydrate the entity.
     *
     * @param $result
     *
     * @return \League\OAuth2\Server\Entity\ClientEntity
     */
    protected function hydrateEntity($result)
    {
        $client = new ClientEntity($this->getServer());
        $client->hydrate([
            "id" => $result->id,
            "name" => $result->name,
            "secret" => $result->secret,
            "redirectUri" => (isset($result->redirect_uri) ? $result->redirect_uri : null),
        ]);

        return $client;
    }
}
