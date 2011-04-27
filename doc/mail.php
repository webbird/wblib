<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
  <meta http-equiv="content-type" content="text/html; charset=windows-1250">
  <link rel="stylesheet" href="../css/default/skin.css" type="text/css" />
  <link rel="stylesheet" href="tutorial.css" type="text/css" />
  <title>wbFormBuilder Demo</title>
  </head>
  <body>
    <div class="header">
      <div>
        <h1>wbFormBuilder Demo</h1>
        [ <a href="http://localhost/_wikis/wblib/de/index.php?n=WbFormBuilder.Index">Zum Tutorial (online)</a>
        | <a href="template.php">wbTemplate Demo</a> ]
      </div>
    </div>
<?php
  include_once dirname(__FILE__).'/../class.wbFormBuilder.php';
  include_once dirname(__FILE__).'/../class.wbI18n.php';
  $form = new wbFormBuilder(
    array(
      'debug' => true,
      'action' => 'mail.php',
      'lang' => new wbI18n('DE'),
      'wblib_base_url' => 'http://localhost/_projects/wb28/modules',
    )
  );
  $form->setForm('mail');
  $form->printForm();
?>
  </body>
</html>