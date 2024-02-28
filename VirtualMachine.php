<?php

namespace IPP\Student;

use IPP\Core\Exception\NotImplementedException;
use IPP\Student\Exception\SemanticError;
use IPP\Student\Exception\UndefinedFrameException;
use IPP\Student\Exception\UndefinedVariableException;
use IPP\Student\Exception\UndefinedValueException;
use IPP\Student\Exception\WrongOperandTypeException;
use IPP\Student\Exception\WrongOperandValueException;

use IPP\Core\Interface\InputReader;
use IPP\Core\Interface\OutputWriter;
use IPP\Student\Exception\StringOperationException;

use DivisionByZeroError;
use IPP\Student\Exception\InvalidStructureException;

/**
 * VirtualMachine
 * @package IPP\Student
 * Represents a virtual machine for IPPcode24
 */
class VirtualMachine {

    /** @var array<Instruction> */
    private $instructions;
    /** @var array<string, int> */
    private $labels;
    /** @var InputReader */
    private $input;
    /** @var OutputWriter */
    private $stdout;
    /** @var OutputWriter */
    private $stderr;

    /** @var int */
    private $ip;
    /** @var Stack<int> */
    private $callStack;
    /** @var Stack<array<string, array<string, string>>> */
    private $frameStack;
    /** @var Stack<array<string, string>> */
    private $dataStack;
    /** @var array<string, array<string, string>> */
    private $globalFrame;
    /** @var array<string, array<string, string>>|null */
    private $temporaryFrame;

    /**
     * VirtualMachine constructor
     * 
     * Setup a new virtual machine with given instructions and labels.
     * @param array<Instruction> $instructions
     * @param array<string, int> $labels
     * @param InputReader $input
     * @param OutputWriter $stdout
     * @param OutputWriter $stderr
     * @return void
     */
    public function __construct($instructions, $labels, $input, $stdout, $stderr)
    {
        $this->instructions = $instructions;
        $this->labels = $labels;
        $this->input = $input;
        $this->stdout = $stdout;
        $this->stderr = $stderr;

        $this->ip = intval(array_key_first($instructions));
        $this->callStack = new Stack();
        $this->frameStack = new Stack();
        $this->dataStack = new Stack();
        $this->globalFrame = [];
        $this->temporaryFrame = null;

        // Use UTF-8 for string operations
        mb_internal_encoding("UTF-8");
    }

    /**
     * Execute instruction
     * 
     * Execute given instruction.
     * @param Instruction $instruction
     * @return void
     */
    private function executeInstruction($instruction)
    {
        // call_user_func(array($this, strtoupper($instruction->getOpcode())), $instruction->getArgs());
        $args = $instruction->getArgs();
        $opcode = strtoupper($instruction->getOpcode());
        switch ($opcode) {
            case "MOVE":
                $this->MOVE($args);
                break;
            case "CREATEFRAME":
                $this->CREATEFRAME($args);
                break;
            case "PUSHFRAME":
                $this->PUSHFRAME($args);
                break;
            case "POPFRAME":
                $this->POPFRAME($args);
                break;
            case "DEFVAR":
                $this->DEFVAR($args);
                break;
            case "CALL":
                $this->CALL($args);
                break;
            case "RETURN":
                $this->RETURN($args);
                break;
            case "PUSHS":
                $this->PUSHS($args);
                break;
            case "POPS":
                $this->POPS($args);
                break;
            case "ADD":
                $this->ADD($args);
                break;
            case "SUB":
                $this->SUB($args);
                break;
            case "MUL":
                $this->MUL($args);
                break;
            case "IDIV":
                $this->IDIV($args);
                break;
            case "LT":
                // $this->LT($args);
                // break;
            case "GT":
                // $this->GT($args);
                // break;
            case "EQ":
                // $this->EQ($args);
                // break;
            case "AND":
                // $this->AND($args);
                // break;
            case "OR":
                // $this->OR($args);
                // break;
            case "NOT":
                // $this->NOT($args);
                // break;
                throw new NotImplementedException("Not implemented yet: " . $opcode);
            case "INT2CHAR":
                $this->INT2CHAR($args);
                break;
            case "STRI2INT":
                $this->STRI2INT($args);
                break;
            case "READ":
                $this->READ($args);
                break;
            case "WRITE":
                $this->WRITE($args);
                break;
            case "CONCAT":
                $this->CONCAT($args);
                break;
            case "STRLEN":
                $this->STRLEN($args);
                break;
            case "GETCHAR":
                $this->GETCHAR($args);
                break;
            case "SETCHAR":
                $this->SETCHAR($args);
                break;
            case "TYPE":
                $this->TYPE($args);
                break;
            case "LABEL":
                break;
            case "JUMP":
                $this->JUMP($args);
                break;
            case "JUMPIFEQ":
                $this->JUMPIFEQ($args);
                break;
            case "JUMPIFNEQ":
                $this->JUMPIFNEQ($args);
                break;
            case "EXIT":
                $this->EXIT($args);
                break;
            case "DPRINT":
                $this->DPRINT($args);
                break;
            case "BREAK":
                $this->BREAK($args);
                break;
            default:
                // throw new NotImplementedException("Not implemented yet: " . $opcode);
                throw new InvalidStructureException("Invalid opcode: " . $opcode);
        }
    }

