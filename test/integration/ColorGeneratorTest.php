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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoDeliverySchedule\test\integration;

use Exception;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoDeliverySchedule\helper\ColorGenerator;

/**
 *
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 * @package taoDeliverySchedule
 */
class ColorGeneratorTest extends TaoPhpUnitTestRunner
{
    /**
     * @var ColorGenerator instance
     */
    private $colorGenerator;

    /**
     *
     * @var string Regular expression to validate hex
     */
    private $hexRegExp = '/^#(?:[0-9a-fA-F]{3}){1,2}$/';

    /**
     * tests initialization
     */
    public function setUp(): void
    {
        TaoPhpUnitTestRunner::initTest();
        $this->colorGenerator = new ColorGenerator();
    }

    public function testSetMaxColorValue()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ColorGenerator::maxColorVal must be in the range from 0 to 100');
        $this->colorGenerator->setMaxColorValue(101);
        $this->colorGenerator->getColor();
    }

    public function testSetMinColorValue()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ColorGenerator::minColorVal must be in the range from 0 to 100');
        $this->colorGenerator->setMinColorValue(-1);
        $this->colorGenerator->getColor();
    }

    public function testGetColorException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ColorGenerator::maxColorVal must be grather than ColorGenerator::minColorVal');
        $this->colorGenerator->setMaxColorValue(1);
        $this->colorGenerator->setMinColorValue(2);
        $this->colorGenerator->getColor();
    }

    /**
     * Check generating color
     */
    public function testGetColor()
    {
        $this->colorGenerator->setMaxColorValue(100);
        $this->colorGenerator->setMinColorValue(0);

        $this->colorGenerator->setFormat('hex');
        $hex = $this->colorGenerator->getColor();
        $this->assertRegExp($this->hexRegExp, $hex);

        $this->colorGenerator->setFormat('hexArray');
        $hexArray = $this->colorGenerator->getColor();
        $this->assertCount(3, $hexArray);
        foreach ($hexArray as $hexVal) {
            $this->assertRegExp('/[0-9a-fA-F]{2}/', $hexVal);
        }

        $this->colorGenerator->setFormat('rgbArray');
        $rgbArray = $this->colorGenerator->getColor();
        $this->assertCount(3, $rgbArray);
        foreach ($rgbArray as $intVal) {
            $this->assertTrue($intVal <= 255 && $intVal >= 0);
        }

        $this->colorGenerator->setMaxColorValue(100);
        $this->colorGenerator->setMinColorValue(95);

        $rgbArray2 = $this->colorGenerator->getColor();
        $this->assertCount(3, $rgbArray);
        foreach ($rgbArray2 as $intVal) {
            $this->assertTrue($intVal <= 255 && $intVal >= (255/100*95));
        }
    }

}
