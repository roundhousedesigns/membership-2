<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/


class MS_Model_Rule_Shortcode extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_SHORTCODE;
	
	public function protect_content() {
		
	}
	
	public function get_content( $args = null ) {
		global $shortcode_tags;
		
		$contents = array();
		foreach( $shortcode_tags as $key => $function ) {
			$id = esc_html( trim( $key ) );
			$contents[ $id ]->id = $id;
			$contents[ $id ]->name = "[$key]";
			
			if( in_array( $id, $this->rule_value ) ) {
				$contents[ $id ]->access = true;
			}
			else {
				$contents[ $id ]->access = false;
			}
		}
		
		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		
		return $contents;
	}
}