    /**
     * Run virtual machine
     * 
     * Start executing instructions.
     * @return void
     */
    public function run()
    {
        while ($this->ip < count($this->instructions)) {
            $instruction = $this->instructions[$this->ip];
            $this->executeInstruction($instruction);
            $this->ip++;
        }
        // var_dump($this->globalFrame);
        // var_dump($this->frameStack);
        // var_dump($this->dataStack);
    }

    /**
     * Get variable
     * 
     * Get variable from correct frame based on its name.
     * @param string $name
     * @param bool $canBeUndefined
     * @return array<string, string>
     * @throws UndefinedFrameException
     * @throws UndefinedVariableException
     * @throws WrongOperandValueException
     */
    private function getVariable($name, $canBeUndefined = false)
    {
        $var = explode("@", $name, 2); // Max 2 parts
        $frame = $var[0];
        $name = $var[1];
        switch($frame) {
            case "GF":
                if (!array_key_exists($name, $this->globalFrame)) {
                    throw new UndefinedVariableException($name);
                }
                $var = $this->globalFrame[$name];
                break;
            case "LF":
                // Undefined frame is handled automatically by stack itself
                // by throwing an EmptyStackException
                if (!array_key_exists($name, $this->frameStack->top())) {
                    throw new UndefinedVariableException($name);
                }
                $var = ($this->frameStack->top())[$name];
                break;
            case "TF":
                if(!is_array($this->temporaryFrame)) {
                    throw new UndefinedFrameException();
                }
                if (!array_key_exists($name, $this->temporaryFrame)) {
                    throw new UndefinedVariableException($name);
                }
                $var = $this->temporaryFrame[$name];
                break;
            default:
                throw new WrongOperandValueException("Invalid frame: " . $frame);
        }
        if ($var["type"] === "undefined") {
            if (!$canBeUndefined) {
                throw new UndefinedValueException($name);
            } else {
                return ["type" => "undefined", "value" => ""];
            }
        }
        return $var;
    }

    /**
     * Set variable
     * 
     * Set variable in correct frame based on its name.
     * @param string $name
     * @param string $type
     * @param string $value
     * @return void
     * @throws UndefinedFrameException
     * @throws UndefinedVariableException
     * @throws WrongOperandValueException
     */
    private function setVariable($name, $type, $value)
    {
        $var = explode("@", $name, 2); // Max 2 parts
        $frame = $var[0];
        $name = $var[1];
        switch($frame) {
            case "GF":
                if (!array_key_exists($name, $this->globalFrame)) {
                    throw new UndefinedVariableException($name);
                }
                $this->globalFrame[$name] = ["type" => $type, "value" => $value];
                break;
            case "LF":
                // Undefined frame is handled automatically by stack itself
                // by throwing an EmptyStackException
                if (!array_key_exists($name, $this->frameStack->top())) {
                    throw new UndefinedVariableException($name);
                }
                // TODO rewrite this to the more effective variant (use reference instead of copy)
                $lf = $this->frameStack->pop();
                $lf[$name] = ["type" => $type, "value" => $value];
                $this->frameStack->push($lf);
                // $this->frameStack->top()[$name] = ["type" => $type, "value" => $value];
                break;
            case "TF":
                if(!is_array($this->temporaryFrame)) {
                    throw new UndefinedFrameException();
                }
                if (!array_key_exists($name, $this->temporaryFrame)) {
                    throw new UndefinedVariableException($name);
                }
                $this->temporaryFrame[$name] = ["type" => $type, "value" => $value];
                break;
            default:
                throw new WrongOperandValueException("Invalid frame: " . $frame);
        }
    }

