<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\XMLException;

use DOMElement;


/**
 * Class Instruction
 * @package IPP\Student
 * Represents one instruction from the XML source file
 */
class Instruction
{
    protected string $opcode;
    /** @var array<int<0, max>, array<string, string>> */
    protected $args;

    /**
     * Instruction constructor
     * 
     * Setup a new instruction with given opcode and arguments.
     * @param string $opcode
     * @param array<int<0, max>, array<string, string>> $args
     * @return void
     */
    public function __construct(string $opcode, array $args)
    {
        $this->opcode = $opcode;
        $this->args = $args;
    }

    /**
     * Get instruction opcode
     * 
     * @return string
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * Get instruction arguments
     * 
     * @return array<int<0, max>, array<string, string>>
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Convert instruction to its string representation
     * 
     * @return string
     */
    public function __toString()
    {
        $args = [];
        foreach ($this->args as $arg) {
            $type = array_key_first($arg);
            $value = $arg[$type];
            $args[] = $type . '@' . $value;
        }
        return $this->opcode . ' ' . implode(' ', $args);
    }
}

/**
 * Main Interpreter
 * @package IPP\Student
 */
class Interpreter extends AbstractInterpreter
{
    /** @var array<int, Instruction> */
    protected array $instructions = [];

    public function load(): int
    {
        $dom = $this->source->getDOMDocument();
        $root = $dom->documentElement;
        try {
            if (empty($root))
                throw new XMLException("Missing root element");
            if ($root->tagName !== "program")
                throw new XMLException("Root element is not called `program`");
            $program = $root;
            $instructions = [];

            foreach ($program->childNodes as $subElement) {

                    if (!$subElement instanceof DOMElement)
                        continue;

                    if ($subElement->tagName !== "instruction")
                        throw new XMLException("Unexpected tag name: " . $subElement->tagName);

                    $instruction = $subElement;

                    $order = $instruction->getAttribute("order");
                    if (empty($order))
                        throw new XMLException("Missing order attribute");
                    if (!is_numeric($order) || $order < 0)
                        throw new XMLException("Invalid order attribute: " . $order);

                    $opcode = $instruction->getAttribute("opcode");
                    if (empty($opcode))
                        throw new XMLException("Missing opcode attribute");

                    $args = [];
                    foreach ($instruction->childNodes as $arg_node) {
                        if (!$arg_node instanceof DOMElement)
                            continue;

                        if (empty($arg_node->nodeValue))
                            throw new XMLException("Invalid or missing argument value");
                        $arg = [];
                        $arg[$arg_node->getAttribute("type")] = trim($arg_node->nodeValue);
                        $args[] = $arg;
                    }
                    $instructions[$order] = new Instruction($opcode, $args);
            }
        } catch (XMLException $e) {
            throw new XMLException("Invalid source XML format: " . $e->getMessage());
        }

        // foreach ($instructions as $instruction) {
        //     $this->stderr->writeString($instruction. PHP_EOL);
        // }

        $this->instructions = $instructions;
        return 0;
    }

    public function execute(): int
    {
        $this->load();
        return 0;
    }
}
