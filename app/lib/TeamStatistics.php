<?php

namespace lib;

/**
 * Підрахунок статистики по учасниках команди та спринтах
 */
class TeamStatistics extends JiraBase
{

    protected $_redis;

    public function __construct()
    {
        parent::__construct();

        $this->_redis = new redis();
    }

    /**
     * Розрахунок статистики по спринтах та розробниках
     *
     * @param int $year
     * @return array
     */
    public function calculateStatistics( $year )
    {

        $result = [];

        /*if ($this->_redis->has('jira_tasks_' . $year))
        {
            $tasks = $this->_redis->get('jira_tasks_' . $year );
        }
        else*/
        {
            $tasks = $this->_getIssuesFromJira( $year );
            if (empty($tasks))
            {
                return $result;
            }

            $this->_redis->set('jira_tasks_' . $year, $tasks, 60 * 30);
        }

        $sprints = [];

        foreach ($tasks as $issueKey => $task) {

            $fields = $task->getFields();

            $issueSprints = $this->_parseIssueSprints($fields['Sprint']);

            foreach ($issueSprints as $key => $sprint)
            {

                $sprintId = $sprint['id'];
                if (empty($sprints[ $sprintId ]))
                {
                    $sprints[ $sprintId ] = $this->_setSprintDefaults();
                }

                $sprints[ $sprintId ]['name'] = $sprint['name'];
                $sprints[ $sprintId ]['state'] = $sprint['state'];
                $sprints[ $sprintId ]['taskTotal'] += 1;
                $sprints[ $sprintId ]['spTotal'] += $fields['Story Points'];

                if (!array_key_exists($fields['Assignee']['name'], $sprints[ $sprintId ]['developers']) )
                {
                    $sprints[ $sprintId ]['developers'][ $fields['Assignee']['name'] ] =
                        [
                            'taskDone'  => 0,
                            'spDone'    => 0,
                        ];
                }

                switch ($fields['Status']['name'])
                {
                    case 'Done':

                        // врахувати закриту таску тільки для останнього спринта
                        if ($key == 0)
                        {
                            $sprints[$sprintId]['taskDone'] += 1;
                            $sprints[$sprintId]['spDone'] += $fields['Story Points'];

                            $sprints[$sprintId]['developers'][$fields['Assignee']['name']]['taskDone'] += 1;
                            $sprints[$sprintId]['developers'][$fields['Assignee']['name']]['spDone'] += $fields['Story Points'];
                        }
                        break;
                    default:

                        $sprints[ $sprintId ]['developers'][ $fields['Assignee']['name'] ]['taskDone'] = 0;
                        $sprints[ $sprintId ]['developers'][ $fields['Assignee']['name'] ]['spDone'] = 0;

                        break;
                }

            }

        }

        ksort($sprints);

        return $sprints;
    }

    /**
     * Формування масиву зі спринтами, в яких брала участь задача
     *
     * @param array $sprints
     * @return array
     */
    private function _parseIssueSprints($sprints = [])
    {
        $issueSprints = [];
        $i = 0;
        foreach ($sprints as $sprint)
        {
            if (preg_match('/id=(.+),rapidViewId/s', $sprint, $matches))
            {
                if (empty($matches[1]) )
                {
                    continue;
                }

                $issueSprints[$i]['id'] = $matches[1];
            }

            if (preg_match('/name=(.+),goal/s', $sprint, $matches))
            {
                $issueSprints[$i]['name'] = $matches[1];
            }

            if (preg_match('/state=(.+),name/s', $sprint, $matches))
            {
                $issueSprints[$i]['state'] = $matches[1];
            }

            $i++;
        }

        $sprintIds = [];
        foreach ($issueSprints as $key => $row)
        {
            $sprintIds[$key] = $row['id'];
        }
        array_multisort($sprintIds, SORT_DESC, $issueSprints);

        return $issueSprints;
    }

    /**
     * Заповнення масиву дефолтними значеннями
     *
     * @return array
     */
    private function _setSprintDefaults()
    {
        return
        [
            'name'          => '',
            'state'         => '',
            'taskTotal'     => 0,
            'spTotal'       => 0,
            'taskDone'      => 0,
            'spDone'        => 0,
            'developers'    => [],
        ];
    }

    /**
     * Отримання списку тасок з жири
     *
     * @param $year
     *
     * @return array
     */
    private function _getIssuesFromJira($year)
    {

        if (empty($year) || !is_int((int)$year) || $year < 2015 )
        {
            return [];
        }

        $dates = $this->_getDates($year);
        $searchString = 'updatedDate >= \'' . $dates['startDate'] . '\' AND updatedDate < \'' . $dates['endDate'] . '\' AND assignee != null AND "Story Points" >= 0 AND type != Sub-task';

        return $this->_getIssuesBySearch($searchString);
    }

    /**
     * Формування початку та кінця періоду
     *
     * @param $year
     *
     * @return array
     */
    private function _getDates($year)
    {
        $startDate = $year . '-01-' . '01';
        $endDate = date("Y-m-d", strtotime("+1 year", strtotime($startDate)));

        return
        [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }
}