<?php
/*
 * Copyright (c) 2011 Mark C. Prins <mc.prins@gmail.com>
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
 * DokuWiki Plugin geotag (Syntax Component)
 *
 * @license BSD
 * @author  Mark C. Prins <mc.prins@gmail.com>
 */
if (!defined('DOKU_INC')) die();

//if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
//if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';
/**
 * Handles the rendering part of the geotag plugin.
 * @author Mark
 *
 */
class syntax_plugin_geotag_geotag extends DokuWiki_Syntax_Plugin {
	public function getType() { return 'substition'; }

	public function getPType() { return 'block'; }

	public function getSort() { return 305; }

	public function connectTo($mode) {
		$this->Lexer->addSpecialPattern('\{\{geotag>.*?\}\}',$mode,'plugin_geotag_geotag');
	}

	public function handle($match, $state, $pos, &$handler){
		$tags = trim(substr($match, 9, -2));
		// parse geotag content
		preg_match("(lat[:|=]\d*\.\d*)",$tags,$lat);
		preg_match("(lon[:|=]\d*\.\d*)",$tags,$lon);
		preg_match("(region[:|=][a-zA-Z\s\w'-]*)",$tags,$region);
		preg_match("(placename[:|=][a-zA-Z\s\w'-]*)",$tags,$placename);
		preg_match("(country[:|=][a-zA-Z\s\w'-]*)",$tags,$country);
		preg_match("(hide|unhide)",$tags,$hide);

		$showlocation=$this->getConf('geotag_location_prefix');
		if ($this->getConf('geotag_showlocation')) {
			$showlocation=trim(substr($placename[0],10));
			if (strlen($showlocation)>0) {
				$showlocation .=': ';
			}
		}
		// read config
		$style='';
		if ($this->getConf('geotag_hide')) {
			$style=' style="display: none;"';
		}
		if (trim($hide[0])=='hide'){
			$style=' style="display: none;"';
		} elseif(trim($hide[0])=='unhide'){
			$style='';
		}

		$data = array(
		trim(substr($lat[0],4)),
		trim(substr($lon[0],4)),
		trim(substr($region[0],7)),
		trim(substr($placename[0],10)),
		trim(substr($country[0],8)),
		$showlocation,
		$style,
		);
		return $data;
	}

	public function render($mode, &$renderer, $data) {
		if ($data === false) return false;
		list ($lat, $lon, $region, $placename, $country, $showlocation, $style) = $data;
		if ($mode == 'xhtml') {
			if ($this->getConf('geotag_prevent_microformat_render')) {
				// config says no microformat
				return true;
			}
			// render geotag microformat
			$renderer->doc .= '<div class="geo"'.$style.'>'.$showlocation.'<span class="latitude">'.
			$lat.'</span>;<span class="longitude">'.$lon.'</span></div>'.DOKU_LF;
			return true;
		} elseif ($mode == 'metadata') {
			// render metadata (action plugin will put it in the page head)
			$renderer->meta['geo']['region'] = $region;
			$renderer->meta['geo']['lat'] = $lat;
			$renderer->meta['geo']['lon'] = $lon;
			$renderer->meta['geo']['country'] = $country;
			$renderer->meta['geo']['placename'] = $placename;
			return true;
		}
		return false;
	}
}
