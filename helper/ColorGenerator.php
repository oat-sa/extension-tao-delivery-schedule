<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDeliverySchedule\helper;

/**
 * Class for generating rgb color from string
 *
 * Usage example:
 * <pre>
 * $colorGenerator = new oat\taoDeliverySchedule\helper\ColorGenerator();
 * $colorGenerator->setMaxColorValue(70);
 * $colorGenerator->setMinColorValue(30);
 * $colorGenerator->setFormat(hex);
 * $color = $colorGenerator->getColor('randomString'); // returns the hex value including the number sign (#)
 * </pre>
 * 
 * @author Aleh Hutnikau <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class ColorGenerator
{
    /**
     * @var integer The max color value (lightness) in percentages
     */
    protected $maxColorVal = 100;
    /**
     * @var integer The min color value (lightness) in percentages
     */
    protected $minColorVal = 0;
    
    /**
     *
     * @var string Generated color format. It can be 'hex', 'hexArray' or 'rgbArray'. <b>hex</b> by default.
     * Examples:
     * <pre>
     * hex      - #E5FFCC                 //sting hex value including the number sign (#)
     * hexArray - array('E5', 'FF', 'CC') //array of hex values
     * rgbArray - array(229, 255, 255)    //array of integer values (0-255)
     * </pre>
     */
    protected $format = 'hex';

    /**
     * Generate hex color value from string.
     * @param type $string Basic string for generating color. If this parameter is not given, random string will be generated.
     * @throws Exception if <b>$this->maxColorVal</b> or <b>$this->minColorVal</b> not in range from 0 to 100 or <b>$this->maxColorVal</b> greather than <b>$this->minColorVal</b>
     */
    public function getColor($string = null) {
        if ($string === null) {
            $string = md5(uniqid(mt_rand(), true));
        }
        $color = substr(md5($string), 0, 6);
        $rgb = array(hexdec(substr($color, 0, 2)), hexdec(substr($color, 2, 2)), hexdec(substr($color, 4, 2)));
        
        if (($this->maxColorVal - $this->minColorVal) <= 0) {
            throw new \Exception('ColorGenerator::maxColorVal must be grather than ColorGenerator::minColorVal');
        }
        
        $delimeter = 100 / ($this->maxColorVal - $this->minColorVal);
        $minVal = (255 / 100 * $this->minColorVal);
        
        $result = array_map(function ($val) use ($delimeter, $minVal) {
            return (integer) ($val / $delimeter) + $minVal; 
        }, $rgb);
        
        switch ($this->format) {
            case 'hex':
                $result = $this->rgbArray2hex($result);
                break;
            case 'hexArray':
                $result = $this->rgbArray2hexArray($result);
                break;
            case 'rgbArray':
                break;
            default :
                $result = $this->rgbArray2hex($result);
                break;
        }
        return $result;
    }
    
    /**
     * Set maximum color value (lightness) in persentage.
     * @param integer $val
     * @throws Exception if value not in the range from 0 to 100
     */
    public function setMaxColorValue($val) {
        if ($val < 0 || $val > 100) {
            throw new \Exception('ColorGenerator::maxColorVal must be in the range from 0 to 100');
        }
        $this->maxColorVal = $val;
    }
    
    /**
     * Set minimum color value (lightness) in persentage.
     * @param integer $val
     * @throws Exception if value not in the range from 0 to 100
     */
    public function setMinColorValue($val) {
        if ($val < 0 || $val > 100) {
            throw new \Exception('ColorGenerator::minColorVal must be in the range from 0 to 100');
        }
        $this->minColorVal = $val;
    }
    
    /**
     * Set generated color format. It can be 'hex', 'hexArray' or 'rgbArray'. <b>hex</b> by default.
     * Examples:
     * <pre>
     * hex      - #E5FFCC                 //sting hex value including the number sign (#)
     * hexArray - array('E5', 'FF', 'CC') //array of hex values
     * rgbArray - array(229, 255, 255)    //array of integer values (0-255)
     * </pre>
     * @param integer $val
     */
    public function setFormat($val) {
        $this->format = $val;
    }
    
    /**
     * Convert rgb array to hex value including the number sign (#)
     * 
     * Example:
     * <pre>
     * $this->rgbArray2hex(array(255,255,0)); //returns '#ffff00'
     * </pre>
     * @param type $rgb
     * @return type
     */
    private function rgbArray2hex($rgb) {
       $hex = "#";
       $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
       $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

       return $hex;
    }
    
    /**
     * Convert rgb array values to hex values 
     * 
     * Example:
     * <pre>
     * $this->rgbArray2hex(array(255,255,0)); //returns array('FF', 'FF', '00')
     * </pre>
     * @param type $rgb
     * @return type
     */
    private function rgbArray2hexArray($rgb) {
       $hex = array();
       foreach ($rgb as $val) {
           $hex[] = str_pad(dechex($val), 2, "0", STR_PAD_LEFT);
       }

       return $hex;
    }
}
