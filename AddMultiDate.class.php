<?php

class AddMultiDate extends StudIPPlugin implements SystemPlugin
{
    public function __construct()
    {
        parent::__construct();

        if ($GLOBALS['perm']->have_perm('dozent')) {
            if (Navigation::hasItem('/course/admin/dates')) {
                //This plugin is only active in courses, not institutes.
                //NOTE: SessSemName has to be replaced by a call to the
                //Context class when this plugin is going to be made
                //compatible with Stud.IP 4.0!
                if ((strpos($_SERVER['REQUEST_URI'], 'dispatch.php/course/timesrooms')
                        or strpos($_SERVER['REQUEST_URI'], 'raumzeit.php'))
                    && (Context::isCourse())) {
                    NotificationCenter::addObserver(
                        $this,
                        'addActionToSidebar',
                        'SidebarWillRender'
                    );
                }
            }
        }
    }

    public function addActionToSidebar()
    {
        $sidebar = Sidebar::get();

        $widgets = $sidebar->getWidgets();

        $link_added = false;

        $link_data = [
            _('Mehrere Termine hinzufügen'),
            PluginEngine::getURL($this, [], 'dates/add'),
            Icon::create('add', "clickable"),
            ['data-dialog' => '1']
        ];

        foreach ($widgets as $widget) {
            if ($widget instanceof ActionsWidget) {
                $link_added = true;
                $widget->addLink(
                    $link_data[0],
                    $link_data[1],
                    $link_data[2],
                    $link_data[3]
                );
                break;
            }
        }

        if (!$link_added) {
            $actions = new ActionsWidget();

            $actions->addLink(
                $link_data[0],
                $link_data[1],
                $link_data[2],
                $link_data[3]
            );

            $sidebar->addWidget($actions);
        }
    }
}
