<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\XMLException;

use IPP\Student\VirtualMachine;
use IPP\Student\Exception\InvalidStructureException;
use IPP\Student\Exception\SemanticError;

use DOMElement;

/**
 * Main Interpreter
 * @package IPP\Student
 */
class Interpreter extends AbstractInterpreter
{
    /** @var array<int, Instruction> */
    protected array $instructions = [];
    /** @var array<string, int> */
    protected array $labels = [];

    private function load(): void
    {
        $dom = $this->source->getDOMDocument();
        $root = $dom->documentElement;
        if (empty($root)) {
            throw new XMLException("Missing root element");
        }
        if ($root->tagName !== "program") {
            throw new InvalidStructureException("Root element is not called `program`");
        }
        $program = $root;

        $language = $program->getAttribute("language");
        if (!$language) {
            throw new InvalidStructureException("Missing language declaration");
        }
        if ($language !== "IPPcode24") {
            throw new InvalidStructureException("Invalid language declaration: " . $language);
        }

        // @var array<int, Instruction>
        $instructions = [];

        // Iterate over all subElements, treat them as instructions
        foreach ($program->childNodes as $subElement) {

                if (!$subElement instanceof DOMElement) {
                    continue;
                }

                if ($subElement->tagName !== "instruction") {
                    throw new InvalidStructureException("Unexpected tag name: " . $subElement->tagName);
                }

                $instruction = $subElement;

                $order = $instruction->getAttribute("order");
                if (empty($order)) {
                    throw new InvalidStructureException("Missing order attribute");
                }
                if (!is_numeric($order) || $order < 0) {
                    throw new InvalidStructureException("Invalid order attribute: " . $order);
                }
                if (isset($instructions[$order])) {
                    throw new InvalidStructureException("Duplicate order attribute: " . $order);
                }

                $opcode = $instruction->getAttribute("opcode");
                if (empty($opcode)) {
                    throw new InvalidStructureException("Missing opcode attribute");
                }

                // Loop through all arguments and store them in an array
                $args = [];
                $expectedOrder = 1;
                foreach ($instruction->childNodes as $argNode) {
                    if (!$argNode instanceof DOMElement) {
                        continue;
                    }

                    if (!preg_match("/^(arg)([1-3])+$/", $argNode->tagName, $matches)) {
                        throw new InvalidStructureException("Unexpected tag name: " . $argNode->tagName);
                    }

                    $argOrder = intval($matches[2]);
                    // if ($argOrder !== $expectedOrder++) {
                    //     throw new InvalidStructureException("Invalid argument order: " . $argOrder);
                    // }

                    if (isset($args[$argOrder])) {
                        throw new InvalidStructureException("Duplicate argument number: " . $argOrder);
                    }

                    $arg = [];
                    $arg["type"] = $argNode->getAttribute("type");
                    if (empty($arg["type"])) {
                        throw new InvalidStructureException("Missing argument type");
                    }

                    if (empty($argNode->nodeValue) && $argNode->nodeValue !== "0" && $arg["type"] !== "string") {
                        throw new InvalidStructureException("Invalid or missing argument value: " . $argNode->nodeValue);
                    }
                    $arg["value"] = trim($argNode->nodeValue ?? "");
                    $args[$argOrder] = $arg;
                }
                ksort($args);
                // $args = array_combine(range(1, count($args)), array_values($args));
                if (!empty($args) && array_keys($args) !== range(1, count($args))) {
                    throw new InvalidStructureException("Invalid argument order");
                }
                $instructions[$order] = new Instruction($opcode, $args);
        }

        // The instruction order in source XML is not guaranteed
        // we need to sort it based on order attribute (first array key)
        ksort($instructions);

        // Reindex the array to get "natural" order
        $instructions = array_values($instructions);

        // foreach ($instructions as $instruction) {
        //     $this->stderr->writeString($instruction. PHP_EOL);
        // }

        // var_dump($instructions);

        $this->instructions = $instructions;
    }

    private function resolve_labels(): void
    {
        foreach ($this->instructions as $order => $instruction) {
            if ($instruction->getOpcode() === "LABEL") {
                $label_name = $instruction->getArgs()[1]["value"];
                if (isset($this->labels[$label_name])) {
                    throw new SemanticError("Label already defined: " . $label_name);
                }
                $this->labels[$label_name] = $order;
            }
        }

        // foreach ($this->labels as $label => $order) {
        //     $this->stderr->writeString($label . " " . $order . PHP_EOL);
        // }
    }

    private function interpret(): void
    {
        $vm = new VirtualMachine($this->instructions, $this->labels, $this->input, $this->stdout, $this->stderr);
        $vm->run();
    }

    public function execute(): int
    {
        $this->load();
        $this->resolve_labels();
        $this->interpret();
        return 0;
    }
}
