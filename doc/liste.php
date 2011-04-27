<?php
  // Array definieren (siehe oben)
  $items = array (
  array (
    'cat_id' => '1',
    'cat_name' => 'Extensions',
    'cat_parent' => '0',
    'cat_level' => '0',
  ),
  array (
    'cat_id' => '6',
    'cat_name' => 'Calendar & Time',
    'cat_parent' => '1',
    'cat_level' => '1',
  ),
  array (
    'cat_id' => '999',
    'cat_name' => 'Nur zum Test',
    'cat_parent' => '6',
    'cat_level' => '2',
  ),
	);
  // virtuelles Root-Element hinzufuegen
  array_unshift( $items, 0 );
  // Den ListBuilder einbinden
  include_once dirname(__FILE__).'/../wb28/modules/wblib/class.wbListBuilder.php';
  // Instanz erzeugen
  $list = new wbListBuilder( array( 'debug' => true ) );
  // ListBuilder konfigurieren
  $list->config(
    array(
      '__id_key' => 'cat_id',
      '__parent_key' => 'cat_parent',
      '__title_key' => 'cat_name',
      '__level_key' => 'cat_level',
    )
  );
  // Liste ausgeben
  echo $list->buildList( $items );
?>