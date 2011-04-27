<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
      "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
  <head>
    <title>{{ TITLE }}</title>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    <link rel="stylesheet" href="tutorial.css" type="text/css" />
  </head>
  <body>
    <div class="header">
      <div>
        <h1>wbTemplate Demo</h1>
        [ <a href="http://localhost/_wikis/wblib/de/index.php?n=WbTemplate.Index">Zum Tutorial (online)</a>
        | <a href="mail.php">wbFormBuilder Demo</a> ]
      </div>
    </div>
    <div class="content">
    <h1>{{ :lang Country codes and currencies overview }}</h1>
{{ :if laender }}
    <table>
      <tr><th>{{ :lang Country code }}</th><th>{{ :lang Country }}</th><th>{{ :lang Currency }}</th></tr>
{{ :loop laender }}
      <tr><td>{{ kuerzel }}</td><td>{{ land }}</td><td>{{ waehrung }}</td></tr>
{{ :loopend }}
    </table>
{{ :else }}
    Hoppla, es wurden keine Daten übergeben!
{{ :ifend }}
    </div>
  </body>
</html>