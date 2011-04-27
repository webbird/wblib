<?php
    // wbTemplate einbinden
    include_once dirname(__FILE__).'/../class.wbTemplate.php';
    include_once dirname(__FILE__).'/../class.wbI18n.php';
    // eine Instanz erzeugen
    $tpl = new wbTemplate( array( 'lang' => new wbI18n('DE') ) );
    // Das statische Array belegen
    $laender = array(
        array( 'kuerzel' => 'DE', 'land' => 'Deutschland', 'waehrung' => 'Euro'   ),
        array( 'kuerzel' => 'EN', 'land' => 'England'    , 'waehrung' => 'Pfund'  ),
        array( 'kuerzel' => 'US', 'land' => 'USA'        , 'waehrung' => 'Dollar' ),
    );
    // das Template ausgeben
    echo $tpl->getTemplate(
        'index.tpl',
        array(
            'TITLE'   => 'Einstieg in wbTemplate',
            'laender' => $laender,
        )
    );
?>