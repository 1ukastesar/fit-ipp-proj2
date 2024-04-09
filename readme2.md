# Implementační dokumentace k 2. úloze do IPP 2023/2024

**Jméno a příjmení:** Lukáš Tesař \
**Login:** xtesar43

## Implementation - core fundamentals
- The implementation is based on the `ipp-core` framework as required by the assignment. It uses its functions for source code loading and parsing, input reading and type checks and last but not least, error handling by the use of custom exceptions which inherit from `IPPException` as their parent class.

### Exceptions
- Starting from the end, all custom exceptions are defined in the `Exception` folder and included by the `use` keyword wherever needed. They are used to handle errors in the code and are thrown whenever an error occurs. It is up to the calling framework to catch them and return correct error code they have set internally.
- These exceptions are:
    - `EmptyStackException` - thrown when the stack is empty and an operation is called that requires a non-empty stack
    - `InvalidStructureException` - thrown when the input XML file contains invalid elements or constructs that should be caught by `parse.py`
    - `SemanticError` - semantic error is encountered, e. g. jump to an undefined label or variable redefinition
    - `StringOperationException` - raised on invalid string operations, such as indexing out of bounds
    - `UndefinedFrameException` - when local or temporary frame is used but is currently undefined
    - `UndefinedVariableException` - when an undefined variable is used
    - `WrongOperandTypeException` - any of the operands are not of the correct type
    - `WrongOperandValueException` - incorrect value is passed to the instruction

### Instructions
- Interpretation starts by calling `Interpret::execute()` which calls several other methods to do the job. The first one is `load()` which loads the XML file and parses it. The second one is `resolve_labels()` which resolves all labels in the code. The last one is `interpret()` which iterates over the instructions and executes them.
- The implementation is very same as in the 1st project, instructions along with their parameters are loaded from the XML along with some type checks. Each instruction is represented by an instance of `Instruction` class and holds its own arguments and opcode. All instructions are stored in the associative array along with their order, the array is then sorted by the order and the order key is removed as it is no longer needed (PHP implicitly reindexes the array). The `load()` method is responsible for all of the above.
- Then, the array is iterated to find any labels and their positions in the array. This is done for easier implementation of all types of jumps (the interpret know where to jump when it encounters a jump instruction). This is done in the `resolve_labels()` method.
- The last method is `interpret()` which creates a separate instance of `VirtualMachine` class, which does the interpretation itself. This is done in order to separate the interpretation logic from the instruction loading and parsing.
- `VirtualMachine` class stores all the necessary stuff: framestack, callstack, current instruction pointer (IP) and several other attributes to ensure full and correct environment for interpretation. The process starts by calling `run()` method that contains just a simple loop which iterates over the instructions and calls the appropriate method for each instruction. These methods are named after the instruction opcodes and are implemented in the `VirtualMachine` class.

## Design decicions and caveats
- One of the caveats was how to represent `undefined` variable value. At first sight that `nil@<nothing>` could be used, but it turned out it could conflict with default nil type in some cases, so `undefined@<nothing>` is now used instead.

## UML diagram
- Class diagram built during implementation is located in the `class_diagram.pdf` file.