    /** 
     * symb
     * 
     * Resolve and return a value from a variable or a constant.
     * 
     * @param array<string, string> $arg
     * @return array<string, string>
     * @throws WrongOperandTypeException
     */
    private function symb($arg)
    {
        $type = $arg["type"];
        $value = $arg["value"];
        switch($type) {
            case "var":
                return $this->getVariable($value);
            case "string":
                $lastPos = 0;
                while($lastPos < mb_strlen($value) && ($lastPos = mb_strpos($value, "\\", $lastPos)) !== false) { // Find all escape sequences
                    $ascii_value = intval(mb_substr($value, $lastPos + 1, 3)); // Get a corresponding ASCII value
                    $escaped = mb_chr($ascii_value); // Find a corresponding character
                    $value = mb_substr($value, 0, $lastPos) . $escaped . mb_substr($value, $lastPos + 4); // Perform replace
                }
                return ["type" => $type, "value" => $value];
            case "int":
                $int = filter_var(
                    $value, 
                    FILTER_VALIDATE_INT, 
                    FILTER_NULL_ON_FAILURE | FILTER_FLAG_ALLOW_OCTAL | FILTER_FLAG_ALLOW_HEX
                );
                if ($int === null) {
                    throw new InvalidStructureException("Invalid argument value: ". $value);
                }
                return ["type" => $type, "value" => strval($int)];
            case "bool":
            case "nil":
                return $arg;
            default:
                throw new WrongOperandTypeException($type);
        }
    }

    /** 
     * var
     * 
     * Check if argument is a variable.
     * 
     * @param array<string, string> $arg
     * @return array<string, string>
     * @throws WrongOperandTypeException
     */
    private function var($arg)
    {
        if ($arg["type"] !== "var") {
            throw new WrongOperandTypeException($arg["type"]);
        }
        return $arg;
    }

    /**
     * Check if argument count is correct.
     * 
     * @param array<int, array<string, string>> $args
     * @param int $count
     * @return void
     */
    private function checkArgCount($args, $count) {
        if (count($args) !== $count) {
            throw new InvalidStructureException("Invalid argument count: " . count($args) . " instead of " . $count);
        }
    }

    /** 
     * MOVE <var> <symb>
     * 
     * Move value (from const or another variable) to a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function MOVE($args)
    {
        $this->checkArgCount($args, 2);

        $dst = $this->var($args[1])["value"];
        $src = $this->symb($args[2]);

        $this->setVariable($dst, $src["type"], $src["value"]);
    }

    /** 
     * CREATEFRAME
     * 
     * Create a new temporary frame.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function CREATEFRAME($args)
    {
        $this->temporaryFrame = [];
    }

    /** 
     * PUSHFRAME
     * 
     * Push temporary frame to the frame stack.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws UndefinedFrameException
     */
    private function PUSHFRAME($args)
    {
        if(!is_array($this->temporaryFrame)) {
            throw new UndefinedFrameException();
        }
        $this->frameStack->push($this->temporaryFrame);
        $this->temporaryFrame = null;
    }

    /** 
     * POPFRAME
     * 
     * Pop frame from the frame stack to the temporary frame.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function POPFRAME($args)
    {
        $this->temporaryFrame = $this->frameStack->pop();
    }

    /** 
     * DEFVAR <var>
     * 
     * Define a new variable in the correct frame.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws SemanticError
     * @throws UndefinedFrameException
     * @throws WrongOperandValueException
     */
    private function DEFVAR($args)
    {
        $this->checkArgCount($args, 1);

        $var = explode("@", $this->var($args[1])["value"], 2); // Max 2 parts
        $frame = $var[0];
        $name = $var[1];
        switch($frame) {
            case "GF":
                if (array_key_exists($name, $this->globalFrame)) {
                    throw new SemanticError($name);
                }
                $this->globalFrame[$name] = ["type" => "undefined", "value" => ""];
                break;
            case "LF":
                // Undefined frame is handled automatically by stack itself
                // by throwing an EmptyStackException
                if (array_key_exists($name, (array) $this->frameStack->top())) {
                    // Variable redefinition is semantic error
                    // weird but ok
                    throw new SemanticError($name);
                }
                // TODO rewrite this to the more effective variant (use reference instead of copy)
                $lf = $this->frameStack->pop();
                $lf[$name] = ["type" => "undefined", "value" => ""];
                $this->frameStack->push($lf);
                // $this->frameStack->top()[$name] = ["type" => "nil", "value" => ""];
                break;
            case "TF":
                if(!is_array($this->temporaryFrame))
                    throw new UndefinedFrameException();
                if (array_key_exists($name, $this->temporaryFrame)) {
                    throw new SemanticError($name);
                }
                $this->temporaryFrame[$name] = ["type" => "undefined", "value" => ""];
                break;
            default:
                throw new WrongOperandValueException("Invalid frame: " . $frame);
        }
    }

