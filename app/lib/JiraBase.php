<?php
namespace lib;

use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Issues\Walker;

/**
 * Базовий клас для роботи з Жира
 */
class JiraBase
{

    /**
     * JiraBase constructor.
     */
    public function __construct()
    {
        $di = \Phalcon\DI::getDefault();

        $this->api = new Api(
            $di->get('config')->jira_auth->host,
            new Basic(
                $di->get('config')->jira_auth->username,
                $di->get('config')->jira_auth->password
            )
        );
    }

    /**
     * Отримання списку тасок з жири
     *
     * @param string $search
     * @return array|bool
     */
    protected function _getIssuesBySearch($search)
    {

        $issues = [];
        if (empty($search) || !is_string((string)$search))
        {
            return false;
        }

        $walker = new Walker($this->api, 50);
        $walker->push(
            $search
        );

        foreach ( $walker as $issue ) {
            $issues[$issue->getKey()] = $issue;
        }

        return $issues;
    }
}