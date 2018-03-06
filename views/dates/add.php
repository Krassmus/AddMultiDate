<form action="<?= PluginEngine::getLink($plugin, array(), "dates/add") ?>"
      class="default studip_form"
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
            <?= QuickSearch::get("resource_id", new SQLSearch("SELECT resource_id, name FROM resources_objects WHERE name LIKE :input OR description LIKE :input "))->render() ?>
        </label>

        <label id="add_freetext_location">
            <?= _("Dauer in Minuten") ?>
            <input type="text" name="dauer" value="60">
        </label>

        <ul class="clean datetime">
            <li>
                <label>
                    <input type="text" name="dates[]" placeholder="<?= _("Datum und Zeit") ?>">
                </label>
                <a class="trash" href="#" onClick="jQuery(this).closest('li').fadeOut(function () { jQuery(this).remove(); }); return false;">
                    <?= Assets::img("icons/20/blue/trash", array('class' => "text-bottom")) ?>
                </a>
                <a class="add" href="#" onClick="jQuery(this).closest('li').clone().appendTo('ul.clean.datetime').find('input').last().removeClass('hasDatepicker').removeAttr('id').datetimepicker(); return false;">
                    <?= Assets::img("icons/20/blue/add", array('class' => "text-bottom")) ?>
                </a>
            </li>
        </ul>
        <script>
            jQuery(function () {
                jQuery("ul.clean.datetime input").datetimepicker();
                jQuery(document).on("change", "ul.clean.datetime input, #add_raumbuchung input", function () {
                    //Abfrage, ob die Kombination aus Terminen und Raumbuchung valide ist.
                    var resource_id = jQuery("#resource_id_1_realvalue").val();
                    var dates = jQuery("ul.clean.datetime input").map(function() {
                        return $(this).val();
                    }).toArray().filter(function (e) { return e; });
                    if (jQuery("input[name=freetext]").is(":checked") || !resource_id || (dates.length === 0)) {
                        jQuery("#datetime_error").hide();
                    } else {
                        jQuery.ajax({
                            "url": STUDIP.URLHelper.getURL("plugins.php/addmultidate/dates/check"),
                            "data": {
                                "resource_id": jQuery("#resource_id_1_realvalue").val(),
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