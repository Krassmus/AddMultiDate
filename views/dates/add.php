<form action="<?= PluginEngine::getLink($plugin, array(), "dates/add") ?>"
      class="default"
      method="post">
    <div id="datetime_error" style="display: none;">
        <?= MessageBox::error("hallo") ?>
    </div>
    <fieldset>
        <legend><?= _("Termine eintragen") ?></legend>
        <label>
            <input type="checkbox" name="freetext" value="1" onChange="jQuery('#add_freetext_location, #add_raumbuchung').toggle();">
            <?= _("Freie Raumangabe") ?>
        </label>

        <label id="add_freetext_location" style="display: none;">
            <?= _("Freie Raumangabe") ?>
            <input type="text" name="freetext_location" placeholder="<?= _("Seminarraum ...") ?>">
        </label>

        <label id="add_raumbuchung">
            <?= _("Raum buchen") ?>
            <?= QuickSearch::get("resource_id", new SQLSearch("SELECT resources.id, resources.name FROM resources INNER JOIN resource_categories ON (resource_categories.id = resources.category_id) WHERE resources.name LIKE :input OR resources.description LIKE :input AND resource_categories.class_name = 'Room' "))->render() ?>
        </label>

        <label id="add_freetext_location">
            <?= _("Dauer in Minuten") ?>
            <input type="text" name="dauer" value="60">
        </label>
        <? if ($date_types): ?>
            <label>
                <?= _('Termintyp') ?>
                <select name="date_type">
                    <? $first_id = array_keys($date_types)[0]; ?>
                    <? foreach ($date_types as $id => $date_type): ?>
                        <option value="<?= htmlReady($id)?>"
                                <?= $first_id == $id
                                  ? 'selected="selected"'
                                  : '' ?>>
                            <?= htmlReady($date_type) ?>
                        </option>
                    <? endforeach ?>
                </select>
            </label>
        <? endif ?>

        <ul class="clean datetime">
            <li>
                <label>
                    <input type="text" name="dates[]" placeholder="<?= _("Datum und Zeit") ?>">
                </label>
                <a class="trash" href="#" onClick="jQuery(this).closest('li').fadeOut(function () { jQuery(this).remove(); }); return false;">
                    <?= Icon::create("trash", "clickable")->asImg(20, array('class' => "text-bottom")) ?>
                </a>
                <a class="add" href="#" onClick="jQuery(this).closest('li').clone().appendTo('ul.clean.datetime').find('input').last().removeClass('hasDatepicker').removeAttr('id').datetimepicker(); return false;">
                    <?= Icon::create("add", "clickable")->asImg(20, array('class' => "text-bottom")) ?>
                </a>
            </li>
        </ul>
        <script>
            jQuery(function () {
                jQuery("ul.clean.datetime input").datetimepicker();
                jQuery(document).on("change", "ul.clean.datetime input, #add_raumbuchung input", function () {
                    //Abfrage, ob die Kombination aus Terminen und Raumbuchung valide ist.
                    var resource_id = jQuery("input[name=resource_id_parameter]").val();
                    var dates = jQuery("ul.clean.datetime input").map(function() {
                        return $(this).val();
                    }).toArray().filter(function (e) { return e; });
                    if (jQuery("input[name=freetext]").is(":checked") || !resource_id || (dates.length === 0)) {
                        jQuery("#datetime_error").hide();
                    } else {
                        jQuery.ajax({
                            "url": STUDIP.URLHelper.getURL("plugins.php/addmultidate/dates/check"),
                            "data": {
                                "resource_id": resource_id,
                                "dates": dates,
                                "dauer": jQuery().val()
                            },
                            "dataType": "json",
                            "success": function (json) {
                                if (json.message) {
                                    jQuery("#datetime_error").html(json.message);
                                    jQuery("#datetime_error").show();
                                } else {
                                    jQuery("#datetime_error").hide();
                                }
                            }
                        });
                    }
                });
            });
        </script>
        <style>
            ul.clean.datetime {
                margin-left: 7px;
            }
            ul.clean.datetime > li * {
                display: inline-block;
            }
            ul.clean.datetime > li a.add {
                display: none;
            }
            ul.clean.datetime > li:last-child a.add {
                display: inline-block;
            }
            ul.clean.datetime > li:last-child a.trash {
                display: none;
            }
        </style>



        <div data-dialog-button>
            <?= \Studip\Button::create(_("Speichern")) ?>
        </div>
    </fieldset>
</form>
