<?php

return [
    'verkehrsunfall' => [
        'name' => 'Verkehrsunfall',
        'title' => 'Verkehrsunfall mit Personenschaden',
        'incident_description' => "Bei Eintreffen an der Einsatzstelle wurde ein Verkehrsunfall zwischen zwei Fahrzeugen vorgefunden. Beteiligte Fahrzeuge: [FAHRZEUG 1] und [FAHRZEUG 2]. Die Unfallstelle war bereits durch die Polizei abgesichert.\n\nFolgende Situation wurde vorgefunden:\n- Person A (Fahrer Fahrzeug 1): \n- Person B (Fahrer Fahrzeug 2): ",
        'actions_taken' => "Folgende Maßnahmen wurden durchgeführt:\n\nBei Patient A:\n- Stiffneck angelegt\n- Vitalparameter überprüft (Blutdruck, Puls, SpO2)\n- Zugang gelegt\n\nBei Patient B:\n- Sichtung und Erstversorgung\n\nBeide Patienten wurden nach der Erstversorgung transportfähig gemacht und in das nächstgelegene Krankenhaus transportiert.",
    ],
    'sturz_hauslich' => [
        'name' => 'Häuslicher Sturz',
        'title' => 'Gestürzte Person im häuslichen Umfeld',
        'incident_description' => "Nach Eintreffen in der Wohnung wurde eine gestürzte Person (ca. [ALTER] Jahre alt) auf dem Boden liegend vorgefunden. Die Person war ansprechbar und orientiert. Eigenen Angaben zufolge ist sie im Badezimmer ausgerutscht und auf die Hüfte gefallen.\n\nSchmerzangabe auf einer Skala von 1-10: [WERT].",
        'actions_taken' => "- Immobilisierung des betroffenen Beins\n- Vitalparameter-Check\n- Schmerzmittelgabe nach Rücksprache mit dem Notarzt\n- Umlagerung auf die Vakuummatratze\n- Transport ins Krankenhaus zur weiteren Abklärung (Verdacht auf Oberschenkelhalsfraktur).",
    ],
    'atemnot' => [
        'name' => 'Atemnot',
        'title' => 'Patient mit akuter Atemnot',
        'incident_description' => "Patient (ca. [ALTER] Jahre alt) klagte über plötzlich aufgetretene, massive Atemnot. Vorerkrankungen: [VORERKRANKUNGEN, z.B. COPD, Asthma].\n\nSauerstoffsättigung bei Eintreffen: [WERT]%.",
        'actions_taken' => "- Sauerstoffgabe über eine Maske ([LITER] l/min)\n- Oberkörperhochlagerung\n- Vitalparameter-Monitoring\n- Verneblung mit [MEDIKAMENT] nach ärztlicher Anordnung\n- Zügiger Transport in die Notaufnahme.",
    ],
];
