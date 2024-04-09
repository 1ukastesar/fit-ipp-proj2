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

## Design decicions and caveats
- One of the caveats was how to represent `undefined` variable value. At first sight that `nil@<nothing>` could be used, but it turned out it could conflict with default nil type in some cases, so `undefined@<nothing>` is now used instead.
