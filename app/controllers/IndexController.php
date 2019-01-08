<?php


class IndexController extends ControllerBase
{

    /**
     * Nothing
     */
    public function indexAction()
    {
        return; //do nothing
    }

    /**
     * Результати спринта, агреговані по розробникам
     *
     * @param $sprint_id
     * @return mixed
     */
    public function sprintInfoAction($sprint_id)
    {

        $jiraWorker = new lib\CurrentSprintJira();
        $storyPointsByDeveloper = $jiraWorker->getStoryPointsByDeveloper($sprint_id);

        return $this->view->setVars(
            [
                'sprintId'          => $jiraWorker->getSprintId(),
                'sprintName'        => $jiraWorker->getSprintName(),
                'sprintStartDate'   => $jiraWorker->getSprintStartDate(),
                'sprintEndDate'     => $jiraWorker->getSprintEndDate(),
                'result'            => $storyPointsByDeveloper
            ]
        );
    }

    /**
     * Завантаження членв команди за місяць
     *
     * @param int $month_id
     * @param int $year
     */
    public function workloadAction($month_id, $year)
    {

        $jiraWorker = new lib\WorkloadCalculator();

        $workloadByDeveloper = $jiraWorker->getWorkloadForAllProjects($month_id, $year);

        return $this->view->setVars(
            [
                'month'     => date('F', mktime(0, 0, 0, $month_id, 10)),
                'result'    => $workloadByDeveloper
            ]
        );
    }

    /**
     * Статистика по учасникам команди по спринтах
     *
     * @param string $year
     * @return mixed
     */
    public function teamStatisticsAction( $year )
    {

        $teamModel = new lib\TeamStatistics();
        $sprints = $teamModel->calculateStatistics( $year );

        return $this->view->setVars(
            [
                'sprints' => $sprints
            ]
        );
    }

    /**
     * HTTP 404
     *
     * @return mixed
     */
    public function route404Action()
    {
        $this->assets->addCss("css/bootstrap.min.css");
        return $this->view->setVars([]);
    }
}

