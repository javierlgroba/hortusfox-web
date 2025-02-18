<?php

return [
    'added_new_plant' => 'Ich habe eine neue Pflanze hinzugefügt: <a href="{url}">{name}</a>',
    'moved_plant_to_history' => 'Ich habe <a href="{url}">{name}</a> zu ' . app('history_name') . ' geschoben',
    'restored_plant_from_history' => 'Ich habe <a href="{url}">{name}</a> von ' . app('history_name') . ' wiederhergestellt',
    'deleted_plant' => 'Ich habe <strong>{name}</strong> gelöscht',
    'created_task' => 'Ich habe eine neue Aufgabe erstellt: <a href="{url}">{name}</a>',
    'completed_task' => 'Ich habe eine Aufgabe fertiggestellt: <a href="{url}">{name}</a>',
    'reactivated_task' => 'Ich habe eine Aufgabe reaktiviert: <a href="{url}">{name}</a>',
    'created_inventory_item' => 'Ich habe einen neuen Eintrag im Inventar erstellt: <a href="{url}">{name}</a>',
    'removed_inventory_item' => 'Ich habe den Eintrag <strong>{name}</strong> aus dem Inventar entfernt'
];