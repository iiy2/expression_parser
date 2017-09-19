<?php

class Parser {

    /**
     * list of allowed operators
     */
    private $operations = array("+", "-", "*", "/");

    /**
     * list of allowed number characters
     */
    private $numbers = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");

    /**
     * performs a calculation of expression provided in the reverse polish notation
     * @param array $stack
     * @return number
     */
    public function calculateStack(array $stack) {
        $temp = array();
        foreach ($stack as $key => $value) {
            if (!$this->isOperation($value)) {
                $temp[] = (int)$value;
            } else if (count($temp) >= 2) {
                $index = count($temp) - 1;
                $tempResult = 0;
                switch ($value) {
                    case "+":
                        $tempResult = $temp[$index - 1] + $temp[$index];
                        break;
                    case "-":
                        $tempResult = $temp[$index - 1] - $temp[$index];
                        break;
                    case "*":
                        $tempResult = $temp[$index - 1] * $temp[$index];
                        break;
                    case "/":
                        $tempResult = $temp[$index - 1] / $temp[$index];
                        break;
                }
                array_pop($temp);
                array_pop($temp);
                $temp[] = $tempResult;
            }
        }
        return $temp[0];
    }

    /**
     * Performs parsing an expression and returns the array of numbers and operators
     *
     * @param string $expression
     * @return array
     * @throws Exception
     */
    public function convertToArray($expression) {
        //validating
        $expression = $this->validateExpression($expression);

        $strLen = strlen($expression);
        $result = array();
        for ($i = 0; $i < $strLen; $i++) {
            if ($this->isNumber($expression[$i])) {
                if ($this->isNumber($expression[$i - 1])) {
                    end($result);
                    $index = key($result);
                    if (is_null($index)) {
                        $index = 0;
                    }
                    $result[$index] .= $expression[$i];
                } else {
                    $result[] = $expression[$i];
                }
                continue;
            }

            if ($this->isOperation($expression[$i])) {
                if ($this->isOperation($expression[$i - 1])) {
                    throw new Exception("Invalid syntax");
                } else {
                    $result[] = $expression[$i];
                }
                continue;
            }

            if ($expression[$i] === "(") {
                if ($i > 0 && ($this->isNumber($expression[$i - 1]) || $expression[$i - 1] === ")")) {
                    throw new Exception("Invalid syntax");
                } else {
                    $result[] = $expression[$i];
                }
                continue;
            }

            if ($expression[$i] === ")") {
                if ($this->isNumber($expression[$i - 1]) || $expression[$i - 1] === ")") {
                    $result[] = $expression[$i];
                } else {
                    throw new Exception("Invalid syntax");
                }
                continue;
            }
        }
        return $result;
    }

    /**
     * Prepares an array of numbers and operators in the reverse polish form
     *
     * @param array $stackArray
     * @return array
     * @throws Exception
     */
    public function createStack(array $stackArray) {
        $result = array();
        $operations = new SplStack();
        foreach ($stackArray as $key => $value) {
            if (is_numeric($value)) {
                $result[] = $value;
            }

            if ($this->isOperation($value)) {
                if ($this->isOperation($operations->current()) && $this->compareOperations($value, $operations->current())) {
                    $result[] = $operations->current();
                    $operations->pop();
                    $operations->push($value);
                    $operations->next();
                } else {
                    $operations->push($value);
                    $operations->next();
                }
            }

            if ($value === "(") {
                $operations->push($value);
            }

            if ($value === ")") {
                $operations->rewind();
                while ($operations->valid()) {
                    if ($operations->current() !== "(") {
                        $result[] = $operations->current();
                        $operations->pop();
                        $operations->next();

                    } else {
                        $closeBracketFound = true;
                        $operations->pop();
                        $operations->next();
                        break;
                    }
                }
                if (!$closeBracketFound) {
                    throw new Exception("Open bracket not found");
                }
            }
        }
        $operations->rewind();
        while ($operations->valid()) {
            if ($operations->current() === "(" || $operations->current() === ")") {
                throw new Exception("Unclosed bracket found");
            } else {
                $result[] = $operations->current();
                $operations->pop();
                $operations->next();
            }
        }

        return $result;
    }

    /**
     * performs basic validation for expression
     *
     * @param string $expression
     * @return string
     * @throws Exception
     */
    private function validateExpression($expression) {
        //escape spaces
        $expression = str_replace(" ", "", $expression);

        //check if the expression contains only allowed chars
        //preg_match("", $expression);
        if (!preg_match("/^[0-9\+\-\*\/\(\)]*$/", $expression)) {
           throw new Exception("The expression should contain only digits and arithmetic operators");
        }

        //check the first symbol of the expression - to be moved from the convertToArray method
        if (!$this->isValidFirstSymbol($expression[0])) {
            throw new Exception("Incorrect first char");
        } else if ($expression[0] === "-") {
            $expression = "0" . $expression;
        }

        //process negative values
        $expression = $this->processNegativeValues($expression);

        return $expression;
    }

    /**
     * convert operators combinations into more simple expressions
     * for example: "a*-b" and "a/*b" will be converted into "a*(0-b)" and "a/(0-b)"
     *
     * @param string $expression
     * @return string
     */
    private function processNegativeValues($expression) {
        //replace the "+-" to "-" and the "--" to "+"
        $expression = str_replace(array("+-", "--"), array("-", "+"), $expression);

        //processing "*-" and "/*" operator combinations and replace them with *(0-a) and /(0-a) correspondingly
        $expression = preg_replace(array("((\*\-)([0-9]+))", "((\/\-)([0-9]+))"), array("*(0-$2)", "/(0-$2)"), $expression);

        return $expression;
    }

    /**
     * Checks if the provided symbols is in the list of allowed operators
     *
     * @param string $char
     * @return bool
     */
    private function isOperation($char) {
        return in_array($char, $this->operations);
    }

    /**
     * Checks if the symbol may be the first in the expression
     *
     * @param string $char
     * @return bool
     */
    private function isValidFirstSymbol($char) {
        $valid = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "(", "-");
        return in_array($char, $valid);
    }

    /**
     * Checks if the symbol is a numeric
     *
     * @param string $char
     * @return bool
     */
    private function isNumber($char) {
        return in_array($char, $this->numbers);
    }

    /**
     * Comperes two operator by theirs priority
     * If the priority is less or equal - returns true
     *
     * @param string $op1
     * @param string $op2
     * @return bool
     */
    private function compareOperations($op1, $op2) {
        if (($op1 === "+" || $op1 === "-") && in_array($op2, $this->operations)) {
            return true;
        }

        if (($op1 === "/" || $op1 === "*") && ($op2 === "/" || $op2 === "*")) {
            return true;
        }

        return false;
    }

}