    /**
     * Check if arg is of type label and exists.
     * 
     * @param array<string, string> $arg
     * @return string
     * @throws SemanticError
     * @throws WrongOperandTypeException
     */
    private function label($arg) {
        if ($arg["type"] !== "label")
            throw new WrongOperandTypeException($arg["type"]);
        if (!array_key_exists($arg["value"], $this->labels))
            throw new SemanticError("Undefined label: ". $arg["value"]);
        return $arg["value"];
    }

    /** 
     * CALL <label>
     * 
     * Call a function.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function CALL($args)
    {
        $this->checkArgCount($args, 1);
        $label = $this->label($args[1]);
        $this->callStack->push($this->ip);
        $this->ip = $this->labels[$label];
    }

    /** 
     * RETURN
     * 
     * Return from a function.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function RETURN($args)
    {
        $this->ip = $this->callStack->pop();
    }

    /** 
     * PUSHS <symb>
     * 
     * Push a value to the data stack.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function PUSHS($args)
    {
        $this->checkArgCount($args, 1);
        $this->dataStack->push($this->symb($args[1]));
    }

    /** 
     * POPS <var>
     * 
     * Pop a value from the data stack to a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function POPS($args)
    {
        $this->checkArgCount($args, 1);
        $dst = $this->var($args[1])["value"];
        $src = $this->dataStack->pop();
        $this->setVariable($dst, $src["type"], $src["value"]);
    }

    /**
     * Convert a symbol to an integer.
     * 
     * @param array<string, string> $arg
     * @return int
     * @throws WrongOperandTypeException
     */
    private function convertToInt($arg)
    {
        if ($arg["type"] !== "int") {
            throw new WrongOperandTypeException($arg["type"]);
        }

        // TODO type checks
        return intval($arg["value"]);
    }

    /** 
     * ADD <var> <symb1> <symb2>
     * 
     * Add two integers and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function ADD($args)
    {
        $this->checkArgCount($args, 3);
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        $value = $this->convertToInt($src1) + $this->convertToInt($src2);
        $this->setVariable($dst, "int", strval($value));
    }

    /** 
     * SUB <var> <symb1> <symb2>
     * 
     * Subtract two integers and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function SUB($args)
    {
        $this->checkArgCount($args, 3);
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        $value = $this->convertToInt($src1) - $this->convertToInt($src2);
        $this->setVariable($dst, "int", strval($value));
    }

    /** 
     * MUL <var> <symb1> <symb2>
     * 
     * Multiply two integers and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function MUL($args)
    {
        $this->checkArgCount($args, 3);
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        $value = $this->convertToInt($src1) * $this->convertToInt($src2);
        $this->setVariable($dst, "int", strval($value));
    }

    /** 
     * IDIV <var> <symb1> <symb2>
     * 
     * Divide two integers and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandValueException
     */
    private function IDIV($args)
    {
        $this->checkArgCount($args, 3);
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        $divisor = $this->convertToInt($src2);

        try {
            $value = intdiv($this->convertToInt($src1), $divisor);
        } catch (DivisionByZeroError $e) {
            throw new WrongOperandValueException("Division by zero");
        }

        $this->setVariable($dst, "int", strval($value));
    }

    /** 
     * INT2CHAR <var> <symb>
     * 
     * Convert an integer to a character and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandValueException
     */
    private function INT2CHAR($args)
    {
        $this->checkArgCount($args, 2);
        $dst = $this->var($args[1])["value"];
        $src = $this->symb($args[2]);

        $value = mb_chr($this->convertToInt($src));
        if ($value == false) {
            throw new WrongOperandValueException("Invalid character");
        }
        $this->setVariable($dst, "string", $value);
    }

