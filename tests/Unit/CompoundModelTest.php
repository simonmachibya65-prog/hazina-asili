<?php
/**
 * Unit Tests for Compound Model — validation & static methods
 */

use PHPUnit\Framework\TestCase;

class CompoundModelTest extends TestCase {

    // ── validateFormula() ─────────────────────────────────────

    public function testValidFormulaSimple(): void {
        $this->assertTrue(Compound::validateFormula('C6H12O6'));
    }

    public function testValidFormulaComplex(): void {
        $this->assertTrue(Compound::validateFormula('C15H10O7'));
    }

    public function testValidFormulaWithSulfur(): void {
        $this->assertTrue(Compound::validateFormula('C6H10OS2'));
    }

    public function testValidFormulaSingleElement(): void {
        $this->assertTrue(Compound::validateFormula('C'));
    }

    public function testValidFormulaTwoLetterElements(): void {
        $this->assertTrue(Compound::validateFormula('NaCl'));
    }

    public function testInvalidFormulaLowercase(): void {
        $this->assertFalse(Compound::validateFormula('ch4'));
    }

    public function testInvalidFormulaEmpty(): void {
        $this->assertFalse(Compound::validateFormula(''));
    }

    public function testInvalidFormulaSpecialChars(): void {
        $this->assertFalse(Compound::validateFormula('C6H12-O6'));
    }

    public function testInvalidFormulaSpaces(): void {
        $this->assertFalse(Compound::validateFormula('C6 H12'));
    }

    public function testInvalidFormulaStartsWithNumber(): void {
        $this->assertFalse(Compound::validateFormula('2C6H12'));
    }

    // ── estimateMolecularWeight() ─────────────────────────────

    public function testEstimateMWWater(): void {
        // H2O = 2(1.008) + 15.999 = 18.015
        $mw = Compound::estimateMolecularWeight('H2O');
        $this->assertEqualsWithDelta(18.015, $mw, 0.01);
    }

    public function testEstimateMWGlucose(): void {
        // C6H12O6 = 6(12.011) + 12(1.008) + 6(15.999) = 180.156
        $mw = Compound::estimateMolecularWeight('C6H12O6');
        $this->assertEqualsWithDelta(180.156, $mw, 0.1);
    }

    public function testEstimateMWQuercetin(): void {
        // C15H10O7 = 15(12.011) + 10(1.008) + 7(15.999) = 302.238
        $mw = Compound::estimateMolecularWeight('C15H10O7');
        $this->assertEqualsWithDelta(302.238, $mw, 0.1);
    }

    public function testEstimateMWCaffeine(): void {
        // C8H10N4O2 = 8(12.011) + 10(1.008) + 4(14.007) + 2(15.999) = 194.19
        $mw = Compound::estimateMolecularWeight('C8H10N4O2');
        $this->assertEqualsWithDelta(194.19, $mw, 0.1);
    }

    public function testEstimateMWUnknownElement(): void {
        // Unknown element gets 0 weight — formula still parses
        $mw = Compound::estimateMolecularWeight('Xx5');
        $this->assertEquals(0.0, $mw);
    }

    public function testEstimateMWSingleCarbon(): void {
        $mw = Compound::estimateMolecularWeight('C');
        $this->assertEqualsWithDelta(12.011, $mw, 0.001);
    }

    public function testEstimateMWReturnsFloat(): void {
        $mw = Compound::estimateMolecularWeight('H2O');
        $this->assertIsFloat($mw);
    }
}
