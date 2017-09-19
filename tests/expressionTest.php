<?php
include_once "../www/index.php";
error_reporting(E_ALL & ~E_NOTICE);

class expressionTest extends PHPUnit_Framework_TestCase {
    public $parser;

    public function setUp() {
        $this->parser = new Parser();
    }

    public function tearDown() {
        $this->parser = null;
    }

    /**
     * positive cases for Parser::convertToArray method
     *
     * @dataProvider expressionProvider
     */
    public function testConvertToArray($expression, $expectedResult) {
        $validatedArray = $this->parser->convertToArray($expression);
        $this->assertEquals($validatedArray, $expectedResult);
    }

    public function expressionProvider() {
        return array(
            array("1+2", array("1", "+", "2")),
            array("(1+2)*4/2", array("(", "1", "+", "2", ")", "*", "4", "/" , "2")),
            array("(((1+2)))", array("(",  "(", "(", "1", "+", "2", ")", ")", ")")),
            array("1+-2", array("1", "-", "2")),
            array("1--2", array("1", "+", "2")),
            array("12*-47", array("12", "*", "(", "0", "-", "47", ")")),
            array("47/-12", array("47", "/", "(", "0", "-", "12", ")")),
            array("4 7    /- 1   2", array("47", "/", "(", "0", "-", "12", ")"))
        );
    }

    /**
     * covers the negative cases for incorrect first char
     *
     * @dataProvider errorFirstCharProvider
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Incorrect first char
     */
    public function testConvertToArrayWrongFirstSymbol($char) {
        $validatedArray = $this->parser->convertToArray($char);
    }

    public function errorFirstCharProvider() {
        return array(array(")"), array("+"), array("*"), array("/"));
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage The expression should contain only digits and arithmetic operators
     */
    public function testErrorContainInvalidChar() {
        $validatedArray = $this->parser->convertToArray("12+acv+233");
    }

    /**
     * @dataProvider invalidSyntaxDataProvider
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Invalid syntax
     */
    public function testConvertToArrayInvalidSyntax($expression) {
        $validatedArray = $this->parser->convertToArray($expression);
    }

    public function invalidSyntaxDataProvider() {
        return array(
            array("12/+23"),
            array("12++23"),
            array("12-+23"),
            array("12*+23"),
            array("12*+23"),
            array("12+*23"),
            array("12-*23"),
            array("12**23"),
            array("12/*23"),
            array("12+/23"),
            array("12-/23"),
            array("12*/23"),
            array("12//23"),
            array("12(12+1)"),
            array("(1+12)(12+1)"),
            array("(1+)"),
            array("(1+()"),
        );
    }


    /**
     * cover the error with no opened bracket
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Open bracket not found
     */
    public function testCreateStackNotOpenedBracket() {
        $this->parser->createStack(array(
            "1", "+", "123", "*", "3", "+", "45", ")"
        ));
    }

    /**
     * cover the error with no opened bracket
     *
     * @expectedException        Exception
     * @expectedExceptionMessage Unclosed bracket found
     */
    public function testCreateStackNotClosedBracket() {
        $this->parser->createStack(array(
            "1", "+", "123", "*", "(", "3", "+", "45", ")", "*", "("
        ));
    }

    /**
     * test converting to reverse polish notation
     */
    public function testCreateStackSuccessCase() {
        $stack = $this->parser->createStack(array(
            "1", "+", "123", "*", "(", "3", "+", "45", ")"
        ));
        $this->assertEquals($stack, array(
            "1", "123", "3", "45", "+", "*", "+"
        ));
    }

    /**
     * testing calculating expressions
     *
     * @dataProvider calculationDataProvider
     */
    public function testSuccessCalculation($expression, $expectedResult) {
        $validateArray = $this->parser->convertToArray($expression);
        $polishReverseFormArray = $this->parser->createStack($validateArray);
        $result = $this->parser->calculateStack($polishReverseFormArray);
        $this->assertEquals($result, $expectedResult);
    }

    public function calculationDataProvider() {
        return array(
            array("((((34+2)*2)-(45+21)/4)-123)-4", -71.5),
            array("200+12*((1/8)+1)-19", 194.5)
        );
    }
}
