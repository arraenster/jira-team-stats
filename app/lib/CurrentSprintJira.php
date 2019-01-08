<?php
namespace lib;

/**
 * Все, що стосується поточного спринту в Жирі
 */
class CurrentSprintJira extends JiraBase
{

    /**
     * @var chobie\Jira\Api
     */
    protected $api;

    /**
     * @var array
     */
    protected $issues;

    protected $sprintId;
    protected $sprintStartDate  = '1970-01-01';
    protected $sprintEndDate    = '1970-01-01';
    protected $sprintName       = 'no name';

    /**
     * CurrentSprintJira constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return int
     */
    public function getSprintId()
    {
        return $this->sprintId;
    }

    /**
     * @return string
     */
    public function getSprintStartDate()
    {
        return $this->sprintStartDate;
    }

    /**
     * @return string
     */
    public function getSprintEndDate()
    {
        return $this->sprintEndDate;
    }

    /**
     * @return string
     */
    public function getSprintName()
    {
        return $this->sprintName;
    }

    /**
     * Calculate sprint results
     *
     * @param int $sprint_id
     * @return array
     */
    public function getStoryPointsByDeveloper($sprint_id)
    {

        $this->sprintId = $sprint_id;
        $users = [];

        $sprint = $this->api->getSprint($sprint_id);

        $this->sprintStartDate = date('Y-m-d H:i:s', strtotime($sprint['startDate']));
        $this->sprintEndDate = date('Y-m-d H:i:s', strtotime($sprint['endDate']));
        $this->sprintName = $sprint['name'];

        echo $this->sprintStartDate . ' ' . $this->sprintName . '<br>';

        $tasks = $this->_getSprintTasks($sprint_id);

        if (empty($tasks))
        {
            return $users;
        }

        foreach ($tasks as $issueKey => $task)
        {

            $fields = $task->getFields();

            if (strtotime($fields['Created']) > strtotime($this->sprintStartDate))
            {
                $isScopeChange = true;
            } else
            {
                $isScopeChange = $this->_isScopeChange($issueKey);
            }

            if (!$isScopeChange)
            {
                $users[ $fields['Assignee']['name'] ]['startOfSprint']['count'] += 1;
                $users[ $fields['Assignee']['name'] ]['startOfSprint']['sp'] += $fields['Story Points'];
            }
            else
            {
                $users[ $fields['Assignee']['name'] ]['startOfSprint']['count'] += 0;
                $users[ $fields['Assignee']['name'] ]['startOfSprint']['sp'] += 0;
            }

            $users[ $fields['Assignee']['name'] ]['endOfSprint']['count'] += 1;
            $users[ $fields['Assignee']['name'] ]['endOfSprint']['sp'] += $fields['Story Points'];

            switch ($fields['Status']['name'])
            {
                case 'To Do':
                case 'In Progress':
                case 'In Testing (branch)':
                case 'In Testing (master)':
                    $users[ $fields['Assignee']['name'] ]['endOfSprint']['done']['count'] += 0;
                    $users[ $fields['Assignee']['name'] ]['endOfSprint']['done']['sp'] += 0;
                    break;
                case 'Done':

                    $users[ $fields['Assignee']['name'] ]['endOfSprint']['done']['count'] += 1;
                    $users[ $fields['Assignee']['name'] ]['endOfSprint']['done']['sp'] += $fields['Story Points'];
                    break;
                default:
                    $users[ $fields['Assignee']['name'] ]['endOfSprint']['done']['count'] += 0;
                    $users[ $fields['Assignee']['name'] ]['endOfSprint']['done']['sp'] += 0;
                    break;
            }

        }

        ksort($users);
        return $users;
    }

    /**
     * Check if this task is scope change
     *
     * @param $issueKey
     * @return bool
     */
    private function _isScopeChange( $issueKey )
    {

        $changeLog = $this->api->getIssue($issueKey, 'changelog');
        $changeLogHistory = $changeLog->getResult();
        foreach ($changeLogHistory['changelog']['histories'] as $historyRow)
        {

            if ($historyRow['items'][0]['field'] == 'Sprint'
                && $historyRow['items'][0]['to'] == (string)$this->sprintId
                && strtotime($historyRow['created']) > strtotime($this->sprintStartDate)
            )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Get issues for given sprints
     *
     * @return mixed
     */
    private function _getSprintTasks( $id )
    {
        if (empty($id) || !is_int((int)$id))
        {
            return false;
        }

        return $this->_getIssuesBySearch('sprint = ' . $id);
    }
}