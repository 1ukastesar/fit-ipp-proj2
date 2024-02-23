<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\XMLException;

use IPP\Student\Exception\InvalidStructureException;

use DOMElement;

/**
 * Main Interpreter
 * @package IPP\Student
 */
class Interpreter extends AbstractInterpreter
{
    /** @var array<int, Instruction> */
    protected array $instructions = [];

    private function load(): int
    {
        $dom = $this->source->getDOMDocument();
        $root = $dom->documentElement;
        if (empty($root))
            throw new XMLException("Missing root element");
        if ($root->tagName !== "program")
            throw new InvalidStructureException("Root element is not called `program`");
        $program = $root;

        $language = $program->getAttribute("language");
        if (!$language)
            throw new InvalidStructureException("Missing language declaration");
        if ($language !== "IPPcode24")
            throw new InvalidStructureException("Invalid language declaration: " . $language);

        $instructions = [];

        // Iterate over all subElements, treat them as instructions
        foreach ($program->childNodes as $subElement) {

                if (!$subElement instanceof DOMElement)
                    continue;

                if ($subElement->tagName !== "instruction")
                    throw new InvalidStructureException("Unexpected tag name: " . $subElement->tagName);

                $instruction = $subElement;

                $order = $instruction->getAttribute("order");
                if (empty($order))
                    throw new InvalidStructureException("Missing order attribute");
                if (!is_numeric($order) || $order < 0)
                    throw new InvalidStructureException("Invalid order attribute: " . $order);
                if (isset($instructions[$order]))
                    throw new InvalidStructureException("Duplicate order attribute: " . $order);

                $opcode = $instruction->getAttribute("opcode");
                if (empty($opcode))
                    throw new InvalidStructureException("Missing opcode attribute");

                // Loop through all arguments and store them in an array
                $args = [];
                foreach ($instruction->childNodes as $arg_node) {
                    if (!$arg_node instanceof DOMElement)
                        continue;

                    if (!preg_match("/^arg[1-3]+$/", $arg_node->tagName))
                        throw new InvalidStructureException("Unexpected tag name: " . $arg_node->tagName);

                    if (empty($arg_node->nodeValue))
                        throw new InvalidStructureException("Invalid or missing argument value");
                    $arg = [];
                    $arg[$arg_node->getAttribute("type")] = trim($arg_node->nodeValue);
                    $args[] = $arg;
                }
                $instructions[$order] = new Instruction($opcode, $args);
        }

        // The instruction order in source XML is not guaranteed
        // we need to sort it based on order attribute (first array key)
        sort($instructions);

        foreach ($instructions as $instruction) {
            $this->stderr->writeString($instruction. PHP_EOL);
        }

        $this->instructions = $instructions;
        return 0;
    }

    public function execute(): int
    {
        $this->load();
        return 0;
    }
}