    /** 
     * STRI2INT <var> <symb1> <symb2>
     * 
     * Get a character from a string and store its unicode value in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws StringOperationException
     * @throws WrongOperandTypeException
     */
    private function STRI2INT($args)
    {
        $this->checkArgCount($args, 3);
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        if ($src1["type"] !== "string") {
            throw new WrongOperandTypeException($src1["type"]);
        }

        $index = $this->convertToInt($src2);
        if ($index < 0 || $index >= mb_strlen($src1["value"])) {
            throw new StringOperationException("Index out of range");
        }

        $char = mb_substr($src1["value"], $index, 1);
        $value = mb_ord($char);
        $this->setVariable($dst, "int", strval($value));
    }

    /**
     * READ <var> <type>
     * 
     * Read a value from stdin to a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandTypeException
     * @throws WrongOperandValueException
     */
    private function READ($args)
    {
        $this->checkArgCount($args, 2);
        $dst = $this->var($args[1])["value"];
        $type = $args[2]["value"]; // TODO check type of "type"

        switch($type) {
            case "int":
                $value = $this->input->readInt();
                break;
            case "string":
                $value = $this->input->readString();
                break;
            case "bool":
                $value = $this->input->readBool();
                break;
            default:
                throw new WrongOperandValueException("Invalid type: " . $type);
        }

        if ($value === null) {
            $type = "nil";
            $value = "nil";
        }

        $this->setVariable($dst, $type, strval($value));
    }

    /**
     * WRITE <symb>
     * 
     * Write a value to stdout.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function WRITE($args)
    {
        $this->checkArgCount($args, 1);
        $src = $this->symb($args[1]);
        switch ($src["type"]) {
            case "int":
                $this->stdout->writeInt($this->convertToInt($src));
                break;
            case "bool":
                $this->stdout->writeBool($src["value"] ? true : false);
                break;
            case "nil":
                $this->stdout->writeString("");
                break;
            case "string":
                $this->stdout->writeString($src["value"]);
                break;
            default:
                throw new WrongOperandTypeException($src["type"]);
        }
    }

    /**
     * CONCAT <var> <symb1> <symb2>
     * 
     * Concatenate two strings and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandTypeException
     */
    private function CONCAT($args)
    {
        $this->checkArgCount($args, 3);
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        if ($src1["type"] !== "string") {
            throw new WrongOperandTypeException($src1["type"]);
        }

        if ($src2["type"] !== "string") {
            throw new WrongOperandTypeException($src2["type"]);
        }

        $value = $src1["value"] . $src2["value"];
        $this->setVariable($dst, "string", $value);
    }

    /**
     * STRLEN <var> <symb>
     * 
     * Get the length of a string and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws WrongOperandTypeException
     */
    private function STRLEN($args)
    {
        $this->checkArgCount($args, 2);
        $dst = $this->var($args[1])["value"];
        $src = $this->symb($args[2]);

        if ($src["type"] !== "string") {
            throw new WrongOperandTypeException($src["type"]);
        }

        $value = mb_strlen($src["value"]);
        $this->setVariable($dst, "int", strval($value));
    }

    /**
     * GETCHAR <var> <symb1> <symb2>
     * 
     * Get a character from a string on a given index and store it in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws StringOperationException
     * @throws WrongOperandTypeException
     */
    private function GETCHAR($args)
    {
        $this->checkArgCount($args, 3);
        $dst = $this->var($args[1])["value"];
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        if ($src1["type"] !== "string") {
            throw new WrongOperandTypeException($src1["type"]);
        }

        $index = $this->convertToInt($src2);
        if ($index < 0 || $index >= mb_strlen($src1["value"])) {
            throw new StringOperationException("Index out of range");
        }

        $value = mb_substr($src1["value"], $index, 1);
        $this->setVariable($dst, "string", $value);
    }

    /**
     * SETCHAR <var> <symb1> <symb2>
     * 
     * Set a character in a string on a given index and store the result in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     * @throws StringOperationException
     * @throws WrongOperandTypeException
     */
    private function SETCHAR($args)
    {
        $this->checkArgCount($args, 3);
        $dst = $this->var($args[1]);
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        if ($dst["type"] !== "string") {
            throw new WrongOperandTypeException($dst["type"]);
        }

        if ($src1["type"] !== "string") {
            throw new WrongOperandTypeException($src1["type"]);
        }

        $index = $this->convertToInt($src2);
        if ($index < 0 || $index >= mb_strlen($dst["value"])) {
            throw new StringOperationException("Index out of range");
        }

        $value = $src1["value"];
        $value = mb_substr($dst["value"], 0, $index) . $value . mb_substr($dst["value"], $index + 1);
        $this->setVariable($dst["value"], "string", $value);
    }

