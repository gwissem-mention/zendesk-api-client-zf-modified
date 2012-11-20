<?php

/**
 * Zendesk API v2 client for Zend Framework
 *
 * <code>
 *   require_once 'ZendeskClient.php';
 *
 *   $client = new ZendeskClient('YOUR_SUBDOMAIN', 'YOUR_LOGIN', 'YOUR_TOKEN');
 *
 *   $jsonData = ['subject'   => 'My printer is on fire!',
 *                'comment'   => ['body' => 'The smoke is very colorful.'],
 *                'requester' => ['locale_id' => 1,
 *                                'name'      => 'Pablo',
 *                                'email'     => 'pablito@example.org']];
 *
 *   $client->create(ZendeskClient::TICKETS, $jsonData);
 * </code>
 *
 * @link    http://developer.zendesk.com/documentation/rest_api/introduction.html
 * @author  Bohdan Zhuravel <bohdan@zhuravel.biz>
 * @version 1.1
 */
class ZendeskClient
{

    /** Pages */
    const CATEGORIES      = 'categories';
    const FORUMS          = 'forums';
    const GROUPS          = 'groups';
    const ITEMS           = 'items';
    const ORGANIZATIONS   = 'organizations';
    const REQUESTS        = 'requests';
    const TICKETS         = 'tickets';
    const TICKET_FIELDS   = 'ticket_fields';
    const TOPICS          = 'topics';
    const USERS           = 'users';

    /** Types */
    const TYPE_PROBLEM    = 'problem';
    const TYPE_INCIDENT   = 'incident';
    const TYPE_QUESTION   = 'question';
    const TYPE_TASK       = 'task';

    /** Priorities */
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW    = 'low';

    /** Statuses */
    const STATUS_NEW      = 'new';
    const STATUS_OPEN     = 'open';
    const STATUS_PENDING  = 'pending';
    const STATUS_HOLD     = 'hold';
    const STATUS_SOLVED   = 'solved';
    const STATUS_CLOSED   = 'closed';

    /** @var string */
    private $subdomain;

    /** @var string */
    private $username;

    /** @var string */
    private $token;

    /**
     * @param string $subdomain
     * @param string $username
     * @param string $token
     */
    public function __construct($subdomain, $username, $token)
    {
        $this->subdomain = $subdomain;
        $this->username  = $username;
        $this->token     = $token;
    }

    /**
     * @param  string $page
     * @param  string $method
     * @param  array  $jsonData
     * @param  mixed  $query
     * @return array
     */
    private function _request($page, $method = 'GET', array $jsonData = null, $query = null)
    {
        $client = new Zend_Http_Client();
        $client->setAdapter(new Zend_Http_Client_Adapter_Curl());

        $client
            ->setUri("https://{$this->subdomain}.zendesk.com/api/v2/{$page}{$query}.json")
            ->setHeaders('Accept', 'application/json')
            ->setHeaders('Content-Type', 'application/json');

        $client->getAdapter()
            ->setCurlOption(CURLOPT_USERPWD, "{$this->username}/token:{$this->token}");

        if ($method == 'POST' || $method == 'PUT') {
            $client
                ->setRawData(Zend_Json::encode([$this->_singular($page) => $jsonData]));
        }

        try {
            $response = $client->request($method);
        } catch (Zend_Http_Client_Exception $e) {
            // Timeout or host not accessible
            return false;
        }

        if ($response->isError()) {
            // Error in response
            return false;
        }

        return Zend_Json::decode($response->getBody());
    }

    /**
     * @param  string $noun
     * @return string
     */
    private function _singular($noun)
    {
        if (preg_match('/ies$/i', $noun)) {
            return preg_replace('/ies$/i', 'y', $noun);
        }

        return substr($noun, 0, -1);
    }

    /**
     * @param string $page
     * @param array  $jsonData
     */
    public function create($page, array $jsonData)
    {
        return $this->_request($page, 'POST', $jsonData);
    }

    /**
     * @param string $page
     * @param string $query
     */
    public function get($page, $query = null)
    {
        return $this->_request($page, 'GET', null, $query);
    }

    /**
     * @param string $page
     * @param array  $jsonData
     * @param string $query
     */
    public function update($page, array $jsonData, $query = null)
    {
        return $this->_request($page, 'PUT', $jsonData, $query);
    }

    /**
     * @param string $page
     * @param string $query
     */
    public function delete($page, $query = null)
    {
        return $this->_request($page, 'DELETE', null, $query);
    }

}