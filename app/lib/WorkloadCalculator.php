<?php

namespace lib;

/**
 * Підрахунок завантаження членів команди про проектаї
 */
class WorkloadCalculator extends JiraBase
{

    /**
     * @var int
     */
    const AVG_STORY_POINTS_PER_MONTH = 40;

    /**
     * Тестувальники та особливі люди, которі рахуються по кількості задач
     */
    const TESTERS_ACCOUNTS = [
        'tester1',
        'tester2',
        'tester3'
    ];

    /**
     * WorkloadCalculator constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Власне підрахунок завантаження розроюників про проектах
     *
     * @param $month_id
     * @param $year
     *
     * @return array
     */
    public function getWorkloadForAllProjects($month_id, $year)
    {

        $users = [];
        echo 'Start: ' . date('Y-m-d H:i:s', time()) . '<br>';
        $tasks = $this->_getIssuesFromJira($month_id,$year);
        echo 'End: ' . date('Y-m-d H:i:s', time()) . '<br>';

        if (empty($tasks))
        {
            return $users;
        }

        $projects = [];
        foreach ($tasks as $issueKey => $task) {

            $fields = $task->getFields();

            $projects[] = $fields['Project']['key'];
            $users[ $fields['Assignee']['name'] ]['projects'][ $fields['Project']['key'] ]['count'] += 1;
            $users[ $fields['Assignee']['name'] ]['projects'][ $fields['Project']['key'] ]['sp'] += $fields['Story Points'];

            $users[ $fields['Assignee']['name'] ]['totalIssues'] += 1;
            $users[ $fields['Assignee']['name'] ]['totalSp'] += $fields['Story Points'];
        }

        foreach ($users as $userName => $userData) {
            $users[$userName] = $this->_calculateWorkloadByUser($userData);
        }

        ksort($users);
        return $users;
    }

    /**
     * Підрахунок процентів по кожному користувачеві окремо
     *
     * @param $user
     * @return array|bool
     */
    private function _calculateWorkloadByUser($user)
    {

        if (!is_array($user))
        {
            return false;
        }

        foreach ($user['projects'] as $projectName => $projectData) {

            if ($user['totalSp'] != 0) {
                if ($user['totalSp'] > self::AVG_STORY_POINTS_PER_MONTH) {
                    $user['projects'][$projectName]['percents'] = round($user['projects'][$projectName]['sp'] / $user['totalSp'] * 100);
                } else {
                    $user['projects'][$projectName]['percents'] = round($user['projects'][$projectName]['sp'] / self::AVG_STORY_POINTS_PER_MONTH * 100);
                }
            } else {
                $user['projects'][$projectName]['percents'] = round($user['projects'][$projectName]['count'] / $user['totalIssues'] * 100);
            }
        }

        return $user;
    }

    /**
     * Отримання списку тасок з жири
     *
     * @param $month_id
     * @param $year
     *
     * @return array
     */
    private function _getIssuesFromJira($month_id, $year)
    {

        if (empty($month_id) || !is_int((int)$month_id) || !in_array($month_id, [1,2,3,4,5,6,7,8,9,10,11,12]))
        {
            return [];
        }

        if (empty($year) || !is_int((int)$year) || $year < 2015 )
        {
            return [];
        }

        $dates = $this->_getDates($month_id, $year);
        $searchString = 'updatedDate >= \'' . $dates['startDate'] . '\' AND updatedDate < \'' . $dates['endDate'] . '\' AND status != \'To Do\' AND type != Sub-task';

        return $this->_getIssuesBySearch($searchString);
    }

    /**
     * @param $month_id
     * @param $year
     *
     * @return array
     */
    private function _getDates($month_id, $year)
    {
        $startDate = $year . '-' . $month_id . '-' . '01';
        $endDate = date("Y-m-d", strtotime("+1 month", strtotime($startDate)));

        return
        [
          'startDate' => $startDate,
          'endDate' => $endDate
        ];
    }

    /*private function _getIssuesForTesters($month_id, $year)
    {

        $dates = $this->_getDates($month_id, $year);

        $searchString = 'updatedDate >= \'' . $dates['startDate'] . '\' AND updatedDate < \'' . $dates['endDate'] . '\' AND status != \'To Do\' AND assignee was in (' . implode(self::TESTERS_ACCOUNTS) . ') AND type != Sub-task';
        return $this->_getIssuesBySearch($searchString);
    }*/
}