    /**
     * TYPE <var> <symb>
     * 
     * Get the type of a variable and store it in a variable.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function TYPE($args)
    {
        $this->checkArgCount($args, 2);
        $dst = $this->var($args[1])["value"];
        if ($args[2]["type"] === "var") {
            $src = $this->getVariable($args[2]["value"], true);
        } else {
            $src = $this->symb($args[2]);
        }

        if ($src["type"] === "undefined") { // Variable undefined
            $value = "";
        } else {
            $value = $src["type"];
        }

        $this->setVariable($dst, "string", $value);
    }

    /**
     * JUMP <label>
     * 
     * Jump to a label.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function JUMP($args)
    {
        $this->checkArgCount($args, 1);
        $label = $this->label($args[1]);
        $this->ip = $this->labels[$label];
    }

    /**
     * JUMPIFEQ <label> <symb1> <symb2>
     * 
     * Jump to a label if two symbols are equal.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function JUMPIFEQ($args)
    {
        $this->checkArgCount($args, 3);
        $label = $this->label($args[1]);
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        if ($src1["type"] !== $src2["type"] && $src1["type"] !== "nil" && $src2["type"] !== "nil") { // Types not equal and none of them is nil
            throw new WrongOperandTypeException($src1["type"] . " and " . $src2["type"] . " are not equal");
        }

        if ($src1["value"] === $src2["value" ] // Values are equal
        || ($src1["type"] === "nil" && $src2["type"] === "nil" // OR either of them is nil
        && $src1["value"] === "nil" && $src2["value"] === "nil")) { // And both are defined
            $this->ip = $this->labels[$label];
        }
    }

    /**
     * JUMPIFNEQ <label> <symb1> <symb2>
     * 
     * Jump to a label if two symbols are not equal.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function JUMPIFNEQ($args)
    {
        $this->checkArgCount($args, 3);
        $label = $this->label($args[1]);
        $src1 = $this->symb($args[2]);
        $src2 = $this->symb($args[3]);

        if ($src1["type"] !== $src2["type"] && $src1["type"] !== "nil" && $src2["type"] !== "nil") { // Types not equal and none of them is nil
            throw new WrongOperandTypeException($src1["type"] . " and " . $src2["type"] . " are not equal");
        }

        if ($src1["value"] !== $src2["value" ] // Values are not equal
        || ($src1["type"] === "nil" && $src2["type"] === "nil" // OR either of them is nil
        && $src1["value"] !== "nil" && $src2["value"] !== "nil")) { // And both are defined
            $this->ip = $this->labels[$label];
        }
    }

    /**
     * EXIT <symb>
     * 
     * Exit the program with a given exit code.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function EXIT($args)
    {
        $this->checkArgCount($args, 1);
        $exitCode = $this->convertToInt($this->symb($args[1]));
        if ($exitCode < 0 || $exitCode > 9) {
            throw new WrongOperandValueException("Invalid exit code: " . $exitCode);
        }
        exit($exitCode);
    }

    /**
     * DPRINT <symb>
     * 
     * Write a value to stderr.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     *
     */
    private function DPRINT($args)
    {
        $this->checkArgCount($args, 1);
        $this->stderr->writeString($this->symb($args[1])["value"] . PHP_EOL);
    }

    /**
     * BREAK
     * 
     * Print the current state of the virtual machine to stderr.
     * 
     * @param array<int, array<string, string>> $args
     * @return void
     */
    private function BREAK($args)
    {
        $this->stderr->writeString("Instruction pointer: " . $this->ip . PHP_EOL);
        $this->stderr->writeString(PHP_EOL);
        $globalFrame    = (empty($this->globalFrame))    ? "Empty" . PHP_EOL : print_r($this->globalFrame, true);
        $temporaryFrame = (empty($this->temporaryFrame)) ? "Empty" . PHP_EOL : print_r($this->temporaryFrame, true);
        $frameStack     = ($this->frameStack->isEmpty()) ? "Empty" . PHP_EOL : print_r($this->frameStack, true);
        $dataStack      = ($this->dataStack->isEmpty())  ? "Empty" . PHP_EOL : print_r($this->dataStack, true);
        $this->stderr->writeString("Global frame: "    . $globalFrame    . PHP_EOL);
        $this->stderr->writeString("Temporary frame: " . $temporaryFrame . PHP_EOL);
        $this->stderr->writeString("Frame stack: "     . $frameStack     . PHP_EOL);
        $this->stderr->writeString("Data stack: "      . $dataStack      . PHP_EOL);
    }
}
