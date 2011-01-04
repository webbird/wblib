<?php

/**

  wblib simple include all

  Copyright (C) 2009, Bianka Martinovic
  Contact me: blackbird(at)webbird.de, http://www.webbird.de/

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 3 of the License, or (at
  your option) any later version.

  This program is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
  General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, see <http://www.gnu.org/licenses/>.

**/

foreach (
    array(
        'wbDatabase',
        'wbFormBuilder',
        'wbTemplate',
        'wbI18n',
        'wbListBuilder',
        'wbHolidays'
    ) as $class
) {
    if ( ! class_exists($class) ) {
        include_once dirname( __FILE__ ).'/class.'.$class.'.php';
    }
}

?>