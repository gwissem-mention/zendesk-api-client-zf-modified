<?php

/**
 * Zendesk API v2 client for Zend Framework
 *
 * <code>
 *   require_once 'ZendeskClient.php';
 *
 *   $client = new ZendeskClient([
 *       'subdomain' => 'YOUR_SUBDOMAIN',
 *       'username'  => 'YOUR_LOGIN',
 *       'token'     => 'YOUR_TOKEN'
 *   ]);
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
 * @version 1.0
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

    /** @var Zend_Http_Client */
    private $client;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->subdomain = $options['subdomain'];
        $this->username  = $options['username'];
        $this->token     = $options['token'];

        $this->client    = new Zend_Http_Client();
        $this->client->setAdapter(new Zend_Http_Client_Adapter_Curl());
    }

    /**
     * @param  string $page
     * @param  array  $jsonData
     * @param  string $method
     * @return array
     */
    private function _request($page, $method = 'GET', array $jsonData = null)
    {
        $this->client
             ->setUri("https://{$this->subdomain}.zendesk.com/api/v2/{$page}.json")
             ->setHeaders('Accept', 'application/json')
             ->setHeaders('Content-Type', 'application/json');

        $this->client->getAdapter()
             ->setCurlOption(CURLOPT_USERPWD, "{$this->username}/token:{$this->token}");

        if ($method == 'POST' || $method == 'PUT') {
            $this->client
                 ->setRawData(Zend_Json::encode([$this->_singular($page) => $jsonData]));
        }

        try {
            $response = $this->client->request($method);
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
     */
    public function get($page)
    {
        return $this->_request($page);
    }

    /**
     * @param string $page
     * @param array  $jsonData
     */
    public function update($page, array $jsonData)
    {
        return $this->_request($page, 'PUT', $jsonData);
    }

    /**
     * @param string $page
     */
    public function delete($page)
    {
        return $this->_request($page, 'DELETE');
    }

}