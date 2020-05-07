<?php

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
        //Get all available date types:
        $this->date_types = [];
        foreach ($GLOBALS['TERMIN_TYP'] as $id => $config_type) {
            $this->date_types[$id] = $config_type['name'];
        }

        if (Request::isPost()) {
            $date_type = Request::get('date_type');
            if (!in_array($date_type, array_keys($this->date_types))) {
                PageLayout::postError(
                    _('Es wurde ein ungültiger Termintyp ausgewählt!')
                );
                return;
            }
            foreach (Request::getArray("dates") as $date) {
                if ($date) {
                    $starttime = strtotime($date);
                    $endtime = $starttime + Request::int("dauer") * 60;
                    $coursedate = new CourseDate();
                    $coursedate['range_id'] = Context::get()->id;
                    $coursedate['date'] = $starttime;
                    $coursedate['end_time'] = $endtime;
                    $coursedate['date_typ'] = $date_type;
                    $coursedate['autor_id'] = $GLOBALS['user']->id;
                    if (Request::int("freetext")) {
                        $coursedate['raum'] = Request::get("freetext_location");
                    }
                    $coursedate->store();
                    if (!Request::int("freetext")) {
                        $assignment = new ResourceBooking();
                        $assignment['resource_id'] = Request::option("resource_id");
                        $assignment['range_id'] = $coursedate->getId();
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
            SELECT resources_objects.name, resource_bookings.begin, resource_bookings.end
            FROM resource_bookings
                INNER JOIN resources ON (resource_bookings.resource_id = resources.id)
            WHERE resource_bookings.resource_id = :resource_id
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
