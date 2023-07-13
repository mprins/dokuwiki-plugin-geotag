<?php
/*
 * Copyright (c) 2011 Mark C. Prins <mprins@users.sf.net>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * Options for the geotag plugin.
 *
 * @license BSD license
 * @author  Mark C. Prins <mprins@users.sf.net>
 */
$meta ['geotag_location_prefix']            = array('string');
$meta ['geotag_showlocation']               = array('onoff');
$meta ['geotag_hide']                       = array('onoff');
$meta ['geotag_prevent_microformat_render'] = array('onoff');
$meta ['toolbar_icon']                      = array('onoff');
$meta ['geotag_showsearch']                 = array('onoff');
$meta ['displayformat']                     = array('multichoice', '_choices' => array('DD', 'DMS'));
