<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\XMLException;
use Exception;


/**
 * Class Instruction
 * @package IPP\Student
 * Represents one instruction from the XML source file
 */
class Instruction
{
    protected string $opcode;
    /** @var array<int, string> */
    protected $args;

    /**
     * Instruction constructor.
     * @param string $opcode
     * @param array<int, string> $args
     */
    public function __construct(string $opcode, array $args)
    {
        $this->opcode = $opcode;
        $this->args = $args;
    }

    public function getOpcode(): string
    {
        return $this->opcode;
    }

    /**
     * @return array<int, string>
     */
    public function getArgs()
    {
        return $this->args;
    }

    public function __toString()
    {
        return $this->opcode . ' ' . implode(' ', $this->args);
    }
}

/**
 * Main Interpreter
 * @package IPP\Student
 */
class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        $dom = $this->source->getDOMDocument();
        $program = $dom->getElementsByTagName("program")->item(0);
        if (empty($program))
            throw new XMLException("Missing program element.");
        $instructions = [];

        foreach ($program->childNodes as $instruction) {
            try {
                if ($instruction->nodeType !== XML_ELEMENT_NODE)
                    continue;

                // The method cannot be undefined because we are sure that the node is an element
                // @phpstan-ignore-next-line
                $order = $instruction->getAttribute("order");
                if(empty($order) || !is_numeric($order) || $order < 0)
                    throw new Exception("Invalid order attribute");

                // Same here
                // @phpstan-ignore-next-line
                $opcode = $instruction->getAttribute("opcode");
                if(empty($opcode))
                    throw new Exception("Invalid or missing opcode attribute");

                $args = [];
                foreach ($instruction->childNodes as $arg) {
                    if ($arg->nodeType === XML_ELEMENT_NODE && !empty($arg->nodeValue))
                        $args[] = trim($arg->nodeValue);
                        $arg->getAttribute("type");
                }

                // $this->stderr->writeString($opcode . ' ' . implode(' ', $args) . PHP_EOL);
                $instructions[$order] = new Instruction($opcode, $args);

            } catch (Exception $e) {
                throw new XMLException("Invalid source XML format: " . $e->getMessage());
            }
        }
        // var_dump($instructions);
        foreach ($instructions as $instruction) {
            $this->stderr->writeString($instruction. PHP_EOL);
        }
        return 0;
    }
}
