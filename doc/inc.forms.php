<?php
  $FORMS = array(
    'mail' => array(
      array(
        'type'     => 'legend',
        'text'     => 'Mail form',
      ),
      // versteckt: Empfänger-Mailadresse (sollte man nicht tun...)
      array(
        'type'     => 'hidden',
        'name'     => 'to',
        'value'    => 'nobody@nowhere.com'
      ),
      // einzeiliges Eingabefeld für die Absender-Mailadresse
      array(
        'type'     => 'text',
        'name'     => 'from',
        'label'    => 'Please insert your eMail address',
        'allow'    => 'email',
        'required' => true,
        'missing'  => 'You forgot to enter your eMail address',
        'invalid'  => 'Please insert a valid eMail address',
        'infotext' => 'Please enter your eMail address here; this will allow me to answer to your mail, and you can require a copy of this mail sent to you.',
      ),
      // einzeiliges Eingabefeld für den Absender-Namen
      array(
        'type'     => 'text',
        'name'     => 'fromname',
        'label'    => 'Please insert your name',
        'allow'    => 'string',
        'required' => true,
        'missing'  => 'You forgot to enter your name',
        'invalid'  => 'Please insert a valid name',
      ),
      // mehrzeiliges Eingabefeld (Textfeld) für die Mitteilung
      array(
        'type'     => 'textarea',
        'name'     => 'message',
        'label'    => 'Your message',
        'allow'    => 'plain',
        'required' => true,
        'missing'  => 'You forgot to enter your message',
      ),
      // radio-Auswahlfeld für die Zusendung einer Kopie
      array(
        'type'     => 'radio',
        'name'     => 'sendcopy',
        'label'    => 'Do you want to receive a copy of your mail?',
        'options'  => array( 'y' => 'yes', 'n' => 'no' ),
        'value'    => 'n',
      ),
    ),
  );
?>