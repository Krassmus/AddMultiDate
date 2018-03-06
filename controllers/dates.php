<?php

/**
 * This file is part of the DateGroupEditPlugin for Stud.IP
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author   Moritz Strohm <strohm@data-quest.de>
 * @license  http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category Plugin
**/

require_once('app/controllers/plugin_controller.php');

class DatesController extends PluginController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        PageLayout::setTitle(_("Termine hinzufügen"));
        if (!$GLOBALS['perm']->have_perm('dozent')) {
            throw new AccessDeniedException();
        }
    }

    public function add_action()
    {
        if (Request::isPost()) {
            foreach (Request::getArray("dates") as $date) {
                if ($date) {
                    $starttime = strtotime($date);
                    $endtime = $starttime + Request::int("dauer") * 60;
                    $coursedate = new CourseDate();
                    $coursedate['range_id'] = $_SESSION['SessionSeminar'];
                    $coursedate['date'] = $starttime;
                    $coursedate['end_time'] = $endtime;
                    $coursedate['date_typ'] = 1;
                    $coursedate['autor_id'] = $GLOBALS['user']->id;
                    if (Request::int("freetext")) {
                        $coursedate['raum'] = Request::get("freetext_location");
                    }
                    $coursedate->store();
                    if (!Request::int("freetext")) {
                        $assignment = new ResourceAssignment();
                        $assignment['resource_id'] = Request::option("resource_id");
                        $assignment['assign_user_id'] = $coursedate->getId();
                        $assignment['begin'] = $starttime;
                        $assignment['end'] = $endtime;
                        $assignment['repeat_end'] = $endtime;
                        $assignment->store();

                    }
                }
            }
            PageLayout::postMessage(MessageBox::success(_("Termine wurden angelegt")));
            $this->redirect(URLHelper::getURL("dispatch.php/course/timesrooms"));
        }
    }

    public function check_action()
    {
        $statement = DBManager::get()->prepare("
            SELECT resources_objects.name, resources_assign.begin, resources_assign.end
            FROM resources_assign
                INNER JOIN resources_objects ON (resources_assign.resource_id = resources_objects.resource_id)
            WHERE resources_assign.resource_id = :resource_id
                AND (
                    (begin >= :start AND begin < :end)
                    OR (end > :start AND end <= :end)
                    OR (end >= :end AND begin <= :start)
                )
        ");
        $conflicts = array();
        foreach (Request::getArray("dates") as $date) {
            $starttime = strtotime($date);
            $endtime = $starttime + Request::int("dauer") * 60;
            $statement->execute(array(
                'resource_id' => Request::option("resource_id"),
                'start' => $starttime,
                'end' => $endtime
            ));
            $results = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $result) {
                $conflicts[] = sprintf(
                    _("Von %s bis %s am %s ist %s schon belegt."),
                    date("H:i", $result['begin']),
                    date("H:i", $result['end']),
                    date("d.m.Y", $result['begin']),
                    $result['name']
                );
            }
        }
        $output = array();
        if (count($conflicts)) {
            $output['message'] = (string) MessageBox::error("Es gibt Kollisionen in den Raumbuchungen!", $conflicts);
        }
        $this->render_json($output);
    }


}
